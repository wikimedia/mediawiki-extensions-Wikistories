const VueRouter = require( '../lib/vue-router/vue-router.common.js' );
const Article = require( './views/Article.vue' );
const Story = require( './views/Story.vue' );
const Search = require( './views/Search.vue' );

window.process = {
	env: {
		NODE_ENV: mw.config.get( 'debug' ) ? 'development' : 'production'
	}
};

const routes = [
	{
		path: '/article/:article?',
		name: 'Article',
		component: Article,
		props: true
	},
	{
		path: '/story',
		name: 'Story',
		component: Story
	},
	{
		path: '/search',
		name: 'Search',
		component: Search
	}
];

module.exports = new VueRouter(
	{
		routes: routes,
		mode: 'abstract'
	}
);
