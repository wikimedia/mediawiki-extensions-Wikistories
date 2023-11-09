/*!
 * Config plugin for Vue that connects to MediaWiki's client-side config system (mw.config).
 */

module.exports = {
	install: function ( app ) {
		/**
		 * Adds an `getConfig()` instance method that can be used in all components.
		 * This method is a proxy to `mw.config.get()`.
		 *
		 * Usage:
		 *     `<p>{{ getConfig( 'wgArticleId' ) }}</p>`
		 *     or
		 *     `this.getConfig( 'wgArticleId' );`
		 *
		 * @param {string} name Name of the config variable (usually starts with wg)
		 * @return {Mixed} Config value
		 */
		app.config.globalProperties.getConfig = function ( name ) {
			return mw.config.get( name );
		};
	}
};
