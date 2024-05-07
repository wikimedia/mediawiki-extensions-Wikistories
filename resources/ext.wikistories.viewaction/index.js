const initStoryViewer = require( 'ext.wikistories.viewer' );
const story = mw.config.get( 'wgWikistoriesStoryContent' );
initStoryViewer( [ story ], story.storyId, false, false );
