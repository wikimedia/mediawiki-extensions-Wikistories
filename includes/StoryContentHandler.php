<?php

namespace MediaWiki\Extension\Wikistories;

use Content;
use IContextSource;
use JsonContentHandler;
use MediaWiki\Content\Renderer\ContentParseParams;
use MediaWiki\MediaWikiServices;
use ParserOutput;
use Title;
use TitleValue;

class StoryContentHandler extends JsonContentHandler {

	/**
	 * @param string $modelId
	 */
	public function __construct( $modelId = 'story' ) {
		parent::__construct( $modelId );
	}

	/**
	 * @return string
	 */
	protected function getContentClass() {
		return StoryContent::class;
	}

	/**
	 * @return array
	 */
	public function getActionOverrides() {
		return [
			'edit' => StoryEditAction::class,
			'submit' => StorySubmitAction::class,
		];
	}

	/**
	 * @return StoryContent
	 */
	public function makeEmptyContent() {
		return new StoryContent( json_encode( [ 'frames' => [] ], JSON_PRETTY_PRINT ) );
	}

	/**
	 * Outputs the plain html version of a story.
	 *
	 * @param Content $content
	 * @param ContentParseParams $cpoParams
	 * @param ParserOutput &$output
	 */
	public function fillParserOutput( Content $content, ContentParseParams $cpoParams, ParserOutput &$output ) {
		'@phan-var StoryContent $content';

		if ( !$cpoParams->getGenerateHtml() ) {
			return;
		}

		$html = '';
		/** @var StoryContent $story */
		$story = $content;
		$renderer = new StoryRenderer( $story );

		// register links from story frames to source articles
		$relatedArticles = [ $story->getFromArticle() ];
		foreach ( $story->getFrames() as $frame ) {
			if ( isset( $frame->source ) ) {
				$title = Title::newFromText( $frame->source );
				if ( $title ) {
					$relatedArticles[] = $title;
				}
			}
		}
		// todo: inject
		/** @var StoriesCache $cache */
		$cache = MediaWikiServices::getInstance()->get( 'Wikistories.Cache' );
		foreach ( array_unique( $relatedArticles ) as $relatedArticle ) {
			$output->addLink( new TitleValue( NS_MAIN, $relatedArticle ) );
			// todo: only invalidate the cache if the links have changed
			$cache->invalidateForArticle( $relatedArticle );
		}

		// no-js
		$parts = $renderer->renderNoJS();
		$output->addModuleStyles( [ $parts['style'] ] );
		$html .= $parts['html'];

		// Show the story title instead of the standard Ns:Title (Story:Boat) as h1 on the page
		if ( count( $story->getFrames() ) >= 1 ) {
			$output->setDisplayTitle( $story->getFrames()[0]->text );
		}

		$output->setText( $html );
	}

	/**
	 * @inheritDoc
	 */
	public function isParserCacheSupported() {
		return true;
	}

	/**
	 * @param IContextSource $context
	 * @param array $options
	 * @return StorySlotDiffRenderer
	 */
	public function getSlotDiffRendererWithOptions( IContextSource $context, $options = [] ) {
		return new StorySlotDiffRenderer();
	}
}
