const Story = require( '../views/Story.vue' );
const Search = require( '../views/Search.vue' );
const PublishForm = require( '../views/PublishForm.vue' );
const Article = require( '../views/Article.vue' );

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
		routeStack: []
	},
	mutations: {
		setCurrentRoute: ( state, data ) => {
			if ( data.replace ) {
				const index = Math.max( state.routeStack.length - 1, 0 );
				state.routeStack[ index ] = data.routeName;
			} else {
				state.routeStack.push( data.routeName );
			}
		},
		routeBack: ( state ) => state.routeStack.pop()
	},
	actions: {
		routePush: ( context, routeName ) => {
			context.commit( 'setCurrentRoute', { routeName: routeName } );
		},
		routeReplace: ( context, routeName ) => {
			context.commit( 'setCurrentRoute', { routeName: routeName, replace: true } );
		},
		routeBack: ( context ) => {
			context.commit( 'routeBack' );
		}
	},
	getters: {
		currentRoute: ( state ) => state.routes[ state.routeStack[ state.routeStack.length - 1 ] ],
		routeStackLength: ( state ) => state.routeStack.length
	}
};
