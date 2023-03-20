<?php

namespace MediaWiki\Extension\Wikistories;

use MediaWiki\Extension\Wikistories\Tests\StoryFactory;
use MediaWikiUnitTestCase;

class StoryContentTest extends MediaWikiUnitTestCase {

	/**
	 * @covers MediaWiki\Extension\Wikistories\StoryContent::isValid
	 */
	public function testIsValid() {
		$story = new StoryContent( '{}' );
		$this->assertTrue( $story->isValid() );
	}

	/**
	 * @covers MediaWiki\Extension\Wikistories\StoryContent::isLatestVersion
	 */
	public function testIsLatestVersion_empty() {
		$story = new StoryContent( '{}' );
		$this->assertFalse( $story->isLatestVersion() );
	}

	/**
	 * @covers MediaWiki\Extension\Wikistories\StoryContent::isLatestVersion
	 */
	public function testIsLatestVersion_latest() {
		$story = StoryFactory::makeValidStory();
		$this->assertTrue( $story->isLatestVersion() );
	}

	/**
	 * @covers MediaWiki\Extension\Wikistories\StoryContent::isLatestVersion
	 */
	public function testIsLatestVersion_v0() {
		$story = StoryFactory::makeV0Story();
		$this->assertFalse( $story->isLatestVersion() );
	}

	/**
	 * @covers MediaWiki\Extension\Wikistories\StoryContent::getTextForDiff
	 */
	public function testGetTextForDiff() {
		$story = StoryFactory::makeValidStory();
		$this->assertEquals(
			"Cat_poster_1.jpg\n" .
			"This is a cat\n\n" .
			"Cat_napping.jpg\n" .
			"Sleeping now...\n\n" .
			"Domesticated animals\n" .
			"Cats",
			$story->getTextForDiff()
		);
	}

	/**
	 * @covers MediaWiki\Extension\Wikistories\StoryContent::getTextForSummary
	 */
	public function testGetTextForSummary() {
		$story = StoryFactory::makeValidStory();
		$this->assertEquals(
			"This is a cat\n" .
			"Sleeping now...",
			$story->getTextForSummary()
		);
	}

}
