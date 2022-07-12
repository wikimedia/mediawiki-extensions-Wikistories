/**
 * @param {string} html
 * @return {string} text content of the given html
 */
const strip = ( html ) => {
	const doc = new window.DOMParser().parseFromString( html, 'text/html' );
	for ( const span of doc.querySelectorAll( 'span' ) ) {
		if ( span.style.display === 'none' ) {
			span.remove();
		}
	}
	for ( const sup of doc.querySelectorAll( 'sup' ) ) {
		sup.remove();
	}

	return doc.body.textContent || '';
};

module.exports = strip;
