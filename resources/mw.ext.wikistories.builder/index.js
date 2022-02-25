const Vue = require( 'vue' );
const App = require( './App.vue' );
const router = require( './router.js' );
const store = require( './store/index.js' );
const config = require( './plugins/config.js' );

router.replace( '/search' );
Vue.createMwApp( $.extend( { router: router }, App ) )
	.use( config )
	.use( store )
	.mount( '.ext-wikistories-container' );
