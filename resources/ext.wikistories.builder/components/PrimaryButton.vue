<template>
	<button class="ext-wikistories-primarybutton" @click="$emit( 'click' )">
		{{ text }}
	</button>
</template>

<script>
// @vue/component
module.exports = {
	name: 'PrimaryButton',
	compatConfig: { MODE: 3 },
	compilerOptions: { whitespace: 'condense' },
	props: {
		text: { type: String, required: true }
	},
	emits: [ 'click' ]
};
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.ext-wikistories-primarybutton {
	background: @background-color-progressive;
	border-radius: @border-radius-base;
	padding: 6px 12px;
	font-family: 'Helvetica Neue', serif;
	font-style: normal;
	font-weight: bold;
	font-size: 16px;
	line-height: 22px;
	color: #fff;
	z-index: 101;
	flex: none;
	order: 0;
	flex-grow: 0;
	margin: 0 6px;
	border: 0;
	cursor: pointer;
}
</style>
