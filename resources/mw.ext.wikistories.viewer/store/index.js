const Vuex = require( 'vuex' );
const story = require( './story.js' );

module.exports = new Vuex.Store( {
	modules: {
		story: story
	}
} );
