<?php
/**
 * @license MIT
 */

namespace MediaWiki\Extension\Wikistories;

use Exception;
use Html;
use MediaWiki\Page\PageLookup;
use MediaWiki\Page\WikiPageFactory;
use SpecialPage;

class SpecialStoryBuilder extends SpecialPage {

	private const MODE_NEW = 'new';
	private const MODE_EDIT = 'edit';

	/** @var WikiPageFactory */
	private $wikiPageFactory;

	/** @var StoryRenderer */
	private $storyRenderer;

	/** @var PageLookup */
	private $pageLookup;

	/**
	 * @param WikiPageFactory $wikiPageFactory
	 * @param StoryRenderer $storyRenderer
	 * @param PageLookup $pageLookup
	 */
	public function __construct(
		WikiPageFactory $wikiPageFactory,
		StoryRenderer $storyRenderer,
		PageLookup $pageLookup
	) {
		parent::__construct( 'StoryBuilder' );
		$this->wikiPageFactory = $wikiPageFactory;
		$this->storyRenderer = $storyRenderer;
		$this->pageLookup = $pageLookup;
	}

	/**
	 * @inheritDoc
	 */
	public function execute( $subPage ) {
		$this->requireLogin( 'wikistories-specialstorybuilder-mustbeloggedin' );
		parent::execute( $subPage );
		$out = $this->getOutput();
		$out->setPageTitle( $this->msg( 'wikistories-specialstorybuilder-title' )->text() );
		$out->addJsConfigVars( $this->getConfigForStoryBuilder( $subPage ) );
		$out->addModuleStyles( [ 'mw.ext.story.builder.styles' ] );
		$out->addModules( [ 'mw.ext.story.builder' ] );
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
	 * @param string $subPage Context article to init the story builder with
	 * @return array Configuration needed by the story builder
	 * @throws Exception When the subpage doesn't exist
	 */
	private function getConfigForStoryBuilder( string $subPage ): array {
		$page = $this->pageLookup->getExistingPageByText( $subPage );
		if ( !$page ) {
			throw new Exception( "Page '$subPage' does't exist" );
		}
		if ( $page->getNamespace() === NS_STORY ) {
			$wikiPage = $this->wikiPageFactory->newFromTitle( $page );
			/** @var StoryContent $story */
			$story = $wikiPage->getContent();
			'@phan-var StoryContent $story';
			$storyContent = $this->storyRenderer->getStoryForBuilder( $story, $page->getDBkey() );
			$mode = self::MODE_EDIT;
		} else {
			$mode = self::MODE_NEW;
			$storyContent = [
				'fromArticle' => $subPage,
				'frames' => [],
			];
		}
		return [
			'wgWikistoriesMode' => $mode,
			'wgWikistoriesStoryContent' => $storyContent,
			'wgWikistoriesMinFrames' => $this->getConfig()->get( 'WikistoriesMinFrames' ),
			'wgWikistoriesMaxFrames' => $this->getConfig()->get( 'WikistoriesMaxFrames' ),
			'wgWikistoriesMaxTextLength' => $this->getConfig()->get( 'WikistoriesMaxTextLength' ),
		];
	}

}
