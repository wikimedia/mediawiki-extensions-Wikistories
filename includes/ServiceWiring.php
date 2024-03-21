<?php

namespace MediaWiki\Extension\Wikistories;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use Psr\Log\LoggerInterface;

return [

	'Wikistories.Cache' => static function ( MediaWikiServices $services ): StoriesCache {
		return new StoriesCache(
			$services->getMainWANObjectCache(),
			$services->get( 'Wikistories.PageLinksSearch' ),
			$services->getWikiPageFactory(),
			$services->get( 'Wikistories.StoryRenderer' )
		);
	},

	'Wikistories.StoryConverter' => static function (): StoryConverter {
		return new StoryConverter();
	},

	'Wikistories.StoryValidator' => static function ( MediaWikiServices $services ): StoryValidator {
		return new StoryValidator(
			new ServiceOptions(
				StoryValidator::CONSTRUCTOR_OPTIONS,
				$services->getMainConfig()
			),
			$services->getRepoGroup(),
			$services->getPageStore()
		);
	},

	'Wikistories.StoryRenderer' => static function ( MediaWikiServices $services ): StoryRenderer {
		return new StoryRenderer(
			$services->getRepoGroup(),
			$services->getRedirectLookup(),
			$services->getPageStore(),
			$services->get( 'Wikistories.Analyzer' ),
			$services->get( 'Wikistories.TrackingCategories' )
		);
	},

	'Wikistories.Logger' => static function (): LoggerInterface {
		return LoggerFactory::getInstance( 'Wikistories' );
	},

	'Wikistories.PageLinksSearch' => static function ( MediaWikiServices $services ): PageLinksSearch {
		return new PageLinksSearch(
			$services->getDBLoadBalancer(),
			$services->getLinksMigration()
		);
	},

	'Wikistories.Analyzer' => static function ( MediaWikiServices $services ): StoryContentAnalyzer {
		return new StoryContentAnalyzer(
			$services->getWikiPageFactory()
		);
	},

	'Wikistories.TrackingCategories' => static function (): StoryTrackingCategories {
		return new StoryTrackingCategories();
	}

];
