const $cta = $( '<div>' ).addClass( 'ext-wikistories-discover-container-cta' )
	.append( $( '<div>' ).addClass( 'ext-wikistories-discover-container-cta-btn' ).text( '+' ) )
	.append( $( '<span>' ).text( mw.message( 'wikistories-discover-cta-text' ).text() ) );

const $container = $( '<div>' )
	.addClass( 'ext-wikistories-discover-container' )
	.append( $cta );

const $discover = $( '<div>' )
	.addClass( 'ext-wikistories-discover' )
	.append( $container );

module.exports = $discover;
