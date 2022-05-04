<?php

namespace MediaWiki\Extension\Wikistories;

use MediaWiki\Extension\Wikistories\Tests\StoryFactory;
use MediaWikiUnitTestCase;

class StoryRendererTest extends MediaWikiUnitTestCase {

	private function createRepoGroupMock() {
		$catPosterFile = $this->getMockBuilder( 'File' )->disableOriginalConstructor()->getMock();
		$catPosterFile->method( 'createThumb' )->willReturn( 'cat-poster-url' );

		$catNappingFile = $this->getMockBuilder( 'File' )->disableOriginalConstructor()->getMock();
		$catNappingFile->method( 'createThumb' )->willReturn( 'cat-napping-url' );

		$repoGroup = $this->getMockBuilder( 'RepoGroup' )->disableOriginalConstructor()->getMock();
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
			'cat-napping-url',
			$storyForViewer[ 'frames' ][ 1 ][ 'img' ]
		);
		$this->assertEquals( 'Sleeping now...', $storyForViewer[ 'frames' ][ 1 ][ 'text' ] );
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
