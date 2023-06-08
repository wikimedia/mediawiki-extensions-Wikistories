<?php

namespace MediaWiki\Extension\Wikistories;

use MediaWiki\Page\PageStore;
use MediaWiki\Page\ParserOutputAccess;
use MediaWiki\Page\RedirectLookup;
use MediaWiki\Page\WikiPageFactory;
use MediaWiki\Title\Title;

class StoryContentAnalyzer {

	/**
	 * This sentence separator works for many but not all languages.
	 * todo: re-visit when deploying to a new wiki
	 */
	private const SENTENCE_SEPARATOR = '.';

	/** @var WikiPageFactory */
	private $wikiPageFactory;

	/**
	 * @var array Instance cache of article text indexed by title
	 */
	private $cache = [];

	/** @var ParserOutputAccess */
	private $parserOutputAccess;

	/** @var PageStore */
	private $pageStore;

	/** @var RedirectLookup */
	private $redirectLookup;

	/**
	 * @param WikiPageFactory $wikiPageFactory
	 * @param ParserOutputAccess $parserOutputAccess
	 * @param PageStore $pageStore
	 * @param RedirectLookup $redirectLookup
	 */
	public function __construct(
		WikiPageFactory $wikiPageFactory,
		ParserOutputAccess $parserOutputAccess,
		PageStore $pageStore,
		RedirectLookup $redirectLookup
	) {
		$this->wikiPageFactory = $wikiPageFactory;
		$this->parserOutputAccess = $parserOutputAccess;
		$this->pageStore = $pageStore;
		$this->redirectLookup = $redirectLookup;
	}

	/**
	 * @param StoryContent $story
	 * @return bool
	 */
	public function hasOutdatedText( StoryContent $story ): bool {
		$articleTitle = $story->getArticleTitle( $this->pageStore, $this->redirectLookup );
		if ( $articleTitle === null ) {
			return false;
		}

		foreach ( $story->getFrames() as $frame ) {
			if ( $this->isOutdatedText(
				$articleTitle,
				$frame->text->value,
				$frame->text->fromArticle->originalText
			) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param Title $articleTitle
	 * @param string $currentText
	 * @param string $originalText
	 * @return bool
	 */
	public function isOutdatedText( Title $articleTitle, string $currentText, string $originalText ): bool {
		$articleText = $this->getArticleText( $articleTitle );
		if ( $articleText === false ) {
			// cannot do the analysis, err on the side of not spamming
			return false;
		}
		return !$this->inText( $currentText, $articleText )
			&& !$this->inText( $originalText, $articleText );
	}

	/**
	 * @param string $part Block of text containing one or more sentences
	 * originally selected from the article text. May have been manually edited
	 * by story editor.
	 * @param string $text Article text
	 * @return bool True if all the sentences in $part are present in the article text
	 */
	private function inText( string $part, string $text ): bool {
		$sentences = explode( self::SENTENCE_SEPARATOR, $part );
		foreach ( $sentences as $sentence ) {
			if ( !str_contains( $text, trim( $sentence ) ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * @param string $html
	 * @return string
	 */
	private function toText( string $html ): string {
		// Remove HTML tags and convert entities
		$text = html_entity_decode( strip_tags( $html ) );

		// Convert multiple spaces to single space
		$text = preg_replace( '/\s+/', ' ', $text );

		// Remove references ([1])
		$text = preg_replace( '/\[\d+\]/', '', $text );
		return $text;
	}

	/**
	 * @param Title $articleTitle
	 * @return string|false
	 */
	private function getArticleText( Title $articleTitle ) {
		$dbKey = $articleTitle->getDBkey();
		if ( isset( $this->cache[ $dbKey ] ) ) {
			return $this->cache[ $dbKey ];
		}

		$page = $this->wikiPageFactory->newFromTitle( $articleTitle );
		$parserOptions = $page->makeParserOptions( 'canonical' );
		$status = $this->parserOutputAccess->getParserOutput( $page, $parserOptions );

		if ( !$status->isOK() ) {
			return false;
		}
		$parserOutput = $status->getValue();
		if ( $parserOutput === null ) {
			return false;
		}

		$html = $parserOutput->getText();
		$text = $this->toText( $html );
		$this->cache[ $dbKey ] = $text;

		return $text;
	}

}
