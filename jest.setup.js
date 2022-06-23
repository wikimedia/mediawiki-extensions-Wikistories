/* eslint-disable no-implicit-globals */
/* global global, jest, mockLocalStorage */
// Assign things to "global" here if you want them to be globally available during tests
global.$ = require( 'jquery' );

// Mock MW object
global.mw = {
	config: {
		get: jest.fn( function ( key ) {
			switch ( key ) {
				case 'wgWikistoriesMinFrames':
					return 2;
				case 'wgWikistoriesMaxFrames':
					return 5;
				default:
					return {};
			}
		} )
	}
// other mw properties as needed...
};

// Mock i18n & store for all tests
var vueTestUtils = require( '@vue/test-utils' );
var vuex = require( 'vuex' );

class Mocki18n {
	constructor( string ) {
		this.string = string;
	}

	text() {
		return this.string;
	}
}

global.$i18n = jest.fn( function ( str ) {
	return new Mocki18n( str );
} );
global.getters = {};
global.state = {};
global.mutations = {};
global.actions = {};
global.modules = {};
global.store = vuex.createStore( {
	state() {
		return global.state;
	},
	getters: global.getters,
	mutations: global.mutations,
	actions: global.actions,
	modules: global.modules
} );

vueTestUtils.config.global.mocks = {
	$i18n: global.$i18n
};

vueTestUtils.config.global.plugins = [ global.store ];
