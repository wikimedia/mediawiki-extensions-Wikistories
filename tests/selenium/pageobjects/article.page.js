'use strict';

const Page = require( 'wdio-mediawiki/Page' );

class ArticlePage extends Page {
	open( name ) {
		super.openTitle( name, { mobileaction: 'toggle_view_mobile' } );
	}
	get createStory() { return $( '.ext-wikistories-discover-item-cta-text-title' ); }
}

module.exports = new ArticlePage();
