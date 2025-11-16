<?php

namespace MediaWiki\Extension\Wikistories;

use MediaWiki\Config\Config;
use MediaWiki\Context\RequestContext;
use MediaWiki\DomainEvent\DomainEventIngress;
use MediaWiki\Extension\Wikistories\Hooks\RecentChangesPropagationHooks;
use MediaWiki\MainConfigNames;
use MediaWiki\Page\DeletePageFactory;
use MediaWiki\Page\Event\PageDeletedEvent;
use MediaWiki\Page\Event\PageDeletedListener;
use MediaWiki\Page\Event\PageRevisionUpdatedEvent;
use MediaWiki\Page\Event\PageRevisionUpdatedListener;
use MediaWiki\Page\PageIdentity;
use MediaWiki\Page\ProperPageIdentity;
use MediaWiki\Page\WikiPageFactory;
use MediaWiki\Permissions\Authority;
use MediaWiki\RecentChanges\RecentChange;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Storage\EditResult;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFormatter;
use MediaWiki\User\UserIdentity;

/**
 * Event subscriber acting as an ingress for relevant events emitted
 * by MediaWiki core.
 */
class StoriesEventIngress
	extends DomainEventIngress
	implements PageRevisionUpdatedListener, PageDeletedListener
{

	private readonly bool $useRCPatrol;

	public function __construct(
		private readonly StoriesCache $storiesCache,
		private readonly PageLinksSearch $linksSearch,
		private readonly WikiPageFactory $wikiPageFactory,
		private readonly DeletePageFactory $deletePageFactory,
		private readonly TitleFormatter $titleFormatter,
		Config $config,
	) {
		$this->useRCPatrol = $config->get( MainConfigNames::UseRCPatrol );
	}

	/**
	 * When editing a story with the form, it is possible to change the 'Related article'
	 * to change which article the story will be shown on. The link to the new article will
	 * be done automatically with the page links but it will still show on the previous
	 * article because of the long-live stories cache.
	 *
	 * This listener invalidates the stories cache for the old article and
	 * purges stories when article is undeleted.
	 *
	 * Also, when a story is saved (created or edited), it creates a recent changes
	 * entry for the related article so that watchers of that article can
	 * be aware of the story change.
	 *
	 * @noinspection PhpUnused
	 */
	public function handlePageRevisionUpdatedEvent( PageRevisionUpdatedEvent $event ): void {
		$page = $event->getPage();
		$revisionRecord = $event->getLatestRevisionAfter();

		// Undeletion in the main namespace
		if ( $page->getNamespace() === NS_MAIN &&
			$event->isCreation() &&
			$event->hasCause( PageRevisionUpdatedEvent::CAUSE_UNDELETE )
		) {
			$this->purgeStories( $page );
		}

		// Story created or edited
		if ( $page->getNamespace() !== NS_STORY ||
			$revisionRecord->getMainContentModel() !== 'story'
		) {
			return;
		}

		$story = $revisionRecord->getMainContentRaw();

		if ( !( $story instanceof StoryContent ) ) {
			// not the story content format
			return;
		}

		// Invalidate caches
		$articleTitle = $story->getArticleTitle();

		if ( $articleTitle ) {
			$this->storiesCache->invalidateForArticle( $articleTitle->getId() );
		}

		$this->storiesCache->invalidateStory( $page->getId() );

		// Inject RecentChanged entry
		$article = Title::newFromText( $story->getFromArticle() );
		$context = RequestContext::getMain();
		$requestIP = $context->getRequest()->getIP();
		$patrolled = $this->getPatrolled( $article, $context->getAuthority() );

		$rc = $this->makeRecentChangesEntry(
			$article,
			$revisionRecord,
			$event->getPerformer(),
			$revisionRecord->getComment( RevisionRecord::RAW )->text,
			$requestIP,
			$revisionRecord->isMinor(),
			$event->isBotUpdate(),
			$patrolled,
			$event->getEditResult()
		);

		$rc->save();
	}

	private function getPatrolled( Title $title, Authority $performer ): int {
		return $this->useRCPatrol && $performer->definitelyCan( 'autopatrol', $title ) ?
			RecentChange::PRC_AUTOPATROLLED :
			RecentChange::PRC_UNPATROLLED;
	}

	/**
	 * Do purge stories when article is deleted.
	 * Invalidate stories cache for the related article.
	 *
	 * @noinspection PhpUnused
	 */
	public function handlePageDeletedEvent( PageDeletedEvent $event ): void {
		$page = $event->getDeletedPage();
		$deletedRev = $event->getLatestRevisionBefore();

		// NS_MAIN deletion
		if ( $page->getNamespace() === NS_MAIN ) {
			$request = RequestContext::getMain()->getRequest();
			$authority = RequestContext::getMain()->getAuthority();

			$deleteStories = $request->getBool( 'wpDeleteStory' );
			if ( $deleteStories ) {
				self::deleteStories(
					$request->getText( 'wpReason' ),
					$page,
					$authority,
					$request->getBool( 'wpSuppress' )
				);
			} else {
				self::purgeStories( $page );
			}

			return;
		}

		// NS_STORY deletion
		if ( $page->getNamespace() !== NS_STORY ) {
			return;
		}

		$story = $deletedRev->getMainContentRaw();
		if ( !( $story instanceof StoryContent ) ) {
			return;
		}

		$articleTitle = $story->getArticleTitle();
		if ( $articleTitle === null ) {
			return;
		}

		$articlePageId = $articleTitle->getId();
		if ( $articlePageId === 0 ) {
			return;
		}

		$this->storiesCache->invalidateForArticle( $articlePageId );
	}

	private function purgeStories( ProperPageIdentity $page ): void {
		$storiesId = $this->linksSearch->getPageLinks( $page->getDBkey(), 99 );
		foreach ( $storiesId as $storyId ) {
			$page = $this->wikiPageFactory->newFromID( $storyId );
			$page->doPurge();
		}
	}

	private function deleteStories(
		string $reason,
		ProperPageIdentity $page,
		Authority $authority,
		bool $suppress,
	): void {
		$storiesId = $this->linksSearch->getPageLinks( $page->getDBkey(), 99 );
		foreach ( $storiesId as $storyId ) {
			$page = $this->wikiPageFactory->newFromID( $storyId );
			$deletePage = $this->deletePageFactory->newDeletePage(
				$page,
				$authority
			);
			$deletePage
				->setSuppress( $suppress )
				->deleteIfAllowed( $reason );
		}
	}

	/**
	 * When a story is saved (created or edited), we create a recent changes
	 * entry for the related article so that watchers of that article can
	 * be aware of the story change.
	 *
	 * @note The logic for creating the fake RecentChanges entry is in this class
	 * because this is where we define how that entry is later visualized.
	 * The actual insertion of the fake RC entry is left to EventIngress, which
	 * handles core events triggered by page changes.
	 */
	private function makeRecentChangesEntry(
		PageIdentity $article,
		RevisionRecord $revisionRecord,
		UserIdentity $user,
		string $summary,
		string $requestIP,
		bool $minor,
		bool $bot,
		int $patrolled,
		?EditResult $editResult
	): RecentChange {
		// NOTE: $revisionRecord does not belong to $article!

		$rc = new RecentChange;
		$rc->mAttribs = [
			'rc_timestamp' => $revisionRecord->getTimestamp(),
			'rc_namespace' => $article->getNamespace(),
			'rc_title' => $article->getDBkey(),
			'rc_source' => RecentChangesPropagationHooks::SRC_WIKISTORIES,
			'rc_minor' => $minor,
			'rc_cur_id' => $article->getId(),
			'rc_user' => $user->getId(),
			'rc_user_text' => $user->getName(),
			'rc_comment' => $summary,
			'rc_comment_text' => $summary,
			'rc_comment_data' => null,
			'rc_this_oldid' => (int)$revisionRecord->getId(),
			'rc_last_oldid' => (int)$revisionRecord->getParentId(),
			'rc_bot' => $bot,
			'rc_ip' => $requestIP,
			'rc_patrolled' => $patrolled,
			'rc_old_len' => 0,
			'rc_new_len' => 0,
			'rc_deleted' => 0,
			'rc_logid' => 0,
			'rc_log_type' => null,
			'rc_log_action' => '',
			'rc_params' => serialize( [
				'story_title' => $revisionRecord->getPage()->getDBkey(),
				'story_id' => $revisionRecord->getPage()->getId(),
			] )
		];

		// TODO: deprecate the 'prefixedDBkey' entry, let callers do the formatting.
		$rc->mExtra = [
			'prefixedDBkey' => $this->titleFormatter->getPrefixedDBkey( $article ),
			'lastTimestamp' => 0,
			'oldSize' => 0,
			'newSize' => 0,
			'pageStatus' => 'changed'
		];

		if ( $editResult ) {
			$rc->setEditResult( $editResult );
		}

		return $rc;
	}

}
