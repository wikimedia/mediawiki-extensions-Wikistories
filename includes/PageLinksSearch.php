<?php

namespace MediaWiki\Extension\Wikistories;

use MediaWiki\Deferred\LinksUpdate\PageLinksTable;
use MediaWiki\Linker\LinksMigration;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleValue;
use Wikimedia\Rdbms\IConnectionProvider;
use Wikimedia\Rdbms\SelectQueryBuilder;

class PageLinksSearch {

	public function __construct(
		private readonly IConnectionProvider $connectionProvider,
		private readonly LinksMigration $linksMigration,
	) {
	}

	/**
	 * Get story page ids linked with target article,
	 * including those stories linked with pre-moved
	 * versions of the target article, as instructed.
	 *
	 * Note that the links in the database are recorded as going
	 * from the story to the article.
	 *
	 * @param string $articleTitle
	 * @param int $limit
	 * @param bool $followRedirects
	 * @return array Page ids of the related stories
	 */
	public function getPageLinks( string $articleTitle, int $limit, bool $followRedirects = true ): array {
		$tv = new TitleValue( NS_MAIN, $articleTitle );
		$ids = $this->getStoriesLinkingToArticle( $tv, $limit );

		// It is possible that $articleTitle is a redirect target and stories may
		// have been created and linked with the previous article name
		// before the article was moved.
		if ( $followRedirects ) {
			$title = Title::newFromText( $articleTitle );
			$redirectSources = $title->getRedirectsHere( NS_MAIN );
			foreach ( $redirectSources as $redirectSource ) {
				$storyIds = $this->getStoriesLinkingToArticle( $redirectSource, $limit );
				$ids = array_merge( $ids, $storyIds );
			}
		}

		return $ids;
	}

	/**
	 * @param LinkTarget $articleTitle
	 * @param int $limit
	 * @return array Story page IDs
	 */
	private function getStoriesLinkingToArticle( LinkTarget $articleTitle, int $limit ): array {
		$conds = $this->linksMigration->getLinksConditions( 'pagelinks', $articleTitle );
		$conds[ 'pl_from_namespace' ] = NS_STORY;

		$query = $this->getPagelinksPageQuery()
			->select( 'pl_from' )
			->where( $conds )
			->orderBy( 'page_touched', 'DESC' )
			->limit( $limit )
			->caller( __METHOD__ );

		$ids = [];
		$rows = $query->fetchResultSet();
		foreach ( $rows as $row ) {
			$ids[] = $row->pl_from;
		}
		return $ids;
	}

	/**
	 * Build a query for "pagelinks JOIN page ON pl_from=page_id"
	 *
	 * @return SelectQueryBuilder
	 */
	private function getPagelinksPageQuery(): SelectQueryBuilder {
		return $this->connectionProvider
			->getReplicaDatabase( PageLinksTable::VIRTUAL_DOMAIN )
			->newSelectQueryBuilder()
			->queryInfo( $this->linksMigration->getQueryInfo( 'pagelinks' ) )
			->join( 'page', null, 'pl_from=page_id' );
	}
}
