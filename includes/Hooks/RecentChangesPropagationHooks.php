<?php

namespace MediaWiki\Extension\Wikistories\Hooks;

use HtmlArmor;
use MediaWiki\CommentStore\CommentStoreComment;
use MediaWiki\Config\Config;
use MediaWiki\Context\IContextSource;
use MediaWiki\Hook\EnhancedChangesListModifyBlockLineDataHook;
use MediaWiki\Hook\EnhancedChangesListModifyLineDataHook;
use MediaWiki\Hook\OldChangesListRecentChangesLineHook;
use MediaWiki\Html\Html;
use MediaWiki\Language\Language;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Page\PageReference;
use MediaWiki\Page\PageReferenceValue;
use MediaWiki\RecentChanges\ChangesListBooleanFilter;
use MediaWiki\RecentChanges\RecentChange;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\RevisionStore;
use MediaWiki\SpecialPage\Hook\ChangesListSpecialPageStructuredFiltersHook;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\User\User;
use MediaWiki\User\UserFactory;
use Wikimedia\Rdbms\ILoadBalancer;

class RecentChangesPropagationHooks implements
	EnhancedChangesListModifyBlockLineDataHook,
	EnhancedChangesListModifyLineDataHook,
	OldChangesListRecentChangesLineHook,
	ChangesListSpecialPageStructuredFiltersHook
{
	public const SRC_WIKISTORIES = 'src_wikistories';

	private readonly string $sep;
	private ?string $wordSep = null;

	public function __construct(
		private readonly RevisionStore $revisionStore,
		private readonly Config $config,
		private readonly LinkRenderer $linkRenderer,
		private readonly ILoadBalancer $loadBalancer,
		private readonly UserFactory $userFactory,
	) {
		$this->sep = ' ' . Html::element( 'span', [ 'class' => 'mw-changeslist-separator' ], '' ) . ' ';
	}

	/**
	 * @param IContextSource $context
	 * @return string Word separator
	 */
	private function getWordSep( IContextSource $context ): string {
		if ( $this->wordSep === null ) {
			$this->wordSep = $context->msg( 'word-separator' )->plain();
		}
		return $this->wordSep;
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
			$this->getWordSep( $context ) .
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
		$data[ 'historyLink' ] = $this->getWordSep( $context ) .
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
		$data[ 'currentAndLastLinks' ] = $this->getWordSep( $context ) .
			$this->makeStoryLink( $context, $story ) .
			$this->getWordSep( $context ) .
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
		$comment = $rev !== null ? $rev->getComment( RevisionRecord::FOR_PUBLIC, $user ) : null;

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
			$this->getWordSep( $context ) .
			$this->makeArticleLink( $article ) .
			$this->getWordSep( $context ) .
			$this->makeStoryLink( $context, $story, true ) .
			$this->getWordSep( $context ) .
			$this->makeTimestampLink( $story, $rc, $lang ) .
			$this->sep .
			$this->makeUserLinks( $context, $rev !== null ? $rev->getVisibility() : 0, $user ) .
			$this->getWordSep( $context ) .
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
				$conds[] = $dbr->expr( 'rc_source', '!=', self::SRC_WIKISTORIES );
			},
			'cssClassSuffix' => 'src-mw-wikistories',
			'isRowApplicableCallable' => function ( $ctx, $rc ) {
				return $this->isWikiStoriesRelatedChange( $rc );
			}
		] );
	}
}
