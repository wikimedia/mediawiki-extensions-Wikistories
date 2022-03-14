<template>
	<div class="ext-wikistories-viewer-container">
		<div
			v-if="story.length"
			class="ext-wikistories-viewer-container-overlay"
			@click="discardStory"
		></div>
		<div
			v-if="story.length"
			class="ext-wikistories-viewer-container-content"
			:style="style"
		>
			<div
				class="ext-wikistories-viewer-container-content-close-icon"
				@click="discardStory"
			></div>
			<div class="ext-wikistories-viewer-container-content-progress">
				<div
					v-for="n in storyLength"
					:key="n"
					class="ext-wikistories-viewer-container-content-progress-container">
					<div
						v-if="currentFrame.id === n"
						class="ext-wikistories-viewer-container-content-progress-container-loading"
					></div>
					<div
						v-else-if="currentFrame.id > n"
						class="ext-wikistories-viewer-container-content-progress-container-loaded"
					></div>
				</div>
			</div>
			<div
				v-if="currentFrame.text"
				class="ext-wikistories-viewer-container-content-story-text">
				{{ currentFrame.text }}
			</div>
			<!--    <ImageAttribution />-->
			<div
				v-if="storyEnd"
				class="ext-wikistories-viewer-container-content-restart-btn"
				@click="restartStory">
				Replay
			</div>
		</div>
	</div>
</template>

<script>

const mapGetters = require( 'vuex' ).mapGetters;
const mapActions = require( 'vuex' ).mapActions;

// import ImageAttribution from '@components/ImageAttribution.vue'
// @vue/component
module.exports = {
	name: 'StoryViewer',
	components: {
		// ImageAttribution
	},
	props: {
		stories: { type: Array, default: () => [] },
		storyId: { type: String, default: '' }
	},
	data: function () {
		return {
			index: 1,
			frameDuration: 2000,
			storyEnd: false
		};
	},
	computed: $.extend( mapGetters( [ 'story' ] ), {
		currentFrame: function () { return this.story[ this.index - 1 ] || {}; },
		storyLength: function () { return this.story.length; },
		style: function () {
			return {
				backgroundImage: 'url(' + this.currentFrame.img + ')',
				backgroundPosition: 'center',
				backgroundSize: 'cover'
			};
		}
	} ),
	methods: $.extend( mapActions( [ 'setStories', 'setStoryId' ] ), {
		selectFrame: function ( i ) {
			this.index = i;
		},
		playNextFrame: function () {
			const timeoutId = setTimeout( function () {
				this.selectFrame( this.currentFrame.id + 1 );
				clearTimeout( timeoutId );
			}.bind( this ), this.frameDuration );
		},
		restartStory: function () {
			this.storyEnd = false;
			this.selectFrame( 1 );
		},
		endStory: function () {
			const timeoutId = setTimeout( function () {
				this.storyEnd = true;
				clearTimeout( timeoutId );
			}.bind( this ), this.frameDuration );
		},
		discardStory: function () {
			this.setStories( [] );
			this.setStoryId( null );
			// @todo mediawiki history solution?
			window.location.hash = '';
		}
	} ),
	beforeMount: function () {
		if ( this.currentFrame.id > 1 ) {
			this.restartStory();
		}
	},
	mounted: function () {
		if ( this.currentFrame.id < this.storyLength ) {
			this.playNextFrame();
		}
	},
	updated: function () {
		if ( this.currentFrame.id < this.storyLength ) {
			this.playNextFrame();
		} else if ( !this.storyEnd ) {
			this.endStory();
		}
	},
	created: function () {
		this.setStories( this.stories );
		this.setStoryId( this.storyId );
	}
};
</script>

<style lang="less">
.ext-wikistories-viewer-container {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	height: 100%;
	width: 100%;

	&-overlay {
		background-color: #00000087;
		position: absolute;
		height: 100%;
		width: 100%;
	}

	&-content {
		max-width: 500px;
		height: 100%;
		width: 100%;
		margin: 0 auto;
		position: relative;
		text-align: center;

		&-story-text {
			position: absolute;
			bottom: 90px;
			left: 20px;
			right: 20px;
			border-radius: 10px;
			background-color: #fff;
			margin: 0;
			padding: 10px;
		}

		&-restart-btn {
			position: absolute;
			bottom: 40px;
			left: 0;
			right: 0;
			margin: auto;
			background-color: #fff;
			padding: 8px;
			font-weight: bold;
			width: 90px;
			cursor: pointer;
		}

		&-progress {
			display: flex;
			flex-direction: row;
			width: 100%;
			padding: 10px 0;

			&-container {
				height: 4px;
				flex-grow: 1;
				margin: 0 5px;
				display: flex;
				background-color: #c4c4c4;

				&-loading {
					height: 100%;
					width: 100%;
					background-color: #fff;
					animation-name: ext-wikistories-viewer-progress-loading;
					animation-iteration-count: 1;
					/* TODO - ideally the animation duration is
						set as var related to frameDuration  */
					animation-duration: 2s;
				}

				&-loaded {
					height: 100%;
					width: 100%;
					background-color: #fff;
				}

				@keyframes ext-wikistories-viewer-progress-loading {
					from {
						width: 0%;
					}

					to {
						width: 100%;
					}
				}
			}
		}

		&-close-icon {
			position: absolute;
			cursor: pointer;
			width: 18px;
			height: 18px;
			padding: 15px;
			background-image: url( ./images/close-white.svg );
			background-position: center;
			background-repeat: no-repeat;
			right: 10px;
			top: 20px;
		}
	}
}
</style>
