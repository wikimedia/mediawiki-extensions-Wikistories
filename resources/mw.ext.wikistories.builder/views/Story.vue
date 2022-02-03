<template>
	<div class="storybuilder-story">
		<primary-button
			class="publish-button"
			:text="$i18n( 'wikistories-story-goto-publish' ).text()"
		></primary-button>
		<current-frame></current-frame>
		<frames></frames>
	</div>
</template>

<script>
const mapGetters = require( 'vuex' ).mapGetters;
const PrimaryButton = require( '../components/PrimaryButton.vue' );
const CurrentFrame = require( '../components/CurrentFrame.vue' );
const Frames = require( '../components/Frames.vue' );

// @vue/component
module.exports = {
	name: 'Story',
	components: {
		'primary-button': PrimaryButton,
		'current-frame': CurrentFrame,
		frames: Frames
	},
	computed: mapGetters( [ 'currentFrame' ] )
};
</script>

<style lang="less">
.storybuilder-story {
	font-size: 24px;
	height: 100vh;
	overflow: hidden;
	position: relative;
}

.publish-button {
	position: absolute;
	right: 24px;
	top: 24px;
}
</style>
