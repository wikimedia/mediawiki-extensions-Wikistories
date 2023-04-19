<?php

namespace MediaWiki\Extension\Wikistories\Tests;

use FormatJson;
use MediaWiki\Extension\Wikistories\StoryContent;

class StoryFactory {

	public static function makeValidStory() {
		return new StoryContent( FormatJson::encode( [
			'schemaVersion' => StoryContent::SCHEMA_VERSION,
			'articleId' => 114,
			'frames' => [
				[
					'image' => [ 'filename' => 'Cat_poster_1.jpg' ],
					'text' => [ 'value' => 'This is a cat' ],
				],
				[
					'image' => [ 'filename' => 'Cat_napping.jpg' ],
					'text' => [ 'value' => 'Sleeping now...' ],
				],
			],
			'categories' => [ 'Domesticated animals', 'Cats' ]
		] ) );
	}

	public static function makeV0Story() {
		return new StoryContent( FormatJson::encode( [
			'fromArticle' => 'Cat',
			'frames' => [
				[
					'img' => 'some file url',
					'text' => 'This is a cat',
				],
				[
					'img' => 'some other file url',
					'text' => 'Sleeping now...',
				],
			]
		] ) );
	}
}
