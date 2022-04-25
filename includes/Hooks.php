<?php

namespace MediaWiki\Extension\Wikistories;

use OutputPage;
use SpecialPage;

class Hooks {

	/**
	 * @param OutputPage $out
	 */
	public static function onBeforePageDisplayMobile( OutputPage $out ) {
		$title = $out->getTitle();
		$storiesFlag = $out->getRequest()->getBool( 'wikistories' );
		if ( $storiesFlag && $title->getNamespace() === NS_MAIN && $title->exists() ) {
			$out->addJsConfigVars(
				'wgWikistoriesCreateUrl',
				SpecialPage::getTitleFor( 'CreateStory', $out->getTitle() )->getLinkURL()
			);
			$out->addModules( [ 'mw.ext.story.discover' ] );
		}
	}

}
