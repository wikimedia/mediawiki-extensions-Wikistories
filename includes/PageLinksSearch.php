<?php

namespace MediaWiki\Extension\Wikistories;

use Wikimedia\Rdbms\ILoadBalancer;

class PageLinksSearch {

	/** @var ILoadBalancer */
	private $loadBalancer;

	/**
	 * @param ILoadBalancer $loadBalancer
	 */
	public function __construct( ILoadBalancer $loadBalancer ) {
		$this->loadBalancer = $loadBalancer;
	}

	/**
	 * Get page links associated with target title
	 *
	 * @param string $target Title string
	 * @param int $limit
	 * @param bool $followRedirects
	 * @return array Page ids of the related stories
	 */
	public function getPageLinks( string $target, int $limit, bool $followRedirects = true ): array {
		$ids = $this->loadBalancer->getConnection( DB_REPLICA )->newSelectQueryBuilder()
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
			->fetchFieldValues();

		if ( $followRedirects ) {
			$redirect = $this->getPageRedirectLinks( $target, $limit );
			$ids = array_merge( $ids, $redirect );
		}

		return $ids;
	}

	/**
	 * Get page links associated with redirect target
	 *
	 * @param string $target Title string
	 * @param int $limit
	 * @return array Page ids of the related stories
	 */
	private function getPageRedirectLinks( string $target, int $limit ): array {
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
			return $this->loadBalancer->getConnection( DB_REPLICA )->newSelectQueryBuilder()
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
				->fetchFieldValues();
		}

		return [];
	}
}
