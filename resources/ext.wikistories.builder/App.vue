<template>
	<div class="storybuilder-app" :style="style">
		<router-view></router-view>
	</div>
</template>

<script>
const events = require( './contributionEvents.js' );
const RouterView = require( './components/RouterView.vue' );
const mapGetters = require( 'vuex' ).mapGetters;

// @vue/component
module.exports = {
	name: 'App',
	components: {
		'router-view': RouterView
	},
	data: function () {
		return { height: 0 };
	},
	computed: $.extend( mapGetters( [ 'storyExists' ] ), {
		style: function () {
			return { height: this.height + 'px' };
		}
	} ),
	methods: {
		updateHeight: function () {
			this.height = window.innerHeight - ( $( 'header' ).height() || 0 );
		}
	},
	created: function () {
		events.logStoryBuilderOpen( this.storyExists );
	},
	beforeMount: function () {
		this.updateHeight();
		window.addEventListener( 'resize', this.updateHeight );
	},
	unmounted: function () {
		window.removeEventListener( 'resize', this.updateHeight );
	}
};
</script>
