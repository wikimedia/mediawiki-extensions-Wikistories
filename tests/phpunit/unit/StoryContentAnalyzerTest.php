<?php

namespace MediaWiki\Extension\Wikistories;

use MediaWiki\Page\WikiPage;
use MediaWiki\Page\WikiPageFactory;
use MediaWikiUnitTestCase;
use MockTitleTrait;

class StoryContentAnalyzerTest extends MediaWikiUnitTestCase {

	use MockTitleTrait;

	private function createPageFactoryMock() {
		$wikiPageMock = $this->createMock( WikiPage::class );
		$wikiPageMock->method( 'makeParserOptions' )->willReturn( 'wikitext' );

		$wikiPageFactoryMock = $this->createMock( WikiPageFactory::class );
		$wikiPageFactoryMock->method( 'newFromTitle' )
			->willReturn( $wikiPageMock );
		return $wikiPageFactoryMock;
	}

	public static function provideIsOutdatedText() {
		return [
			[ 'This is Story <b>A</b>.', 'This is Story A.', 'This is Story B.' ],
			[ 'This is        Story A.', 'This is Story A', 'This is Story A' ],
			// phpcs:ignore Generic.Files.LineLength.TooLong
			[ 'Another Story<sup id="cite_ref-1" class="reference"><a href="#cite_note-1"><span>[</span>1<span>]</span></a></sup>.', 'Another Story.', 'Another Story.' ],
		];
	}

	/**
	 * @covers MediaWiki\Extension\Wikistories\StoryContentAnalyzer::isOutdatedText
	 * @dataProvider provideIsOutdatedText
	 * @param string $htmlText
	 * @param string $currentText
	 * @param string $originalText
	 */
	public function testIsOutdatedText( $htmlText, $currentText, $originalText ) {
		$analyzer = new StoryContentAnalyzer(
			$this->createPageFactoryMock()
		);

		$this->assertFalse(
			$analyzer->isOutdatedText(
				$analyzer->transformText( $htmlText ),
				$currentText,
				$originalText
			)
		);
	}
}
