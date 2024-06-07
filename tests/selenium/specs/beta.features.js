'use strict';

const Api = require( 'wdio-mediawiki/Api' );
const ArticlePage = require( '../pageobjects/article.page' );
const assert = require( 'assert' );
const BetaFeaturesPage = require( '../pageobjects/beta.features.page' );
const LoginPage = require( 'wdio-mediawiki/LoginPage' );
const Util = require( 'wdio-mediawiki/Util' );
const WikistoriesPage = require( '../pageobjects/wikistories.page' );

describe( 'Wikistories', () => {
	context( 'Beta feature', () => {
		let content, name, bot, username, password;

		before( async () => {
			bot = await Api.bot();

			password = Util.getTestString();
			username = Util.getTestString( 'User-' );
			await Api.createAccount( bot, username, password );

			content = Util.getTestString();
			name = Util.getTestString( 'Page' );
			await bot.edit( name, content );

			await LoginPage.login( username, password );
			await BetaFeaturesPage.open();
			await BetaFeaturesPage.wikistories.scrollIntoView();
		} );

		it( 'is present', async () => {
			assert( await BetaFeaturesPage.wikistories.isEnabled(), 'Wikistories is not displayed.' );
		} );

		it( 'can be enabled and the create story CTA can be seen', async () => {
			await BetaFeaturesPage.wikistories.click();
			await BetaFeaturesPage.save.click();

			await ArticlePage.open( name );
			assert( await ArticlePage.createStory.isEnabled(), 'Create A Story button is not present.' );
		} );

		it( 'can be created', async () => {
			name = Util.getTestString( 'Story' );
			await WikistoriesPage.createStory( name );
			assert.match( await browser.getUrl(), /story/ );
		} );

	} );

} );
