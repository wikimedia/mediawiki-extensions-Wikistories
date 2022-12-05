const shallowRef = require( 'vue' ).shallowRef;
const Story = shallowRef( require( '../views/Story.vue' ) );
const Search = shallowRef( require( '../views/Search.vue' ) );
const PublishForm = shallowRef( require( '../views/PublishForm.vue' ) );
const Article = shallowRef( require( '../views/Article.vue' ) );

module.exports = {
	state: {
		routes: {
			story: {
				component: Story
			},
			searchOne: {
				component: Search,
				props: { mode: 'one' }
			},
			searchMany: {
				component: Search,
				props: { mode: 'many' }
			},
			publish: {
				component: PublishForm
			},
			article: {
				component: Article
			}
		},
		currentRouteName: ''
	},
	mutations: {
		setCurrentRoute: ( state, routeName ) => {
			if ( Object.prototype.hasOwnProperty.call( state.routes, routeName ) ) {
				state.currentRouteName = routeName;
			}
		}
	},
	actions: {
		routePush: ( context, routeName ) => {
			const url = new URL( window.location );
			url.hash = routeName;
			window.history.pushState( { wikistoryBuilderRoute: true }, '', url );
			context.commit( 'setCurrentRoute', routeName );
		},
		routeReplace: ( context, routeName ) => {
			const url = new URL( window.location );
			url.hash = routeName;
			window.history.replaceState( { wikistoryBuilderRoute: true }, '', url );
			context.commit( 'setCurrentRoute', routeName );
		},
		routeBack: () => {
			window.history.back();
		},
		init: ( context ) => {
			window.addEventListener( 'hashchange', () => {
				const routeName = window.location.hash.slice( 1 );
				context.commit( 'setCurrentRoute', routeName );
			} );
		}
	},
	getters: {
		currentRoute: ( state ) => state.routes[ state.currentRouteName ],
		isBuilderRouteAvailable: () => window.history.state.wikistoryBuilderRoute
	}
};
