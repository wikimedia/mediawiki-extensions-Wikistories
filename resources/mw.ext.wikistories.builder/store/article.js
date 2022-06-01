const transforms = {
	'put styles in body': ( doc ) => {
		for ( const style of doc.head.querySelectorAll( 'link[rel="stylesheet"]' ) ) {
			doc.body.prepend( style );
		}
	},
	'remove scripts': ( doc ) => {
		for ( const n of doc.querySelectorAll( 'script' ) ) {
			n.remove();
		}
	},
	'turn links into plain text': ( doc ) => {
		let span;
		for ( const a of doc.querySelectorAll( 'a' ) ) {
			span = doc.createElement( 'span' );
			span.innerHTML = a.innerHTML;
			a.replaceWith( span );
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
			const url = 'https://' + lang + '.wikipedia.org/api/rest_v1/page/mobile-html/' + encodeURIComponent( title );
			fetch( url ).then( ( resp ) => resp.text() ).then( ( html ) => {
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
