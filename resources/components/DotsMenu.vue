<template>
	<div class="ext-wikistories-dotsmenu">
		<div
			class="ext-wikistories-dotsmenu-button"
			tabindex="0"
			@click="toggle"
			@blur="onBlur"
		></div>
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
@import 'mediawiki.ui/variables.less';

.ext-wikistories-dotsmenu {
	&-button {
		cursor: pointer;
		height: 48px;
		width: 48px;
		text-align: center;
		color: #fff;
		background-image: url( ../images/menu.svg );
		background-position: center;
	}

	&-content {
		background-color: #fff;
		position: absolute;
		right: 0;
		top: 0;
		border: solid @colorGray10 1px;
		box-shadow: 0 2px 2px 0 rgba( 0, 0, 0, 0.25 );
		border-radius: 0 0 2px 2px;
	}
}
</style>
