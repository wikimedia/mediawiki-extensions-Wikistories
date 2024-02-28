<template>
	<div
		class="ext-wikistories-dotsmenuitem skin-invert"
		:class="icon ? 'ext-wikistories-dotsmenuitem-' + icon : ''"
		@mousedown="onMousedown"
	>
		{{ text }}
	</div>
</template>

<script>
// @vue/component
module.exports = {
	name: 'MenuItem',
	props: {
		text: {
			type: String,
			required: true
		},
		icon: {
			type: String,
			required: false,
			default: null
		}
	},
	emits: [ 'click' ],
	methods: {
		onMousedown: function () {
			this.$emit( 'click' );
		}
	}
};
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.ext-wikistories-dotsmenuitem {
	cursor: pointer;
	white-space: nowrap;
	text-overflow: ellipsis;
	overflow: hidden;
	max-width: 50vw;
	margin: 0;
	font-size: 16px;
	padding: 6px 12px 6px 38px;
	background-repeat: no-repeat;
	background-position: 10px center;
	color: @color-base;
	background-color: @background-color-base;

	&:last-of-type {
		border: 0;
	}

	&-replace {
		background-image: url( ./images/replace.svg );
	}

	&-delete {
		background-image: url( ./images/delete.svg );
	}

	&-edit {
		background-image: url( ./images/edit.svg );
	}

	&-talk {
		background-image: url( ./images/talk.svg );
	}

	&-textsize {
		background-image: url( ./images/textsize.svg );
	}

	@media screen and ( min-width: 720px ) {
		max-width: 795px;
	}
}
</style>
