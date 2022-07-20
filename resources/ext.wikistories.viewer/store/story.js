module.exports = {
	state: {
		stories: [],
		storyId: null,
		frameId: 0,
		isStoryEnd: false
	},
	getters: {
		currentStory: ( state ) => {
			return state.stories.find( story => {
				return story.pageId.toString() === state.storyId;
			} ) || {};
		},
		story: ( state ) => {
			const stories = state.stories;
			const storyId = state.storyId;
			const currentStory = stories.find( story => story.pageId.toString() === storyId );
			if ( currentStory ) {
				const coverFrame = [ {
					id: 0,
					attribution: currentStory.frames[ 0 ].attribution,
					url: currentStory.frames[ 0 ].url,
					text: null
				} ];
				return coverFrame.concat( currentStory.frames );
			}

			return [];
		},
		isFirstFrame: ( state ) => {
			return state.frameId === 0;
		},
		isLastFrame: ( state, getters ) => {
			return state.frameId === getters.story.length - 1;
		},
		isFramePlaying: ( state ) => ( n ) => {
			return state.frameId === n - 1;
		},
		isFrameDonePlaying: ( state ) => ( n ) => {
			return state.frameId > n - 1;
		},
		isFrameViewed: ( state ) => ( n ) => {
			return n <= state.frameId;
		},
		isStoryEnd: ( state ) => {
			return state.isStoryEnd;
		},
		currentFrame: ( state, getters ) => {
			if ( getters.story.length ) {
				return getters.story[ state.frameId ];
			} else {
				return {};
			}
		},
		imgAttribution: ( state, getters ) => {
			return getters.currentFrame.attribution || {};
		},
		currentStoryTitle: ( state, getters ) => {
			return getters.currentStory.title;
		},
		isLastStory: ( state ) => {
			return state.stories[ state.stories.length - 1 ].pageId.toString() === state.storyId;
		},
		editUrl: ( state, getters ) => {
			return getters.currentStory.editUrl;
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
		prevFrame: ( context ) => {
			if ( !context.getters.isFirstFrame ) {
				context.commit( 'setIsStoryEnd', false );
				context.commit( 'setStoryFrameId', context.state.frameId - 1 );
			}
		},
		nextFrame: ( context ) => {
			if ( !context.getters.isLastFrame ) {
				context.commit( 'setStoryFrameId', context.state.frameId + 1 );
			}
		},
		resetFrame: ( context ) => {
			context.commit( 'setStoryFrameId', 0 );
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
