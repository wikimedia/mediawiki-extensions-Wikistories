<?php

namespace MediaWiki\Extension\Wikistories;

use Article;
use Html;
use MWException;
use OutputPage;
use Parser;
use ParserOutput;
use SkinTemplate;

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
	 * Show related story on article pages
	 *
	 * @param Article &$article
	 * @param bool|ParserOutput &$outputDone
	 * @param bool &$pcache
	 */
	public static function onArticleViewHeader( &$article, &$outputDone, &$pcache ) {
		if ( $article->getTitle()->getNamespace() === NS_MAIN && $article->getPage()->exists() ) {
			$out = $article->getContext()->getOutput();
			$out->addModules( [ 'mw.ext.story.builder' ] );
			$out->addHTML(
				Html::element(
					'a',
					[ 'class' => 'wikistories-create', 'style' => 'display: none;' ],
					$out->msg( 'wikistories-hooks-createstory' )->text()
				)
			);
		}
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
			$out->addModules( [ 'mw.ext.story.discover' ] );
		}
	}

}
