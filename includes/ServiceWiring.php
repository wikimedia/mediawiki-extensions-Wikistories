<?php

namespace MediaWiki\Extension\Wikistories;

use MediaWiki\MediaWikiServices;

return [

	'Wikistories.Cache' => static function ( MediaWikiServices $services ) {
		return new StoriesCache(
			$services->getMainWANObjectCache(),
			$services->getDBLoadBalancer(),
			$services->getWikiPageFactory(),
			$services->getPageStore()
		);
	},

];
