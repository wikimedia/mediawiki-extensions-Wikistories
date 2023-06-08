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
			if ( node.textContent.includes( '.' ) ) {
				let text = node.textContent;
				let parts = text.split( '.' );
				buffer += parts[ 0 ] + '.';
				output += ' <span class="' + sentenceClass + '">' + buffer.trim() + '</span>';

				buffer = parts[ parts.length - 1 ];
				for ( let j = 1; j < parts.length - 1; j++ ) {
					output += ' <span class="' + sentenceClass + '">' + parts[ j ].trim() + '.</span>';
				}
			} else {
				buffer += node.textContent;
			}
		} else if ( node.nodeType !== Node.COMMENT_NODE ) {
			buffer += node.outerHTML;
		}
	}
	return output;
};

module.exports = splitSentences;
