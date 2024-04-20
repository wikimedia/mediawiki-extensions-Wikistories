'use strict';

const Page = require( 'wdio-mediawiki/Page' );

class BetaFeaturesPage extends Page {
	open() {
		super.openTitle( 'Special:Preferences', {}, 'mw-prefsection-betafeatures' );
	}

	get wikistories() {
		return $( '[name=wpwikistories-storiesonarticles]' );
	}

	get save() {
		return $( '#prefcontrol' );
	}
}

module.exports = new BetaFeaturesPage();
