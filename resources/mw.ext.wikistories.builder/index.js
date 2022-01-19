var Vue = require('vue');
var App = require('./App.vue');
var router = require( './router.js' );
var store = require( './store/index.js' );

router.replace('/story');

$( 'body' ).append( $( '<div>' ).addClass( 'wikistories-container' ) );

$( '.wikistories-create' ).on( 'click', function ( e ) {
  e.preventDefault();
  Vue.createMwApp( $.extend( { router: router }, App ) )
    .use( store )
    .mount( '.wikistories-container' );
} );

