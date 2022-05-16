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

	/**
	 * Register a message to make sure Special:CreateStory can redirect
	 * to the login page when the user is logged out.
	 *
	 * @param string[] &$messages List of messages valid on login screen
	 */
	public static function onLoginFormValidErrorMessages( &$messages ) {
		$messages[] = 'wikistories-specialcreatestory-mostbeloggedin';
	}

}
