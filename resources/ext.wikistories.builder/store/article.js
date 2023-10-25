const splitSentences = require( '../util/splitSentences.js' );

const transforms = {
	'put styles in body': ( doc ) => {
		for ( const style of doc.head.querySelectorAll( 'link[rel="stylesheet"]' ) ) {
			doc.body.prepend( style );
		}
	},
	'remove stuff': ( doc ) => {
		const selector = [
			'script',
			'figure',
			'table',
			'sup',
			'.pcs-collapse-table-container',
			'.thumb',
			'.hatnote',
			"[ role='navigation' ]"
		].join( ',' );
		for ( const n of doc.querySelectorAll( selector ) ) {
			n.remove();
		}
	},
	'turn links into plain text': ( doc ) => {
		for ( const a of doc.querySelectorAll( 'a' ) ) {
			a.replaceWith( a.innerHTML );
		}
	},
	'remove phonetic notations': ( doc ) => {
		for ( const p of doc.querySelectorAll( 'p' ) ) {
			p.innerHTML = p.innerHTML.replace( /\s\(.*?class=".*?(ext-phonos|IPA).*?".*?\)/g, '' );
		}
	},
	'remove sections after fold and the fold itself': ( doc ) => {
		const foldHr = doc.querySelector( '.pcs-fold-hr' );
		for ( const section of doc.querySelectorAll( '.pcs-fold-hr ~ section' ) ) {
			section.remove();
		}
		if ( foldHr ) {
			foldHr.remove();
		}
	},
	'Split and wrap sentences': ( doc ) => {
		for ( const p of doc.querySelectorAll( 'p, li' ) ) {
			if ( p.textContent.trim() !== '' ) {
				p.innerHTML = splitSentences( p, 'ext-wikistories-article-view-content-sentence' );
			}
		}
	}
};

module.exports = {
	state: {
		article: {
			title: '',
			html: ''
		}
	},
	mutations: {
		setArticle: ( state, data ) => {
			state.article.title = data.title;
			state.article.html = data.html;
		}
	},
	actions: {
		fetchArticle: ( context, title ) => {
			if ( context.state.article.title === title && context.state.article.html ) {
				// already loaded
				return;
			}
			const lang = mw.config.get( 'wgContentLanguage' );
			const REST_DOMAIN = mw.config.get( 'wgWikistoriesRestDomain' );
			const endpointPath = '/api/rest_v1/page/mobile-html/' + encodeURIComponent( title );
			const url = REST_DOMAIN === null ?
				endpointPath :
				'https://' + lang + '.' + REST_DOMAIN + endpointPath;

			return fetch( url ).then( ( resp ) => {
				if ( resp.ok ) {
					return resp.text();
				} else {
					throw new Error();
				}
			} ).then( ( html ) => {
				const doc = new DOMParser().parseFromString( html, 'text/html' );
				Object.keys( transforms ).forEach( ( key ) => {
					transforms[ key ]( doc );
				} );
				context.commit( 'setArticle', { title: title, html: doc.body.outerHTML } );
			} );
		}
	},
	getters: {
		currentArticle: ( state ) => state.article
	}
};
