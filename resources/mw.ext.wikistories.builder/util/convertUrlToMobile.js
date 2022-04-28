/**
 * @param {string} url
 * @return {string} returns url suited for mobile viewing
 */
const convertUrlToMobile = ( url ) => {
	return url.replace( /https:\/\/(.*?)(\.m)?\./, ( subDomain ) => {
		if ( subDomain.endsWith( '.m.' ) ) {
			return subDomain;
		} else {
			return subDomain + 'm.';
		}
	} );
};

module.exports = convertUrlToMobile;
