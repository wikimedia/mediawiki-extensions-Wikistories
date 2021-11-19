<?php

namespace MediaWiki\Extension\Wikistories;

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
}
