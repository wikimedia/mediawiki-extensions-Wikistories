<?php

namespace MediaWiki\Extension\Wikistories;

use Html;
use JsonContent;
use ParserOptions;
use ParserOutput;
use Title;

class StoryContent extends JsonContent {

	public const MAX_FRAMES = 5;

	/**
	 * @param string $text
	 * @param string $modelId
	 */
	public function __construct( $text, $modelId = 'story' ) {
		parent::__construct( $text, $modelId );
	}

	/**
	 * @return array
	 */
	public function getFrames() {
		$story = $this->getData()->getValue();
		return $story->frames ?? [];
	}

	/**
	 * @return bool True when all frames contain an image URL and some text
	 */
	private function framesAreValid() {
		$frames = $this->getFrames();
		$frameCount = count( $frames );
		if ( $frameCount === 0 || $frameCount <= self::MAX_FRAMES ) {
			foreach ( $frames as $frame ) {
				if ( empty( $frame->img ) || empty( $frame->text ) ) {
					return false;
				}
			}
			return true;
		}
		return false;
	}

	public function isValid() {
		return parent::isValid() && $this->framesAreValid();
	}

	/**
	 * @param Title $title
	 * @param int $revId
	 * @param ParserOptions $options
	 * @param bool $generateHtml
	 * @param ParserOutput &$output
	 */
	protected function fillParserOutput( Title $title, $revId,
			ParserOptions $options, $generateHtml, ParserOutput &$output
		) {
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
						'style' => 'background-image:url(' . $frame->img . ' );',
					],
					Html::element( 'p', [], $frame->text )
				);
			}, $this->getFrames() ) )
		);
		$output->setText( $html );
	}
}
