const MIN_FRAMES = mw.config.get( 'wgWikistoriesMinFrames' );
const MAX_FRAMES = mw.config.get( 'wgWikistoriesMaxFrames' );
const MAX_TEXT_LENGTH = mw.config.get( 'wgWikistoriesMaxTextLength' );
const storyContent = mw.config.get( 'wgWikistoriesStoryContent' );
const storyEditMode = mw.config.get( 'wgWikistoriesMode' );
const lang = mw.config.get( 'wgContentLanguage' );
const watchDefault = mw.config.get( 'wgWikistoriesWatchDefault' );
const watchlistExpiryEnabled = mw.config.get( 'wgWikistoriesWatchlistExpiryEnabled' );
const watchlistExpiryOptions = mw.config.get( 'wgWikistoriesWatchlistExpiryOptions' );

const searchTools = require( '../api/searchImages.js' );
const getImageExtMetadata = searchTools.getImageExtMetadata;

let orderKey = 10;

const makeFrameStyle = ( f, thumbnail = false ) => {
	return f.url ?
		{
			backgroundImage: 'url(' + f.url + ')',
			backgroundPosition: 'center',
			backgroundSize: 'cover'
		} : {
			backgroundColor: thumbnail ? '#eaecf0' : '#fff'
		};
};

module.exports = {
	state: {
		fromArticle: storyContent.articleTitle,
		articleId: storyContent.articleId,
		currentFrameIndex: null,
		/*
			frames: [ { url, filename, text, textFromArticle } ]
		 */
		frames: storyContent.frames,
		mode: storyEditMode,
		storyTitle: storyContent.storyTitle,
		editingText: false,
		watchlistExpiryEnabled: watchlistExpiryEnabled,
		watchlistExpiryOptions: watchlistExpiryOptions,
		watchDefault: watchDefault
	},
	mutations: {
		selectFrame: ( state, index ) => {
			// Make sure it's an integer
			index = parseInt( index ) || 0;
			// Make sure it's >= 0
			index = Math.max( index, 0 );
			// Make sure it's <= max index
			index = Math.min( index, state.frames.length - 1 );
			state.currentFrameIndex = index;
		},
		removeFrame: function ( state ) {
			state.frames.splice( state.currentFrameIndex, 1 );
			if ( state.frames.length > 0 && ( state.frames.length === state.currentFrameIndex ) ) {
				state.currentFrameIndex--;
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
		setLastEditedText: ( state, text ) => {
			const frame = state.frames[ state.currentFrameIndex ];
			frame.lastEditedText = text.slice( 0, MAX_TEXT_LENGTH );
		},
		setFrame: ( state, data ) => {
			state.frames[ state.currentFrameIndex ] = data;
		},
		setFrameImageAttribution: ( state, data ) => {
			state.frames
				.filter( frame => frame.title === data.title )
				.forEach( frame => {
					frame.attribution = data.attribution;
				} );
		},
		setEditingText: ( state, value ) => {
			state.editingText = value;
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

			getImageExtMetadata( frames.map( f => f.title ), lang ).then( response => {
				frames.forEach( frame => {
					const attribution = response[ frame.title ];
					attribution.url = frame.attribution.url;

					context.commit( 'setFrameImageAttribution', {
						attribution: attribution,
						title: frame.title
					} );
				} );
			} );
		},
		setText: ( context, text ) => {
			context.commit( 'setText', text );
		},
		setTextFromArticle: ( context, textFromArticle ) => {
			context.commit( 'setTextFromArticle', textFromArticle );
		},
		setLastEditedText: ( context, text ) => {
			context.commit( 'setLastEditedText', text );
		},
		setFrameImage: ( context, data ) => {
			// frame text content remain
			const currentFrame = context.getters.currentFrame;
			data.text = currentFrame.text;
			data.textFromArticle = currentFrame.textFromArticle;

			// frame image releated attribution
			const url = data.attribution.url;
			context.commit( 'setFrame', data );

			getImageExtMetadata( data.title, lang ).then( response => {
				const author = response[ data.title ].author;
				const license = response[ data.title ].license;

				context.commit( 'setFrameImageAttribution', {
					attribution: {
						author: author,
						license: license,
						url: url
					},
					title: data.title
				} );
			} );

		},
		reorderFrames: ( context, data ) => {
			context.commit( 'reorderFrames', data );
		},
		setEditingText: ( context, value ) => {
			context.commit( 'setEditingText', value );
		}
	},
	getters: {
		mode: ( state ) => state.mode,
		storyExists: ( state ) => state.mode === 'edit',
		watchlistExpiryEnabled: ( state ) => state.watchlistExpiryEnabled,
		watchlistExpiryOptions: ( state ) => state.watchlistExpiryOptions,
		watchDefault: ( state ) => state.watchDefault,
		title: ( state ) => state.storyTitle,
		thumbnails: ( state ) => {
			return state.frames.map( ( f, index ) => {
				const newFrame = $.extend( {}, f );
				if ( index === state.currentFrameIndex ) {
					newFrame.selected = true;
				}
				newFrame.style = makeFrameStyle( f, true );
				return newFrame;
			} );
		},
		frameCount: ( state ) => state.frames.length,
		currentFrame: ( state ) => {
			const f = state.frames[ state.currentFrameIndex ] || {};
			return {
				text: f.text,
				style: makeFrameStyle( f ),
				imgAttribution: f.attribution,
				fileNotFound: f.fileNotFound,
				textFromArticle: f.textFromArticle,
				lastEditedText: f.lastEditedText
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
				articleId: state.articleId,
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
		},
		editingText: ( state ) => state.editingText
	}
};
