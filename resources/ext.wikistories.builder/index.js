const Vue = require( 'vue' );
const App = require( './App.vue' );
const router = require( './router.js' );
const store = require( './store/index.js' );
const config = require( './plugins/config.js' );
const storyEditMode = mw.config.get( 'wgWikistoriesMode' );

if ( storyEditMode === 'edit' ) {
	// Edit an existing story
	const params = new URLSearchParams( window.location.search );
	store.dispatch( 'selectFrame', params.get( 'frameid' ) );
	router.push( '/story' );
} else {
	// Create a new story
	router.replace( '/search' );
}

Vue.createMwApp( $.extend( { router: router }, App ) )
	.use( config )
	.use( store )
	.mount( '.ext-wikistories-container' );
