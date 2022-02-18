<template>
	<div class="navigator">
		<router-link
			v-if="history"
			to="/story"
			class="back"></router-link>
		<div
			v-if="info > 0"
			class="next"
			@click="next">
			{{ $i18n( 'wikistories-navigator-next' ).text() }}
		</div>
		<span v-if="info" class="info">{{ info }}</span>
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
.navigator {
	position: relative;
	height: 40px;
}

.navigator .info {
	position: absolute;
	margin: 15px 0;
	left: 25px;
	// stylelint-disable-next-line font-family-no-missing-generic-family-keyword
	font-family: 'Helvetica Neue';
	font-style: normal;
	font-weight: normal;
	font-size: 17.6px;
	line-height: 25px;
	color: #000;
}

.navigator .back {
	background-image: url( ../images/back.svg );
	position: absolute;
	width: 16px;
	height: 16px;
	left: 0;
	margin: 20px 0;
	cursor: pointer;
}

.navigator .next {
	position: absolute;
	min-width: 16px;
	min-height: 16px;
	right: 0;
	margin: 20px;
	cursor: pointer;
	font-weight: bold;
}
</style>
