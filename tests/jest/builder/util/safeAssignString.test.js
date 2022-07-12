const safeAssignString = require( '../../../../resources/ext.wikistories.builder/util/safeAssignString.js' );

describe( 'safeAssignString', function () {
	let obj;

	beforeEach( () => {
		obj = {};
	} );

	it( 'assigns a harmless value to a harmless prop on an object', () => {
		safeAssignString( obj, 'foo', 'bar' );
		expect( obj.foo ).toEqual( 'bar' );
	} );

	it( 'rejects falsy object', () => {
		expect( () => {
			safeAssignString( null, 'foo', 'bar' );
		} ).toThrow( /not an object/ );
	} );

	it( 'rejects a non-string value', () => {
		expect( () => {
			safeAssignString( obj, 'foo', {} );
		} ).toThrow( /not a string/ );
	} );

	[ 'prototype', '__proto__', 'constructor' ].forEach( ( prop ) => {
		it( 'rejects prop ' + prop, () => {
			expect( () => {
				safeAssignString( obj, prop, 'bar' );
			} ).toThrow( /illegal value/ );
		} );
	} );

} );
