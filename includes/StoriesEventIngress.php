<?php

namespace MediaWiki\Extension\Wikistories;

use MediaWiki\Config\Config;
use MediaWiki\Context\RequestContext;
use MediaWiki\DomainEvent\DomainEventIngress;
use MediaWiki\Extension\Wikistories\Hooks\RecentChangesPropagationHooks;
use MediaWiki\MainConfigNames;
use MediaWiki\Page\DeletePageFactory;
use MediaWiki\Page\Event\PageDeletedEvent;
use MediaWiki\Page\Event\PageRevisionUpdatedEvent;
use MediaWiki\Page\ProperPageIdentity;
use MediaWiki\Page\WikiPageFactory;
use MediaWiki\Permissions\Authority;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Title\Title;
use RecentChange;

/**
 * Event subscriber acting as an ingress for relevant events emitted
 * by MediaWiki core.
 */
class StoriesEventIngress extends DomainEventIngress {

	private StoriesCache $storiesCache;
	private PageLinksSearch $linksSearch;
	private WikiPageFactory $wikiPageFactory;
	private DeletePageFactory $deletePageFactory;
	private bool $useRCPatrol;

	public function __construct(
		StoriesCache $storiesCache,
		PageLinksSearch $linksSearch,
		WikiPageFactory $wikiPageFactory,
		DeletePageFactory $deletePageFactory,
		Config $config
	) {
		$this->deletePageFactory = $deletePageFactory;
		$this->linksSearch = $linksSearch;
		$this->storiesCache = $storiesCache;
		$this->wikiPageFactory = $wikiPageFactory;

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
	public function handlePageRevisionUpdatedEvent( PageRevisionUpdatedEvent $event ) {
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

		$rc = RecentChangesPropagationHooks::makeRecentChangesEntry(
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

	/**
	 * @param Title $title
	 * @param Authority $performer
	 * @return int
	 */
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
	public function handlePageDeletedEvent( PageDeletedEvent $event ) {
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

	/**
	 * @param ProperPageIdentity $page
	 */
	private function purgeStories( ProperPageIdentity $page ) {
		$storiesId = $this->linksSearch->getPageLinks( $page->getDBkey(), 99 );
		foreach ( $storiesId as $storyId ) {
			$page = $this->wikiPageFactory->newFromID( $storyId );
			$page->doPurge();
		}
	}

	private function deleteStories( string $reason, ProperPageIdentity $page, Authority $authority, bool $suppress ) {
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
}
