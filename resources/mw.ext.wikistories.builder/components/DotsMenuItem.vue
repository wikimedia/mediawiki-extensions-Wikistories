<template>
	<div class="ext-wikistories-dotsmenuitem" @mousedown="onMousedown">
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
	margin: 0;
	border-bottom: solid #000 1px;
	font-size: 20px;
	padding: 4px 10px;

	&:last-of-type {
		border: 0;
	}

	@media screen and ( min-width: 720px ) {
		max-width: 795px;
	}
}
</style>
