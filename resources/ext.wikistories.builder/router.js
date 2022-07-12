const VueRouter = require( './vue-router.common.js' );
const Story = require( './views/Story.vue' );
const Search = require( './views/Search.vue' );
const PublishForm = require( './views/PublishForm.vue' );
const Article = require( './views/Article.vue' );

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
	},
	{
		path: '/publish',
		name: 'PublishForm',
		component: PublishForm
	},
	{
		path: '/article',
		name: 'Article',
		component: Article
	}
];

module.exports = new VueRouter(
	{
		routes: routes,
		mode: 'abstract'
	}
);
