<template>
	<div class="storybuilder-story">
		<primary-button
			class="publish-button"
			:text="$i18n( 'wikistories-story-goto-publish' ).text()"
		></primary-button>
		<current-frame @edit="showPopup"></current-frame>
		<frames></frames>
		<div v-if="viewPopup" class="storybuilder-story-popup">
			<div class="storybuilder-story-popup-overlay" @click="hidePopup"></div>
			<div class="storybuilder-story-popup-content">
				<div class="storybuilder-story-popup-cue"></div>
				<article-view @text-selected="hidePopup"></article-view>
			</div>
		</div>
	</div>
</template>

<script>
const mapGetters = require( 'vuex' ).mapGetters;
const PrimaryButton = require( '../components/PrimaryButton.vue' );
const CurrentFrame = require( '../components/CurrentFrame.vue' );
const Article = require( '../components/Article.vue' );
const Frames = require( '../components/Frames.vue' );

// @vue/component
module.exports = {
	name: 'Story',
	components: {
		'primary-button': PrimaryButton,
		'current-frame': CurrentFrame,
		'article-view': Article,
		frames: Frames
	},
	data: function () {
		return {
			viewPopup: false
		};
	},
	computed: mapGetters( [ 'currentFrame' ] ),
	methods: {
		showPopup: function () {
			this.viewPopup = true;
		},
		hidePopup: function () {
			this.viewPopup = false;
		}
	}
};
</script>

<style lang="less">
.storybuilder-story {
	font-size: 24px;
	height: 100vh;
	overflow: hidden;
	position: relative;

	&-popup {
		position: absolute;
		height: 100vh;
		width: 100vw;
		top: 0;
		z-index: 102;

		&-overlay {
			background-color: #808080;
			position: absolute;
			height: 100vh;
			width: 100vw;
			opacity: 0.5;
			z-index: 103;
		}

		&-content {
			position: absolute;
			height: 90%;
			width: 100%;
			top: 10%;
			background-color: #fff;
			z-index: 104;
			border-radius: 14px;
		}

		&-cue {
			height: 3px;
			width: 30px;
			background-color: #808080;
			margin: auto;
			margin-top: 10px;
		}
	}
}

.publish-button {
	position: absolute;
	right: 24px;
	top: 24px;
}
</style>
