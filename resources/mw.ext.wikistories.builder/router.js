const VueRouter = require( '../lib/vue-router/vue-router.common.js' );
const Story = require( './views/Story.vue' );
const Search = require( './views/Search.vue' );

window.process = {
	env: {
		NODE_ENV: mw.config.get( 'debug' ) ? 'development' : 'production'
	}
};

const routes = [
	{
		path: '/story',
		name: 'Story',
		component: Story
	},
	{
		path: '/search/:mode?',
		name: 'Search',
		component: Search,
		props: true
	}
];

module.exports = new VueRouter(
	{
		routes: routes,
		mode: 'abstract'
	}
);
