<?php

namespace MediaWiki\Extension\Wikistories;

use Parser;
use Title;

class StoryParserFunction {

	/**
	 * From a parser function, the story content, as it exists as the time of
	 * saving the article, can be included in the article content in the
	 * parser cache.
	 *
	 * Another alternative is for the parser function to return a snippet of html
	 * that can load the story dynamically. <div data-story-title="Story:Boat"></div>
	 *
	 * @param Parser $parser
	 * @param string $storyTitle
	 * @return array with $output and options
	 */
	public static function renderStory( Parser $parser, $storyTitle ) {
		$title = Title::newFromText( $storyTitle, NS_STORY );
		$record = $parser->fetchCurrentRevisionRecordOfTitle( $title );
		if ( $record ) {
			/** @var StoryContent $story */
			$story = $record->getContent( 'main' );
			'@phan-var StoryContent $story';
			$renderer = new StoryRenderer( $story );
			$parts = $renderer->renderJs();

			$parser->getOutput()->addModuleStyles( [ $parts['style'] ] );
			$parser->getOutput()->addModules( [ $parts['script'] ] );
			$parser->getOutput()->addJsConfigVars( $parts['configVars'] );

			// Special:WhatLinksHere/Story:$storyTitle will list the articles embedding this story
			$parser->getOutput()->addLink( $title, $record->getPageId() );

			return [ $parts['html'], 'isHTML' => true ];
		}
	}

}
