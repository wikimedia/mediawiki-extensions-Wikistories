/**
 * @param {string} title
 * @param {Object} content Content of the story (frames with images and text)
 * @return {jQuery.Promise} resolves with the result of the save operation
 */
const saveStory = function ( title, content ) {
	const api = new mw.Api();
	return api.create(
		title,
		{
			contentformat: 'application/json',
			contentmodel: 'story'
		},
		JSON.stringify( content )
	);
};

module.exports = saveStory;
