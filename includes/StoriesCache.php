<?php

namespace MediaWiki\Extension\Wikistories;

use MediaWiki\Content\Transform\ContentTransformer;
use MediaWiki\Page\PageLookup;
use MediaWiki\Page\WikiPageFactory;
use ParserOptions;
use WANObjectCache;
use Wikimedia\Rdbms\ILoadBalancer;

class StoriesCache {

	/**
	 * This needs to be incremented every time we change
	 * the structure of the cached stories so they can
	 * be invalidated and re-created with the most recent
	 * structure.
	 */
	private const CACHE_VERSION = 9;

	/**
	 * This defines how long stories will stay in the cache if they not edited.
	 * For testing use WANObjectCache::TTL_UNCACHEABLE
	 */
	private const CACHE_TTL = WANObjectCache::TTL_WEEK;

	/** @var WANObjectCache */
	private $wanObjectCache;

	/** @var ILoadBalancer */
	private $loadBalancer;

	/** @var WikiPageFactory */
	private $wikiPageFactory;

	/** @var PageLookup */
	private $pageLookup;

	/** @var StoryRenderer */
	private $storyRenderer;

	/** @var ContentTransformer */
	private $contentTransformer;

	/**
	 * @param WANObjectCache $wanObjectCache
	 * @param ILoadBalancer $loadBalancer
	 * @param WikiPageFactory $wikiPageFactory
	 * @param PageLookup $pageLookup
	 * @param StoryRenderer $storyRenderer
	 * @param ContentTransformer $contentTransformer
	 */
	public function __construct(
		WANObjectCache $wanObjectCache,
		ILoadBalancer $loadBalancer,
		WikiPageFactory $wikiPageFactory,
		PageLookup $pageLookup,
		StoryRenderer $storyRenderer,
		ContentTransformer $contentTransformer
	) {
		$this->wanObjectCache = $wanObjectCache;
		$this->loadBalancer = $loadBalancer;
		$this->wikiPageFactory = $wikiPageFactory;
		$this->pageLookup = $pageLookup;
		$this->storyRenderer = $storyRenderer;
		$this->contentTransformer = $contentTransformer;
	}

	/**
	 * Retrieve the stories related to the give title
	 *
	 * @param string $titleDbKey
	 * @param int $pageId
	 * @return array Stories linked to the given title
	 */
	public function getRelatedStories( string $titleDbKey, int $pageId ): array {
		return $this->wanObjectCache->getWithSetCallback(
			$this->makeRelatedStoriesKey( $pageId ),
			self::CACHE_TTL,
			function () use ( $titleDbKey ) {
				return $this->fetchStories( $titleDbKey );
			}
		);
	}

	/**
	 * Clear the cached stories for the given article.
	 *
	 * @param string $title
	 */
	public function invalidateForArticle( string $title ) {
		$page = $this->pageLookup->getExistingPageByText( $title );
		if ( $page ) {
			$this->wanObjectCache->delete( $this->makeRelatedStoriesKey( $page->getId() ) );
		}
	}

	/**
	 * Fetch the related stories from the database
	 *
	 * @param string $titleDbKey
	 * @return array Stories linked to the given title
	 */
	private function fetchStories( string $titleDbKey ): array {
		$limit = 10;
		$result = [];
		$rows = $this->loadBalancer->getConnectionRef( DB_REPLICA )->newSelectQueryBuilder()
			->table( 'pagelinks' )
			->join( 'page', null, 'pl_from=page_id' )
			->fields( [ 'pl_from' ] )
			->conds( [
				'pl_from_namespace' => NS_STORY,
				'pl_namespace' => NS_MAIN,
				'pl_title' => $titleDbKey,
			] )
			->orderBy( 'page_touched', 'DESC' )
			->limit( $limit )
			->caller( __METHOD__ )
			->fetchResultSet();
		foreach ( $rows as $row ) {
			$page = $this->wikiPageFactory->newFromID( $row->pl_from );
			/** @var StoryContent $story */
			$story = $this->contentTransformer->preloadTransform(
				$page->getContent(),
				$page,
				ParserOptions::newFromAnon()
			);
			'@phan-var StoryContent $story';
			$result[] = $this->storyRenderer->getStoryForViewer(
				$story,
				$page->getId(),
				$page->getTitle()
			);
		}
		return $result;
	}

	/**
	 * @param int $pageId
	 * @return string Cache key for the related stories for an article
	 */
	private function makeRelatedStoriesKey( int $pageId ): string {
		return $this->wanObjectCache->makeKey( 'wikistories', self::CACHE_VERSION, 'related', $pageId );
	}
}
