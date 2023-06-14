const Vue = require( 'vue' );
const StoryViewer = require( './StoryViewer.vue' );
const store = require( './store/index.js' );

/**
 * @param {Array} stories All the stories attaches to an article
 * @param {number} storyId ID of the current story to view
 * @param {Function} logStoryViewFn Function that can be used to log 'story_view' events
 * @param {boolean} allowEdit
 * @param {boolean} allowClose
 */
const initStoryViewer = function ( stories, storyId, logStoryViewFn, allowEdit, allowClose ) {
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
	const props = {
		stories: stories,
		storyId: storyId,
		logStoryViewFn: logStoryViewFn,
		allowEdit: allowEdit,
		allowClose: allowClose
	};
	Vue.createMwApp( StoryViewer, props )
		.use( store )
		.mount( '.' + storyViewerContainerClassName );
};

module.exports = initStoryViewer;
