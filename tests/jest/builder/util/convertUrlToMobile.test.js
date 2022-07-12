const convertUrlToMobile = require( '../../../../resources/ext.wikistories.builder/util/convertUrlToMobile.js' );

describe( 'convertUrlToMobile', function () {

	it( 'handles non-mobile url', () => {
		const url = 'https://commons.wikimedia.org/w/index.php?curid=6412607';
		const expected = 'https://commons.m.wikimedia.org/w/index.php?curid=6412607';
		expect( convertUrlToMobile( url ) ).toMatch( expected );
	} );

	it( 'handles mobile url', () => {
		const url = 'https://commons.m.wikimedia.org/w/index.php?curid=6412607';
		const expected = 'https://commons.m.wikimedia.org/w/index.php?curid=6412607';
		expect( convertUrlToMobile( url ) ).toMatch( expected );
	} );
} );
