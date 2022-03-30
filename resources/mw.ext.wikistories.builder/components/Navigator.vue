<template>
	<div class="ext-wikistories-navigator">
		<router-link
			v-if="history"
			class="ext-wikistories-navigator-back"></router-link>
		<div
			v-if="info"
			class="ext-wikistories-navigator-next"
			@click="next">
			{{ $i18n( 'wikistories-navigator-next' ).text() }}
		</div>
		<span v-if="info" class="ext-wikistories-navigator-info">{{ info }}</span>
	</div>
</template>

<script>
// @vue/component
module.exports = {
	name: 'Navigator',
	props: {
		next: { type: Function, default: () => {} },
		info: { type: String, default: '' }
	},
	computed: {
		history: function () {
			return this.$router.history.index !== -1;
		}
	}
};
</script>

<style lang="less">
.ext-wikistories-navigator {
	height: 40px;
	display: flex;
	justify-content: space-between;
	align-items: center;

	&-info {
		margin: 15px 0;
		font-size: 17.6px;
		line-height: 25px;
		color: #000;
	}

	&-back {
		background-image: url( ../images/back.svg );
		width: 16px;
		height: 16px;
		margin: 20px 0;
		cursor: pointer;
	}

	&-next {
		min-width: 16px;
		min-height: 16px;
		margin: 20px 0;
		cursor: pointer;
		font-weight: bold;
	}
}
</style>
