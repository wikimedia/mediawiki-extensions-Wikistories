/**
 * @param {string} title
 * @return {jQuery.Promise} resolves with thumbnail info about the page
 */
const getArticleThumbnail = function ( title ) {
	const api = new mw.Api();
	return api.get( { action: 'query', prop: 'pageimages', titles: title } )
		.then( response => {
			if ( !response.query || !response.query.pages ) {
				return null;
			}

			const thumbnail = Object.keys( response.query.pages )[ 0 ];
			return thumbnail && thumbnail.source;
		} );
};

module.exports = getArticleThumbnail;
