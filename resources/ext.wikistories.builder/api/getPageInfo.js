/**
 * @param {string} title
 * @return {jQuery.Promise} resolves with basic info about the page
 * or null if the page doesn't exist
 */
const getPageInfo = function ( title ) {
	const api = new mw.Api();
	return api.get( { action: 'query', prop: 'info', titles: title } )
		.then( response => {
			if ( !response.query || !response.query.pages ) {
				return null;
			}

			const pageId = Object.keys( response.query.pages )[ 0 ];
			return pageId === '-1' ? null : response.query.pages[ pageId ];
		} );
};

module.exports = getPageInfo;
