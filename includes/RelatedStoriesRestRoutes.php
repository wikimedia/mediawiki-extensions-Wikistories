<?php

namespace MediaWiki\Extension\Wikistories;

use MediaWiki\Page\PageLookup;
use MediaWiki\Rest\LocalizedHttpException;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Title\MalformedTitleException;
use MediaWiki\Title\TitleFormatter;
use MediaWiki\Title\TitleParser;
use Wikimedia\Message\MessageValue;
use Wikimedia\Message\ParamType;
use Wikimedia\Message\ScalarParam;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * Class RelatedStoriesRestRoutes
 *
 * Handles the route to get the stories related to an article.
 *
 * const rest = new mw.Rest();
 * rest.get( '/wikistories/v0/page/<article title>/stories' ).done( function ( stories ) {
 *     // ...
 * } );
 *
 * @package MediaWiki\Extension\Wikistories
 */
class RelatedStoriesRestRoutes extends SimpleHandler {

	public function __construct(
		private readonly TitleFormatter $titleFormatter,
		private readonly TitleParser $titleParser,
		private readonly PageLookup $pageLookup,
		private readonly StoriesCache $storiesCache,
	) {
	}

	/**
	 * @param string $title
	 * @return Response
	 * @throws LocalizedHttpException
	 * @throws MalformedTitleException
	 */
	public function run( $title ) {
		$titleValue = $this->titleParser->parseTitle( $title );

		if ( $titleValue->getNamespace() !== NS_MAIN ) {
			$ns = $this->titleFormatter->getNamespaceName( $titleValue->getNamespace(), $titleValue->getText() );
			throw new LocalizedHttpException(
				new MessageValue( 'wikistories-rest-unsupported-namespace',
					[ new ScalarParam( ParamType::PLAINTEXT, $ns ) ]
				),
				422
			);
		}

		$page = $this->pageLookup->getExistingPageByText( $titleValue->getText() );
		if ( !$page ) {
			throw new LocalizedHttpException(
				new MessageValue( 'rest-nonexistent-title',
					[ new ScalarParam( ParamType::PLAINTEXT, $title ) ]
				),
				404
			);
		}

		$stories = $this->storiesCache->getRelatedStories( $page->getDBkey(), $page->getId() );
		return $this->getResponseFactory()->createJson( $stories );
	}

	/**
	 * @inheritDoc
	 */
	public function needsWriteAccess() {
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function getParamSettings() {
		return [
			'title' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
		];
	}

}
