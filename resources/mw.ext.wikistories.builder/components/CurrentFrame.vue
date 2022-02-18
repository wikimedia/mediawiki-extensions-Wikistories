<template>
	<div class="current-frame" :style="currentFrame.style">
		<div class="story-text">
			<div class="select-text-btn" @click="$emit( 'edit' )">
				{{ $i18n( 'wikistories-story-edittext' ).text() }}
			</div>
			<p @click="selectTextFromArticle" v-html="currentFrame.text"></p>
		</div>
		<image-attribution></image-attribution>
	</div>
</template>

<script>
const mapGetters = require( 'vuex' ).mapGetters;
const ImageAttribution = require( './ImageAttribution.vue' );

// @vue/component
module.exports = {
	name: 'CurrentFrame',
	components: {
		'image-attribution': ImageAttribution
	},
	emits: [ 'edit' ],
	computed: mapGetters( [ 'currentFrame' ] )
};
</script>

<style lang="less">
.current-frame {
	height: 90%;
	position: relative;
	text-align: center;

	.story-text {
		position: absolute;
		bottom: 60px;
		left: 20px;
		right: 20px;
		border-radius: 10px;
		background-color: #fff;
		margin: 0;
		padding: 10px;

		p {
			padding: 0;
		}

		.select-text-btn {
			display: block;
			width: 100%;
			font-size: 0.5em;
			text-align: end;
		}
	}
}
</style>
