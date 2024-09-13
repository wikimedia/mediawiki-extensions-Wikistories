<template>
	<div
		class="ext-wikistories-dotsmenuitem"
		@mousedown="onMousedown"
	>
		<span
			class="ext-wikistories-dotsmenuitem-icon skin-invert"
			:class="icon ? 'ext-wikistories-dotsmenuitem-icon-' + icon : ''"
		></span>
		<span class="ext-wikistories-dotsmenuitem-text">{{ text }}</span>
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
	padding: 6px 12px;
	height: 24px;
	color: @color-base;
	background-color: @background-color-base;

	&:last-of-type {
		border: 0;
	}

	&-icon {
		background-repeat: no-repeat;
		background-position: center;
		vertical-align: middle;
		display: inline-block;
		height: 100%;
		width: 24px;

		&-replace {
			background-image: url( ../resources/images/replace.svg );
		}

		&-delete {
			background-image: url( ../resources/images/delete.svg );
		}

		&-edit {
			background-image: url( ../resources/images/edit.svg );
		}

		&-talk {
			background-image: url( ../resources/images/talk.svg );
		}

		&-textsize {
			background-image: url( ../resources/images/textsize.svg );
		}
	}

	&-text {
		height: 100%;
		display: inline-block;
	}

	@media screen and ( min-width: 720px ) {
		max-width: 795px;
	}
}
</style>
