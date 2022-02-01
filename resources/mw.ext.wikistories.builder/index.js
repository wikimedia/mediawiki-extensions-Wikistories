const Vue = require( 'vue' );
const App = require( './App.vue' );
const router = require( './router.js' );
const store = require( './store/index.js' );

router.replace( '/search' );

$( 'body' ).append( $( '<div>' ).addClass( 'wikistories-container' ) );

$( '.wikistories-create' ).on( 'click', function ( e ) {
	e.preventDefault();
	Vue.createMwApp( $.extend( { router: router }, App ) )
		.use( store )
		.mount( '.wikistories-container' );
} );
