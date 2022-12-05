const Vue = require( 'vue' );
const App = require( './App.vue' );
const store = require( './store/index.js' );
const config = require( './plugins/config.js' );
const storyEditMode = mw.config.get( 'wgWikistoriesMode' );

store.dispatch( 'init' );

if ( storyEditMode === 'edit' ) {
	// Edit an existing story
	const params = new URLSearchParams( window.location.search );
	store.dispatch( 'selectFrame', params.get( 'frameid' ) );
	store.dispatch( 'routePush', 'story' );
} else {
	// Create a new story
	store.dispatch( 'routeReplace', 'searchMany' );
}

Vue.createMwApp( App )
	.use( config )
	.use( store )
	.mount( '.ext-wikistories-container' );
