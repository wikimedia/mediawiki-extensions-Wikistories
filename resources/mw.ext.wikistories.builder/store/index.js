var Vuex = require( 'vuex' );
var article = require( './article.js' );
var story = require( './story.js' );

module.exports = new Vuex.Store( {
  modules: {
    article: article,
    story: story
  }
} );
