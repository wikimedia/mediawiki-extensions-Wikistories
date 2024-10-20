<?php

namespace MediaWiki\Extension\Wikistories;

use File;
use MediaWiki\Config\HashConfig;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Json\FormatJson;
use MediaWiki\Page\ExistingPageRecord;
use MediaWiki\Page\PageLookup;
use MediaWikiIntegrationTestCase;
use RepoGroup;

class StoryValidatorTest extends MediaWikiIntegrationTestCase {

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

	private function createPageStoreMock() {
		$pageStore = $this->createMock( PageLookup::class );
		$pageStore->method( 'getPageById' )->willReturnMap( [
			[ 114, 0, $this->createMock( ExistingPageRecord::class ) ],
			[ 999, 0, null ],
		] );
		return $pageStore;
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
				'WikistoriesMaxTextLength' => 50,
			] )
		);

		$story = new StoryContent( FormatJson::encode( $obj ) );
		$validator = new StoryValidator(
			$options,
			$this->createRepoGroupMock(),
			$this->createPageStoreMock()
		);
		$this->assertEquals( $expectedValid, $validator->isValid( $story )->isGood() );
	}

	public static function provideIsValidStories() {
		return [
			'invalid json' => [ false, 'this is not even json' ],
			'Not enough frames' => [ false, [
				'schemaVersion' => 1,
				'articleId' => 114,
				'frames' => [
					[
						'image' => [ 'filename' => 'Cat_poster_1.jpg' ],
						'text' => [ 'value' => 'This is a cat' ]
					],
				]
			] ],
			'Too many frames' => [ false, [
				'schemaVersion' => 1,
				'articleId' => 114,
				'frames' => array_fill( 0, 4, [
					'image' => [ 'filename' => 'Cat_poster_1.jpg' ],
					'text' => [ 'value' => 'This is a cat' ]
				] )
			] ],
			'Missing image' => [ false, [
				'schemaVersion' => 1,
				'articleId' => 114,
				'frames' => [
					[
						'image' => [],
						'text' => [ 'value' => 'This is a cat' ]
					],
					[
						'text' => [ 'value' => 'Sleeping now...' ]
					],
				]
			] ],
			'Missing text' => [ false, [
				'schemaVersion' => 1,
				'articleId' => 114,
				'frames' => [
					[
						'image' => [ 'filename' => 'Cat_poster_1.jpg' ],
					],
					[
						'image' => [ 'filename' => 'Cat_napping.jpg' ],
						'text' => [ 'value' => '' ]
					],
				]
			] ],
			'Missing articleId' => [ false, [
				'schemaVersion' => 1,
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
			] ],
			'article id not found' => [ false, [
				'schemaVersion' => 1,
				'articleId' => 999,
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
			] ],
			'Minimum story content' => [ true, [
				'schemaVersion' => 1,
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
				]
			] ],
			'Text too long' => [ false, [
				'schemaVersion' => 1,
				'articleId' => 114,
				'frames' => [
					[
						'image' => [ 'filename' => 'Cat_poster_1.jpg' ],
						'text' => [ 'value' => 'This is a cat' ]
					],
					[
						'image' => [ 'filename' => 'Cat_napping.jpg' ],
						'text' => [ 'value' => '123456789 123456789 123456789 123456789 123456789 asdf' ]
					],
				]
			] ],
			'File does not exist' => [ false, [
				'schemaVersion' => 1,
				'articleId' => 114,
				'frames' => [
					[
						'image' => [ 'filename' => 'Cat_poster_1.jpg' ],
						'text' => [ 'value' => 'This is a cat' ]
					],
					[
						'image' => [ 'filename' => 'NOT-A-FILE.jpg' ],
						'text' => [ 'value' => 'Sleeping now...' ]
					],
				]
			] ],
			'Empty is OK' => [ true, (object)[] ]
		];
	}
}
