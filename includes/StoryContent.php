<?php

namespace MediaWiki\Extension\Wikistories;

use Html;
use JsonContent;

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
	 * Return a single image or a gallery in wikitext
	 *
	 * @return bool|string
	 */
	public function getWikitextForTransclusion() {
		if ( !$this->isValid() ) {
			return false;
		}

		$length = count( $this->getFrames() );
		if ( $length === 1 ) {
			// Single image
			$img = $this->getFrames()[0]->img;
			$path = parse_url( $img, PHP_URL_PATH );
			$pathFragments = explode( '/', $path );
			$file = end( $pathFragments );
			$text = $this->getFrames()[0]->text;
			return "[[Image:$file|$text]]";
		} elseif ( $length > 1 ) {
			// Gallery
			return Html::element(
				'gallery',
				[],
				implode( "\n", array_map( static function ( $frame ) {
					$path = parse_url( $frame->img, PHP_URL_PATH );
					$pathFragments = explode( '/', $path );
					$file = end( $pathFragments );
					return $file . '|' . $frame->text;
				}, $this->getFrames() ) )
			);
		}
	}
}
