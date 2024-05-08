const tokenise = function ( string ) {
	if ( !string ) {
		return [];
	}

	// TODO support all languages
	// if ( $.uls.data.scriptgroups.CJK.indexOf( $.uls.data.getScript( language ) ) >= 0 ) {
	// return string.split( '' );
	// }

	// Match all non whitespace characters for tokens.
	return string.match( /\S+/g ) || [];
};

/**
 * A very simple method to calculate the difference between two strings in the scale
 * of 0 to 1, based on relative number of tokens changed in string2 from string1.
 *
 * Taken from: https://github.com/wikimedia/mediawiki-extensions-ContentTranslation/blob/master/modules/mw.cx.TranslationTracker.js#L79
 *
 * @param {string} originalString
 * @param {string} targetString
 * @return {number} Percentage of unmodified content
 */
const calculateUnmodifiedContent = function ( originalString, targetString ) {
	if ( !originalString || !targetString ) {
		return 0;
	}

	if ( originalString === targetString ) {
		// Both strings are equal. So targetString is 100% unmodified version of originalString
		return 1;
	}

	var tokens1, tokens2;
	var bigSet = tokens1 = tokenise( originalString );
	var smallSet = tokens2 = tokenise( targetString );

	if ( tokens2.length > tokens1.length ) {
		// Swap the sets
		bigSet = tokens2;
		smallSet = tokens1;
	}

	// Find the intersection(tokens that did not change) two token sets
	var unmodifiedTokens = bigSet.filter( function ( token ) {
		return smallSet.indexOf( token ) >= 0;
	} );

	// If originalString has 10 tokens and we see that 2 tokens
	// are different or not present in targetString, we are saying
	// that targetString is 80% (ie. 10-2/10) of unmodified version of originalString.
	return unmodifiedTokens.length / bigSet.length;
};

module.exports = calculateUnmodifiedContent;
