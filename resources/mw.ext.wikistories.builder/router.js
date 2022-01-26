var VueRouter = require( '../lib/vue-router/vue-router.common.js' );
var Article = require( './views/Article.vue' );
var Story = require( './views/Story.vue' );
var Search = require( './views/Search.vue' );

window.process = {
  env: {
    NODE_ENV: mw.config.get('debug') ? 'development' : 'production'
  }
};

var routes = [
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
