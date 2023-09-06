<?php

namespace MediaWiki\Extension\Wikistories;

use ForeignAPIFile;
use MediaWiki\Extension\Wikistories\Tests\StoryFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\Page\ExistingPageRecord;
use MediaWiki\Page\PageLookup;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;
use RepoGroup;

/**
 * @group Database
 */
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

	private function createPageStoreMock() {
		$pageRecordMock = $this->createMock( ExistingPageRecord::class );
		$pageRecordMock->method( 'getDbKey' )->willReturn( 'Main_page' );

		$pageStore = $this->createMock( PageLookup::class );
		$pageStore->method( 'getPageById' )->willReturnMap( [
			[ 114, 0, $pageRecordMock ],
			[ 999, 0, null ],
		] );
		return $pageStore;
	}

	/**
	 * @covers MediaWiki\Extension\Wikistories\StoryRenderer::getStoryData
	 */
	public function testGetStoryData() {
		$story = StoryFactory::makeValidStory();
		$repoGroup = $this->createRepoGroupMock();
		$renderer = new StoryRenderer(
			$repoGroup,
			MediaWikiServices::getInstance()->getRedirectLookup(),
			$this->createPageStoreMock(),
			$this->createAnalyzerMock()
		);
		$storyData = $renderer->getStoryData(
			$story,
			Title::makeTitle( NS_STORY, 'Story about Cats' )
		);

		$this->assertEquals( 'Story about Cats', $storyData[ 'storyTitle' ] );
		$this->assertArrayHasKey( 'storyId', $storyData );
		$this->assertStringEndsWith( 'Special:StoryBuilder/Story:Story_about_Cats', $storyData[ 'editUrl' ] );
		$this->assertCount( 2, $storyData[ 'frames' ] );
		$this->assertEquals(
			'cat-poster-url',
			$storyData[ 'frames' ][ 0 ][ 'url' ]
		);
		$this->assertEquals( 'This is a cat', $storyData[ 'frames' ][ 0 ][ 'text' ] );
		$this->assertEquals(
			'cat-poster-attribution-url',
			$storyData[ 'frames' ][ 0 ][ 'attribution' ][ 'url' ]
		);
		$this->assertEquals(
			'cat-poster-artist',
			$storyData[ 'frames' ][ 0 ][ 'attribution' ][ 'author' ]
		);
		$this->assertContains( 'Public', $storyData[ 'frames' ][ 0 ][ 'attribution' ][ 'license' ] );
		$this->assertEquals(
			'cat-napping-url',
			$storyData[ 'frames' ][ 1 ][ 'url' ]
		);
		$this->assertEquals( 'Sleeping now...', $storyData[ 'frames' ][ 1 ][ 'text' ] );
		$this->assertEquals(
			'cat-napping-attribution-url',
			$storyData[ 'frames' ][ 1 ][ 'attribution' ][ 'url' ]
		);
		$this->assertEquals(
			'cat-napping-artist',
			$storyData[ 'frames' ][ 1 ][ 'attribution' ][ 'author' ]
		);
		$this->assertContains( 'CC', $storyData[ 'frames' ][ 1 ][ 'attribution' ][ 'license' ] );
		$this->assertContains( 'SA', $storyData[ 'frames' ][ 1 ][ 'attribution' ][ 'license' ] );
	}

	/**
	 * @covers MediaWiki\Extension\Wikistories\StoryRenderer::renderNoJS
	 */
	public function testRenderNoJS() {
		$storyData = StoryFactory::makeValidStoryData();
		$repoGroup = $this->createRepoGroupMock();
		$renderer = new StoryRenderer(
			$repoGroup,
			MediaWikiServices::getInstance()->getRedirectLookup(),
			$this->createPageStoreMock(),
			$this->createAnalyzerMock()
		);
		$parts = $renderer->renderNoJS( $storyData );

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

	private function createAnalyzerMock() {
		$analyzerMock = $this->createMock( StoryContentAnalyzer::class );
		$analyzerMock->method( 'hasOutdatedText' )->willReturn( false );
		return $analyzerMock;
	}
}
