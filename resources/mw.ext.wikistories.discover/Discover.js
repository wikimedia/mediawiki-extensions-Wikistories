const convertUrlToThumbnail = require( './util/convertUrlToThumbnail.js' );

const generateCtaElement = function ( link, thumbnail, thumbnailText, text ) {
	const $thumbnail = $( '<span>' )
		.addClass( 'ext-wikistories-discover-container-cta-btn' )
		.text( thumbnailText );
	const $text = $( '<span>' )
		.addClass( 'ext-wikistories-discover-container-cta-text' )
		.text( text );

	if ( thumbnail ) {
		$thumbnail.css( 'background-image', 'url(' + thumbnail + ')' );
	}

	return $( '<a>' ).addClass( 'ext-wikistories-discover-container-cta' )
		.attr( 'href', link )
		.append( $thumbnail )
		.append( $text );
};

const generateStoryDiscoverElement = function ( title, thumbnail, storyId ) {
	return $( '<div>' )
		.addClass( 'ext-wikistories-discover-container' )
		.append( generateCtaElement( '#/story/' + storyId, thumbnail, '', title ) );
};

const generateCreateElement = function ( hasStories, thumbnail ) {
	const link = mw.config.get( 'wgWikistoriesCreateUrl' );
	const text = mw.message( 'wikistories-discover-cta-text' ).text();
	return $( '<div>' )
		.addClass( 'ext-wikistories-discover-container' )
		.addClass( hasStories ? 'ext-wikistories-discover-container-create' : '' )
		.append( generateCtaElement( link, thumbnail, '+', text ) );
};

const getArticleThumbnail = function () {
	const ogImageMetaTag = $( 'meta[property="og:image"]' )[ 0 ];
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

const getDiscoverSection = function ( stories ) {

	const $discover = $( '<div>' ).addClass( 'ext-wikistories-discover' );

	// existing stories
	stories.forEach( story => {
		const storyThumbnail = story.thumbnail;
		const storyId = story.pageId;
		$discover.append( generateStoryDiscoverElement( story.title, storyThumbnail, storyId ) );
	} );

	// create story cta
	$discover.append( generateCreateElement( !!stories.length, getArticleThumbnail() ) );

	return $discover;
};

module.exports = getDiscoverSection;
