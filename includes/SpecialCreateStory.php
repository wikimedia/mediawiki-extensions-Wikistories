<?php
/**
 * @license MIT
 */

namespace MediaWiki\Extension\Wikistories;

use Html;
use SpecialPage;

class SpecialCreateStory extends SpecialPage {

	public function __construct() {
		parent::__construct( 'CreateStory' );
	}

	/**
	 * @inheritDoc
	 */
	public function execute( $subPage ) {
		$this->requireLogin( 'wikistories-specialcreatestory-mostbeloggedin' );
		parent::execute( $subPage );
		$out = $this->getOutput();
		$out->setPageTitle( $this->msg( 'wikistories-specialcreatestory-title' )->text() );
		$out->addModuleStyles( [ 'mw.ext.story.builder.styles' ] );
		$out->addModules( [ 'mw.ext.story.builder' ] );
		$out->addJsConfigVars( $this->getConfigForStoryBuilder( $subPage ) );
		$out->addHTML(
			Html::rawElement(
				'div',
				[ 'class' => 'ext-wikistories-container' ],
				Html::element(
					'span',
					[ 'class' => 'ext-wikistories-loading' ],
					$this->msg( 'wikistories-specialcreatestory-loading' )->text()
				)
			)
		);
		$out->addHTML(
			Html::element(
				'div',
				[ 'class' => 'ext-wikistories-nojswarning' ],
				$this->msg( 'wikistories-specialcreatestory-nojswarning' )->text()
			)
		);
	}

	/**
	 * @param string $subPage Context article to init the story builder with
	 * @return array Configuration needed by the story builder
	 */
	private function getConfigForStoryBuilder( string $subPage ): array {
		return [
			'wgWikistoriesFromArticle' => $subPage,
			'wgWikistoriesMinFrames' => $this->getConfig()->get( 'WikistoriesMinFrames' ),
			'wgWikistoriesMaxFrames' => $this->getConfig()->get( 'WikistoriesMaxFrames' ),
			'wgWikistoriesMaxTextLength' => $this->getConfig()->get( 'WikistoriesMaxTextLength' ),
		];
	}

}
