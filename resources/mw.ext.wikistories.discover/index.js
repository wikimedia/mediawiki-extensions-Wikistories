const getDiscoverSection = require( './Discover.js' );
const getStories = require( './api/getStories.js' );
const getArticleThumbnail = require( './api/getArticleThumbnail.js' );
const events = require( './consumptionEvents.js' );

const loadingViewer = mw.loader.using( 'mw.ext.story.viewer' );
const articleTitle = mw.config.get( 'wgTitle' );

// add Story discover thumbnail and Story Viewer
$.when(
	getStories( articleTitle ),
	getArticleThumbnail( articleTitle )
).done( function ( stories, thumbnail ) {

	const renderStoryViewer = function () {
		const urlHashMatch = location.hash.match( /#\/story\/(\d+)/ );
		const storyId = urlHashMatch && urlHashMatch[ 1 ];

		if ( storyId && stories.find( story => story.pageId.toString() === storyId ) ) {
			loadingViewer.then( function () {
				const initStoryViewer = require( 'mw.ext.story.viewer' );
				initStoryViewer( stories, storyId, events.logStoryView );
			} );
		}
	};

	// @todo show stories thumbnail in discover section
	// add Discover UI below the article title
	getDiscoverSection( stories, thumbnail ).insertAfter( '.page-heading' );

	if ( stories.length ) {
		events.logStoriesImpression( stories.length );
	}

	// Load Story Viewer App if necessary
	renderStoryViewer();

	// listen to hash change
	window.addEventListener( 'hashchange', renderStoryViewer );
} );
