'use strict';

const Page = require( 'wdio-mediawiki/Page' );

class WikistoriesPage extends Page {
	open( name ) {
		super.openTitle( name, { mobileaction: 'toggle_view_mobile' } );
	}

	get addCTA() {
		return $( '.ext-wikistories-article-view-toolbar-confirm-button' );
	}

	get createStoryButton() {
		return $( '.ext-wikistories-discover-item-cta-btn' );
	}

	get frameTwo() {
		return $( '.ext-wikistories-frames-thumbnails-frame:nth-child(2)' );
	}

	get imageSearch() {
		return $( '.ext-wikistories-storybuilder-search-form-query' );
	}

	get image1() {
		return $( '[data-id="0"]' );
	}

	get image2() {
		return $( '[data-id="1"]' );
	}

	get next() {
		return $( '.ext-wikistories-navigator-button.next' );
	}

	get publishCTA() {
		return $( '.ext-wikistories-navigator-withtext-content' );
	}

	get storyCover() {
		return $( '.ext-wikistories-viewer-container-content-story-cover-wikistory' );
	}

	get storyTitle() {
		return $( '.ext-wikistories-publishform-content-input-title' );
	}

	get textArea() {
		return $( '.ext-wikistories-wikipedia' );
	}

	get text() {
		return $( '.mw-page-title-main' );
	}

	async addTextToFrame() {
		await this.textArea.click();
		await this.text.doubleClick();
		await this.addCTA.click();
	}

	async createStory( name ) {
		this.open( 'Cats' );
		await this.createStoryButton.click();
		await this.image1.click();
		await this.image2.click();
		await this.next.click();

		await this.addTextToFrame();
		await this.frameTwo.click();
		await this.addTextToFrame();
		await this.next.click();

		await this.storyTitle.setValue( name );
		await this.publishCTA.click();
		await this.storyCover.waitForDisplayed( { timeout: 15000 } );
	}
}

module.exports = new WikistoriesPage();
