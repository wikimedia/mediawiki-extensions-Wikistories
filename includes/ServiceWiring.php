<?php

namespace MediaWiki\Extension\Wikistories;

use MediaWiki\Config\ServiceOptions;
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
			$services->getRepoGroup()
		);
	},

	'Wikistories.StoryRenderer' => static function ( MediaWikiServices $services ) {
		return new StoryRenderer( $services->getRepoGroup() );
	},

];
