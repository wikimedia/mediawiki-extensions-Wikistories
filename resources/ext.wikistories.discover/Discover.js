const convertUrlToThumbnail = require( './util/convertUrlToThumbnail.js' );

const generateItem = function ( link, thumbnail, thumbnailText, title, cta = false ) {
	const className = cta ? 'ext-wikistories-discover-item-cta' : 'ext-wikistories-discover-item';
	const $thumbnail = $( '<span>' )
		// The following classes are used here:
		// * ext-wikistories-discover-item-cta-btn
		// * ext-wikistories-discover-item-btn
		.addClass( `${className}-btn` )
		.text( thumbnailText );

	const $title = $( '<p>' )
		// The following classes are used here:
		// * ext-wikistories-discover-item-cta-text-title
		// * ext-wikistories-discover-item-text-title
		.addClass( `${className}-text-title` )
		.text( title );

	const $text = $( '<div>' )
		// The following classes are used here:
		// * ext-wikistories-discover-item-cta-text
		// * ext-wikistories-discover-item-text
		.addClass( `${className}-text` );

	if ( !cta ) {
		$text.append( $title );
	}

	if ( thumbnail ) {
		const overlay = thumbnailText === '+' ?
			'linear-gradient(rgba(0, 0, 0, 0.45), rgba(0, 0, 0, 0.45)), ' :
			'';
		$thumbnail.css( 'background-image', overlay + 'url(' + thumbnail + ')' );
	}

	return $( '<a>' ).addClass( className )
		.attr( 'href', link )
		.append( $thumbnail )
		.append( $text );
};

const generateCTA = function ( thumbnail ) {
	const storyBuilder = require( './data.json' ).storyBuilder;
	const pageName = mw.config.get( 'wgPageName' );
	const url = mw.Title.newFromText( 'Special:' + storyBuilder + '/' + pageName ).getUrl();
	return generateItem( url, thumbnail, '+', '', true );
};

const addCtaText = function ( $discover ) {
	const $cta = $discover.find( '.ext-wikistories-discover-item-cta' );
	const $text = $discover.find( '.ext-wikistories-discover-item-cta-text' );
	const $title = $( '<p>' ).addClass( 'ext-wikistories-discover-item-cta-text-title' );
	const $subtitle = $( '<p>' )
		.addClass( 'ext-wikistories-discover-item-cta-text-subtitle' )
		.text( mw.msg( 'wikistories-discover-cta-text-subheader' ) );

	$title.text( mw.msg( 'wikistories-discover-cta-text' ) );
	$text.append( $title );
	$text.append( $subtitle );
	$cta.addClass( 'ext-wikistories-discover-item-no-border' );

	$cta.append( $text );
};

const getArticleThumbnail = function () {
	// false positive eslint warning because of the ':' character
	// eslint-disable-next-line no-jquery/no-sizzle
	const ogImageMetaTag = $( document.head ).find( 'meta[property="og:image"]' )[ 0 ];
	if ( ogImageMetaTag ) {
		const content = ogImageMetaTag.content;
		const imageUrl = mw.util.parseImageUrl( content );

		if ( imageUrl.width ) {
			return imageUrl.resizeUrl( 52 );
		} else {
			return convertUrlToThumbnail( content );
		}
	}

	return '';
};

const getDiscoverSection = function () {
	return $( '<div>' )
		.addClass( 'ext-wikistories-discover' )
		.append( generateCTA( getArticleThumbnail() ) );
};

const addStoriesToDiscoverSection = function ( $discover, stories ) {
	const validStories = stories.filter( s => s.frames.some( f => f.fileNotFound === false ) );
	if ( validStories.length > 0 ) {
		const $stories = $( '<div>' ).addClass( 'ext-wikistories-discover-stories' );
		$discover.append( $stories );
		validStories.forEach( story => {
			const link = '#/story/' + story.storyId;
			$stories.append( generateItem( link, story.thumbnail, '', story.storyTitle ) );
		} );
	} else {
		addCtaText( $discover );
	}
};

module.exports = {
	getDiscoverSection: getDiscoverSection,
	addStoriesToDiscoverSection: addStoriesToDiscoverSection
};
