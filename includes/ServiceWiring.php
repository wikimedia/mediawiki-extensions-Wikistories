<?php

namespace MediaWiki\Extension\Wikistories;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;

return [

	'Wikistories.Cache' => static function ( MediaWikiServices $services ) {
		return new StoriesCache(
			$services->getMainWANObjectCache(),
			$services->getDBLoadBalancer(),
			$services->getWikiPageFactory(),
			$services->getPageStore(),
			$services->get( 'Wikistories.StoryRenderer' ),
			$services->getContentTransformer()
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
		return new StoryRenderer( $services->getRepoGroup(), $services->getTitleFormatter() );
	},

	'Wikistories.Logger' => static function () {
		return LoggerFactory::getInstance( 'Wikistories' );
	},

];
