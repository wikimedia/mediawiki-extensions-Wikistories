const NS_STORY = mw.config.get( 'wgNamespaceIds' ).story;
const getPageInfo = require( '../api/getPageInfo.js' );

/**
 * @param {string} userInput Potential title for a story, without the namespace
 * @param {boolean} mustExist
 * @return {jQuery.Promise} resolves with validity and optional error message
 */
const validateTitle = function ( userInput, mustExist ) {
	// Not empty
	if ( !userInput || !userInput.trim() ) {
		return $.Deferred().resolve( {
			valid: false,
			message: 'wikistories-builder-publishform-invalidtitle-empty'
		} );
	}

	// Client side format validation
	const titleObject = mw.Title.newFromUserInput( userInput, NS_STORY );
	if ( !titleObject ) {
		return $.Deferred().resolve( {
			valid: false,
			message: 'wikistories-builder-publishform-invalidtitle-format'
		} );
	}

	// Server side format validation and uniqueness
	return getPageInfo( titleObject.getPrefixedDb() )
		.then( function ( pageInfo ) {
			const exists = !!pageInfo;
			if ( exists !== mustExist ) {
				return {
					valid: false,
					message: mustExist ?
						'wikistories-builder-publishform-invalidtitle-notfound' :
						'wikistories-builder-publishform-invalidtitle-duplicate'
				};
			}
			return { valid: true };
		} );
};

module.exports = validateTitle;
