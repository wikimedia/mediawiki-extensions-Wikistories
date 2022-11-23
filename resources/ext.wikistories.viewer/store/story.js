const PREF_TEXTSIZE = 'wikistories-pref-viewertextsize';

module.exports = {
	state: {
		stories: [],
		storyId: null,
		frameId: 0,
		isStoryEnd: false,
		loadedImages: [],
		textsize: mw.user.options.get( PREF_TEXTSIZE ) || 'regular',
		textsizes: {
			small: 90,
			regular: 100,
			large: 120
		}
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
					text: null,
					fileNotFound: currentStory.frames[ 0 ].fileNotFound
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
			if ( getters.isStoryEnd && getters.nextStories.length ) {
				return {
					text: null,
					fileNotFound: true
				};
			} else if ( getters.story.length ) {
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
		editUrl: ( state, getters ) => {
			// Correct frameId to 0 for the cover frame AND the first frame of content
			const frameId = Math.max( state.frameId - 1, 0 );
			return getters.currentStory.editUrl + '?frameid=' + frameId;
		},
		talkUrl: ( state, getters ) => {
			return getters.currentStory.talkUrl;
		},
		isCurrentImageLoaded: ( state, getters ) => {
			return state.loadedImages.indexOf( getters.currentFrame.url ) !== -1;
		},
		textsizes: ( state ) => state.textsizes,
		textsize: ( state ) => state.textsize,
		nextStories: ( state ) => {
			const stories = state.stories;
			const storyId = state.storyId;
			const currentStoriesIndex = stories.findIndex(
				story => story.pageId.toString() === storyId
			);

			// get 3 random stories
			const clonedStories = JSON.parse( JSON.stringify( stories ) );
			clonedStories.splice( currentStoriesIndex, 1 );
			const shuffledStories = clonedStories.sort( () => ( Math.random() > 0.5 ) ? 1 : -1 );
			return shuffledStories.slice( 0, 3 );
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
		},
		setImageIsLoaded: ( state, imageUrl ) => {
			if ( state.loadedImages.indexOf( imageUrl ) === -1 ) {
				state.loadedImages.push( imageUrl );
			}
		},
		setTextsize: ( state, newTextsize ) => {
			state.textsize = newTextsize;
		}
	},
	actions: {
		setStories: ( context, stories ) => {
			context.commit( 'setStories', stories );
		},
		setStoryId: ( context, storyId ) => {
			context.commit( 'setStoryId', storyId );

			// Preload story images
			context.getters.story.forEach( ( frame ) => {
				const img = new Image();
				img.src = frame.url;
				img.onload = () => {
					context.commit( 'setImageIsLoaded', frame.url );
				};
			} );
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
		nextStory: ( context, nextStoryId ) => {
			context.commit( 'setStoryId', nextStoryId );
			window.location.replace( '#/story/' + nextStoryId );
		},
		setTextsize: ( context, newTextsize ) => {
			if ( Object.prototype.hasOwnProperty.call( context.getters.textsizes, newTextsize ) ) {
				context.commit( 'setTextsize', newTextsize );
				mw.user.options.set( PREF_TEXTSIZE, newTextsize );
				new mw.Api().saveOption( PREF_TEXTSIZE, newTextsize );
			}
		}
	}
};
