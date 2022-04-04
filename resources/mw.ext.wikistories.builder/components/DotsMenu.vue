<template>
	<div class="ext-wikistories-dotsmenu">
		<div
			class="ext-wikistories-dotsmenu-button"
			tabindex="0"
			@click="toggle"
			@blur="onBlur"
		>
			&#xFE19;
		</div>
		<div v-show="expanded" class="ext-wikistories-dotsmenu-content">
			<slot></slot>
		</div>
	</div>
</template>

<script>
// @vue/component
module.exports = {
	name: 'DotsMenu',
	data: function () {
		return {
			expanded: false
		};
	},
	methods: {
		toggle: function () {
			this.expanded = !this.expanded;
		},
		onBlur: function () {
			this.expanded = false;
		}
	}
};
</script>

<style lang="less">
.ext-wikistories-dotsmenu {
	&-button {
		cursor: pointer;
		height: 40px;
		width: 40px;
		text-align: center;
		color: #fff;
	}

	&-content {
		background-color: #fff;
		position: absolute;
		right: 0;
		border: solid #000 1px;
	}
}
</style>
