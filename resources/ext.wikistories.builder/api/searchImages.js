const strip = require( '../util/strip.js' );
const convertUrlToMobile = require( '../util/convertUrlToMobile.js' );
const safeAssignString = require( '../util/safeAssignString.js' );

/**
 * Keeps track of all on-going requests
 * from both getCommonsImages and getArticleImages
 */
let requests = [];

/**
 * Aborts all ongoing requests
 */
const abortAllRequests = () => {
	requests.forEach( ( x ) => x && x.abort() );
	requests = [];
};

/**
 * @param {string} lang
 * @param {string} queryString
 * @return {jQuery.Promise} resolves with images from Commons,
 * or an empty array if no images are found
 */
const getCommonsImages = ( lang, queryString ) => {
	const commonsUrl = 'https://commons.wikimedia.org/w/api.php';
	const mwForeign = new mw.ForeignApi( commonsUrl, { anonymous: true } );
	const params = {
		action: 'query',
		format: 'json',
		uselang: lang,
		generator: 'search',
		gsrsearch: 'filetype:bitmap|drawing filew:>639 fileh:>639 ' + queryString,
		gsrlimit: 40,
		gsroffset: 0,
		gsrinfo: 'totalhits',
		prop: 'imageinfo',
		gsrnamespace: 6,
		iiprop: 'url|mediatype|size',
		iiurlheight: 180
	};

	requests.push( mwForeign );

	return mwForeign.get( params ).then( function ( data ) {
		if ( data.query.searchinfo.totalhits === 0 ) {
			return [];
		}
		return Object.keys( data.query.pages ).map( pageId => {
			return data.query.pages[ pageId ];
		} );
	} ).catch( function () {
		return [];
	} );
};

/**
 * @param {Array} titles includes image filenames as strings
 * @return {jQuery.Promise} resolves with image attribution data
 */
const getImageInfo = function ( titles ) {
	const commonsUrl = 'https://commons.wikimedia.org/w/api.php';
	const mwForeign = new mw.ForeignApi( commonsUrl, { anonymous: true } );
	const params = {
		action: 'query',
		format: 'json',
		titles: titles,
		prop: 'imageinfo',
		iiprop: 'url|mediatype|size'
	};

	requests.push( mwForeign );

	return mwForeign.get( params );
};

const getImageExtMetadata = function ( titles, lang ) {
	const commonsUrl = 'https://commons.wikimedia.org/w/api.php';
	const mwForeign = new mw.ForeignApi( commonsUrl, { anonymous: true } );
	const params = {
		action: 'query',
		format: 'json',
		titles: titles,
		prop: 'imageinfo',
		iiprop: 'extmetadata',
		iiextmetadatafilter: 'License|LicenseShortName|Artist',
		iiextmetadatalanguage: lang
	};

	requests.push( mwForeign );

	return mwForeign.get( params ).then( response => {
		if ( !response.query || !response.query.pages ) {
			return [];
		}

		return Object.keys( response.query.pages ).reduce( ( previousValue, pageId ) => {
			const title = response.query.pages[ pageId ].title;
			const imageinfo = response.query.pages[ pageId ].imageinfo[ 0 ];

			const extmetadata = imageinfo.extmetadata;
			const artist = extmetadata && extmetadata.Artist;
			// const license = extmetadata && extmetadata.LicenseShortName;
			const licString = extmetadata &&
				extmetadata.LicenseShortName &&
				extmetadata.LicenseShortName.value || '';
			const licenses = [ 'CC', 'BY', 'SA', 'Fair', 'Public' ];

			previousValue[ title ] = {
				author: artist ? strip( artist.value ) : '',
				license: licenses.filter( license => licString.indexOf( license ) !== -1 )
			};

			return previousValue;
		}, {} );
	} );
};

/**
 * @param {string} lang
 * @param {string} queryString
 * @return {jQuery.Promise} resolves with images from the corresponding
 * Wikipedia article (if any), or an empty array if no images are found
 */
const getArticleImages = ( lang, queryString ) => {
	const baseUrl = 'https://' + lang + '.wikipedia.org';
	const actionApi = new mw.ForeignApi( baseUrl + '/w/api.php' );
	const mwForeignRest = new mw.ForeignRest( baseUrl, actionApi, { anonymous: true } );
	const mwTitle = mw.Title.newFromUserInput( queryString, 0 );
	const normalizedTitle = mwTitle.getPrefixedDb();
	const mediaList = '/api/rest_v1/page/media-list/' + encodeURIComponent( normalizedTitle );

	requests.push( mwForeignRest );

	return mwForeignRest.get( mediaList ).then( function ( articleImages ) {
		const images = articleImages.items && articleImages.items.filter( i => {
			return i.type === 'image' &&
				i.srcset &&
				// keep only images hosted on Commons
				i.srcset[ 0 ].src.startsWith( '//upload.wikimedia.org/wikipedia/commons/' );
		} );

		if ( !images ) {
			return [];
		}

		const fileUrlMap = {};
		images.forEach( i => {
			safeAssignString( fileUrlMap, i.title, i.srcset[ 0 ].src );
		} );

		return getImageInfo( images.map( i => i.title ) ).then( data => {
			if ( !data.query || !data.query.pages ) {
				return [];
			}
			return Object.keys( data.query.pages ).filter( pageId => {
				const page = data.query.pages[ pageId ];
				if ( !page.imageinfo ) {
					return false;
				}
				const imageinfo = page.imageinfo[ 0 ];
				const type = imageinfo.mediatype;
				// keep only images that are big enough
				return ( type === 'BITMAP' || type === 'DRAWING' ) &&
					imageinfo.width >= 640 &&
					imageinfo.height >= 640;
			} ).map( pageId => {
				const page = data.query.pages[ pageId ];
				page.imageinfo[ 0 ].responsiveUrls = { 1: fileUrlMap[ page.title ] };
				return page;
			} );
		} );
	} ).catch( function () {
		return [];
	} );
};

/**
 * @param {string} queryString
 * @return {jQuery.Promise} resolves with images from both Commons
 * and the corresponding Wikipedia article (if any), or an empty array
 * if no images are found
 */
const searchAllImages = ( queryString ) => {
	if ( !queryString ) {
		return $.Deferred().resolve( [] );
	}

	const lang = mw.config.get( 'wgContentLanguage' );
	abortAllRequests();

	return $.when(
		getArticleImages( lang, queryString ),
		getCommonsImages( lang, queryString )
	).then( function ( article, commons ) {
		let id = 0;
		return article.concat( commons )
			.map( ( page ) => {
				const imageinfo = page.imageinfo[ 0 ];
				const responsiveUrls = imageinfo.responsiveUrls &&
					Object.keys( imageinfo.responsiveUrls )
						.map( ( p ) => imageinfo.responsiveUrls[ p ] )[ 0 ];

				return {
					id: ( id++ ).toString(),
					filename: page.title.split( ':' )[ 1 ],
					title: page.title,
					url: responsiveUrls || imageinfo.url,
					width: imageinfo.thumbwidth,
					attribution: {
						url: convertUrlToMobile( imageinfo.descriptionshorturl )
					}
				};
			} );
	} );
};

module.exports = {
	searchImages: searchAllImages,
	abortSearch: abortAllRequests,
	getImageExtMetadata: getImageExtMetadata
};
