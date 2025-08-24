<?php

namespace MediaWiki\Extension\Wikistories;

use MediaWiki\Page\WikiPageFactory;
use Wikimedia\ObjectCache\WANObjectCache;

class StoriesCache {

	/**
	 * This needs to be incremented every time we change
	 * the structure of the cached stories so they can
	 * be invalidated and re-created with the most recent
	 * structure.
	 */
	private const CACHE_VERSION = 14;

	/**
	 * This defines how long stories will stay in the cache if they not edited.
	 * For testing use WANObjectCache::TTL_UNCACHEABLE
	 */
	private const CACHE_TTL = WANObjectCache::TTL_WEEK;

	public function __construct(
		private readonly WANObjectCache $wanObjectCache,
		private readonly PageLinksSearch $pageLinksSearch,
		private readonly WikiPageFactory $wikiPageFactory,
		private readonly StoryRenderer $storyRenderer,
	) {
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
	private function getStories( array $ids ): array {
		$keys = $this->wanObjectCache->makeMultiKeys(
			$ids,
			function ( $id ) {
				return $this->makeStoryKey( $id );
			}
		);
		$stories = $this->wanObjectCache->getMultiWithSetCallback(
			$keys,
			self::CACHE_TTL,
			function ( $id ) {
				return $this->loadAndRenderStory( $id );
			}
		);
		return array_filter( $stories );
	}

	/**
	 * Clear the cached stories for the given article.
	 */
	public function invalidateForArticle( int $articleId ): void {
		$this->wanObjectCache->delete( $this->makeRelatedStoriesKey( $articleId ) );
	}

	public function invalidateStory( int $storyId ): void {
		$this->wanObjectCache->delete( $this->makeStoryKey( $storyId ) );
	}

	private function loadAndRenderStory( int $storyId ): ?array {
		$page = $this->wikiPageFactory->newFromID( $storyId );
		$storyContent = $page->getContent();
		return $storyContent instanceof StoryContent ?
			$this->storyRenderer->getStoryData( $storyContent, $page->getTitle() ) :
			null;
	}

	/**
	 * @param int $articleId
	 * @return string Cache key for the related stories for an article
	 */
	private function makeRelatedStoriesKey( int $articleId ): string {
		return $this->wanObjectCache->makeKey( 'wikistories', self::CACHE_VERSION, 'related', $articleId );
	}

	private function makeStoryKey( int $storyId ): string {
		return $this->wanObjectCache->makeKey( 'wikistories', self::CACHE_VERSION, 'story', $storyId );
	}
}
