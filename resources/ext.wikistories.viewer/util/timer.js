/**
 * @return {Object} Timer Object
 */
const Timer = function () {
	let timerId, start, callback, remaining;

	return {
		setup: function ( cb, delay ) {
			if ( timerId ) {
				window.clearTimeout( timerId );
			}
			callback = cb;
			remaining = delay;
			this.play();
		},
		pause: function () {
			window.clearTimeout( timerId );
			timerId = null;
			this.isPaused = true;
			remaining -= Date.now() - start;
		},
		play: function () {
			start = Date.now();
			this.isPaused = false;
			timerId = window.setTimeout( callback, remaining );
		},
		clear: function () {
			window.clearTimeout( timerId );
			this.isPaused = null;
		},
		isPaused: null
	};
};

module.exports = Timer;
