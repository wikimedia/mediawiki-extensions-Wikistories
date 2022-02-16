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
		parent::execute( $subPage );
		$out = $this->getOutput();
		$out->setPageTitle( '' );
		$out->addModuleStyles( [ 'mw.ext.story.builder.styles' ] );
		$out->addModules( [ 'mw.ext.story.builder' ] );
		$out->addJsConfigVars( [ 'wgStoryArticle' => $subPage ] );
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

}
