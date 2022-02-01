<template>
	<button
		name="button"
		class="primary-button"
		@click="onClick">
		{{ text }}
	</button>
</template>

<script>
// @vue/component
module.exports = {
	name: 'PrimaryButton',
	props: {
		text: { type: String, required: true },
		onClick: { type: Function, required: true }
	}
};
</script>

<style lang="less">
.primary-button {
	background: #36c;
	border-radius: 2px;
	padding: 6px 12px;
	// stylelint-disable-next-line font-family-no-missing-generic-family-keyword
	font-family: 'Helvetica Neue';
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
