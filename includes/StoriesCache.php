<?php

namespace MediaWiki\Extension\Wikistories;

use MediaWiki\Page\WikiPageFactory;
use WANObjectCache;

class StoriesCache {

	/**
	 * This needs to be incremented every time we change
	 * the structure of the cached stories so they can
	 * be invalidated and re-created with the most recent
	 * structure.
	 */
	private const CACHE_VERSION = 13;

	/**
	 * This defines how long stories will stay in the cache if they not edited.
	 * For testing use WANObjectCache::TTL_UNCACHEABLE
	 */
	private const CACHE_TTL = WANObjectCache::TTL_WEEK;

	/** @var WANObjectCache */
	private $wanObjectCache;

	/** @var PageLinksSearch */
	private $pageLinksSearch;

	/** @var WikiPageFactory */
	private $wikiPageFactory;

	/** @var StoryRenderer */
	private $storyRenderer;

	/**
	 * @param WANObjectCache $wanObjectCache
	 * @param PageLinksSearch $pageLinksSearch
	 * @param WikiPageFactory $wikiPageFactory
	 * @param StoryRenderer $storyRenderer
	 */
	public function __construct(
		WANObjectCache $wanObjectCache,
		PageLinksSearch $pageLinksSearch,
		WikiPageFactory $wikiPageFactory,
		StoryRenderer $storyRenderer
	) {
		$this->wanObjectCache = $wanObjectCache;
		$this->pageLinksSearch = $pageLinksSearch;
		$this->wikiPageFactory = $wikiPageFactory;
		$this->storyRenderer = $storyRenderer;
	}

	/**
	 * Retrieve the stories related to the give title
	 *
	 * @param string $titleDbKey
	 * @param int $pageId
	 * @return array Stories linked to the given title
	 */
	public function getRelatedStories( string $titleDbKey, int $pageId ): array {
		$ids = $this->wanObjectCache->getWithSetCallback(
			$this->makeRelatedStoriesKey( $pageId ),
			self::CACHE_TTL,
			function () use ( $titleDbKey ) {
				return $this->pageLinksSearch->getPageLinks( $titleDbKey, 10 );
			}
		);
		return array_values( $this->getStories( $ids ) );
	}

	/**
	 * @param int $id
	 * @return mixed
	 */
	public function getStory( int $id ) {
		return $this->wanObjectCache->getWithSetCallback(
			$this->makeStoryKey( $id ),
			self::CACHE_TTL,
			function () use ( $id ) {
				return $this->loadAndRenderStory( $id );
			}
		);
	}

	/**
	 * @param array $ids
	 * @return mixed[]
	 */
	private function getStories( array $ids ) {
		$keys = $this->wanObjectCache->makeMultiKeys(
			$ids,
			function ( $id ) {
				return $this->makeStoryKey( $id );
			}
		);
		return $this->wanObjectCache->getMultiWithSetCallback(
			$keys,
			self::CACHE_TTL,
			function ( $id ) {
				return $this->loadAndRenderStory( $id );
			}
		);
	}

	/**
	 * Clear the cached stories for the given article.
	 *
	 * @param int $articleId
	 */
	public function invalidateForArticle( int $articleId ) {
		$this->wanObjectCache->delete( $this->makeRelatedStoriesKey( $articleId ) );
	}

	/**
	 * @param int $storyId
	 */
	public function invalidateStory( int $storyId ) {
		$this->wanObjectCache->delete( $this->makeStoryKey( $storyId ) );
	}

	/**
	 * @param int $storyId
	 * @return array
	 */
	private function loadAndRenderStory( int $storyId ) {
		$page = $this->wikiPageFactory->newFromID( $storyId );
		/** @var StoryContent $storyContent */
		$storyContent = $page->getContent();
		'@phan-var StoryContent $storyContent';
		return $this->storyRenderer->getStoryData( $storyContent, $page->getTitle() );
	}

	/**
	 * @param int $articleId
	 * @return string Cache key for the related stories for an article
	 */
	private function makeRelatedStoriesKey( int $articleId ): string {
		return $this->wanObjectCache->makeKey( 'wikistories', self::CACHE_VERSION, 'related', $articleId );
	}

	/**
	 * @param int $storyId
	 * @return string
	 */
	private function makeStoryKey( int $storyId ): string {
		return $this->wanObjectCache->makeKey( 'wikistories', self::CACHE_VERSION, 'story', $storyId );
	}
}
