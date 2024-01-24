<template>
	<div class="ext-wikistories-toast">
		<notice :message="message" :mode="mode"></notice>
	</div>
</template>

<script>
const Notice = require( './Notice.vue' );

// @vue/component
module.exports = {
	name: 'Toast',
	components: {
		notice: Notice
	},
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
@import 'mediawiki.skin.variables.less';

.ext-wikistories-toast {
	position: absolute;
	margin: 0 15px;
	top: 103px;
	left: 0;
	right: 0;
	z-index: 105;
}
</style>
