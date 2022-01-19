var Vuex = require( 'vuex' );
var story = require( './story.js' );

module.exports = new Vuex.Store({
  modules: {
    story
  }
});
