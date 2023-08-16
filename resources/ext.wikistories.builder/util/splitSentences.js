// source code from
// https://github.com/wikimedia/mediawiki-services-cxserver/blob/master/lib/segmentation/languages/SegmenterDefault.js

/**
 * Find all matches of regex in text, calling callback with each match object
 *
 * @param {string} text The text to search
 * @param {RegExp} regex The regex to search; should be created for this function call
 * @param {Function} callback Function to call with each match
 * @return {Array} The return values from the callback
 */
const findAll = function ( text, regex, callback ) {
	const boundaries = [];
	while ( true ) {
		const match = regex.exec( text );
		if ( match === null ) {
			break;
		}
		const boundary = callback( text, match );
		if ( boundary !== null ) {
			boundaries.push( boundary );
		}
	}
	return boundaries;
};

/**
 * Test a possible English sentence boundary match
 *
 * @param {string} text The plaintext to segment
 * @param {Object} match The possible boundary match (returned by regex.exec)
 * @return {number|null} The boundary offset, or null if not a sentence boundary
 */
const findBoundary = function ( text, match ) {
	const tail = text.slice( match.index + 1, text.length );
	const head = text.slice( 0, match.index );

	// Trailing non-final punctuation: not a sentence boundary
	if ( tail.match( /^[,;:]/ ) ) {
		return null;
	}
	// Next word character is number or lower-case: not a sentence boundary
	if ( tail.match( /^\W*[0-9a-z]/ ) ) {
		return null;
	}

	// Do not break in abbreviations. Example D. John, St. Peter
	const lastWord = head.match( /(\w*)$/ )[ 0 ];
	// Exclude at most 2 letter abbreviations. Examples: T. Dr. St. Jr. Sr. Ms. Mr.
	// But not all caps like "UK." as in  "UK. Not US",
	if ( lastWord.length <= 2 && lastWord.match( /^\W*[A-Z][a-z]?$/ ) && tail.match( /^\W*[A-Z]/ ) ) {
		return null;
	}
	// Exclude Mrs.
	if ( lastWord === 'Mrs' ) {
		return null;
	}

	// Include any closing punctuation and trailing space
	return match.index + 1 + tail.match( /^['”"’]*\s*/ )[ 0 ].length;
};

// end of source code

/**
 * Split and wrap each sentences with a span
 *
 * @param {Node} root
 * @param {string} sentenceClass CSS class to add to each sentence
 * @return {string}
 */
const splitSentences = function ( root, sentenceClass ) {
	let output = '';
	let buffer = '';
	for ( let i = 0; i < root.childNodes.length; ++i ) {
		let node = root.childNodes[ i ];
		if ( node.nodeType === Node.TEXT_NODE ) {
			let textContent = node.textContent;

			let boundaries = findAll( textContent, /[.!?]/g, findBoundary );

			if ( boundaries.length ) {
				let j;
				buffer += textContent.slice( 0, boundaries[ 0 ] );
				output += ' <span class="' + sentenceClass + '">' + buffer.trim() + '</span>';
				buffer = '';

				for ( j = 1; j < boundaries.length; j++ ) {
					output += ' <span class="' + sentenceClass + '">' + textContent.slice( boundaries[ j - 1 ], boundaries[ j ] ).trim() + '</span>';
				}

				// remaining content
				if ( textContent[ boundaries[ j - 1 ] + 1 ] !== undefined ) {
					buffer = textContent.slice( -( textContent.length - [ boundaries[ j - 1 ] ] ) );
				}

			} else {
				buffer += textContent;
			}

		} else if ( node.nodeType !== Node.COMMENT_NODE ) {
			buffer += node.outerHTML;
		}

	}

	// remaining content from the last sentence
	if ( buffer ) {
		output += ' <span class="' + sentenceClass + '">' + buffer.trim() + '</span>';
	}
	return output.trim();
};

module.exports = splitSentences;
