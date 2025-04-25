<?php
/**
 * @license MIT
 */

namespace MediaWiki\Extension\Wikistories;

use MediaWiki\Actions\WatchAction;
use MediaWiki\Config\Config;
use MediaWiki\Exception\ErrorPageError;
use MediaWiki\Html\Html;
use MediaWiki\MainConfigNames;
use MediaWiki\Page\ExistingPageRecord;
use MediaWiki\Page\PageLookup;
use MediaWiki\Page\WikiPageFactory;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\User\Options\UserOptionsLookup;
use MediaWiki\Watchlist\WatchedItemStore;
use MediaWiki\Watchlist\WatchlistManager;

class SpecialStoryBuilder extends SpecialPage {

	private const MODE_NEW = 'new';
	private const MODE_EDIT = 'edit';

	/** @var WikiPageFactory */
	private $wikiPageFactory;

	/** @var PageLookup */
	private $pageLookup;

	/** @var UserOptionsLookup */
	private $userOptionsLookup;

	/** @var WatchlistManager */
	private $watchlistManager;

	/** @var Config */
	private $config;

	/** @var WatchedItemStore */
	private $watchedItemStore;

	/** @var StoriesCache */
	private $storiesCache;

	/** @var PermissionManager */
	private $permissionManager;

	/**
	 * @param WikiPageFactory $wikiPageFactory
	 * @param PageLookup $pageLookup
	 * @param UserOptionsLookup $userOptionsLookup
	 * @param WatchlistManager $watchlistManager
	 * @param WatchedItemStore $watchedItemStore
	 * @param Config $config
	 * @param StoriesCache $storiesCache
	 * @param PermissionManager $permissionManager
	 */
	public function __construct(
		WikiPageFactory $wikiPageFactory,
		PageLookup $pageLookup,
		UserOptionsLookup $userOptionsLookup,
		WatchlistManager $watchlistManager,
		WatchedItemStore $watchedItemStore,
		Config $config,
		StoriesCache $storiesCache,
		PermissionManager $permissionManager
	) {
		parent::__construct( 'StoryBuilder' );
		$this->wikiPageFactory = $wikiPageFactory;
		$this->pageLookup = $pageLookup;
		$this->userOptionsLookup = $userOptionsLookup;
		$this->watchlistManager = $watchlistManager;
		$this->config = $config;
		$this->watchedItemStore = $watchedItemStore;
		$this->storiesCache = $storiesCache;
		$this->permissionManager = $permissionManager;
	}

	/**
	 * @inheritDoc
	 */
	public function execute( $subPage ) {
		$this->requireNamedUser( 'wikistories-specialstorybuilder-mustbeloggedin' );
		parent::execute( $subPage );
		$out = $this->getOutput();
		$out->setPageTitleMsg( $this->msg( 'wikistories-specialstorybuilder-title' ) );
		$out->addJsConfigVars( $this->getConfigForStoryBuilder( $this->getSubPage( $subPage ) ) );
		$out->addModuleStyles( [ 'ext.wikistories.builder.styles' ] );
		$out->addModules( [ 'ext.wikistories.builder' ] );
		$out->addHTML(
			Html::rawElement(
				'div',
				[ 'class' => 'ext-wikistories-container' ],
				Html::element(
					'span',
					[ 'class' => 'ext-wikistories-loading' ],
					$this->msg( 'wikistories-specialstorybuilder-loading' )->text()
				)
			)
		);
		$out->addHTML(
			Html::element(
				'div',
				[ 'class' => 'ext-wikistories-nojswarning' ],
				$this->msg( 'wikistories-specialstorybuilder-nojswarning' )->text()
			)
		);
	}

	/**
	 * @param string|null $subPage
	 * @return ExistingPageRecord
	 * @throws ErrorPageError when the subpage is empty or invalid
	 */
	private function getSubPage( $subPage ): ExistingPageRecord {
		if ( !$subPage ) {
			throw new ErrorPageError(
				'wikistories-specialstorybuilder-title',
				'wikistories-specialstorybuilder-invalidsubpage'
			);
		}
		$page = $this->pageLookup->getExistingPageByText( $subPage );
		if ( !$page ) {
			throw new ErrorPageError(
				'wikistories-specialstorybuilder-title',
				'wikistories-specialstorybuilder-invalidsubpage'
			);
		}
		return $page;
	}

	/**
	 * @param ExistingPageRecord $page
	 * @return bool
	 */
	private function getUserBlockStatus( $page ): bool {
		return $this->permissionManager->isBlockedFrom( $this->getUser(), $page );
	}

	/**
	 * @param ExistingPageRecord $page
	 * @return array Configuration needed by the story builder
	 */
	private function getConfigForStoryBuilder( ExistingPageRecord $page ): array {
		$watchExpiryEnabled = $this->config->get( MainConfigNames::WatchlistExpiry );
		if ( $page->getNamespace() === NS_STORY ) {
			$wikiPage = $this->wikiPageFactory->newFromTitle( $page );
			$storyContent = $this->storiesCache->getStory( $page->getId() );
			$mode = self::MODE_EDIT;
			$articlePage = $this->pageLookup->getExistingPageByText( $storyContent[ 'articleTitle' ] );
			$userBlock = $articlePage ? $this->getUserBlockStatus( $articlePage ) : false;
			$watchDefault = $this->userOptionsLookup->getOption( $this->getUser(), 'watchdefault' ) ||
				$this->watchlistManager->isWatched( $this->getUser(), $page );
			$watchExpiryOptions = WatchAction::getExpiryOptions(
				$this->getContext(),
				$this->watchedItemStore->getWatchedItem( $this->getUser(), $wikiPage )
			);
		} else {
			$mode = self::MODE_NEW;
			$storyContent = [
				'articleId' => $page->getId(),
				'articleTitle' => $page->getDBkey(),
				'frames' => [],
			];
			$userBlock = $this->getUserBlockStatus( $page );
			$watchDefault = $this->userOptionsLookup->getOption( $this->getUser(), 'watchcreations' );
			$watchExpiryOptions = WatchAction::getExpiryOptions( $this->getContext(), false );
		}
		return [
			'wgWikistoriesMode' => $mode,
			'wgWikistoriesStoryContent' => $storyContent,
			'wgWikistoriesMinFrames' => $this->getConfig()->get( 'WikistoriesMinFrames' ),
			'wgWikistoriesMaxFrames' => $this->getConfig()->get( 'WikistoriesMaxFrames' ),
			'wgWikistoriesMaxTextLength' => $this->getConfig()->get( 'WikistoriesMaxTextLength' ),
			'wgWikistoriesCommonsDomain' => $this->getConfig()->get( 'WikistoriesCommonsDomain' ),
			'wgWikistoriesRestDomain' => $this->getConfig()->get( 'WikistoriesRestDomain' ),
			'wgWikistoriesUnmodifiedTextThreshold' => $this->getConfig()->get( 'WikistoriesUnmodifiedTextThreshold' ),
			'wgWikistoriesWatchDefault' => $watchDefault,
			'wgWikistoriesWatchlistExpiryEnabled' => $watchExpiryEnabled,
			'wgWikistoriesWatchlistExpiryOptions' => $watchExpiryOptions,
			'wgWikistoriesUserBlockStatus' => $userBlock,
		];
	}

}
