module.exports = {
	state: {
		stories: [],
		storyId: null,
		frameId: 0,
		isStoryEnd: false
	},
	getters: {
		story: ( state ) => {
			const stories = state.stories;
			const storyId = state.storyId;
			const currentStory = stories.find( story => story.pageId.toString() === storyId );
			return currentStory ? currentStory.frames : [];
		},
		isStoryEnd: ( state ) => {
			return state.isStoryEnd;
		},
		currentFrame: ( state, getters ) => {
			if ( getters.story.length ) {
				return getters.story[ state.frameId - 1 ];
			} else {
				return {};
			}
		},
		currentStoryTitle: ( state ) => {
			const stories = state.stories;
			const storyId = state.storyId;
			const currentStory = stories.find( story => story.pageId.toString() === storyId );
			return currentStory.title;
		},
		isLastStory: ( state ) => {
			return state.stories[ state.stories.length - 1 ].pageId.toString() === state.storyId;
		}
	},
	mutations: {
		setStories: ( state, stories ) => {
			state.stories = stories;
		},
		setStoryId: ( state, storyId ) => {
			state.storyId = storyId;
		},
		setStoryFrameId: ( state, frameId ) => {
			state.frameId = frameId;
		},
		setIsStoryEnd: ( state, isStoryEnd ) => {
			state.isStoryEnd = isStoryEnd;
		}
	},
	actions: {
		setStories: ( context, stories ) => {
			context.commit( 'setStories', stories );
		},
		setStoryId: ( context, storyId ) => {
			context.commit( 'setStoryId', storyId );
		},
		setStoryFrameId: ( context, frameId ) => {
			context.commit( 'setStoryFrameId', frameId );
		},
		setIsStoryEnd: ( context, isStoryEnd ) => {
			context.commit( 'setIsStoryEnd', isStoryEnd );
		},
		nextFrame: ( context ) => {
			context.commit( 'setStoryFrameId', context.state.frameId + 1 );
		},
		resetFrame: ( context ) => {
			context.commit( 'setStoryFrameId', 1 );
		},
		nextStory: ( context ) => {
			const stories = context.state.stories;
			const storyId = context.state.storyId;
			const currentStoriesIndex = stories.findIndex(
				story => story.pageId.toString() === storyId
			);
			const nextStoryId = stories[ currentStoriesIndex + 1 ].pageId.toString();

			context.commit( 'setStoryId', nextStoryId );
			window.location.hash = '#/story/' + nextStoryId;
		}
	}
};
