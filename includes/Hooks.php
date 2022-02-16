<?php

namespace MediaWiki\Extension\Wikistories;

use MWException;
use OutputPage;
use Parser;
use SkinTemplate;
use SpecialPage;

class Hooks {

	/**
	 * Register the #story parser function
	 *
	 * @param Parser $parser
	 * @throws MWException
	 */
	public static function onParserFirstCallInit( Parser $parser ) {
		$parser->setFunctionHook( 'story', [ StoryParserFunction::class, 'renderStory' ] );
	}

	/**
	 * @param SkinTemplate $sktemplate
	 * @param array &$links
	 */
	public static function onSkinTemplateNavigationUniversal( SkinTemplate $sktemplate, array &$links ) {
		if ( $sktemplate->getTitle()->getNamespace() !== NS_STORY ) {
			return;
		}
		// todo: consider changing the edit action for "edit" (builder) and "edit raw" (no js)
	}

	/**
	 * @param OutputPage $out
	 */
	public static function onBeforePageDisplayMobile( OutputPage $out ) {
		if ( $out->isArticle() ) {
			$out->addJsConfigVars(
				'wgWikistoriesCreateUrl',
				SpecialPage::getTitleFor( 'CreateStory', $out->getTitle() )->getLinkURL()
			);
			$out->addModules( [ 'mw.ext.story.discover' ] );
		}
	}

}
