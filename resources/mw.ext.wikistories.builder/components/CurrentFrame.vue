<template>
	<div class="ext-wikistories-current-frame" :style="currentFrame.style">
		<div class="ext-wikistories-story-text">
			<div class="ext-wikistories-select-text-btn" @click="$emit( 'edit' )">
				{{ $i18n( 'wikistories-story-edittext' ).text() }}
			</div>
			<p @click="selectTextFromArticle">
				{{ currentFrame.text }}
			</p>
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
.ext-wikistories-current-frame {
	height: 90%;
	position: relative;
	text-align: center;

	.ext-wikistories-story-text {
		position: absolute;
		bottom: 60px;
		left: 20px;
		right: 20px;
		border-radius: 10px;
		background-color: #fff;
		margin: 0;
		padding: 10px;
		max-height: 40%;
		overflow: hidden;

		p {
			padding: 0;
		}

		.ext-wikistories-select-text-btn {
			display: block;
			width: 100%;
			font-size: 0.5em;
			text-align: end;
		}
	}
}
</style>
