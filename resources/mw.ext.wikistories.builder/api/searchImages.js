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
 * @param {string} html
 * @return {string} text content of the given html
 */
const strip = ( html ) => {
	const doc = new window.DOMParser().parseFromString( html, 'text/html' );
	for ( const span of doc.querySelectorAll( 'span' ) ) {
		if ( span.style.display === 'none' ) {
			span.remove();
		}
	}
	for ( const sup of doc.querySelectorAll( 'sup' ) ) {
		sup.remove();
	}

	return doc.body.textContent || '';
};

/**
 * @param {string} url
 * @return {string} returns url suited for mobile viewing
 */
const convertUrlToMobile = ( url ) => {
	return url.replace( /https:\/\/(.*?)\./, ( subDomain ) => subDomain + 'm.' );
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
		gsrsearch: queryString,
		gsrlimit: 40,
		gsroffset: 0,
		gsrinfo: 'totalhits',
		prop: 'imageinfo',
		gsrnamespace: 6,
		iiprop: 'url|extmetadata|mediatype|size',
		iiurlheight: 180,
		iiextmetadatafilter: 'License|LicenseShortName|Artist',
		iiextmetadatalanguage: lang
	};

	requests.push( mwForeign );

	return mwForeign.get( params ).then( function ( data ) {
		if ( data.query.searchinfo.totalhits === 0 ) {
			return [];
		}

		const pages = Object.keys( data.query.pages )
			.map( ( p ) => data.query.pages[ p ] )
			.filter( p => {
				const type = p.imageinfo[ 0 ].mediatype;
				return ( type === 'BITMAP' || type === 'DRAWING' ) &&
					p.imageinfo[ 0 ].width >= 640 &&
					p.imageinfo[ 0 ].height >= 640;
			} )
			.sort( ( a, b ) => a.index - b.index );

		return pages.map( ( page ) => {
			const imageinfo = page.imageinfo[ 0 ];
			const responsiveUrls = imageinfo.responsiveUrls &&
				Object.keys( imageinfo.responsiveUrls )
					.map( ( p ) => imageinfo.responsiveUrls[ p ] )[ 0 ];
			const extmetadata = imageinfo.extmetadata;
			const artist = extmetadata && extmetadata.Artist;
			const license = extmetadata && extmetadata.LicenseShortName;

			return {
				fromCommons: true,
				title: page.title,
				thumb: responsiveUrls || imageinfo.url,
				width: imageinfo.thumbwidth,
				attribution: {
					author: artist ? strip( artist.value ) : '',
					url: convertUrlToMobile( imageinfo.descriptionshorturl ),
					license: license && license.value
				}
			};
		} );
	} ).catch( function () {
		return [];
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

	return mwForeignRest.get( mediaList ).then( function ( data ) {
		const images = data.items && data.items.filter( i => {
			return i.type === 'image' &&
				i.srcset &&
				// keep only images hosted on Commons
				i.srcset[ 0 ].src.startsWith( '//upload.wikimedia.org/wikipedia/commons/' );
		} );

		if ( !images ) {
			return [];
		}

		return images.map( ( img ) => {
			return {
				title: img.title,
				thumb: img.srcset[ 0 ].src,
				width: 300,
				attribution: null
			};
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
		const allImages = article.concat( commons )
			.filter( ( element ) => {
				// todo: fix thumb url generation and allow SVGs
				return !element.title.toLowerCase().endsWith( '.svg' );
			} )
			.map( ( element, index ) => {
				element.id = index.toString();
				return element;
			} );

		return allImages;
	} );
};

module.exports = { searchImages: searchAllImages, abortSearch: abortAllRequests };
