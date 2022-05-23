/**
 * @param {string} url
 * @return {string} thumbnail url in 52px
 */
const convertUrlToThumbnail = function ( url ) {
	const regexWithPixels = /\/\d+px-/;
	const regexWithoutPixels = /(\/.)(\/..)\/((.+)\.(jpg|png))$/;

	if ( regexWithPixels.test( url ) ) {
		// https://upload.wikimedia.org/wikipedia/commons/thumb/0/0b/Cat_poster_1.jpg/52px-Cat_poster_1.jpg
		return url.replace( regexWithPixels, '/52px-' );
	} else if ( regexWithoutPixels.test( url ) ) {
		// https://upload.wikimedia.org/wikipedia/commons/1/1a/Cat_crying_%28Lolcat%29.jpg
		return url.replace( regexWithoutPixels, '/thumb$1$2/$3/52px-$3' );
	}

	return url;
};

module.exports = convertUrlToThumbnail;
