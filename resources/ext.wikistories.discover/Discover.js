const convertUrlToThumbnail = require( './util/convertUrlToThumbnail.js' );

const generateItem = function ( link, thumbnail, thumbnailText, title, cta = false ) {
	const className = cta ? 'ext-wikistories-discover-item-cta' : 'ext-wikistories-discover-item';
	const $thumbnail = $( '<span>' )
		.addClass( `${className}-btn` )
		.text( thumbnailText );

	const $title = $( '<p>' )
		.addClass( `${className}-text-title` )
		.text( title );

	const $text = $( '<div>' )
		.addClass( `${className}-text` )
		.append( $title );

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

const addCtaText = function () {
	const $cta = $( '.ext-wikistories-discover-item-cta' );
	const $text = $( '.ext-wikistories-discover-item-cta-text' );
	const $title = $( '.ext-wikistories-discover-item-cta-text-title' );
	const $subtitle = $( '<p>' )
		.addClass( 'ext-wikistories-discover-item-cta-text-subtitle' )
		.text( mw.message( 'wikistories-discover-cta-text-subheader' ).text() );

	$title.text( mw.message( 'wikistories-discover-cta-text' ).text() );
	$text.append( $subtitle );
	$cta.addClass( 'ext-wikistories-discover-item-no-border' );

	$cta.append( $text );
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
	if ( stories.length > 0 ) {
		const $stories = $( '<div>' ).addClass( 'ext-wikistories-discover-stories' );
		$discover.append( $stories );
		stories.forEach( story => {
			const link = '#/story/' + story.pageId;
			$stories.append( generateItem( link, story.thumbnail, '', story.title ) );
		} );
	} else {
		addCtaText();
	}
};

module.exports = {
	getDiscoverSection: getDiscoverSection,
	addStoriesToDiscoverSection: addStoriesToDiscoverSection
};
