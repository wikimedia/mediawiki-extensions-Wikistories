<?php

namespace MediaWiki\Extension\Wikistories;

use MediaWiki\Page\WikiPageFactory;
use Wikimedia\Rdbms\ILoadBalancer;

class PageLinksSearch {
	/** @var ILoadBalancer */
	private $loadBalancer;

	/** @var WikiPageFactory */
	private $wikiPageFactory;

	/**
	 * @param ILoadBalancer $loadBalancer
	 * @param WikiPageFactory $wikiPageFactory
	 */
	public function __construct(
		ILoadBalancer $loadBalancer,
		WikiPageFactory $wikiPageFactory
	) {
		$this->loadBalancer = $loadBalancer;
		$this->wikiPageFactory = $wikiPageFactory;
	}

	/**
	 * Get page links associated with target title
	 *
	 * @param string $target Title string
	 * @param int $limit
	 * @param bool $followRedirects
	 * @return array Wikipages linked to target
	 */
	public function getPageLinks( string $target, int $limit, bool $followRedirects = true ): array {
		$result = [];
		$rows = $this->loadBalancer->getConnection( DB_REPLICA )->newSelectQueryBuilder()
			->table( 'pagelinks' )
			->join( 'page', null, 'pl_from=page_id' )
			->fields( [ 'pl_from' ] )
			->conds( [
				'pl_from_namespace' => NS_STORY,
				'pl_namespace' => NS_MAIN,
				'pl_title' => $target,
			] )
			->orderBy( 'page_touched', 'DESC' )
			->limit( $limit )
			->caller( __METHOD__ )
			->fetchResultSet();

		foreach ( $rows as $row ) {
			$page = $this->wikiPageFactory->newFromID( $row->pl_from );
			$result[] = $page;
		}

		if ( $followRedirects ) {
			$redirect = $this->getPageRedirectLinks( $target, $limit );
			$result = array_merge( $result, $redirect );
		}

		return $result;
	}

	/**
	 * Get page links associated with redirect target
	 *
	 * @param string $target Title string
	 * @param int $limit
	 * @return array page IDs linked to target
	 */
	private function getPageRedirectLinks( string $target, int $limit ): array {
		$redirectResult = [];
		$redirectTarget = $this->loadBalancer->getConnection( DB_REPLICA )->newSelectQueryBuilder()
			->table( 'pagelinks' )
			->join( 'page', null, 'pl_from=page_id' )
			->fields( [ 'page_title' ] )
			->conds( [
				'pl_title' => $target,
				'page_is_redirect' => 1,
			] )
			->caller( __METHOD__ )
			->fetchFieldValues();

		// TODO: add recursion support for multiple redirects beyond 1 level deep (T336602)
		if ( $redirectTarget ) {
			$redirectRows = $this->loadBalancer->getConnection( DB_REPLICA )->newSelectQueryBuilder()
				->table( 'pagelinks' )
				->join( 'page', null, 'pl_from=page_id' )
				->fields( [ 'pl_from' ] )
				->conds( [
					'pl_from_namespace' => NS_STORY,
					'pl_namespace' => NS_MAIN,
					'pl_title' => $redirectTarget,
				] )
				->orderBy( 'page_touched', 'DESC' )
				->limit( $limit )
				->caller( __METHOD__ )
				->fetchResultSet();

			foreach ( $redirectRows as $row ) {
				$page = $this->wikiPageFactory->newFromID( $row->pl_from );
				$redirectResult[] = $page;
			}
		}

		return $redirectResult;
	}
}
