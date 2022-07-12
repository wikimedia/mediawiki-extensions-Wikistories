const Vuex = require( 'vuex' );
const article = require( './article.js' );
const story = require( './story.js' );
const search = require( './search.js' );

module.exports = new Vuex.Store( {
	modules: {
		article: article,
		story: story,
		search: search
	}
} );
