<template>
	<div class="ext-wikistories-popup">
		<div class="ext-wikistories-popup-overlay" @click="$emit( 'overlay-click' )"></div>
		<div class="ext-wikistories-popup-content">
			<slot></slot>
		</div>
	</div>
</template>

<script>
// @vue/component
module.exports = {
	name: 'Popup',
	compatConfig: { MODE: 3 },
	compilerOptions: { whitespace: 'condense' },
	emits: [ 'overlay-click' ]
};
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.ext-wikistories-popup {
	position: absolute;
	width: 100%;
	top: 0;
	bottom: 0;
	z-index: 102;

	&-overlay {
		background-color: #808080;
		position: absolute;
		height: 100%;
		width: 100%;
		opacity: 0.5;
		z-index: 103;
	}

	&-content {
		position: absolute;
		height: 90%;
		width: 100%;
		top: 10%;
		background-color: @background-color-base;
		z-index: 104;
		border-radius: 14px 14px 0 0;
	}
}
</style>
