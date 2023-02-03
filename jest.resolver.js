module.exports = function ( path, options ) {
	switch ( path ) {
		case './vue-router.common.js':
			return options.basedir + '/../lib/vue-router/vue-router.common.js';
		case '../DotsMenu.vue':
			return options.basedir + '/../../components/DotsMenu.vue';
		case '../DotsMenuItem.vue':
			return options.basedir + '/../../components/DotsMenuItem.vue';
		case '../data.json':
			return 'data.json'; // this is needed for mocking the module in tests
		default:
			return options.defaultResolver( path, options );
	}
};
