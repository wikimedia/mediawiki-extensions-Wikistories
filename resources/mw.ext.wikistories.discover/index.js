const $discover = require( './Discover.js' );
const getStories = require( './api/getStories.js' );
const loadingViewer = mw.loader.using( 'mw.ext.story.viewer' );

// add Discover UI below the article title
$discover.insertAfter( '.page-heading' );

// add Story discover thumbnail and Story Viewer
getStories( mw.config.get( 'wgTitle' ) ).done( function ( stories ) {

	const urlHashMatch = location.hash.match( /#\/story\/(\d+)/ );
	const storyId = urlHashMatch && urlHashMatch[ 1 ];

	//  Initialize Story Viewer only when stories found on the article
	if ( !stories ) {
		return;
	}

	// @todo show stories thumbnail in discover section

	// Load Story Viewer App
	if ( storyId && stories.find( story => story.pageId.toString() === storyId ) ) {
		loadingViewer.then( function () {
			const initStoryViewer = require( 'mw.ext.story.viewer' );
			initStoryViewer( stories, storyId );
		} );
	}
} );
