<?php

namespace MediaWiki\Extension\Wikistories;

use MediaWiki\Page\WikiPageFactory;
use MediaWiki\Title\Title;

class StoryContentAnalyzer {

	/**
	 * This sentence separator works for many but not all languages.
	 * todo: re-visit when deploying to a new wiki
	 */
	private const SENTENCE_SEPARATOR_REGEX = '/[.:;]/';

	/** @var WikiPageFactory */
	private $wikiPageFactory;

	/**
	 * @var array Instance cache of article text indexed by title
	 */
	private $cache = [];

	/**
	 * @param WikiPageFactory $wikiPageFactory
	 */
	public function __construct(
		WikiPageFactory $wikiPageFactory
	) {
		$this->wikiPageFactory = $wikiPageFactory;
	}

	/**
	 * @param StoryContent $story
	 * @return bool
	 */
	public function hasOutdatedText( StoryContent $story ): bool {
		$articleTitle = $story->getArticleTitle();
		if ( $articleTitle === null ) {
			return false;
		}

		$articleText = $this->getArticleText( $articleTitle );
		if ( $articleText === false ) {
			return false;
		}

		foreach ( $story->getFrames() as $frame ) {
			if ( $this->isOutdatedText(
				$articleText,
				$frame->text->value,
				$frame->text->fromArticle->originalText
			) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param string $articleText
	 * @param string $currentText
	 * @param string $originalText
	 * @return bool
	 */
	public function isOutdatedText( string $articleText, string $currentText, string $originalText ): bool {
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
		$sentences = preg_split( self::SENTENCE_SEPARATOR_REGEX, $part );
		foreach ( $sentences as $sentence ) {
			if ( !str_contains( $text, trim( $sentence ) ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Remove unnecessary elements from the html text
	 * @param string $html
	 * @return string
	 */
	public function transformText( string $html ): string {
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
	public function getArticleText( Title $articleTitle ) {
		$dbKey = $articleTitle->getDBkey();
		if ( isset( $this->cache[ $dbKey ] ) ) {
			return $this->cache[ $dbKey ];
		}

		$page = $this->wikiPageFactory->newFromTitle( $articleTitle );
		$parserOptions = $page->makeParserOptions( 'canonical' );
		$parserOutput = $page->getParserOutput( $parserOptions );

		if ( !$parserOutput ) {
			return false;
		}

		$html = $parserOutput->getText();
		$text = $this->transformText( $html );
		$this->cache[ $dbKey ] = $text;

		return $text;
	}

}
