const Vue = require( 'vue' );
const StoryViewer = require( './StoryViewer.vue' );
const store = require( './store/index.js' );

const initStoryViewer = function ( stories, storyId ) {
	const storyViewerContainerClassName = 'ext-wikistories-viewer';
	const $storyViewerContainer = $( '<div>' ).addClass( storyViewerContainerClassName );

	// update Story Viewer state when it existed
	if ( $( '.' + storyViewerContainerClassName ).length ) {
		store.dispatch( 'setStoryId', storyId );
		return;
	}

	// Add Story Viewer to the body
	$( 'body' ).append( $storyViewerContainer );

	// Setup Story View App
	Vue.createMwApp( StoryViewer, { stories: stories, storyId: storyId } )
		.use( store )
		.mount( '.' + storyViewerContainerClassName );
};

module.exports = initStoryViewer;
