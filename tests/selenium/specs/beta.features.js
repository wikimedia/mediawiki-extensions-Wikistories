'use strict';

const assert = require( 'assert' ),
	BetaFeaturesPage = require( '../pageobjects/beta.features.page' ),
	LoginPage = require( 'wdio-mediawiki/LoginPage' );

describe( 'Wikistories', function () {

	it( 'is present in Beta Features', async function () {
		await LoginPage.loginAdmin();
		await BetaFeaturesPage.open();

		await BetaFeaturesPage.wikistories.scrollIntoView();
		assert( await BetaFeaturesPage.wikistories.isEnabled(), 'Wikistories is not displayed.' );
	} );

} );
