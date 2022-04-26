<?php

namespace MediaWiki\Extension\Wikistories;

use MediaWiki\Content\Transform\ContentTransformer;
use MediaWiki\Page\PageLookup;
use MediaWiki\Page\WikiPageFactory;
use ParserOptions;
use WANObjectCache;
use Wikimedia\Rdbms\ILoadBalancer;

class StoriesCache {

	private const CACHE_VERSION = 4;

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
			WANObjectCache::TTL_WEEK,
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
				$page->getTitle()->getText()
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
