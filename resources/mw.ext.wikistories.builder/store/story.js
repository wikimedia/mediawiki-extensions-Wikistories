const router = require( '../router.js' );

const MIN_FRAMES = mw.config.get( 'wgWikistoriesMinFrames' );
// const MAX_FRAMES = mw.config.get( 'wgWikistoriesMaxFrames' );

let orderKey = 10;

const makeFrameStyle = f => {
	return f.img ?
		{
			backgroundImage: 'url(' + f.img + ')',
			backgroundPosition: 'center',
			backgroundSize: 'cover'
		} :
		{ background: 'linear-gradient(338.27deg, #0BD564 -70.53%, #3366CC 71.84%)' };
};

module.exports = {
	state: {
		fromArticle: mw.config.get( 'wgWikistoriesFromArticle' ),
		currentFrameIndex: null,
		frames: []
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
			state.frames[ state.currentFrameIndex ].text = text;
		},
		setTextFromArticle: ( state, textFromArticle ) => {
			state.frames[ state.currentFrameIndex ].textFromArticle = textFromArticle;
		},
		setImg: ( state, img ) => {
			state.frames[ state.currentFrameIndex ].img = img;
		},
		setImgTitle: ( state, title ) => {
			state.frames[ state.currentFrameIndex ].imgTitle = title;
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
		setImg: ( context, img ) => {
			context.commit( 'setImg', img );
		},
		setImgTitle: ( context, title ) => {
			context.commit( 'setImgTitle', title );
		},
		setFrameImage: ( context, data ) => {
			context.commit( 'setImg', data.thumb );
			context.commit( 'setImgTitle', data.title );
		},
		reorderFrames: ( context, data ) => {
			context.commit( 'reorderFrames', data );
		}
	},
	getters: {
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
		framesWithoutText: ( state ) => state.frames.filter( f => !f.text ).length,
		frames: ( state ) => state.frames,
		fromArticle: ( state ) => state.fromArticle,
		storyForSave: ( state ) => {
			return {
				fromArticle: state.fromArticle,
				frames: state.frames.map( ( f ) => {
					return {
						image: {
							filename: f.imgTitle.split( ':' )[ 1 ],
							repo: ( new mw.Uri( f.img ).path ).split( '/' )[ 2 ]
						},
						text: {
							value: f.text,
							fromArticle: {
								articleTitle: state.fromArticle,
								originalText: f.textFromArticle
							}
						}
					};
				} )
			};
		}
	}
};
