var VueRouter = require( '../lib/vue-router/vue-router.common.js' );
var Story = require( './views/Story.vue' );

window.process = {
  env: {
    NODE_ENV: mw.config.get('debug') ? 'development' : 'production'
  }
};

var routes = [
  {
    path: '/story',
    name: 'Story',
    component: Story
  }
];

module.exports = new VueRouter(
  {
    routes: routes,
    mode: 'abstract'
  }
);
