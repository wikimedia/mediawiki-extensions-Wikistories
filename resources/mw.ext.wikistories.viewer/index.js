const Vue = require( 'vue' );
const StoryViewer = require( './StoryViewer.vue' );
const store = require( './store/index.js' );

const initStoryViewer = function ( stories, storyId ) {
	const storyViewerContainerClassName = 'ext-wikistories-viewer';
	const $storyViewerContainer = $( '<div>' ).addClass( storyViewerContainerClassName );

	// add Story Viewer container, remove if it existed
	// @todo refactor Story Viewer component to be reusable
	if ( $( '.' + storyViewerContainerClassName ).length ) {
		$( '.' + storyViewerContainerClassName ).remove();
	}

	$( 'body' ).append( $storyViewerContainer );

	// Setup Story View App
	Vue.createMwApp( StoryViewer, { stories: stories, storyId: storyId } )
		.use( store )
		.mount( '.' + storyViewerContainerClassName );
};

module.exports = initStoryViewer;
