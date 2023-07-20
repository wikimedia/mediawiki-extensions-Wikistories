const MIN_FRAMES = mw.config.get( 'wgWikistoriesMinFrames' );
const MAX_FRAMES = mw.config.get( 'wgWikistoriesMaxFrames' );
const MAX_TEXT_LENGTH = mw.config.get( 'wgWikistoriesMaxTextLength' );
const storyContent = mw.config.get( 'wgWikistoriesStoryContent' );
const storyEditMode = mw.config.get( 'wgWikistoriesMode' );
const lang = mw.config.get( 'wgContentLanguage' );
const watchDefault = mw.config.get( 'wgWikistoriesWatchDefault' );
const watchlistExpiryEnabled = mw.config.get( 'wgWikistoriesWatchlistExpiryEnabled' );
const watchlistExpiryOptions = mw.config.get( 'wgWikistoriesWatchlistExpiryOptions' );
const TEXT_EDIT_THRESHOLD = mw.config.get( 'wgWikistoriesUnmodifiedTextThreshold' );

const searchTools = require( '../api/searchImages.js' );
const calculateUnmodifiedContent = require( '../util/calculateUnmodifiedContent.js' );
const getImageExtMetadata = searchTools.getImageExtMetadata;

let orderKey = 10;

module.exports = {
	state: {
		fromArticle: storyContent.articleTitle,
		articleId: storyContent.articleId,
		storyPageId: null,
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
			const frame = state.frames[ state.currentFrameIndex ];
			const truncatedText = text.slice( 0, MAX_TEXT_LENGTH );
			if ( frame.text !== truncatedText ) {
				frame.text = truncatedText;
				frame.outdatedText = false;
			}
		},
		setTextFromArticle: ( state, textFromArticle ) => {
			const frame = state.frames[ state.currentFrameIndex ];
			frame.textFromArticle = textFromArticle.slice( 0, MAX_TEXT_LENGTH );
			frame.outdatedText = false;
		},
		setLastEditedText: ( state, text ) => {
			const frame = state.frames[ state.currentFrameIndex ];
			frame.lastEditedText = text.slice( 0, MAX_TEXT_LENGTH );
		},
		setImageFocalRect: ( state, rect ) => {
			state.frames[ state.currentFrameIndex ].focalRect = rect;
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
		},
		setWarningFrames: ( state, payload ) => {
			state.frames.forEach( ( frame, index ) => {
				// key: message, icon, isAlwaysShown, replace
				frame.warning = payload[ index ];
			} );
		},
		setStoryPageId: ( state, value ) => {
			state.storyPageId = value;
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
			context.dispatch( 'checkWarningStatus' );
		},
		setTextFromArticle: ( context, textFromArticle ) => {
			context.commit( 'setTextFromArticle', textFromArticle );
			context.dispatch( 'checkWarningStatus' );
		},
		setLastEditedText: ( context, text ) => {
			context.commit( 'setLastEditedText', text );
		},
		setImageFocalRect: ( context, rect ) => {
			context.commit( 'setImageFocalRect', rect );
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
			context.dispatch( 'checkWarningStatus' );
		},
		checkWarningStatus: ( context ) => {
			const frames = context.getters.frames;
			const editingText = context.getters.editingText;

			const duplicates = {};
			const warningMessage = {};
			const duplicate = {
				message: mw.msg( 'wikistories-story-edittext-duplicate' ),
				icon: 'warning',
				isAlwaysShown: true,
				replace: true
			};

			for ( let i = 0; i < frames.length; i++ ) {
				const frame = frames[ i ];

				// skip when no text
				if ( !frame.text ) {
					continue;
				}

				// check if frame text is outdated (based on older version of article)
				if ( frame.outdatedText ) {
					warningMessage[ i ] = {
						message: mw.msg( 'wikistories-story-edittext-outdated' ),
						icon: 'warning',
						isAlwaysShown: true,
						replace: true
					};
					continue;
				}

				// check if frames has duplicate story text when it is not editing text
				if ( !editingText ) {
					const currentValue = frame.text;
					if ( duplicates[ currentValue ] !== undefined ) {
						warningMessage[ i ] = duplicate;
						if ( duplicates[ currentValue ] !== null ) {
							warningMessage[ duplicates[ currentValue ] ] = duplicate;
							duplicates[ currentValue ] = null;
						}
					} else {
						duplicates[ currentValue ] = i;
					}

					// skip when duplication found
					if ( warningMessage[ i ] ) {
						continue;
					}
				}

				// check edit guide messages
				const unmodified = calculateUnmodifiedContent( frame.textFromArticle, frame.text );
				warningMessage[ i ] = {};
				if ( unmodified === 1 ) {
					warningMessage[ i ].message = mw.msg( 'wikistories-story-edittext-initial' );
					warningMessage[ i ].icon = 'edit_reference';
				} else if ( unmodified < TEXT_EDIT_THRESHOLD ) {
					warningMessage[ i ].message = mw.msg( 'wikistories-story-edittext-last' );
					warningMessage[ i ].icon = 'alert';
				} else {
					warningMessage[ i ].message = mw.msg( 'wikistories-story-edittext-medium' );
					warningMessage[ i ].icon = 'alert';
				}
			}

			context.commit( 'setWarningFrames', warningMessage );
		},
		setStoryPageId: ( context, value ) => {
			context.commit( 'setStoryPageId', value );
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
				return newFrame;
			} );
		},
		frameCount: ( state ) => state.frames.length,
		currentFrame: ( state ) => {
			const f = state.frames[ state.currentFrameIndex ] || {};
			return {
				text: f.text,
				imageSrc: f.url,
				imgFocalRect: f.focalRect,
				imgAttribution: f.attribution,
				fileNotFound: f.fileNotFound,
				textFromArticle: f.textFromArticle,
				lastEditedText: f.lastEditedText,
				warning: f.warning,
				filename: f.filename
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

					if ( f.focalRect ) {
						frame.image.focalRect = f.focalRect;
					}

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
		editingText: ( state ) => state.editingText,
		storyUrl: ( state ) => {
			const titleObj = mw.Title.newFromText( state.fromArticle + '#/story/' + state.storyPageId );
			return titleObj ? titleObj.getUrl() : '';
		}
	}
};
