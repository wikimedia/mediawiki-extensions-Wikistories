<?php

namespace MediaWiki\Extension\Wikistories;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;

return [

	'Wikistories.Cache' => static function ( MediaWikiServices $services ) {
		return new StoriesCache(
			$services->getMainWANObjectCache(),
			$services->get( 'Wikistories.PageLinksSearch' ),
			$services->getWikiPageFactory(),
			$services->get( 'Wikistories.StoryRenderer' )
		);
	},

	'Wikistories.StoryConverter' => static function () {
		return new StoryConverter();
	},

	'Wikistories.StoryValidator' => static function ( MediaWikiServices $services ) {
		return new StoryValidator(
			new ServiceOptions(
				StoryValidator::CONSTRUCTOR_OPTIONS,
				$services->getMainConfig()
			),
			$services->getRepoGroup(),
			$services->getPageStore()
		);
	},

	'Wikistories.StoryRenderer' => static function ( MediaWikiServices $services ) {
		return new StoryRenderer(
			$services->getRepoGroup(),
			$services->getRedirectLookup(),
			$services->getPageStore(),
			$services->get( 'Wikistories.Analyzer' ),
			$services->get( 'Wikistories.TrackingCategories' )
		);
	},

	'Wikistories.Logger' => static function () {
		return LoggerFactory::getInstance( 'Wikistories' );
	},

	'Wikistories.PageLinksSearch' => static function ( MediaWikiServices $services ) {
		return new PageLinksSearch(
			$services->getDBLoadBalancer()
		);
	},

	'Wikistories.Analyzer' => static function ( MediaWikiServices $services ) {
		return new StoryContentAnalyzer(
			$services->getWikiPageFactory(),
			$services->getParserOutputAccess(),
			$services->getPageStore(),
			$services->getRedirectLookup()
		);
	},

	'Wikistories.TrackingCategories' => static function () {
		return new StoryTrackingCategories();
	}

];
