<?php

namespace MediaWiki\Extension\Wikistories;

use MediaWiki\Extension\Wikistories\Tests\StoryFactory;
use MediaWikiUnitTestCase;

class StoryRendererTest extends MediaWikiUnitTestCase {

	/**
	 * @covers MediaWiki\Extension\Wikistories\StoryRenderer::getStoryForViewer
	 */
	public function testGetStoryForViewer() {
		$story = StoryFactory::makeValidStory();
		$renderer = new StoryRenderer();
		$storyForViewer = $renderer->getStoryForViewer( $story, 12, 'Story about Cats' );

		$this->assertEquals( 'Story about Cats', $storyForViewer[ 'title' ] );
		$this->assertEquals( 12, $storyForViewer[ 'pageId' ] );
		$this->assertCount( 2, $storyForViewer[ 'frames' ] );
		$this->assertEquals(
			'https://upload.wikimedia.org/wikipedia/commons/thumb/0/0b/Cat_poster_1.jpg/640px-Cat_poster_1.jpg',
			$storyForViewer[ 'frames' ][ 0 ][ 'img' ]
		);
		$this->assertEquals( 'This is a cat', $storyForViewer[ 'frames' ][ 0 ][ 'text' ] );
		$this->assertEquals(
			'https://upload.wikimedia.org/wikipedia/en/thumb/5/58/Cat_napping.jpg/640px-Cat_napping.jpg',
			$storyForViewer[ 'frames' ][ 1 ][ 'img' ]
		);
		$this->assertEquals( 'Sleeping now...', $storyForViewer[ 'frames' ][ 1 ][ 'text' ] );
	}

	/**
	 * @covers MediaWiki\Extension\Wikistories\StoryRenderer::renderNoJS
	 */
	public function testRenderNoJS() {
		$story = StoryFactory::makeValidStory();
		$renderer = new StoryRenderer();
		$parts = $renderer->renderNoJS( $story );

		$this->assertArrayHasKey( 'html', $parts );
		$this->assertArrayHasKey( 'style', $parts );
	}
}
