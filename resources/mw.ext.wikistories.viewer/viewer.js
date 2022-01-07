var Vue = require('vue');
var StoryViewer = require('./StoryViewer.vue');

var story = mw.config.get( 'story' ).frames.map( function (s, i) {
  s.id = i + 1;
  return s;
} );

Vue.createMwApp( StoryViewer, { story: story } ).mount( '.story-viewer-js-root' );
