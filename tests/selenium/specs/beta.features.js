'use strict';

const Api = require( 'wdio-mediawiki/Api' );
const ArticlePage = require( '../pageobjects/article.page' );
const assert = require( 'assert' );
const BetaFeaturesPage = require( '../pageobjects/beta.features.page' );
const LoginPage = require( 'wdio-mediawiki/LoginPage' );
const Util = require( 'wdio-mediawiki/Util' );

describe( 'Wikistories', function () {
	context( 'Beta feature', function () {

		it( 'is present', async function () {
			await LoginPage.loginAdmin();
			await BetaFeaturesPage.open();

			await BetaFeaturesPage.wikistories.scrollIntoView();
			assert( await BetaFeaturesPage.wikistories.isEnabled(), 'Wikistories is not displayed.' );
		} );

		it( 'can be enabled and the create story CTA can be seen', async function () {

			await browser.deleteAllCookies();
			const bot = await Api.bot();

			const password = Util.getTestString();
			const username = Util.getTestString( 'User-' );
			await Api.createAccount( bot, username, password );

			const content = Util.getTestString();
			const name = Util.getTestString( 'Page' );
			await bot.edit( name, content );

			await LoginPage.login( username, password );
			await BetaFeaturesPage.open();
			await BetaFeaturesPage.wikistories.scrollIntoView();
			await BetaFeaturesPage.wikistories.click();
			await BetaFeaturesPage.save.click();

			await ArticlePage.open( name );
			assert( await ArticlePage.createStory.isEnabled(), 'Create A Story button is not present.' );

		} );

	} );

} );
