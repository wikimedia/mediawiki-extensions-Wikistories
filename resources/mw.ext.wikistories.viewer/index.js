const Vue = require( 'vue' );
const StoryViewer = require( './StoryViewer.vue' );
const store = require( './store/index.js' );

const initStoryViewer = function ( stories, storyId ) {
	// add Story Viewer container
	const storyViewerContainerClassName = 'ext-wikistories-viewer';
	const $storyViewerContainer = $( '<div>' ).addClass( storyViewerContainerClassName );
	$( 'body' ).append( $storyViewerContainer );

	// Setup Story View App
	Vue.createMwApp( StoryViewer, { stories: stories, storyId: storyId } )
		.use( store )
		.mount( '.' + storyViewerContainerClassName );
};

module.exports = initStoryViewer;
