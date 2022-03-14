const searchTools = require( '../api/searchImages.js' );
const searchImages = searchTools.searchImages;
const abortSearch = searchTools.abortSearch;

module.exports = {
	state: {
		selection: [],
		loading: false,
		results: [],
		query: ''
	},
	mutations: {
		setSelection: ( state, selection ) => { state.selection = selection; },
		setLoading: ( state, loading ) => { state.loading = loading; },
		setQuery: ( state, query ) => { state.query = query; },
		setResults: ( state, results ) => { state.results = results; }
	},
	actions: {
		search: ( context, query ) => {
			const queryString = query.trim();

			context.commit( 'setQuery', query );

			if ( !queryString ) {
				abortSearch();
				context.commit( 'setSelection', [] );
				context.commit( 'setLoading', false );
				context.commit( 'setResults', [] );
				return;
			}

			context.commit( 'setLoading', true );
			context.commit( 'setResults', [] );

			searchImages( queryString ).then( ( images ) => {
				context.commit( 'setResults', images );
				context.commit( 'setSelection', [] );
				context.commit( 'setLoading', false );
			} );
		},
		clear: ( context ) => {
			abortSearch();
			context.commit( 'setSelection', [] );
			context.commit( 'setLoading', false );
			context.commit( 'setResults', [] );
			context.commit( 'setQuery', '' );
		},
		select: ( context, data ) => {
			context.commit( 'setSelection', data );
		}
	},
	getters: {
		selection: ( state ) => state.selection,
		loading: ( state ) => state.loading,
		results: ( state ) => state.results,
		query: ( state ) => state.query,
		noResults: ( state ) => state.results.length < 1 && state.query && !state.loading
	}
};
