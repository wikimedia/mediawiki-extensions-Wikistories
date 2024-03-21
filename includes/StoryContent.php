<?php

namespace MediaWiki\Extension\Wikistories;

use IDBAccessObject;
use JsonContent;
use MediaWiki\Html\Html;
use MediaWiki\MediaWikiServices;
use MediaWiki\Page\PageIdentityValue;
use MediaWiki\Title\Title;

class StoryContent extends JsonContent {

	public const SCHEMA_VERSION = 1;

	/**
	 * @param string $text
	 */
	public function __construct( $text ) {
		parent::__construct( $text, 'story' );
	}

	/**
	 * @return array
	 */
	public function getFrames() {
		$story = $this->getData()->getValue();
		return $story->frames ?? [];
	}

	/**
	 * @return string The title of the article this story was created from
	 */
	public function getFromArticle(): string {
		$story = $this->getData()->getValue();

		if ( isset( $story->articleId ) ) {
			$title = Title::newFromId( $story->articleId );
			if ( $title ) {
				return $title->getDBkey();
			}
		}

		return $story->fromArticle ?? '';
	}

	/**
	 * @return int The page id of the article this story was created from
	 */
	public function getArticleId(): int {
		$story = $this->getData()->getValue();

		if ( !isset( $story->articleId ) && isset( $story->fromArticle ) ) {
			$articleTitle = Title::newFromText( $story->fromArticle );
			return $articleTitle->getArticleID( IDBAccessObject::READ_LATEST );
		}

		return $story->articleId ?? -1;
	}

	/**
	 * @return Title|null
	 */
	public function getArticleTitle(): ?Title {
		$services = MediaWikiServices::getInstance();
		$pageLookup = $services->getPageStore();
		$story = $this->getData()->getValue();

		if ( isset( $story->articleId ) && $story->articleId > 0 ) {
			$pageRecord = $pageLookup->getPageById( $story->articleId );
		} elseif ( isset( $story->fromArticle ) && $story->fromArticle !== '' ) {
			$pageRecord = $pageLookup->getExistingPageByText( $story->fromArticle );
		} else {
			return null;
		}
		if ( $pageRecord === null ) {
			return null;
		}
		if ( $pageRecord->isRedirect() ) {
			$identity = PageIdentityValue::localIdentity(
				$pageRecord->getId(), $pageRecord->getNamespace(), $pageRecord->getDBkey()
			);
			$linkTarget = $services->getRedirectLookup()->getRedirectTarget( $identity );
			if ( $linkTarget !== null ) {
				return Title::newFromLinkTarget( $linkTarget );
			}
		} else {
			return Title::newFromPageIdentity( $pageRecord );
		}
	}

	/**
	 * @return string[]
	 */
	public function getCategories(): array {
		$story = $this->getData()->getValue();
		return $story->categories ?? [];
	}

	/**
	 * Return a single image or a gallery in wikitext
	 *
	 * @return bool|string
	 */
	public function getWikitextForTransclusion() {
		if ( !$this->isValid() ) {
			return false;
		}

		$length = count( $this->getFrames() );
		if ( $length === 1 ) {
			// Single image
			$img = $this->getFrames()[0]->img;
			$path = parse_url( $img, PHP_URL_PATH );
			$pathFragments = explode( '/', $path );
			$file = end( $pathFragments );
			$text = $this->getFrames()[0]->text;
			return "[[Image:$file|$text]]";
		} elseif ( $length > 1 ) {
			// Gallery
			return Html::element(
				'gallery',
				[],
				implode( "\n", array_map( static function ( $frame ) {
					$path = parse_url( $frame->img, PHP_URL_PATH );
					$pathFragments = explode( '/', $path );
					$file = end( $pathFragments );
					return $file . '|' . $frame->text;
				}, $this->getFrames() ) )
			);
		}
	}

	/**
	 * @return string A simple version of the story frames and categories as text for diff
	 */
	public function getTextForDiff() {
		// story frames
		$text = implode( "\n\n", array_map( static function ( $frame ) {
			return $frame->image->filename . "\n" . $frame->text->value;
		}, $this->getFrames() ) );

		// categories
		$categories = $this->getCategories();
		if ( $categories ) {
			$text .= "\n\n" . implode( "\n", $categories );
		}

		return $text;
	}

	/**
	 * @inheritDoc
	 */
	public function getTextForSummary( $maxlength = 250 ) {
		$framesText = implode( "\n", array_map( static function ( $frame ) {
			return $frame->text->value;
		}, $this->getFrames() ) );
		return mb_substr( $framesText, 0, $maxlength );
	}

	/**
	 * @return bool This story is using the latest schema version
	 */
	public function isLatestVersion(): bool {
		return $this->getSchemaVersion() === self::SCHEMA_VERSION;
	}

	/**
	 * @return int Schema version used by this story
	 */
	public function getSchemaVersion(): int {
		$content = $this->getData()->getValue();
		return $content->schemaVersion ?? 0;
	}
}
