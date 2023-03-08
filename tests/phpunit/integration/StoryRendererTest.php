<?php

namespace MediaWiki\Extension\Wikistories;

use ForeignAPIFile;
use MediaWiki\Extension\Wikistories\Tests\StoryFactory;
use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use RepoGroup;
use TitleValue;

class StoryRendererTest extends MediaWikiIntegrationTestCase {

	private function createRepoGroupMock() {
		$catPosterFile = $this->createMock( ForeignAPIFile::class );
		$catPosterFile->method( 'createThumb' )->willReturn( 'cat-poster-url' );
		$catPosterFile->method( 'getExtendedMetadata' )->willReturn(
			[
				'Artist' => [ 'value' => 'cat-poster-artist' ],
				'LicenseShortName' => [ 'value' => 'Public domain' ],
			]
		);
		$catPosterFile->method( 'getDescriptionUrl' )->willReturn( 'cat-poster-attribution-url' );
		$catPosterFile->method( 'getSha1' )->willReturn( 'cat-poster-sha1' );

		$catNappingFile = $this->createMock( ForeignAPIFile::class );
		$catNappingFile->method( 'createThumb' )->willReturn( 'cat-napping-url' );
		$catNappingFile->method( 'getExtendedMetadata' )->willReturn(
			[
				'Artist' => [ 'value' => 'cat-napping-artist' ],
				'LicenseShortName' => [ 'value' => 'CC SA' ],
			]
		);
		$catNappingFile->method( 'getDescriptionUrl' )->willReturn( 'cat-napping-attribution-url' );
		$catNappingFile->method( 'getSha1' )->willReturn( 'cat-napping-sha1' );

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
		$renderer = new StoryRenderer( $repoGroup, MediaWikiServices::getInstance()->getTitleFormatter() );
		$storyForViewer = $renderer->getStoryForViewer(
			$story,
			12,
			new TitleValue( NS_STORY, 'Story about Cats' )
		);

		$this->assertEquals( 'Story about Cats', $storyForViewer[ 'title' ] );
		$this->assertEquals( 12, $storyForViewer[ 'pageId' ] );
		$this->assertStringEndsWith( 'Special:StoryBuilder/Story:Story_about_Cats', $storyForViewer[ 'editUrl' ] );
		$this->assertCount( 2, $storyForViewer[ 'frames' ] );
		$this->assertEquals(
			'cat-poster-url',
			$storyForViewer[ 'frames' ][ 0 ][ 'url' ]
		);
		$this->assertEquals( 'This is a cat', $storyForViewer[ 'frames' ][ 0 ][ 'text' ] );
		$this->assertEquals(
			'cat-poster-attribution-url',
			$storyForViewer[ 'frames' ][ 0 ][ 'attribution' ][ 'url' ]
		);
		$this->assertEquals(
			'cat-poster-artist',
			$storyForViewer[ 'frames' ][ 0 ][ 'attribution' ][ 'author' ]
		);
		$this->assertContains( 'Public', $storyForViewer[ 'frames' ][ 0 ][ 'attribution' ][ 'license' ] );
		$this->assertEquals(
			'cat-napping-url',
			$storyForViewer[ 'frames' ][ 1 ][ 'url' ]
		);
		$this->assertEquals( 'Sleeping now...', $storyForViewer[ 'frames' ][ 1 ][ 'text' ] );
		$this->assertEquals(
			'cat-napping-attribution-url',
			$storyForViewer[ 'frames' ][ 1 ][ 'attribution' ][ 'url' ]
		);
		$this->assertEquals(
			'cat-napping-artist',
			$storyForViewer[ 'frames' ][ 1 ][ 'attribution' ][ 'author' ]
		);
		$this->assertContains( 'CC', $storyForViewer[ 'frames' ][ 1 ][ 'attribution' ][ 'license' ] );
		$this->assertContains( 'SA', $storyForViewer[ 'frames' ][ 1 ][ 'attribution' ][ 'license' ] );
	}

	/**
	 * @covers MediaWiki\Extension\Wikistories\StoryRenderer::renderNoJS
	 */
	public function testRenderNoJS() {
		$story = StoryFactory::makeValidStory();
		$repoGroup = $this->createRepoGroupMock();
		$renderer = new StoryRenderer( $repoGroup, MediaWikiServices::getInstance()->getTitleFormatter() );
		$parts = $renderer->renderNoJS( $story, 12 );

		$this->assertArrayHasKey( 'html', $parts );
		$this->assertStringContainsString(
			'ext-wikistories-viewer-nojs-frame-text',
			$parts[ 'html' ]
		);
		$this->assertStringContainsString(
			'ext-wikistories-viewer-nojs-frame-attribution-info',
			$parts[ 'html' ]
		);
		$this->assertStringContainsString(
			'ext-wikistories-viewer-nojs-frame-attribution-more-info',
			$parts[ 'html' ]
		);
		$this->assertArrayHasKey( 'style', $parts );
	}
}
