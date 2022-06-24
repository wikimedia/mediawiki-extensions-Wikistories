<template>
	<div v-show="story.length" class="ext-wikistories-viewer-container">
		<div
			class="ext-wikistories-viewer-container-overlay"
			@click="discardStory"
		></div>
		<div
			class="ext-wikistories-viewer-container-content"
			:style="style"
			@click="navigateFrame">
			<div
				v-if="isFirstFrame"
				class="ext-wikistories-viewer-container-cover-overlay"
			></div>
			<div class="ext-wikistories-viewer-container-topbar"></div>
			<div
				class="ext-wikistories-viewer-container-content-close-icon"
				@click="discardStory"
			></div>
			<div
				v-if="!isStoryEnd"
				:class="{
					'ext-wikistories-viewer-container-content-pause-icon': !timer.isPaused,
					'ext-wikistories-viewer-container-content-play-icon': timer.isPaused
				}"
				@click="toggleStory"
			></div>
			<dots-menu class="ext-wikistories-viewer-container-menu">
				<dots-menu-item
					:text="$i18n( 'wikistories-storyviewer-edit' ).text()"
					icon="edit"
					@click="edit"
				></dots-menu-item>
			</dots-menu>
			<div class="ext-wikistories-viewer-container-content-progress">
				<div
					v-for="n in story.length"
					:key="n"
					class="ext-wikistories-viewer-container-content-progress-container">
					<div
						v-if="isFramePlaying( n )"
						class="ext-wikistories-viewer-container-content-progress-container-loading"
						:style="{ 'animation-play-state': timer.isPaused ? 'paused' : 'running' }"
					></div>
					<div
						v-else-if="isFrameDonePlaying( n )"
						class="ext-wikistories-viewer-container-content-progress-container-loaded"
					></div>
				</div>
			</div>
			<div
				v-if="currentFrame.text"
				class="ext-wikistories-viewer-container-content-story-text">
				{{ currentFrame.text }}
			</div>
			<div
				v-if="isFirstFrame"
				class="ext-wikistories-viewer-container-content-story-cover">
				<div
					class="ext-wikistories-viewer-container-content-story-cover-wikistory">
					{{ $i18n( 'wikistories-storyviewer-cover-page-heading' ).text() }}
				</div>
				<div
					class="ext-wikistories-viewer-container-content-story-cover-title">
					{{ currentStoryTitle }}
				</div>
			</div>
			<image-attribution></image-attribution>
			<div
				v-if="isStoryEnd && !isLastStory"
				class="ext-wikistories-viewer-container-content-next-btn"
				@click="playNextStory">
				{{ $i18n( "wikistories-storyviewer-next-story-button" ).text() }}
			</div>
		</div>
		<div
			v-if="!isFirstFrame"
			class="ext-wikistories-viewer-container-prev"
			@click="prevFrame"
		></div>
		<div
			v-if="!isLastFrame"
			class="ext-wikistories-viewer-container-next"
			@click="nextFrame"
		></div>
	</div>
</template>

<script>

const mapGetters = require( 'vuex' ).mapGetters;
const mapActions = require( 'vuex' ).mapActions;
const ImageAttribution = require( './components/ImageAttribution.vue' );
const DotsMenu = require( '../../components/DotsMenu.vue' );
const DotsMenuItem = require( '../../components/DotsMenuItem.vue' );
const Timer = require( './util/timer.js' );
const isTouchDevice = require( './util/isTouchDevice.js' );

// @vue/component
module.exports = {
	name: 'StoryViewer',
	components: {
		'image-attribution': ImageAttribution,
		'dots-menu': DotsMenu,
		'dots-menu-item': DotsMenuItem
	},
	props: {
		stories: { type: Array, default: () => [] },
		storyId: { type: String, default: '' },
		logStoryViewFn: { type: Function, default: () => {} }
	},
	data: function () {
		return {
			frameDuration: 5000,
			timer: new Timer(),
			storyStart: 0,
			frameViewedCount: 0
		};
	},
	computed: $.extend( mapGetters( [
		'story', 'currentFrame', 'editUrl',
		'isStoryEnd', 'isLastStory', 'isFirstFrame', 'isLastFrame',
		'currentStoryTitle'
	] ), {
		style: function () {
			return {
				backgroundImage: 'url(' + this.currentFrame.url + ')',
				backgroundPosition: 'center',
				backgroundSize: 'cover'
			};
		}
	} ),
	methods: $.extend( mapActions( [
		'setStories', 'setStoryId', 'nextStory',
		'prevFrame', 'nextFrame', 'resetFrame', 'setIsStoryEnd'
	] ), {
		playNextFrame: function () {
			this.timer.setup( function () {
				this.nextFrame();
			}.bind( this ), this.frameDuration );
		},
		playNextStory: function () {
			this.logStoryViewEvent();
			this.nextStory();
		},
		logStoryViewEvent: function () {
			const storyOpenTime = Date.now() - this.storyStart;
			this.logStoryViewFn(
				this.currentStoryTitle,
				this.story.length,
				this.frameViewedCount,
				storyOpenTime,
				this.stories.length
			);
			this.storyStart = Date.now();
			this.frameViewedCount = 0;
		},
		endStory: function () {
			this.timer.setup( function () {
				this.setIsStoryEnd( true );
			}.bind( this ), this.frameDuration );
		},
		discardStory: function () {
			this.logStoryViewEvent();
			this.setStoryId( null );
			this.timer.clear();
			window.location.hash = '';
		},
		toggleStory: function () {
			if ( this.timer.isPaused ) {
				this.timer.play();
			} else {
				this.timer.pause();
			}
		},
		preloadStory: function () {
			this.story.forEach( ( frame ) => {
				const img = new Image();
				img.src = frame.url;
			} );
		},
		navigateFrame: function ( e ) {

			if ( !isTouchDevice ) {
				return;
			}

			const pressTargetClassName = e.target.className;

			if (
				pressTargetClassName === 'ext-wikistories-viewer-container-content' ||
				pressTargetClassName === 'ext-wikistories-viewer-container-cover-overlay'
			) {
				const screenWidth = window.innerWidth;
				const pressAxisX = e.clientX;

				if ( pressAxisX >= screenWidth / 2 ) {
					this.nextFrame();
				} else {
					this.prevFrame();
				}
			}

		},
		edit: function () {
			this.logStoryViewEvent();
			window.location = this.editUrl;
		},
		isFramePlaying: function ( n ) {
			return this.currentFrame.id === n - 1;
		},
		isFrameDonePlaying: function ( n ) {
			return this.currentFrame.id > n - 1;
		}
	} ),
	watch: {
		story: function ( newStory ) {
			if ( newStory.length ) {
				this.preloadStory();
				this.setIsStoryEnd( false );
				this.resetFrame();
				this.storyStart = Date.now();
				this.frameViewedCount = 0;
			}
		},
		currentFrame: function () {
			if ( this.currentFrame.id < this.story.length - 1 ) {
				this.playNextFrame();
				this.setIsStoryEnd( false );
			} else if ( this.isLastFrame ) {
				this.endStory();
			}

			// log events used
			if ( this.frameViewedCount <= this.currentFrame.id ) {
				this.frameViewedCount++;
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

@z-level-one: 100;
@z-level-two: 300;

.ext-wikistories-viewer-container {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	height: 100%;
	width: 100%;

	&-overlay {
		background-color: #000;
		position: absolute;
		height: 100%;
		width: 100%;
		opacity: 0.7;
	}

	&-cover-overlay {
		background-color: #000;
		position: absolute;
		height: 100%;
		width: 100%;
		opacity: 0.7;
		z-index: @z-level-one;
	}

	&-topbar {
		position: absolute;
		right: 0;
		left: 0;
		height: 140px;
		background: linear-gradient( 180deg, rgba( 0, 0, 0, 0.35 ) 0%, rgba( 0, 0, 0, 0 ) 100% );
	}

	&-menu {
		position: absolute;
		top: 18px;
		right: 0;
		z-index: @z-level-two;
	}

	.arrow() {
		width: 30px;
		height: 30px;
		position: absolute;
		top: 50%;
		z-index: @z-level-two;
		background: url( ../images/arrow.svg );
		background-size: contain;
		cursor: pointer;
	}

	@media screen and ( min-width: 1000px ) {
		&-prev {
			.arrow();
			left: 10px;
		}

		&-next {
			.arrow();
			right: 10px;
			-webkit-transform: rotate( 180deg );
			transform: rotate( 180deg );
		}
	}

	&-content {
		height: 100%;
		margin: 0 auto;
		position: relative;
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
			line-height: 27px;
		}

		&-story-cover {
			position: absolute;
			bottom: 90px;
			left: 16px;
			width: 80%;
			color: #fff;
			z-index: @z-level-two;
			text-align: left;

			&-wikistory {
				font-family: 'Linux Libertine', 'Georgia', 'Times', serif;
			}

			&-title {
				overflow: hidden;
				line-height: 44px;
				font-size: 32px;
				display: -webkit-box;
				max-height: 50vh;
				word-wrap: break-word;
				-webkit-line-clamp: 6;
				-webkit-box-orient: vertical;
			}
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
			z-index: @z-level-two;

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
			width: 48px;
			height: 48px;
			background-image: url( ../images/close-white.svg );
			background-position: center;
			background-repeat: no-repeat;
			left: 0;
			top: 18px;
			z-index: @z-level-two;
		}

		&-pause-icon {
			position: absolute;
			cursor: pointer;
			width: 48px;
			height: 48px;
			background-image: url( ../images/pause.svg );
			background-position: center;
			background-repeat: no-repeat;
			right: 50px;
			top: 18px;
			z-index: @z-level-two;
		}

		&-play-icon {
			position: absolute;
			cursor: pointer;
			width: 18px;
			height: 18px;
			padding: 15px;
			background-image: url( ../images/play.svg );
			background-position: center;
			background-repeat: no-repeat;
			right: 50px;
			top: 18px;
			z-index: @z-level-two;
		}
	}
}
</style>
