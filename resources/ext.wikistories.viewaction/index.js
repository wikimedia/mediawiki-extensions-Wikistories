const initStoryViewer = require( 'ext.wikistories.viewer' );
const story = mw.config.get( 'wgStory' );
initStoryViewer( [ story ], story.storyId, () => {}, false, false );
