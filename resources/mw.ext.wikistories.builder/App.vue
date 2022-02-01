<template>
	<div class="storybuilder-app">
		<router-view></router-view>
	</div>
</template>

<script>
// @vue/component
module.exports = {
	name: 'App'
};
</script>

<style lang="less">
	.storybuilder-app {
		background-color: #fff;
		position: absolute;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
	}
</style>
