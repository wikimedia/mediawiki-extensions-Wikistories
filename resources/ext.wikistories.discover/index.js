const getDiscoverSection = require( './Discover.js' ).getDiscoverSection;
const addStoriesToDiscoverSection = require( './Discover.js' ).addStoriesToDiscoverSection;
const getStories = require( './api/getStories.js' );
const events = require( './consumptionEvents.js' );

const loadingViewer = mw.loader.using( 'ext.wikistories.viewer' );
const articleTitle = mw.config.get( 'wgPageName' );

const $discover = getDiscoverSection().insertAfter( '.page-heading' );

// add Story discover thumbnail and Story Viewer
getStories( articleTitle ).then( function ( stories ) {
	const renderStoryViewer = function () {
		const urlHashMatch = location.hash.match( /#\/story\/(\d+)/ );
		const storyId = urlHashMatch && urlHashMatch[ 1 ];

		if ( storyId && stories.find( story => story.pageId.toString() === storyId ) ) {
			loadingViewer.then( function () {
				const initStoryViewer = require( 'ext.wikistories.viewer' );
				initStoryViewer( stories, storyId, events.logStoryView );
			} );
		}
	};

	addStoriesToDiscoverSection( $discover, stories );

	if ( stories.length ) {
		events.logStoriesImpression( stories.length );
	}

	// Load Story Viewer App if necessary
	renderStoryViewer();

	// listen to hash change
	window.addEventListener( 'hashchange', renderStoryViewer );
} );
