<?php

namespace MediaWiki\Extension\Wikistories;

use ForeignAPIFile;
use MediaWiki\Extension\Wikistories\Tests\StoryFactory;
use MediaWikiUnitTestCase;
use RepoGroup;

class StoryRendererTest extends MediaWikiUnitTestCase {

	private function createRepoGroupMock() {
		$catPosterFile = $this->createMock( ForeignAPIFile::class );
		$catPosterFile->method( 'createThumb' )->willReturn( 'cat-poster-url' );
		$catPosterFile->method( 'getExtendedMetadata' )->willReturn(
			[ 'Artist' => 'cat-poster-artist', 'LicenseShortName' => 'cat-poster-license' ]
		);
		$catPosterFile->method( 'getDescriptionUrl' )->willReturn( 'cat-poster-attribution-url' );

		$catNappingFile = $this->createMock( ForeignAPIFile::class );
		$catNappingFile->method( 'createThumb' )->willReturn( 'cat-napping-url' );
		$catNappingFile->method( 'getExtendedMetadata' )->willReturn(
			[ 'Artist' => 'cat-napping-artist', 'LicenseShortName' => 'cat-napping-license' ]
		);
		$catNappingFile->method( 'getDescriptionUrl' )->willReturn( 'cat-napping-attribution-url' );

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
	 * @covers MediaWiki\Extension\Wikistories\StoryRenderer::getStoryForViewer
	 */
	public function testGetStoryForViewer() {
		$story = StoryFactory::makeValidStory();
		$repoGroup = $this->createRepoGroupMock();
		$renderer = new StoryRenderer( $repoGroup );
		$storyForViewer = $renderer->getStoryForViewer( $story, 12, 'Story about Cats' );

		$this->assertEquals( 'Story about Cats', $storyForViewer[ 'title' ] );
		$this->assertEquals( 12, $storyForViewer[ 'pageId' ] );
		$this->assertCount( 2, $storyForViewer[ 'frames' ] );
		$this->assertEquals(
			'cat-poster-url',
			$storyForViewer[ 'frames' ][ 0 ][ 'img' ]
		);
		$this->assertEquals( 'This is a cat', $storyForViewer[ 'frames' ][ 0 ][ 'text' ] );
		$this->assertEquals(
			'cat-poster-attribution-url',
			$storyForViewer[ 'frames' ][ 0 ][ 'attribution' ][ 'url' ]
		);
		$this->assertEquals(
			[ 'Artist' => 'cat-poster-artist', 'LicenseShortName' => 'cat-poster-license' ],
			$storyForViewer[ 'frames' ][ 0 ][ 'attribution' ][ 'extmetadata' ]
		);
		$this->assertEquals(
			'cat-napping-url',
			$storyForViewer[ 'frames' ][ 1 ][ 'img' ]
		);
		$this->assertEquals( 'Sleeping now...', $storyForViewer[ 'frames' ][ 1 ][ 'text' ] );
		$this->assertEquals(
			'cat-napping-attribution-url',
			$storyForViewer[ 'frames' ][ 1 ][ 'attribution' ][ 'url' ]
		);
		$this->assertEquals(
			[ 'Artist' => 'cat-napping-artist', 'LicenseShortName' => 'cat-napping-license' ],
			$storyForViewer[ 'frames' ][ 1 ][ 'attribution' ][ 'extmetadata' ]
		);
	}

	/**
	 * @covers MediaWiki\Extension\Wikistories\StoryRenderer::renderNoJS
	 */
	public function testRenderNoJS() {
		$story = StoryFactory::makeValidStory();
		$repoGroup = $this->createRepoGroupMock();
		$renderer = new StoryRenderer( $repoGroup );
		$parts = $renderer->renderNoJS( $story );

		$this->assertArrayHasKey( 'html', $parts );
		$this->assertArrayHasKey( 'style', $parts );
	}
}
