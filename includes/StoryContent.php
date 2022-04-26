<?php

namespace MediaWiki\Extension\Wikistories;

use Html;
use JsonContent;

class StoryContent extends JsonContent {

	public const SCHEMA_VERSION = 1;

	/**
	 * @param string $text
	 */
	public function __construct( $text ) {
		parent::__construct( $text, 'story' );
	}

	/**
	 * @return array
	 */
	public function getFrames() {
		$story = $this->getData()->getValue();
		return $story->frames ?? [];
	}

	/**
	 * @return string The title of the article this story was created from
	 */
	public function getFromArticle(): string {
		$story = $this->getData()->getValue();
		return $story->fromArticle ?? '';
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

	/**
	 * @return string A simple version of the story frames as text for diff
	 */
	public function getTextForDiff() {
		return implode( "\n\n", array_map( static function ( $frame ) {
			return $frame->image->filename . "\n" . $frame->text->value;
		}, $this->getFrames() ) );
	}

	/**
	 * @inheritDoc
	 */
	public function getTextForSummary( $maxlength = 250 ) {
		$framesText = implode( "\n", array_map( static function ( $frame ) {
			return $frame->text->value;
		}, $this->getFrames() ) );
		return mb_substr( $framesText, 0, $maxlength );
	}

	/**
	 * @return bool This story is using the latest schema version
	 */
	public function isLatestVersion(): bool {
		return $this->getSchemaVersion() === self::SCHEMA_VERSION;
	}

	/**
	 * @return int Schema version used by this story
	 */
	public function getSchemaVersion(): int {
		$content = $this->getData()->getValue();
		return $content->schemaVersion ?? 0;
	}
}
