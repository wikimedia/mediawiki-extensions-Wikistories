/**
 * Throws an error with a standard message combining the parameter name
 * and the specific error message.
 *
 * @param {string} param
 * @param {string} msg
 */
const reject = ( param, msg ) => {
	throw new Error( 'safeAssignString error: ' + param + ': ' + msg );
};

/**
 * Assigns a string to an arbitrary property of an object
 * and try to avoid prototype pollution.
 *
 * @param {Object} obj
 * @param {string} prop
 * @param {string} value
 */
const safeAssignString = ( obj, prop, value ) => {
	if ( !obj ) {
		reject( 'obj', 'not an object' );
	}

	if ( typeof prop !== 'string' ) {
		reject( 'prop', 'not a string' );
	}

	const illegalProps = [
		'prototype',
		'__proto__',
		'constructor'
	];
	prop = prop.trim().toLowerCase();
	if ( illegalProps.indexOf( prop ) !== -1 ) {
		reject( 'prop', 'illegal value' );
	}

	if ( typeof value !== 'string' ) {
		reject( 'value', 'not a string' );
	}

	obj[ prop ] = value;
};

module.exports = safeAssignString;
