const router = require( '../router.js' );

const MIN_FRAMES = mw.config.get( 'wgWikistoriesMinFrames' );
// const MAX_FRAMES = mw.config.get( 'wgWikistoriesMaxFrames' );

const makeFrameStyle = f => {
	return f.img ?
		{
			backgroundImage: 'url(' + f.img + ')',
			backgroundPosition: 'center',
			backgroundSize: 'cover'
		} :
		{ background: 'linear-gradient(338.27deg, #0BD564 -70.53%, #3366CC 71.84%)' };
};

const strip = ( html ) => {
	const doc = new window.DOMParser().parseFromString( html, 'text/html' );
	for ( const span of doc.querySelectorAll( 'span' ) ) {
		if ( span.style.display === 'none' ) {
			span.remove();
		}
	}
	return doc.body.textContent || '';
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
		},
		setImgAttribution: ( state, attribution ) => {
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
		setImg: ( context, img ) => {
			context.commit( 'setImg', img );
		},
		setImgTitle: ( context, title ) => {
			context.commit( 'setImgTitle', title );
		},
		fetchImgAttribution: function async( context, image ) {
			const api = new mw.Api();
			api.get( {
				prop: 'imageinfo',
				iiextmetadatafilter: [ 'License', 'LicenseShortName', 'ImageDescription', 'Artist' ],
				iiextmetadatalanguage: mw.config.get( 'wgContentLanguage' ),
				iiextmetadatamultilang: 1,
				iiprop: [ 'url', 'extmetadata' ],
				titles: image.title
			} ).then( parsedAttribution => {
				const imageInfo = parsedAttribution.query.pages[ 0 ].imageinfo[ 0 ];
				if ( imageInfo ) {
					const { Artist, LicenseShortName } = imageInfo.extmetadata;
					const attribution = {
						author: Artist ? strip( Artist.value ) : '',
						url: imageInfo.descriptionshorturl,
						license: LicenseShortName && LicenseShortName.value,
						id: image.id
					};
					context.commit( 'setImgAttribution', attribution );
				}
			} );
		},
		setFrameImage: ( context, data ) => {
			context.commit( 'setImg', data.thumb );
			context.commit( 'setImgTitle', data.title );
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
		attributionData: ( state ) => {
			return state.frames.map( f => {
				return {
					id: f.id,
					title: f.imgTitle,
					attribution: f.attribution
				};
			} );
		},
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
