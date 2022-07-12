const convertUrlToThumbnail = require( '../../../../resources/ext.wikistories.discover/util/convertUrlToThumbnail.js' );

describe( 'convertUrlToThumbnail', function () {

	it( 'convert pixels (jpg) to 52px', () => {
		const url = 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/0d/XX.jpg/1200px-XX.jpg';
		const expected = 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/0d/XX.jpg/52px-XX.jpg';
		expect( convertUrlToThumbnail( url ) ).toEqual( expected );
	} );

	it( 'convert pixels (png) to 52px', () => {
		const url = 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/0d/XX.png/1200px-XX.png';
		const expected = 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/0d/XX.png/52px-XX.png';
		expect( convertUrlToThumbnail( url ) ).toEqual( expected );
	} );

	it( 'convert non thumb into thumb image', () => {
		const url = 'https://upload.wikimedia.org/wikipedia/commons/0/0d/XX.png';
		const expected = 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/0d/XX.png/52px-XX.png';
		expect( convertUrlToThumbnail( url ) ).toEqual( expected );
	} );

	it( 'return normal url', () => {
		const url = 'XX.png';
		const expected = 'XX.png';
		expect( convertUrlToThumbnail( url ) ).toEqual( expected );
	} );
} );
