/**
 * @param {Object} event
 */
const beforeUnloadListener = ( event ) => {
	event.preventDefault();
	event.returnValue = '';
};

module.exports = beforeUnloadListener;
