<template>
	<div class="ext-wikistories-toast" :class="{ 'ext-wikistories-toast-error': mode === 'error' }">
		<div class="ext-wikistories-toast-icon"></div>
		<div class="ext-wikistories-toast-message">
			{{ message }}
		</div>
	</div>
</template>

<script>
// @vue/component
module.exports = {
	name: 'Toast',
	props: {
		message: { type: String, required: true },
		mode: { type: String, required: false, default: 'warning' }
	},
	emits: [ 'hide-toast' ],
	data: function () {
		return {
			timeoutId: null,
			duration: 8000
		};
	},
	mounted: function () {
		this.timeoutId = setTimeout( function () {
			this.$emit( 'hide-toast' );
		}.bind( this ), this.duration );
	},
	unmounted: function () {
		clearTimeout( this.timeoutId );
	}
};
</script>

<style lang="less">
@import 'mediawiki.ui/variables.less';

.ext-wikistories-toast {
	background-color: @background-color-warning;
	position: absolute;
	margin: 0 15px;
	padding: 20px;
	top: 103px;
	left: 0;
	right: 0;
	z-index: 100;
	border: 1px solid @border-color-warning;
	border-radius: 2px;
	display: flex;

	&-icon {
		background-image: url( ./../images/union.svg );
		background-repeat: no-repeat;
		height: 18px;
		width: 26px;
		margin-right: 10px;
		margin-top: 4px;
	}

	&-message {
		font-size: 16px;
		width: 90%;
		line-height: 22px;
	}
}

.ext-wikistories-toast.ext-wikistories-toast-error {
	background-color: @background-color-error;
	border: 1px solid @border-color-error;
	top: 60px;

	.ext-wikistories-toast-icon {
		background-image: url( ./../images/error.svg );
		height: 20px;
	}

	.ext-wikistories-toast-message {
		color: @colorGray1;
	}
}
</style>
