
module.exports = {
  state: {
    frames: []
  },
  mutations: {
    setFrames: function (state, frames) {
      state.frames = frames;
    }
  },
  actions: {
    /**
     * Initialize the story with a single empty frame
     *
     * @param context
     */
    initNewStory: function ( context ) {
      context.commit( 'setFrames', [ { img: '', text: '' } ] );
    }
  },
  getters: {
    currentFrame: function ( state ) {
      var f = state.frames[0];
      return {
        text: f.text,
        img: f.img
      }
    }
  }
};
