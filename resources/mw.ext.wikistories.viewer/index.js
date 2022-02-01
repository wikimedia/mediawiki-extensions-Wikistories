const Vue = require( 'vue' );
const StoryViewer = require( './StoryViewer.vue' );

const story = mw.config.get( 'story' ).frames.map( function ( s, i ) {
	s.id = i + 1;
	return s;
} );

Vue.createMwApp( StoryViewer, { story: story } ).mount( '.story-viewer-js-root' );
