const router = require( '../router.js' );

const MIN_FRAMES = mw.config.get( 'wgWikistoriesMinFrames' );
const MAX_FRAMES = mw.config.get( 'wgWikistoriesMaxFrames' );
const MAX_TEXT_LENGTH = mw.config.get( 'wgWikistoriesMaxTextLength' );
const storyContent = mw.config.get( 'wgWikistoriesStoryContent' );
const storyEditMode = mw.config.get( 'wgWikistoriesMode' );

let orderKey = 10;

const makeFrameStyle = f => {
	return f.url ?
		{
			backgroundImage: 'url(' + f.url + ')',
			backgroundPosition: 'center',
			backgroundSize: 'cover'
		} :
		{ background: 'linear-gradient(338.27deg, #0BD564 -70.53%, #3366CC 71.84%)' };
};

module.exports = {
	state: {
		fromArticle: storyContent.fromArticle,
		currentFrameIndex: null,
		/*
			frames: [ { url, filename, text, textFromArticle } ]
		 */
		frames: storyContent.frames,
		mode: storyEditMode,
		title: storyContent.title
	},
	mutations: {
		selectFrame: ( state, index ) => { state.currentFrameIndex = index; },
		removeFrame: ( state ) => {
			state.frames.splice( state.currentFrameIndex, 1 );
			if ( state.frames.length < 1 ) {
				router.replace( '/search' );
			} else {
				if ( state.frames.length === state.currentFrameIndex ) {
					state.currentFrameIndex--;
				}
			}
		},
		reorderFrames: ( state, order ) => {
			const newFramesObject = new Array( order.length );
			order.forEach( ( value, index ) => {
				newFramesObject[ value ] = state.frames[ index ];
				newFramesObject[ value ].key = orderKey++;
			} );

			state.frames = newFramesObject;
			state.currentFrameIndex = order[ state.currentFrameIndex ];
		},
		addFrames: ( state, frames ) => {
			const newSelectedFrameIndex = state.frames.length;
			state.frames = state.frames.concat( frames );
			state.currentFrameIndex = newSelectedFrameIndex;
		},
		setText: ( state, text ) => {
			state.frames[ state.currentFrameIndex ].text = text.slice( 0, MAX_TEXT_LENGTH );
		},
		setTextFromArticle: ( state, textFromArticle ) => {
			const frame = state.frames[ state.currentFrameIndex ];
			frame.textFromArticle = textFromArticle.slice( 0, MAX_TEXT_LENGTH );
		},
		setImageUrl: ( state, url ) => {
			state.frames[ state.currentFrameIndex ].url = url;
		},
		setImageFilename: ( state, title ) => {
			state.frames[ state.currentFrameIndex ].filename = title;
		},
		setImageAttribution: ( state, attribution ) => {
			state.frames[ state.currentFrameIndex ].attribution = attribution;
		}
	},
	actions: {
		selectFrame: ( context, id ) => {
			context.commit( 'selectFrame', id );
		},
		removeFrame: ( context ) => {
			context.commit( 'removeFrame' );
		},
		addFrames: ( context, frames ) => {
			context.commit( 'addFrames', frames );
		},
		setText: ( context, text ) => {
			context.commit( 'setText', text );
		},
		setTextFromArticle: ( context, textFromArticle ) => {
			context.commit( 'setTextFromArticle', textFromArticle );
		},
		setImageUrl: ( context, url ) => {
			context.commit( 'setImageUrl', url );
		},
		setImageFilename: ( context, filename ) => {
			context.commit( 'setImageFilename', filename );
		},
		setFrameImage: ( context, data ) => {
			context.commit( 'setImageUrl', data.url );
			context.commit( 'setImageFilename', data.filename );
			context.commit( 'setImageAttribution', data.attribution );
		},
		reorderFrames: ( context, data ) => {
			context.commit( 'reorderFrames', data );
		}
	},
	getters: {
		mode: ( state ) => state.mode,
		title: ( state ) => state.title,
		thumbnails: ( state ) => {
			return state.frames.map( ( f, index ) => {
				const newFrame = $.extend( {}, f );
				if ( index === state.currentFrameIndex ) {
					newFrame.selected = true;
				}
				newFrame.style = makeFrameStyle( f );
				return newFrame;
			} );
		},
		currentFrame: ( state ) => {
			const f = state.frames[ state.currentFrameIndex ];
			return {
				text: f.text,
				style: makeFrameStyle( f ),
				imgAttribution: f.attribution
			};
		},
		missingFrames: ( state ) => {
			return state.frames.length < MIN_FRAMES ?
				MIN_FRAMES - state.frames.length : 0;
		},
		maxFrames: ( state ) => {
			return state.frames.length === MAX_FRAMES;
		},
		framesWithoutText: ( state ) => state.frames.filter( f => !f.text ).length,
		frames: ( state ) => state.frames,
		fromArticle: ( state ) => state.fromArticle,
		storyForSave: ( state ) => {
			return {
				fromArticle: state.fromArticle,
				frames: state.frames.map( ( f ) => {
					const frame = {
						image: {
							filename: f.filename
						},
						text: {
							value: f.text
						}
					};
					if ( f.textFromArticle ) {
						frame.text.fromArticle = {
							articleTitle: state.fromArticle,
							originalText: f.textFromArticle
						};
					}
					return frame;
				} )
			};
		}
	}
};
