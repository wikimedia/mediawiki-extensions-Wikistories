/**
 * @param {string} title
 * @param {Object} content Content of the story (frames with images and text)
 * @param {string} mode 'edit' or 'new'
 * @return {jQuery.Promise} resolves with the result of the save operation
 */
const saveStory = function ( title, content, mode ) {
	const api = new mw.Api();
	const contentString = JSON.stringify( content );
	if ( mode === 'edit' ) {
		return api.edit( title, function () {
			return contentString;
		} );
	} else {
		return api.create(
			title,
			{
				contentformat: 'application/json',
				contentmodel: 'story'
			},
			contentString
		);
	}
};

module.exports = saveStory;
