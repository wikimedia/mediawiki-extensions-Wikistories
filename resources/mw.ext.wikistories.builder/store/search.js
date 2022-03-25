const searchTools = require( '../api/searchImages.js' );
const searchImages = searchTools.searchImages;
const abortSearch = searchTools.abortSearch;

const debouncedSearch = mw.util.debounce( function ( context, queryString ) {
	context.commit( 'setLoading', true );
	searchImages( queryString ).then( ( images ) => {
		context.commit( 'setResults', images );
		context.commit( 'setSelection', [] );
		context.commit( 'setLoading', false );
	} );
}, 500 );

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
			context.commit( 'setResults', [] );
			abortSearch();
			context.commit( 'setQuery', query );
			context.commit( 'setResults', [] );
			context.commit( 'setSelection', [] );
			context.commit( 'setLoading', !!queryString );
			debouncedSearch( context, queryString );
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
