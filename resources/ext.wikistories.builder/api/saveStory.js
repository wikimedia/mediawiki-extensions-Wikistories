/**
 * @param {string} title
 * @param {Object} content Content of the story (frames with images and text)
 * @param {string} mode 'edit' or 'new'
 * @param {boolean} watchlist Whether the story should be on the current user's watchlist
 * @param {string} watchlistExpiry Watch expiration
 * @return {jQuery.Promise} resolves with the result of the save operation
 */
const saveStory = function ( title, content, mode, watchlist, watchlistExpiry ) {
	const api = new mw.Api();
	const contentString = JSON.stringify( content );
	const watchlistValue = watchlist ? 'watch' : 'unwatch';
	if ( mode === 'edit' ) {
		return api.edit( title, function () {
			return {
				text: contentString,
				watchlist: watchlistValue,
				watchlistexpiry: watchlistExpiry
			};
		} );
	} else {
		return api.create(
			title,
			{
				contentformat: 'application/json',
				contentmodel: 'story',
				watchlist: watchlistValue,
				watchlistexpiry: watchlistExpiry
			},
			contentString
		);
	}
};

module.exports = saveStory;
