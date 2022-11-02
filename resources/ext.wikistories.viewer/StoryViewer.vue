<template>
	<div v-show="story.length" class="ext-wikistories-viewer-container">
		<!-- OVERLAY  -->
		<div
			class="ext-wikistories-viewer-container-overlay"
			@click="discardStory"
		></div>
		<!-- STORY IMAGE -->
		<div
			class="ext-wikistories-viewer-container-content"
			:style="style"
			@click="navigateFrame">
			<!-- OVERLAY (FIRST FRAME) -->
			<div
				v-if="isFirstFrame"
				class="ext-wikistories-viewer-container-cover-overlay"
			></div>
			<!-- SHADED TOPBAR -->
			<div class="ext-wikistories-viewer-container-topbar"></div>
			<!-- CLOSE BUTTON -->
			<div
				class="ext-wikistories-viewer-container-content-close-icon"
				@click="discardStory"
			></div>
			<!-- PAUSE BUTTON -->
			<div
				v-if="!isStoryEnd"
				:class="{
					'ext-wikistories-viewer-container-content-pause-icon': !timer.isPaused,
					'ext-wikistories-viewer-container-content-play-icon': timer.isPaused
				}"
				@click="toggleStory"
			></div>
			<!-- MENU -->
			<dots-menu class="ext-wikistories-viewer-container-menu">
				<dots-menu-item
					:text="$i18n( 'wikistories-storyviewer-textsize' ).text()"
					icon="textsize"
					@click="showTextSizeModal"
				></dots-menu-item>
				<dots-menu-item
					:text="$i18n( 'wikistories-storyviewer-edit' ).text()"
					icon="edit"
					@click="edit"
				></dots-menu-item>
				<dots-menu-item
					:text="$i18n( 'wikistories-storyviewer-talk' ).text()"
					icon="talk"
					@click="talk"
				></dots-menu-item>
			</dots-menu>
			<!-- PROGRESS BAR -->
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
			<!-- STORY TEXT -->
			<textbox
				:is-paused="timer.isPaused"
				:textsize="textsize"
				@scroll-pause="pauseOnScroll"
			></textbox>
			<!-- STORY TITLE (FIRST PAGE)-->
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
			<!-- FILE ATTRIBUTION -->
			<div v-show="!currentFrame.fileNotFound">
				<image-attribution></image-attribution>
			</div>
			<!-- NEXT STORY BUTTON (LAST FRAME) -->
			<div
				v-if="isStoryEnd && !isLastStory"
				class="ext-wikistories-viewer-container-content-next-btn"
				@click="playNextStory">
				{{ $i18n( "wikistories-storyviewer-next-story-button" ).text() }}
			</div>
		</div>
		<!-- PREVIOUS FRAME BUTTON -->
		<div
			v-if="!isFirstFrame"
			class="ext-wikistories-viewer-container-prev"
			@click="prevFrame"
		></div>
		<!-- NEXT FRAME BUTTON -->
		<div
			v-if="!isLastFrame"
			class="ext-wikistories-viewer-container-next"
			@click="nextFrame"
		></div>
		<!-- CHANGE TEXT SIZE DIALOG -->
		<confirm-dialog
			v-if="viewChangeTextSizeConfirmDialog"
			:title="$i18n( 'wikistories-storyviewer-textsize-title' ).text()"
			:accept="$i18n( 'wikistories-confirmdialog-ok' ).text()"
			@cancel="hideTextSizeDialog"
			@confirm="confirmTextsize">
			<ul class="ext-wikistories-viewer-textsize">
				<li v-for="( size, name ) in textsizes" :key="name">
					<input
						:id="name"
						v-model="tempTextsize"
						type="radio"
						:name="name"
						:value="name">
					<!-- wikistories-storyviewer-textsize-label-small -->
					<!-- wikistories-storyviewer-textsize-label-regular -->
					<!-- wikistories-storyviewer-textsize-label-large -->
					<label :for="name">
						{{ $i18n( "wikistories-storyviewer-textsize-label-" + name ).text() }}
					</label>
				</li>
			</ul>
		</confirm-dialog>
	</div>
</template>

<script>

const mapGetters = require( 'vuex' ).mapGetters;
const mapActions = require( 'vuex' ).mapActions;
const ImageAttribution = require( './components/ImageAttribution.vue' );
const Textbox = require( './components/Textbox.vue' );
const ConfirmDialog = require( './ConfirmDialog.vue' );
const DotsMenu = require( './DotsMenu.vue' );
const DotsMenuItem = require( './DotsMenuItem.vue' );
const Timer = require( './util/timer.js' );
const isTouchDevice = require( './util/isTouchDevice.js' );

// @vue/component
module.exports = {
	name: 'StoryViewer',
	components: {
		'image-attribution': ImageAttribution,
		'confirm-dialog': ConfirmDialog,
		'dots-menu': DotsMenu,
		'dots-menu-item': DotsMenuItem,
		textbox: Textbox
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
			frameViewedCount: 0,
			viewChangeTextSizeConfirmDialog: false,
			tempTextsize: null
		};
	},
	computed: $.extend( mapGetters( [
		'story', 'currentFrame', 'editUrl', 'talkUrl', 'isCurrentImageLoaded',
		'isStoryEnd', 'isLastStory', 'isFirstFrame', 'isLastFrame', 'textsize', 'textsizes',
		'isFramePlaying', 'isFrameDonePlaying', 'isFrameViewed', 'currentStoryTitle'
	] ), {
		style: function () {
			return {
				backgroundImage: 'url(' + this.currentFrame.url + ')',
				backgroundPosition: 'center',
				backgroundSize: 'cover',
				backgroundColor: this.isCurrentImageLoaded ? '#fff' : '#000'
			};
		}
	} ),
	methods: $.extend( mapActions( [
		'setStories', 'setStoryId', 'nextStory', 'setTextsize',
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
		pauseOnScroll: function () {
			this.timer.pause();
		},
		navigateFrame: function ( e ) {
			if ( !isTouchDevice ) {
				return;
			}

			const pressTargetClassName = e.target.className;

			if (
				pressTargetClassName === 'ext-wikistories-viewer-container-content' ||
				pressTargetClassName === 'ext-wikistories-viewer-container-cover-overlay' ||
				pressTargetClassName.indexOf( 'ext-wikistories-viewer-container-content-story-' ) !== -1 // cover, text
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
		showTextSizeModal: function () {
			this.timer.pause();
			this.viewChangeTextSizeConfirmDialog = true;
		},
		hideTextSizeDialog: function () {
			this.viewChangeTextSizeConfirmDialog = false;
			this.tempTextsize = this.textsize;
		},
		confirmTextsize: function () {
			this.viewChangeTextSizeConfirmDialog = false;
			this.setTextsize( this.tempTextsize );
		},
		edit: function () {
			this.logStoryViewEvent();
			window.location = this.editUrl;
		},
		talk: function () {
			this.logStoryViewEvent();
			window.location = this.talkUrl;
		}
	} ),
	watch: {
		story: function ( newStory ) {
			if ( newStory.length ) {
				this.setIsStoryEnd( false );
				this.resetFrame();
				this.storyStart = Date.now();
				this.frameViewedCount = 0;
			}
		},
		currentFrame: function () {
			if ( this.isLastFrame ) {
				this.endStory();
			} else {
				this.playNextFrame();
			}

			// log events used
			if ( this.isFrameViewed( this.frameViewedCount ) ) {
				this.frameViewedCount++;
			}
		}
	},
	created: function () {
		this.setStories( this.stories );
		this.setStoryId( this.storyId );
		this.storyStart = Date.now();
		this.tempTextsize = this.textsize;
	}
};
</script>

<style lang="less">
@import 'mediawiki.ui/variables.less';

@z-level-one: 100;
@z-level-two: 300;

.ext-wikistories-viewer {
	&-container {
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
			background: url( ./images/arrow.svg );
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

			@media screen and ( min-width: 720px ) {
				max-width: 993.3px;
			}

			&-story-cover {
				position: absolute;
				bottom: 90px;
				left: 16px;
				width: 80%;
				color: #fff;
				z-index: @z-level-two;
				text-align: left;

				&-title {
					font-family: 'Linux Libertine', 'Georgia', 'Times', serif;
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
				background-image: url( ./images/close-white.svg );
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
				background-image: url( ./images/pause.svg );
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
				background-image: url( ./images/play.svg );
				background-position: center;
				background-repeat: no-repeat;
				right: 50px;
				top: 18px;
				z-index: @z-level-two;
			}
		}
	}

	&-textsize {
		font-size: 1.2em;
		line-height: 1.5;
	}
}
</style>
