const isTouchDevice = 'ontouchstart' in window ||
	// Give me a break eslint, we are actually checking for support
	// eslint-disable-next-line compat/compat
	( navigator.maxTouchPoints > 0 ) ||
	( navigator.msMaxTouchPoints > 0 );

module.exports = isTouchDevice;
