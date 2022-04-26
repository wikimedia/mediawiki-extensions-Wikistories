<?php

namespace MediaWiki\Extension\Wikistories;

use Exception;
use FormatJson;

class StoryConverter {

	/**
	 * @param StoryContent $story
	 * @return StoryContent New instance migrated to the latest version of the schema
	 */
	private function fromV0( StoryContent $story ): StoryContent {
		return new StoryContent( FormatJson::encode( [
			'fromArticle' => $story->getFromArticle(),
			'frames' => array_map( static function ( $f ) {
				$parts = explode( '/', parse_url( $f->img, PHP_URL_PATH ) );
				$filename = preg_replace( '/^\d+px\-/', '', urldecode( end( $parts ) ) );
				return [
					'image' => [
						'filename' => $filename,
						'repo' => $parts[ 2 ],
					],
					'text' => [
						'value' => $f->text
					]
				];
			}, $story->getFrames() )
		] ) );
	}

	/**
	 * @param StoryContent $story Story to convert to latest version
	 * @return StoryContent converted story or same instance if already at latest version
	 * @throws Exception When the story doesn't have a known version
	 */
	public function toLatest( StoryContent $story ): StoryContent {
		if ( $story->getSchemaVersion() === 0 ) {
			return $this->fromV0( $story );
		}
		if ( $story->isLatestVersion() ) {
			return $story;
		}
		throw new Exception( 'Unknown schema version: ' . $story->getSchemaVersion() );
	}

	/**
	 * @param StoryContent $story
	 * @return StoryContent New instance with the latest schema version set
	 * or the same instance if the version is already set
	 */
	public function withSchemaVersion( StoryContent $story ): StoryContent {
		if ( $story->isLatestVersion() ) {
			return $story;
		}
		$content = $story->getData()->getValue();
		$content->schemaVersion = StoryContent::SCHEMA_VERSION;
		return new StoryContent( FormatJson::encode( $content ) );
	}
}
