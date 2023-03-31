<?php

namespace MediaWiki\Extension\Wikistories\hooks;

use ChangesListBooleanFilter;
use Config;
use DeferredUpdates;
use HtmlArmor;
use IContextSource;
use Language;
use MediaWiki\CommentStore\CommentStoreComment;
use MediaWiki\Extension\Wikistories\StoryContent;
use MediaWiki\Hook\EnhancedChangesListModifyBlockLineDataHook;
use MediaWiki\Hook\EnhancedChangesListModifyLineDataHook;
use MediaWiki\Hook\OldChangesListRecentChangesLineHook;
use MediaWiki\Html\Html;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\MainConfigNames;
use MediaWiki\Page\PageReference;
use MediaWiki\Page\PageReferenceValue;
use MediaWiki\Permissions\Authority;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\RevisionStore;
use MediaWiki\SpecialPage\Hook\ChangesListSpecialPageStructuredFiltersHook;
use MediaWiki\Storage\Hook\PageSaveCompleteHook;
use MediaWiki\User\UserFactory;
use RecentChange;
use RequestContext;
use SpecialPage;
use Title;
use User;
use Wikimedia\Rdbms\ILoadBalancer;

class RecentChangesPropagationHooks implements
	PageSaveCompleteHook,
	EnhancedChangesListModifyBlockLineDataHook,
	EnhancedChangesListModifyLineDataHook,
	OldChangesListRecentChangesLineHook,
	ChangesListSpecialPageStructuredFiltersHook
{
	private const SRC_WIKISTORIES = 'src_wikistories';

	/** @var RevisionStore */
	private $revisionStore;

	/** @var Config */
	private $config;

	/** @var LinkRenderer */
	private $linkRenderer;

	/** @var ILoadBalancer */
	private $loadBalancer;

	/** @var string */
	private $sep;

	/** @var string */
	private $wordSep;

	/** @var UserFactory */
	private $userFactory;

	/**
	 * @param RevisionStore $revisionStore
	 * @param Config $config
	 * @param LinkRenderer $linkRenderer
	 * @param ILoadBalancer $loadBalancer
	 * @param UserFactory $userFactory
	 */
	public function __construct(
		RevisionStore $revisionStore,
		Config $config,
		LinkRenderer $linkRenderer,
		ILoadBalancer $loadBalancer,
		UserFactory $userFactory
	) {
		$this->revisionStore = $revisionStore;
		$this->config = $config;
		$this->linkRenderer = $linkRenderer;
		$this->loadBalancer = $loadBalancer;
		$this->userFactory = $userFactory;

		$this->wordSep = wfMessage( 'word-separator' )->plain();
		$this->sep = ' ' . Html::element( 'span', [ 'class' => 'mw-changeslist-separator' ], '' ) . ' ';
	}

	/**
	 * When a story is saved (created or edited), create a recent changes
	 * entry for the related article so that watchers of that article can
	 * be aware of the story change.
	 *
	 * @inheritDoc
	 */
	public function onPageSaveComplete( $wikiPage, $user, $summary, $flags, $revisionRecord, $editResult ) {
		if ( $wikiPage->getNamespace() !== NS_STORY ) {
			// not the Story namespace
			return;
		}
		/** @var StoryContent $newStory */
		$newStory = $revisionRecord->getContent( 'main' );

		if ( $newStory === null ) {
			// can't get the content
			return;
		}

		if ( !( $newStory instanceof StoryContent ) ) {
			// not the story content format
			return;
		}

		$article = Title::newFromText( $newStory->getFromArticle() );
		$context = RequestContext::getMain();

		$rc = new RecentChange;
		$rc->mAttribs = [
			'rc_timestamp' => $revisionRecord->getTimestamp(),
			'rc_namespace' => $article->getNamespace(),
			'rc_title' => $article->getDBkey(),
			'rc_type' => RC_EXTERNAL,
			'rc_source' => self::SRC_WIKISTORIES,
			'rc_minor' => ( $flags & EDIT_MINOR ) > 0,
			'rc_cur_id' => $article->getId(),
			'rc_user' => $user->getId(),
			'rc_user_text' => $user->getName(),
			'rc_comment' => &$summary,
			'rc_comment_text' => &$summary,
			'rc_comment_data' => null,
			'rc_this_oldid' => (int)$revisionRecord->getId(),
			'rc_last_oldid' => (int)$revisionRecord->getParentId(),
			'rc_bot' => ( $flags & EDIT_FORCE_BOT ) > 0,
			'rc_ip' => $context->getRequest()->getIP(),
			'rc_patrolled' => $this->getPatrolled( $article, $context->getAuthority() ),
			'rc_new' => 0,
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

		$rc->mExtra = [
			'prefixedDBkey' => $article->getPrefixedDBkey(),
			'lastTimestamp' => 0,
			'oldSize' => 0,
			'newSize' => 0,
			'pageStatus' => 'changed'
		];

		DeferredUpdates::addCallableUpdate(
			static function () use ( $rc, $editResult ) {
				$rc->setEditResult( $editResult );
				$rc->save();
			},
			DeferredUpdates::POSTSEND,
			$this->loadBalancer->getConnection( DB_PRIMARY )
		);
	}

	/**
	 * @param Title $title
	 * @param Authority $performer
	 * @return int
	 */
	private function getPatrolled( Title $title, Authority $performer ): int {
		$useRCPatrol = $this->config->get( MainConfigNames::UseRCPatrol );
		return $useRCPatrol && $performer->definitelyCan( 'autopatrol', $title ) ?
			RecentChange::PRC_AUTOPATROLLED :
			RecentChange::PRC_UNPATROLLED;
	}

	/**
	 * @param RecentChange $rc
	 * @return bool
	 */
	private function isWikiStoriesRelatedChange( RecentChange $rc ): bool {
		return $rc->getAttribute( 'rc_source' ) === self::SRC_WIKISTORIES;
	}

	/**
	 * @param IContextSource $context
	 * @param PageReference $story
	 * @param bool $parens
	 * @return string
	 */
	private function makeStoryLink( IContextSource $context, PageReference $story, $parens = false ): string {
		$storyLink = $this->linkRenderer->makeKnownLink( $story );
		$formattedLink = $parens ?
			$context->msg( 'parentheses' )->rawParams( $storyLink )->text() :
			$storyLink;
		return Html::rawElement(
			'span',
			[],
			$formattedLink
		);
	}

	/**
	 * @param PageReference $article
	 * @return string
	 */
	private function makeArticleLink( PageReference $article ): string {
		return Html::rawElement(
			'span',
			[ 'class' => 'mw-title' ],
			$this->linkRenderer->makeKnownLink( $article )
		);
	}

	/**
	 * @param RecentChange $rc
	 * @return int
	 */
	private function getStoryId( RecentChange $rc ): int {
		$params = $rc->parseParams();
		return $params[ 'story_id' ];
	}

	/**
	 * @param IContextSource $context
	 * @param PageReference $story
	 * @param RecentChange $rc
	 * @return string
	 */
	private function makeDiffLink( IContextSource $context, PageReference $story, RecentChange $rc ): string {
		return Html::rawElement(
			'span',
			[],
			$this->linkRenderer->makeKnownLink(
				$story,
				new HtmlArmor( $context->msg( 'diff' )->escaped() ),
				[ 'class' => 'mw-changeslist-diff' ],
				[
					'curid' => $this->getStoryId( $rc ),
					'diff' => $rc->getAttribute( 'rc_this_oldid' ),
					'oldid' => $rc->getAttribute( 'rc_last_oldid' ),
				]
			)
		);
	}

	/**
	 * @param IContextSource $context
	 * @param PageReference $story
	 * @param RecentChange $rc
	 * @return string
	 */
	private function makeHistLink( IContextSource $context, PageReference $story, RecentChange $rc ): string {
		return Html::rawElement(
			'span',
			[],
			$this->linkRenderer->makeKnownLink(
				$story,
				new HtmlArmor( $context->msg( 'hist' )->escaped() ),
				[ 'class' => 'mw-changeslist-history' ],
				[
					'curid' => $this->getStoryId( $rc ),
					'action' => 'history',
				]
			)
		);
	}

	/**
	 * @param IContextSource $context
	 * @param PageReference $story
	 * @param RecentChange $rc
	 * @return string
	 */
	private function makeDiffHistLinks(
		IContextSource $context,
		PageReference $story,
		RecentChange $rc
	): string {
		$diffLink = $this->makeDiffLink( $context, $story, $rc );
		$histLink = $this->makeHistLink( $context, $story, $rc );
		return Html::rawElement(
			'span',
			[ 'class' => 'mw-changeslist-links' ],
			$diffLink . $histLink
		);
	}

	/**
	 * @param PageReference $story
	 * @param RecentChange $rc
	 * @param Language $lang
	 * @return string
	 */
	private function makeTimestampLink( PageReference $story, RecentChange $rc, Language $lang ): string {
		$user = $rc->getPerformerIdentity();
		return $this->linkRenderer->makeKnownLink(
			$story,
			$lang->userTime( $rc->getAttribute( 'rc_timestamp' ), $user ),
			[ 'class' => 'mw-changeslist-date' ],
			[
				'title' => $story->getDBkey(),
				'curid' => $this->getStoryId( $rc ),
				'oldid' => $rc->getAttribute( 'rc_this_oldid' ),
			]
		);
	}

	/**
	 * @param IContextSource $context
	 * @param int $visibility
	 * @param User $user
	 * @return string
	 */
	private function makeUserLinks( IContextSource $context, int $visibility, User $user ) {
		if ( !RevisionRecord::userCanBitfield(
			$visibility,
			RevisionRecord::DELETED_USER,
			$user )
		) {
			// The username has been moderated and cannot be seen by the current user
			return Html::rawElement(
				'span',
				[ 'class' => 'history-deleted' ],
				$context->msg( 'rev-deleted-user' )->escaped()
			);
		}

		$userLink = $this->linkRenderer->makeLink(
			$user->getUserPage(),
			$user->getName(),
			[ 'class' => 'mw-userlink' ]
		);

		$links = [];

		$links[] = Html::rawElement(
			'span',
			[],
			$this->linkRenderer->makeLink(
				$user->getTalkPage(),
				$context->msg( 'talkpagelinktext' )->text(),
				[ 'class' => 'mw-usertoollinks-talk' ]
			)
		);

		if ( $user->isRegistered() ) {
			$links[] = Html::rawElement(
				'span',
				[],
				$this->linkRenderer->makeLink(
					SpecialPage::getTitleValueFor( 'Contributions', $user->getName() ),
					$context->msg( 'contribslink' )->text(),
					[ 'class' => 'mw-usertoollinks-contribs' ]
				)
			);
		}
		return $userLink .
			$this->wordSep .
			Html::rawElement(
				'span',
				[ 'class' => 'mw-usertoollinks mw-changeslist-links' ],
				implode( '', $links )
			);
	}

	/**
	 * @param IContextSource $context
	 * @param CommentStoreComment|null $comment
	 * @return string
	 */
	private function makeComment( IContextSource $context, $comment ): string {
		$text = $comment ? $comment->text : null;
		if ( $text !== null && $text !== '' ) {
			return Html::rawElement(
				'span',
				[ 'class' => 'comment' ],
				$context->msg( 'parentheses', $text )->parse()
			);

		}
		return '';
	}

	/**
	 * Use this hook to alter data used to build a non-grouped recent change line in
	 * EnhancedChangesList.
	 *
	 * @inheritDoc
	 */
	public function onEnhancedChangesListModifyBlockLineData( $changesList, &$data, $rc ) {
		if ( !$this->isWikiStoriesRelatedChange( $rc ) ) {
			return;
		}

		$params = $rc->parseParams();
		$story = PageReferenceValue::localReference( NS_STORY, $params[ 'story_title' ] );
		$lang = $changesList->getLanguage();
		$context = $changesList->getContext();

		$data[ 'recentChangesFlags' ][ 'wikistories-edit' ] = true;

		// Make timestamp link to specific revision
		$data[ 'timestampLink' ] = $this->makeTimestampLink( $story, $rc, $lang );

		// Append story link to article link
		$data[ 'articleLink' ] .= $this->sep . $this->makeStoryLink( $context, $story, true );

		// Remove character diff section
		unset( $data['characterDiff'] );
		unset( $data['separatorAftercharacterDiff'] );

		// Make DIFF and HIST links for story instead of article
		$data[ 'historyLink' ] = $this->wordSep .
			$this->makeDiffHistLinks( $context, $story, $rc );
	}

	/**
	 * Use this hook to alter data used to build a grouped recent change inner line in
	 * EnhancedChangesList.
	 *
	 * @inheritDoc
	 */
	public function onEnhancedChangesListModifyLineData( $changesList, &$data, $block, $rc, &$classes, &$attribs ) {
		if ( !$this->isWikiStoriesRelatedChange( $rc ) ) {
			return;
		}

		$params = $rc->parseParams();
		$story = PageReferenceValue::localReference( NS_STORY, $params[ 'story_title' ] );
		$lang = $changesList->getLanguage();
		$context = $changesList->getContext();

		$data[ 'recentChangesFlags' ][ 'wikistories-edit' ] = true;

		// Make timestamp link to specific revision
		$data[ 'timestampLink' ] = $this->makeTimestampLink( $story, $rc, $lang );

		// Replace "(cur last)" links with "story (diff hist)" links
		$data[ 'currentAndLastLinks' ] = $this->wordSep .
			$this->makeStoryLink( $context, $story ) .
			$this->wordSep .
			$this->makeDiffHistLinks( $context, $story, $rc );

		// Remove character diff section
		unset( $data['characterDiff'] );
		unset( $data['separatorAfterCharacterDiff'] );
	}

	/**
	 * Use this hook to customize a recent changes line.
	 *
	 * @inheritDoc
	 */
	public function onOldChangesListRecentChangesLine( $changeslist, &$s, $rc, &$classes, &$attribs ) {
		if ( !$this->isWikiStoriesRelatedChange( $rc ) ) {
			return;
		}

		$params = $rc->parseParams();
		$story = PageReferenceValue::localReference( NS_STORY, $params['story_title'] );
		$rev = $this->revisionStore->getRevisionById( $rc->getAttribute( 'rc_this_oldid' ) );
		$user = $this->userFactory->newFromUserIdentity( $rc->getPerformerIdentity() );
		$lang = $changeslist->getLanguage();
		$context = $changeslist->getContext();
		$comment = $rev->getComment( RevisionRecord::FOR_PUBLIC, $user );

		$flag = $changeslist->recentChangesFlags(
			[
				'wikistories-edit' => true,
				'minor' => $rc->getAttribute( 'rc_minor' ),
				'bot' => $rc->getAttribute( 'rc_bot' ),
			],
			''
		);

		$article = $rc->getPage();
		if ( $article === null ) {
			return;
		}

		$s = Html::rawElement(
			'span',
			[ 'class' => 'mw-changeslist-line-inner' ],
			$this->makeDiffHistLinks( $context, $story, $rc ) .
			$this->sep .
			$flag .
			$this->wordSep .
			$this->makeArticleLink( $article ) .
			$this->wordSep .
			$this->makeStoryLink( $context, $story, true ) .
			$this->wordSep .
			$this->makeTimestampLink( $story, $rc, $lang ) .
			$this->sep .
			$this->makeUserLinks( $context, $rev->getVisibility(), $user ) .
			$this->wordSep .
			$this->makeComment( $context, $comment )
		);
	}

	/**
	 * @inheritDoc
	 */
	public function onChangesListSpecialPageStructuredFilters( $special ) {
		// @phan-suppress-next-line PhanNoopNew
		new ChangesListBooleanFilter( [
			'name' => 'hidewikistories',
			'group' => $special->getFilterGroup( 'changeType' ),
			'priority' => -4,
			'label' => 'wikistories-rcfilters-hidewikistories-label',
			'description' => 'wikistories-rcfilters-hidewikistories-description',
			'showHide' => 'rcshowhidewikistories',
			'default' => false,
			'queryCallable' => static function (
				$specialClassName, $ctx, $dbr, &$tables, &$fields, &$conds, &$query_options, &$join_conds
			) {
				$conds[] = 'rc_source != ' . $dbr->addQuotes( self::SRC_WIKISTORIES );
			},
			'cssClassSuffix' => 'src-mw-wikistories',
			'isRowApplicableCallable' => function ( $ctx, $rc ) {
				return $this->isWikiStoriesRelatedChange( $rc );
			}
		] );
	}
}
