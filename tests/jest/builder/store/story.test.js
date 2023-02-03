jest.mock( 'data.json', () => {
	return {};
}, { virtual: true } );

const storyModule = require( '../../../../resources/ext.wikistories.builder/store/story.js' );

describe( 'story Vuex module', function () {
	it( 'exists', function () {
		expect( storyModule ).toBeDefined();
	} );
} );
