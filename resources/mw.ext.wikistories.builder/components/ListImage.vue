<template>
	<img
		:src="observerSupported ? false : source"
		:data-src="source"
		:alt="alt"
		loading="lazy"
	>
</template>

<script>
// Inspired from https://github.com/wikimedia/mediawiki-extensions-MediaSearch
const observer = require( '../mixins/observer.js' );

// @vue/component
module.exports = {
	name: 'ListImage',
	mixins: [ observer ],
	props: {
		source: {
			type: String,
			required: true
		},
		alt: {
			type: String,
			default: ''
		}
	},

	data: function () {
		return {
			debounceTimeoutId: null,
			/* eslint-disable vue/no-unused-properties */
			observerOptions: {
				// Set the detection area to extend past the bottom of the
				// viewport by 50% (a figure that comes from MobileFrontEnd) so
				// images will load before they enter the viewport.
				rootMargin: '0px 0px 50% 0px',
				threshold: 0
			}
			/* eslint-enable vue/no-unused-properties */
		};
	},

	methods: {
		loadImageIfIntersecting: function () {
			if ( this.observerIntersecting ) {
				// set the "src" attribute so the image loads
				this.$el.src = this.$el.dataset.src;
				this.disconnectObserver();
			}
		}
	},

	watch: {
		observerIntersecting: {
			handler: function ( intersecting ) {
				if ( intersecting ) {
					// debounce to avoid loading images that are rapidly scrolled
					// out of screen anyway
					clearTimeout( this.debounceTimeoutId );
					this.debounceTimeoutId = setTimeout(
						this.loadImageIfIntersecting.bind( this ),
						250
					);
				}
			},
			immediate: true
		}
	}
};
</script>
