const convertUrlToThumbnail = require( './util/convertUrlToThumbnail.js' );

const generateItem = function ( link, thumbnail, thumbnailText, text ) {
	const $thumbnail = $( '<span>' )
		.addClass( 'ext-wikistories-discover-item-btn' )
		.text( thumbnailText );
	const $text = $( '<span>' )
		.addClass( 'ext-wikistories-discover-item-text' )
		.text( text );

	if ( thumbnail ) {
		const overlay = thumbnailText === '+' ?
			'linear-gradient(rgba(0, 0, 0, 0.45), rgba(0, 0, 0, 0.45)), ' :
			'';
		$thumbnail.css( 'background-image', overlay + 'url(' + thumbnail + ')' );
	}

	return $( '<a>' ).addClass( 'ext-wikistories-discover-item' )
		.attr( 'href', link )
		.append( $thumbnail )
		.append( $text );
};

const generateCTA = function ( thumbnail ) {
	const storyBuilder = require( './data.json' ).storyBuilder;
	const pageName = mw.config.get( 'wgPageName' );
	const url = mw.Title.newFromText( 'Special:' + storyBuilder + '/' + pageName ).getUrl();
	const text = mw.message( 'wikistories-discover-cta-text' ).text();
	return generateItem( url, thumbnail, '+', text );
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

const getDiscoverSection = function () {
	return $( '<div>' )
		.addClass( 'ext-wikistories-discover' )
		.append( generateCTA( getArticleThumbnail() ) );
};

const addStoriesToDiscoverSection = function ( $discover, stories ) {
	stories.forEach( story => {
		const link = '#/story/' + story.pageId;
		$discover.append( generateItem( link, story.thumbnail, '', story.title ) );
	} );
};

module.exports = {
	getDiscoverSection: getDiscoverSection,
	addStoriesToDiscoverSection: addStoriesToDiscoverSection
};
