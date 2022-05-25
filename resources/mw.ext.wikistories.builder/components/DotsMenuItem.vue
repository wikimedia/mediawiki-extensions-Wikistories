<template>
	<div
		class="ext-wikistories-dotsmenuitem"
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
		routeTo: {
			type: String,
			required: false,
			default: null
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
			if ( this.routeTo ) {
				this.$router.push( this.routeTo );
			}
		}
	}
};
</script>

<style lang="less">
.ext-wikistories-dotsmenuitem {
	cursor: pointer;
	white-space: nowrap;
	text-overflow: ellipsis;
	overflow: hidden;
	max-width: 80vw;
	width: 176px;
	margin: 0;
	font-size: 20px;
	padding: 6px 12px;
	padding-left: 38px;
	background-repeat: no-repeat;
	background-position: 12px 13px;

	&:last-of-type {
		border: 0;
		padding-bottom: 12px;
	}

	&-replace {
		background-image: url( ../../images/replace.svg );
	}

	&-delete {
		background-image: url( ../../images/delete.svg );
	}

	@media screen and ( min-width: 720px ) {
		max-width: 795px;
	}
}
</style>
