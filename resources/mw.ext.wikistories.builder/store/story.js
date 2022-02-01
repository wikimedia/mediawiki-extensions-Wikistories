
const MAX_FRAMES = 5;
const INITIAL_FRAME_ID = 1;

const makeFrameStyle = f => {
  return f.img ?
    {
      backgroundImage: 'url(' + f.img + ')',
      backgroundPosition: 'center',
      backgroundSize: 'cover'
    } :
    { background: 'linear-gradient(338.27deg, #0BD564 -70.53%, #3366CC 71.84%)' }
};

const getNextFrameId = state => {
  if ( state.frames && state.frames.length > 0 ) {
    // Find the max frame id and increment by 1
    return state.frames.reduce( ( max, f ) => Math.max( max, f.id ), INITIAL_FRAME_ID ) + 1;
  }
  // No frames yet, start with id=1
  return INITIAL_FRAME_ID;
};

module.exports = {
  state: {
    storyTitle: null,
    creationDate: null,
    currentFrameId: INITIAL_FRAME_ID,
    frames: [
      {
        id: INITIAL_FRAME_ID,
        img: null,
        text: '',
        imgTitle: '',
        attribution: null
      }
    ]
  },
  mutations: {
    selectFrame: ( state, id ) => state.currentFrameId = id,
    addFrame: ( state ) => {
      if ( state.frames.length === MAX_FRAMES ) {
        return;
      }
      const newId = getNextFrameId( state );
      state.frames.push( { text:'', img: '', imgTitle: '', id: newId, attribution: null } );
      state.currentFrameId = newId;
    },
    resetFrame: ( state, frames ) => {
      state.frames = frames;
      state.currentFrameId = frames[ frames.length - 1 ].id;
    },
    setText: ( state, text ) => {
      const f = state.frames.find( f => f.id === state.currentFrameId );
      f.text = text;
    },
    setImg: ( state, img ) => {
      const f = state.frames.find( f => f.id === state.currentFrameId );
      f.img = img;
    },
    setImgTitle: ( state, title ) => {
      const f = state.frames.find( f => f.id === state.currentFrameId );
      f.imgTitle = title;
    },
    setImgAttribution: ( state, attribution ) => {
      // TODO: clarify that this should really find a frame by attribution id
      const f = state.frames.find( f => f.id === attribution.id );
      f.attribution = attribution;
    },
    setCreationDate: ( state, date ) => {
      state.creationDate = date;
    },
    updateStoryTitle: ( state, title ) => {
      state.storyTitle = title;
    }
  },
  actions: {
    selectFrame: ( context, id ) => {
      context.commit( 'selectFrame', id );
    },
    addFrame: ( context ) => {
      context.commit( 'addFrame' );
    },
    resetFrame: ( context, frames ) => {
      context.commit( 'resetFrame', frames );
    },
    setText: ( context, text ) => {
      context.commit( 'setText', text );
    },
    setImg: ( context, img ) => {
      context.commit( 'setImg', img );
    },
    setImgTitle: ( context, title ) => {
      context.commit( 'setImgTitle', title );
    },
    updateStoryTitle: ( context, title ) => {
      context.commit( 'updateStoryTitle', title );
    },
    fetchImgAttribution: async ( context, image ) => {
      const api = new mw.Api();
      api.get( {
        prop: 'imageinfo',
        iiextmetadatafilter: [ 'License', 'LicenseShortName', 'ImageDescription', 'Artist' ],
        iiextmetadatalanguage: mw.config.get( 'wgContentLanguage' ),
        iiextmetadatamultilang: 1,
        iiprop: [ 'url', 'extmetadata' ],
        titles: image.title
      } ).then( parsedAttribution => {
        const imageInfo = parsedAttribution.query.pages[0].imageinfo[0];
        if (imageInfo) {
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
    setCreationDate: ( context ) => {
      context.commit( 'setCreationDate', (new Date()).getTime() );
    }
  },
  getters: {
    thumbnails: ( state ) => {
      return state.frames.map( f => {
        const newFrame = $.extend( {}, f );
        if ( f.id === state.currentFrameId ) {
          newFrame.selected = true;
        }
        newFrame.style = makeFrameStyle( f );
        return newFrame;
      } );
    },
    currentFrame: ( state ) => {
      const isCoverFrame = state.currentFrameId === 0;
      const f = isCoverFrame ? state.frames[0] : state.frames.find( f => f.id === state.currentFrameId );
      return {
        text: isCoverFrame ? state.storyTitle : f.text,
        style: makeFrameStyle( f ),
        noImage: f.img === '',
        id: state.currentFrameId,
        imgAttribution: f.attribution,
        imgTitle: isCoverFrame ? state.storyTitle : f.imgTitle
      };
    },
    storyLength: state => state.frames.length,
    storyViewerLength: state => state.frames.length + 1,
    storyInfo: ( state ) => {
      return {
        title: state.storyTitle,
        creationDate: state.creationDate
      };
    },
    valid: ( state ) => {
      return state.frames.length >= 2 && state.frames.every( f => f.img && f.text );
    },
    attributionData: ( state ) => {
      return state.frames.map( f => {
        return {
          id: f.id,
          title: f.imgTitle,
          attribution: f.attribution
        };
      } );
    }
  }
};
