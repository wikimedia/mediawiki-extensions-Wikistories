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
		gsrinfo: 'totalhits|suggestion',
		gsrprop: 'snippet',
		prop: 'imageinfo',
		gsrnamespace: 6,
		iiprop: 'url|extmetadata|mediatype',
		iiurlheight: 180,
		iiextmetadatafilter: 'License|LicenseShortName|ImageDescription|Artist',
		iiextmetadatalanguage: lang
	};

	requests.push( mwForeign );

	return mwForeign.get( params ).then( function ( data ) {
		if ( data.query.searchinfo.totalhits === 0 ) {
			return [];
		}

		const pages = Object.keys( data.query.pages )
			.map( ( p ) => data.query.pages[ p ] )
			.filter( p => p.imageinfo[ 0 ].mediatype === 'BITMAP' || p.imageinfo[ 0 ].mediatype === 'DRAWING' )
			.sort( ( a, b ) => a.index - b.index );

		return pages.map( ( page ) => {
			const imageinfo = page.imageinfo[ 0 ];
			const responsiveUrls = imageinfo.responsiveUrls &&
				Object.keys( imageinfo.responsiveUrls )
					.map( ( p ) => imageinfo.responsiveUrls[ p ] )[ 0 ];
			const extmetadata = imageinfo.extmetadata;
			const description = extmetadata && extmetadata.ImageDescription &&
				extmetadata.ImageDescription.value;
			const artist = extmetadata && extmetadata.Artist;
			const license = extmetadata && extmetadata.LicenseShortName;

			return {
				fromCommons: true,
				title: page.title,
				desc: strip( description || page.snippet ),
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
	const mediaListUrl = 'https://' + lang + '.wikipedia.org';
	const mwForeignRest = new mw.ForeignRest( mediaListUrl, '', { anonymous: true } );
	const mwTitle = mw.Title.newFromUserInput( queryString, 0 );
	const normalizedTitle = mwTitle.getPrefixedDb();
	const mediaList = '/api/rest_v1/page/media-list/' + encodeURIComponent( normalizedTitle );

	requests.push( mwForeignRest );

	return mwForeignRest.get( mediaList ).then( function ( data ) {
		const items = data.items;
		const images = items && items.filter( i => i.type === 'image' && i.srcset );

		if ( !images ) {
			return [];
		}

		return images.map( ( img ) => {
			return {
				title: img.title,
				desc: img.caption ? img.caption.text : null,
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
	const lang = mw.config.get( 'wgContentLanguage' );

	abortAllRequests();

	return $.when(
		getArticleImages( lang, queryString ),
		getCommonsImages( lang, queryString )
	).then( function ( article, commons ) {
		const allImages = article.concat( commons )
			.map( ( element, index ) => {
				element.id = index.toString();
				return element;
			} );

		return allImages;
	} );
};

module.exports = { searchImages: searchAllImages, abortSearch: abortAllRequests };