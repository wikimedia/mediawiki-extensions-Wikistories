let abortFunctions = [];

const strip = html => {
	const doc = new window.DOMParser().parseFromString( html, 'text/html' )
	for ( const span of doc.querySelectorAll( 'span' ) ) {
		if ( span.style.display === 'none' ) {
			span.remove()
		}
	}
	return doc.body.textContent || ''
}

const convertUrlToMobile = url => {
	return url.replace( /https:\/\/(.*?)\./, ( subDomain ) => subDomain + 'm.' )
}

const request = ( url, params, callback ) => {
  abortAllRequest();

  const mwForeign = new mw.ForeignApi( url, { anonymous: true } );
  mwForeign.get( params ).done( function ( data ) {
    callback(data)
  } );

  abortFunctions.push( mwForeign );
}

const abortAllRequest = () => {
	abortFunctions.forEach( ( x ) => x && x.abort() );
	abortFunctions = [];
};


module.exports = {
  state: {
    selection: [],
    loading: false,
    results: [],
    query: ''
  },
  mutations: {
    setSelection: (state, selection) => state.selection = selection,
    setLoading: (state, loading) => state.loading = loading,
    setQuery: (state, query) => state.query = query,
    setResults: (state, results) => state.results = results,
  },
  actions: {
    search: (context, query) => {
      const queryString = query.trim();
      const lang = mw.config.get( 'wgContentLanguage' );
      const url = 'https://commons.wikimedia.org/w/api.php'
      const params = {
        action: 'query',
        format: 'json',
        uselang: lang,
        generator: 'search',
        gsrsearch: queryString,
        gsrlimit: 40,
        gsroffset: 0,
        gsrinfo: 'totalhits|suggestion',
        gsrprop: 'snippet',
        prop: 'imageinfo',
        gsrnamespace: 6,
        iiprop: 'url|extmetadata',
        iiurlheight: 180,
        iiextmetadatafilter: 'License|LicenseShortName|ImageDescription|Artist',
        iiextmetadatalanguage: lang
      }

      context.commit('setQuery', query)
      
      if ( !queryString ) {
        abortAllRequest();
        context.commit('setSelection', []);
        context.commit('setLoading', false);
        context.commit('setResults', []);
        return;
      }

      context.commit('setLoading', true);
      request( url, params, data => {
        if ( data.query && data.query.pages ) {
          const pages = Object.keys( data.query.pages ).map( p => data.query.pages[p] ).sort( ( a, b ) => a.index - b.index );
          context.commit('setResults', pages.map(p => {
            const imageinfo = p.imageinfo[0];
            const responsiveUrls = imageinfo.responsiveUrls && Object.keys( imageinfo.responsiveUrls ).map( p => imageinfo.responsiveUrls[p] )[0];
            const extmetadata = imageinfo.extmetadata;
            const description = extmetadata && extmetadata.ImageDescription && extmetadata.ImageDescription.value;
            const { Artist, LicenseShortName } = imageinfo.extmetadata;
            return {
              id: p.pageid.toString(),
              title: p.title,
              desc: description || p.snippet,
              thumb: responsiveUrls || imageinfo.url,
              width: imageinfo.thumbwidth,
              attribution: {
                author: Artist ? strip(Artist.value) : '',
                url: convertUrlToMobile(imageinfo.descriptionshorturl),
                license: LicenseShortName && LicenseShortName.value
              }
            }
          }))
        }
        context.commit('setSelection', []);
        context.commit('setLoading', false);
      })
      
    },
    clear: (context) => {
      abortAllRequest();
      context.commit('setSelection', []);
      context.commit('setLoading', false);
      context.commit('setResults', []);
      context.commit('setQuery', '');
    },
    select: ( context, data ) => {
        context.commit( 'setSelection', data );
    }
  },
  getters: {
    selection: (state) => state.selection,
    loading: (state) => state.loading,
    results: (state) => state.results,
    query: (state) => state.query
  }
}
