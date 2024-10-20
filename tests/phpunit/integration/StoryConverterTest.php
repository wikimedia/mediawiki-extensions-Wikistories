<?php

namespace MediaWiki\Extension\Wikistories;

use MediaWiki\Json\FormatJson;
use MediaWikiIntegrationTestCase;

/**
 * @group Database
 */
class StoryConverterTest extends MediaWikiIntegrationTestCase {

	/**
	 * @covers MediaWiki\Extension\Wikistories\StoryConverter::withSchemaVersion
	 */
	public function testWithSchemaVersion() {
		$content = [
			'fromArticle' => 'Cat',
			'frames' => [
				[
					'image' => [ 'filename' => 'Cat_poster_1.jpg' ],
					'text' => [ 'value' => 'This is a cat' ]
				],
				[
					'image' => [ 'filename' => 'Cat_napping.jpg' ],
					'text' => [ 'value' => 'Sleeping now...' ]
				],
			]
		];
		$story = new StoryContent( FormatJson::encode( $content ) );
		$converter = new StoryConverter();
		$storyWithVersion = $converter->withSchemaVersion( $story );
		$this->assertEquals(
			StoryContent::SCHEMA_VERSION,
			$storyWithVersion->getData()->getValue()->schemaVersion
		);
	}

	/**
	 * @covers MediaWiki\Extension\Wikistories\StoryConverter::toLatest
	 */
	public function testToLatest() {
		$contentBefore = [
			'fromArticle' => 'Cat',
			'articleId' => 114,
			'frames' => [
				[
					// phpcs:ignore Generic.Files.LineLength.TooLong
					'img' => 'https://upload.wikimedia.org/wikipedia/en/thumb/c/cc/Cat_poster_1.jpg/480px-Cat_poster_1.jpg',
					'text' => 'This is a cat'
				],
				[
					// phpcs:ignore Generic.Files.LineLength.TooLong
					'img' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/cc/Cat_napping.jpg/480px-Cat_napping.jpg',
					'text' => 'Sleeping now...'
				],
				[
					'img' => 'https://upload.wikimedia.org/wikipedia/en/1/18/Titanic_%281997_film%29_poster.png',
					'text' => 'Something else entirely'
				],
			]
		];
		$contentAfter = [
			'fromArticle' => 'Cat',
			'articleId' => 114,
			'frames' => [
				[
					'image' => [ 'filename' => 'Cat_poster_1.jpg' ],
					'text' => [ 'value' => 'This is a cat' ]
				],
				[
					'image' => [ 'filename' => 'Cat_napping.jpg' ],
					'text' => [ 'value' => 'Sleeping now...' ]
				],
				[
					'image' => [ 'filename' => 'Titanic_(1997_film)_poster.png' ],
					'text' => [ 'value' => 'Something else entirely' ]
				],
			]
		];
		$story = new StoryContent( FormatJson::encode( $contentBefore ) );
		$converter = new StoryConverter();
		$storyUpdated = $converter->toLatest( $story );
		$expectedStory = new StoryContent( FormatJson::encode( $contentAfter ) );
		$this->assertEquals( $expectedStory->getData()->getValue(), $storyUpdated->getData()->getValue() );
	}
}
