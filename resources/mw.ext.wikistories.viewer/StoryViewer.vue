<template>
	<div v-show="story.length" class="ext-wikistories-viewer-container">
		<div class="ext-wikistories-viewer-container-overlay" @click="discardStory"></div>
		<div class="ext-wikistories-viewer-container-content" :style="style">
			<div class="ext-wikistories-viewer-container-topbar"></div>
			<div
				class="ext-wikistories-viewer-container-content-close-icon"
				@click="discardStory"
			></div>
			<div class="ext-wikistories-viewer-container-content-progress">
				<div
					v-for="n in story.length"
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
			<image-attribution></image-attribution>
			<div
				v-if="isStoryEnd && !isLastStory"
				class="ext-wikistories-viewer-container-content-next-btn"
				@click="playNextStory">
				{{ $i18n( "wikistories-storyviewer-next-story-button" ).text() }}
			</div>
		</div>
	</div>
</template>

<script>

const mapGetters = require( 'vuex' ).mapGetters;
const mapActions = require( 'vuex' ).mapActions;
const ImageAttribution = require( './components/ImageAttribution.vue' );

// @vue/component
module.exports = {
	name: 'StoryViewer',
	components: {
		'image-attribution': ImageAttribution
	},
	props: {
		stories: { type: Array, default: () => [] },
		storyId: { type: String, default: '' },
		logStoryViewFn: { type: Function, default: () => {} }
	},
	data: function () {
		return {
			frameDuration: 5000,
			timeoutId: null,
			storyStart: 0
		};
	},
	computed: $.extend( mapGetters( [
		'story', 'currentFrame',
		'isStoryEnd', 'isLastStory', 'currentStoryTitle', 'imgAttribution'
	] ), {
		style: function () {
			return {
				backgroundImage: 'url(' + this.currentFrame.img + ')',
				backgroundPosition: 'center',
				backgroundSize: 'cover'
			};
		}
	} ),
	methods: $.extend( mapActions( [
		'setStories', 'setStoryId', 'nextStory',
		'nextFrame', 'resetFrame', 'setIsStoryEnd'
	] ), {
		playNextFrame: function () {
			this.timeoutId = setTimeout( function () {
				this.nextFrame();
			}.bind( this ), this.frameDuration );
		},
		playNextStory: function () {
			this.nextStory();
			this.storyStart = Date.now();
		},
		endStory: function () {
			this.timeoutId = setTimeout( function () {
				this.setIsStoryEnd( true );
				const storyOpenTime = Date.now() - this.storyStart;
				this.logStoryViewFn(
					this.currentStoryTitle,
					this.story.length,
					this.currentFrame.id,
					storyOpenTime,
					this.stories.length
				);
			}.bind( this ), this.frameDuration );
		},
		discardStory: function () {
			if ( !this.isStoryEnd ) {
				const storyOpenTime = Date.now() - this.storyStart;
				this.logStoryViewFn(
					this.currentStoryTitle,
					this.story.length,
					this.currentFrame.id,
					storyOpenTime,
					this.stories.length
				);
			}
			this.setStoryId( null );
			clearTimeout( this.timeoutId );
			window.location.hash = '';
		},
		preloadStory: function () {
			this.story.forEach( ( frame ) => {
				const img = new Image();
				img.src = frame.img;
			} );
		}
	} ),
	watch: {
		story: function ( newStory ) {
			if ( newStory.length ) {
				this.preloadStory();
				this.setIsStoryEnd( false );
				this.resetFrame();
			}
		},
		currentFrame: function () {
			if ( this.currentFrame.id < this.story.length ) {
				this.playNextFrame();
			} else if ( this.currentFrame.id === this.story.length ) {
				this.endStory();
			}
		}
	},
	created: function () {
		this.setStories( this.stories );
		this.setStoryId( this.storyId );
		this.storyStart = Date.now();
	}
};
</script>

<style lang="less">
@import 'mediawiki.ui/variables.less';

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

	&-topbar {
		position: absolute;
		right: 0;
		left: 0;
		height: 140px;
		background: linear-gradient( 180deg, rgba( 0, 0, 0, 0.35 ) 0%, rgba( 0, 0, 0, 0 ) 100% );
	}

	&-content {
		height: 100%;
		margin: 0 auto;
		position: relative;
		text-align: center;
		background-color: @colorGray1;

		@media screen and ( min-width: 720px ) {
			max-width: 993.3px;
		}

		&-story-text {
			position: absolute;
			bottom: 90px;
			left: 20px;
			right: 20px;
			border-radius: 2px;
			background: linear-gradient( 0deg, #fff, #fff, #fff );
			box-shadow: 0 2px 2px rgba( 0, 0, 0, 0.25 );
			margin: 0;
			padding: 10px;
			font-size: 18px;
			text-align: left;
			line-height: 27px;
		}

		&-next-btn {
			position: absolute;
			bottom: 50px;
			left: 0;
			right: 0;
			margin: auto;
			background-color: @color-primary;
			width: fit-content;
			border-radius: 2px;
			padding: 6px 12px;
			// stylelint-disable-next-line font-family-no-missing-generic-family-keyword
			font-family: 'Helvetica Neue';
			font-style: normal;
			font-weight: bold;
			font-size: 16px;
			line-height: 22px;
			color: #fff;
			cursor: pointer;
		}

		&-progress {
			position: relative;
			display: flex;
			flex-direction: row;
			padding: 8px 16px;

			&-container {
				height: 2px;
				flex-grow: 1;
				margin: 0 2px;
				display: flex;
				background-color: @colorGray10;

				&-loading {
					height: 100%;
					width: 100%;
					background-color: #fff;
					animation-name: ext-wikistories-viewer-progress-loading;
					animation-iteration-count: 1;
					/* TODO - ideally the animation duration is
						set as var related to frameDuration  */
					animation-duration: 5s;
					animation-timing-function: linear;
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
			background-image: url( ../images/close-white.svg );
			background-position: center;
			background-repeat: no-repeat;
			right: 10px;
			top: 18px;
		}
	}
}
</style>
