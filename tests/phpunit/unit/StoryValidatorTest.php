<?php

namespace MediaWiki\Extension\Wikistories;

use File;
use FormatJson;
use HashConfig;
use MediaWiki\Config\ServiceOptions;
use MediaWikiUnitTestCase;
use RepoGroup;

class StoryValidatorTest extends MediaWikiUnitTestCase {

	private function createRepoGroupMock() {
		$catPosterFile = $this->createMock( File::class );
		$catPosterFile->method( 'createThumb' )->willReturn( 'cat-poster-url' );

		$catNappingFile = $this->createMock( File::class );
		$catNappingFile->method( 'createThumb' )->willReturn( 'cat-napping-url' );

		$repoGroup = $this->createMock( RepoGroup::class );
		$repoGroup->method( 'findFiles' )->willReturn(
			[
				'Cat_poster_1.jpg' => $catPosterFile,
				'Cat_napping.jpg' => $catNappingFile,
			]
		);

		return $repoGroup;
	}

	/**
	 * @dataProvider provideIsValidStories
	 * @covers MediaWiki\Extension\Wikistories\StoryValidator::isValid
	 * @param bool $expectedValid
	 * @param array $obj Story object structure
	 */
	public function testIsValid( $expectedValid, $obj ) {
		$options = new ServiceOptions(
			StoryValidator::CONSTRUCTOR_OPTIONS,
			new HashConfig( [
				'WikistoriesMinFrames' => 2,
				'WikistoriesMaxFrames' => 3,
			] )
		);

		$story = new StoryContent( FormatJson::encode( $obj ) );
		$validator = new StoryValidator( $options, $this->createRepoGroupMock() );
		$this->assertEquals( $expectedValid, $validator->isValid( $story )->isGood() );
	}

	public function provideIsValidStories() {
		return [
			'invalid json' => [ false, 'this is not even json' ],
			'Not enough frames' => [ false, [
				'schemaVersion' => 1,
				'fromArticle' => 'Cat',
				'frames' => [
					[
						'image' => [ 'filename' => 'Cat_poster_1.jpg', 'repo' => 'en' ],
						'text' => [ 'value' => 'This is a cat' ]
					],
				]
			] ],
			'Too many frames' => [ false, [
				'schemaVersion' => 1,
				'fromArticle' => 'Cat',
				'frames' => array_fill( 0, 4, [
					'image' => [ 'filename' => 'Cat_poster_1.jpg', 'repo' => 'en' ],
					'text' => [ 'value' => 'This is a cat' ]
				] )
			] ],
			'Missing image' => [ false, [
				'schemaVersion' => 1,
				'fromArticle' => 'Cat',
				'frames' => [
					[
						'image' => [ 'repo' => 'en' ],
						'text' => [ 'value' => 'This is a cat' ]
					],
					[
						'text' => [ 'value' => 'Sleeping now...' ]
					],
				]
			] ],
			'Missing text' => [ false, [
				'schemaVersion' => 1,
				'fromArticle' => 'Cat',
				'frames' => [
					[
						'image' => [ 'filename' => 'Cat_poster_1.jpg', 'repo' => 'en' ],
					],
					[
						'image' => [ 'filename' => 'Cat_napping.jpg', 'repo' => 'en' ],
						'text' => [ 'value' => '' ]
					],
				]
			] ],
			'Missing fromArticle' => [ false, [
				'schemaVersion' => 1,
				'frames' => [
					[
						'image' => [ 'filename' => 'Cat_poster_1.jpg', 'repo' => 'en' ],
						'text' => [ 'value' => 'This is a cat' ]
					],
					[
						'image' => [ 'filename' => 'Cat_napping.jpg', 'repo' => 'en' ],
						'text' => [ 'value' => 'Sleeping now...' ]
					],
				]
			] ],
			'Minimum story content' => [ true, [
				'schemaVersion' => 1,
				'fromArticle' => 'Cat',
				'frames' => [
					[
						'image' => [ 'filename' => 'Cat_poster_1.jpg', 'repo' => 'en' ],
						'text' => [ 'value' => 'This is a cat' ]
					],
					[
						'image' => [ 'filename' => 'Cat_napping.jpg', 'repo' => 'en' ],
						'text' => [ 'value' => 'Sleeping now...' ]
					],
				]
			] ],
			'Empty is OK' => [ true, (object)[] ]
		];
	}
}
