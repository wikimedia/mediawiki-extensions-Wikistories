const strip = require( '../util/strip.js' );
const convertUrlToMobile = require( '../util/convertUrlToMobile.js' );

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
		gsrnamespace: 6,
		iiprop: 'url|extmetadata|mediatype',
		iiextmetadatafilter: 'License|LicenseShortName|Artist',
		iiextmetadatalanguage: mw.config.get( 'wgContentLanguage' )
	};

	return mwForeign.get( params ).then( function ( response ) {
		const pages = Object.keys( response.query.pages ).map( p => response.query.pages[ p ] );

		return pages.map( ( page ) => {
			const imageinfo = page.imageinfo && page.imageinfo[ 0 ];
			if ( imageinfo ) {
				const extmetadata = imageinfo.extmetadata;
				const artist = extmetadata && extmetadata.Artist;
				const license = extmetadata && extmetadata.LicenseShortName;

				return {
					title: page.title,
					attribution: {
						author: artist ? strip( artist.value ) : '',
						url: convertUrlToMobile( imageinfo.descriptionshorturl ),
						license: license && license.value
					}
				};
			}
			return {};
		} );
	} );
};

module.exports = getImageInfo;
