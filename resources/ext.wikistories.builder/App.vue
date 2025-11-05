<template>
	<div class="storybuilder-app" :style="style">
		<router-view></router-view>
	</div>
</template>

<script>
const beforeUnloadListener = require( './util/beforeUnloadListener.js' );
const RouterView = require( './components/RouterView.vue' );

// @vue/component
module.exports = {
	name: 'App',
	components: {
		'router-view': RouterView
	},
	data: function () {
		return { height: 0 };
	},
	computed: {
		style: function () {
			return { height: this.height + 'px' };
		}
	},
	methods: {
		updateHeight: function () {
			this.height = window.innerHeight - ( $( document.body ).find( 'header' ).height() || 0 );
		}
	},
	beforeMount: function () {
		this.updateHeight();
		window.addEventListener( 'resize', this.updateHeight );
		window.addEventListener( 'beforeunload', beforeUnloadListener );
	},
	unmounted: function () {
		window.removeEventListener( 'resize', this.updateHeight );
		window.removeEventListener( 'beforeunload', beforeUnloadListener );
	}
};
</script>
