<template>
	<div class="storybuilder-app" :style="style">
		<router-view></router-view>
	</div>
</template>

<script>
// @vue/component
module.exports = {
	name: 'App',
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
			this.height = window.innerHeight - $( 'header' ).height();
		}
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
