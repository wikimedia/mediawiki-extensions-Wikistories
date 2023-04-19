<?php
/**
 * @license MIT
 */

namespace MediaWiki\Extension\Wikistories;

use Config;
use ErrorPageError;
use Html;
use MediaWiki\MainConfigNames;
use MediaWiki\Page\ExistingPageRecord;
use MediaWiki\Page\PageLookup;
use MediaWiki\Page\WikiPageFactory;
use MediaWiki\User\UserOptionsLookup;
use MediaWiki\Watchlist\WatchlistManager;
use SpecialPage;
use WatchAction;
use WatchedItemStore;

class SpecialStoryBuilder extends SpecialPage {

	private const MODE_NEW = 'new';
	private const MODE_EDIT = 'edit';

	/** @var WikiPageFactory */
	private $wikiPageFactory;

	/** @var StoryRenderer */
	private $storyRenderer;

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

	/**
	 * @param WikiPageFactory $wikiPageFactory
	 * @param StoryRenderer $storyRenderer
	 * @param PageLookup $pageLookup
	 * @param UserOptionsLookup $userOptionsLookup
	 * @param WatchlistManager $watchlistManager
	 * @param WatchedItemStore $watchedItemStore
	 * @param Config $config
	 */
	public function __construct(
		WikiPageFactory $wikiPageFactory,
		StoryRenderer $storyRenderer,
		PageLookup $pageLookup,
		UserOptionsLookup $userOptionsLookup,
		WatchlistManager $watchlistManager,
		WatchedItemStore $watchedItemStore,
		Config $config
	) {
		parent::__construct( 'StoryBuilder' );
		$this->wikiPageFactory = $wikiPageFactory;
		$this->storyRenderer = $storyRenderer;
		$this->pageLookup = $pageLookup;
		$this->userOptionsLookup = $userOptionsLookup;
		$this->watchlistManager = $watchlistManager;
		$this->config = $config;
		$this->watchedItemStore = $watchedItemStore;
	}

	/**
	 * @inheritDoc
	 */
	public function execute( $subPage ) {
		$this->requireLogin( 'wikistories-specialstorybuilder-mustbeloggedin' );
		parent::execute( $subPage );
		$out = $this->getOutput();
		$out->setPageTitle( $this->msg( 'wikistories-specialstorybuilder-title' )->text() );
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
	 * @return array Configuration needed by the story builder
	 */
	private function getConfigForStoryBuilder( ExistingPageRecord $page ): array {
		$watchExpiryEnabled = $this->config->get( MainConfigNames::WatchlistExpiry );
		if ( $page->getNamespace() === NS_STORY ) {
			$wikiPage = $this->wikiPageFactory->newFromTitle( $page );
			/** @var StoryContent $story */
			$story = $wikiPage->getContent();
			'@phan-var StoryContent $story';
			$storyContent = $this->storyRenderer->getStoryForBuilder( $story, $page->getDBkey() );
			$mode = self::MODE_EDIT;
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
				'fromArticle' => $page->getDBkey(),
				'frames' => [],
			];
			$watchDefault = $this->userOptionsLookup->getOption( $this->getUser(), 'watchcreations' );
			$watchExpiryOptions = WatchAction::getExpiryOptions( $this->getContext(), false );
		}
		return [
			'wgWikistoriesMode' => $mode,
			'wgWikistoriesStoryContent' => $storyContent,
			'wgWikistoriesMinFrames' => $this->getConfig()->get( 'WikistoriesMinFrames' ),
			'wgWikistoriesMaxFrames' => $this->getConfig()->get( 'WikistoriesMaxFrames' ),
			'wgWikistoriesMaxTextLength' => $this->getConfig()->get( 'WikistoriesMaxTextLength' ),
			'wgWikistoriesUnmodifiedTextThreshold' => $this->getConfig()->get( 'WikistoriesUnmodifiedTextThreshold' ),
			'wgWikistoriesWatchDefault' => $watchDefault,
			'wgWikistoriesWatchlistExpiryEnabled' => $watchExpiryEnabled,
			'wgWikistoriesWatchlistExpiryOptions' => $watchExpiryOptions,
		];
	}

}
