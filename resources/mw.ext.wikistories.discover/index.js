const $Discover = require( './Discover.js' );

$Discover.insertAfter( '.page-heading' );

// @todo leave it here as the creation/builder will move to the special page
$( '.ext-wikistories-discover-container-cta' ).on( 'click', () => {
	$( '.wikistories-create' ).click();
} );
