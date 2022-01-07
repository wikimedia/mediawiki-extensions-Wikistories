<?php

namespace MediaWiki\Extension\Wikistories;

use Article;
use Html;
use MediaWiki\MediaWikiServices;
use MWException;
use Parser;
use ParserOutput;

class Hooks {

	/**
	 * Register the #story parser function
	 *
	 * @param Parser $parser
	 * @throws MWException
	 */
	public static function onParserFirstCallInit( Parser $parser ) {
		$parser->setFunctionHook( 'story', [ StoryParserFunction::class, 'renderStory' ] );
	}

	/**
	 * Show related story on article pages
	 *
	 * @param Article &$article
	 * @param bool|ParserOutput &$outputDone
	 * @param bool &$pcache
	 */
	public static function onArticleViewHeader( &$article, &$outputDone, &$pcache ) {
		if ( $article->getTitle()->getNamespace() === NS_MAIN && $article->getPage()->exists() ) {
			$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );
			$rows = $dbr->newSelectQueryBuilder()
				->table( 'pagelinks' )
				->fields( [ 'pl_from' ] )
				->conds( [
					'pl_from_namespace' => NS_STORY,
					'pl_namespace' => NS_MAIN,
					'pl_title' => $article->getTitle()->getDBkey(),
				] )
				->limit( 6 )
				->caller( __METHOD__ )
				->fetchResultSet();
			$nb = $rows->numRows();
			if ( $nb > 0 ) {
				$storyIds = [];
				foreach ( $rows as $row ) {
					$storyIds[] = $row->pl_from;
				}
				$article->getContext()->getOutput()->addHTML(
					Html::element( 'h2', [], "Related stories: $nb (" . implode( ", ", $storyIds ) . ")" )
				);
			}

		}
	}

}
