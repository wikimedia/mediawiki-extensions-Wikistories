/**
 * @param {string} title
 * @return {jQuery.Promise} resolves with stories about the page
 */
const getStories = function ( title ) {
	const api = new mw.Rest();
	const mwTitle = mw.Title.newFromText( title );
	const normalizedTitle = mwTitle.getPrefixedDb();
	const url = '/wikistories/v0/page/' + normalizedTitle + '/stories';
	return api.get( url );
};

module.exports = getStories;
