<?php

namespace MediaWiki\Extension\Wikistories;

use Content;
use Html;
use MediaWiki\Content\Renderer\ContentParseParams;
use ParserOutput;

class StoryContentHandler extends \JsonContentHandler {

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

		// todo: js version
//		$output->addModules( 'mw.ext.story.viewer-js' );
//		$output->addJsConfigVars( 'story', $this->getData()->getValue() );
//		$html .= Html::element( 'div', [ 'class' => 'story-viewer-js-root' ] );

		// nojs
		$output->addModuleStyles( 'mw.ext.story.viewer-nojs' );
		$html .= Html::rawElement(
			'div',
			[ 'class' => 'story-viewer-nojs-root' ],
			implode( '', array_map( static function ( $frame ) {
				return Html::rawElement(
					'div', [
					'class' => 'story-viewer-frame',
					'style' => 'background-image:url(' . $frame->img . ');',
				],
					Html::element( 'p', [], $frame->text )
				);
			}, $content->getFrames() ) )
		);
		$output->setText( $html );
	}
}
