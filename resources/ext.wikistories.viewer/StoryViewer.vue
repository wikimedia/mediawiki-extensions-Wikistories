<template>
	<div v-if="story.length" class="ext-wikistories-viewer-container">
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
			<story-image
				v-if="!isStoryEnd || !nextStories.length"
				:src="currentFrame.url"
				:rect="currentFrame.focalRect"
				:alt="currentFrame.filename"
				:thumbnail="false"
				:allow-gestures="false"
				:error="onImgError"
			></story-image>
			<!-- OVERLAY (FIRST FRAME) -->
			<div
				v-if="isFirstFrame"
				class="ext-wikistories-viewer-container-cover-overlay"
			></div>
			<!-- SHADED TOPBAR-->
			<div
				v-if="!( isStoryEnd && nextStories.length )"
				class="ext-wikistories-viewer-container-topbar"
			></div>
			<!-- CLOSE BUTTON -->
			<div
				v-if="allowClose"
				class="ext-wikistories-viewer-container-content-close-icon"
				@click="discardStory"
			></div>
			<!-- SHARE BUTTON -->
			<div
				v-show="canShare"
				class="ext-wikistories-viewer-container-content-share-icon"
				:style="shareStyle"
				@click="shareStory"
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
					v-if="allowEdit"
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
				v-if="currentFrame.text"
				:is-paused="timer.isPaused"
				:textsizes="textsizes"
				:textsize="textsize"
				:content="currentFrame.text"
				@scroll-pause="pauseOnAction"
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
				<image-attribution :data="imgAttribution"></image-attribution>
			</div>
			<!-- NEXT STORY (LAST FRAME) -->
			<div
				v-if="isStoryEnd && nextStories.length"
				class="ext-wikistories-viewer-container-content-read-more"
			>
				<div class="ext-wikistories-viewer-container-content-read-more-header">
					{{ $i18n( 'wikistories-storyviewer-next-story-header' ).text() }}
				</div>
				<div
					v-for="story in nextStories"
					:key="story.storyId"
					class="ext-wikistories-viewer-container-content-read-more-item"
					@click="playNextStory( story.storyId )"
				>
					<div class="ext-wikistories-viewer-container-content-read-more-item-info">
						<!-- eslint-disable max-len -->
						<div
							:style="{ background: 'url(' + story.thumbnail + ')' }"
							class="ext-wikistories-viewer-container-content-read-more-item-info-thumbnail"
						></div>
						<!-- eslint-enable -->
						<span class="ext-wikistories-viewer-container-content-read-more-item-info-title">
							{{ story.storyTitle }}
						</span>
					</div>
					<div class="ext-wikistories-viewer-container-content-read-more-item-view">
						{{ $i18n( 'wikistories-storyviewer-next-story-viewtext' ).text() }}
					</div>
				</div>
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
			class="ext-wikistories-viewer-textsize"
			:title="$i18n( 'wikistories-storyviewer-textsize-title' ).text()"
			:accept="$i18n( 'wikistories-confirmdialog-ok' ).text()"
			align="left"
			@cancel="hideTextSizeDialog"
			@confirm="confirmTextsize">
			<ul>
				<li v-for="( size, name ) in textsizes" :key="name">
					<input
						:id="name"
						v-model="tempTextsize"
						type="radio"
						:name="name"
						:value="name">
					<label :for="name">
						{{ getTextSizeLabel( name ) }}
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
const StoryImage = require( './StoryImage.vue' );
const DotsMenu = require( './DotsMenu.vue' );
const DotsMenuItem = require( './DotsMenuItem.vue' );
const Timer = require( './util/timer.js' );
const isTouchDevice = require( './util/isTouchDevice.js' );
const consumptionEvents = require( './consumptionEvents.js' );
const contributionEvents = require( './contributionEvents.js' );

// @vue/component
module.exports = {
	name: 'StoryViewer',
	components: {
		'image-attribution': ImageAttribution,
		'confirm-dialog': ConfirmDialog,
		'story-image': StoryImage,
		'dots-menu': DotsMenu,
		'dots-menu-item': DotsMenuItem,
		textbox: Textbox
	},
	props: {
		stories: { type: Array, default: () => [] },
		storyId: { type: Number, default: 0 },
		allowClose: { type: Boolean, required: true },
		allowEdit: { type: Boolean, required: true }
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
	computed: Object.assign( mapGetters( [
		'story', 'currentFrame', 'editUrl', 'talkUrl', 'shareUrl',
		'isStoryEnd', 'isFirstFrame', 'isLastFrame', 'textsize', 'textsizes',
		'isFramePlaying', 'isFrameDonePlaying', 'isFrameViewed', 'currentStoryTitle',
		'nextStories', 'imgAttribution'
	] ), {
		style: function () {
			if ( this.isStoryEnd && this.nextStories.length ) {
				return {
					backgroundColor: '#3366cc'
				};
			}
		},
		shareStyle: function () {
			return {
				right: this.isStoryEnd ? '50px' : '108px'
			};
		},
		canShare: function () {
			return !!navigator.share;
		}
	} ),
	methods: Object.assign( mapActions( [
		'setStories', 'setStoryId', 'nextStory', 'setTextsize',
		'prevFrame', 'nextFrame', 'resetFrame', 'setIsStoryEnd',
		'purgeStory'
	] ), {
		getTextSizeLabel: function ( name ) {
			// The following classes are used here:
			// * wikistories-storyviewer-textsize-label-small
			// * wikistories-storyviewer-textsize-label-regular
			// * wikistories-storyviewer-textsize-label-large
			return this.$i18n( 'wikistories-storyviewer-textsize-label-' + name ).text();
		},
		playNextFrame: function () {
			this.timer.setup( () => {
				this.nextFrame();
			}, this.frameDuration );
		},
		playNextStory: function ( storyId ) {
			this.logStoryViewEvent();
			this.nextStory( storyId );
		},
		logStoryViewEvent: function () {
			if ( this.storyStart !== 0 ) {
				const storyOpenTime = Date.now() - this.storyStart;
				consumptionEvents.logStoryView(
					this.currentStoryTitle,
					this.story.length,
					this.frameViewedCount,
					storyOpenTime,
					this.stories.length
				);
				this.frameViewedCount = 0;
				this.storyStart = 0;
			}
		},
		endStory: function () {
			this.timer.setup( () => {
				this.setIsStoryEnd( true );
			}, this.frameDuration );
		},
		discardStory: function () {
			if ( !this.allowClose ) {
				return;
			}
			this.logStoryViewEvent();
			this.setStoryId( null );
			this.timer.clear();
			window.location.hash = '';
		},
		shareStory: function () {
			this.pauseOnAction();
			navigator.share( {
				title: this.currentStoryTitle,
				url: this.shareUrl
			} ).then( () => {
				contributionEvents.logShareAction( this.currentStoryTitle );
			} );
		},
		toggleStory: function () {
			if ( this.timer.isPaused ) {
				this.timer.play();
			} else {
				this.timer.pause();
			}
		},
		pauseOnAction: function () {
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
				pressTargetClassName.indexOf( 'ext-wikistories-viewer-container-content-story-' ) !== -1 || // cover, text
				pressTargetClassName === 'ext-wikistories-image' ||
				pressTargetClassName === 'ext-wikistories-image-container'
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
			window.location = this.editUrl;
		},
		talk: function () {
			window.location = this.talkUrl;
		},
		onImgError: function () {
			// purge when there is an error while loading the image
			// the story page will contain no image categories if the image is missing
			this.purgeStory( this.storyId );
		}
	} ),
	watch: {
		story: {
			handler: function ( newStory ) {
				if ( newStory.length ) {
					this.setIsStoryEnd( false );
					this.resetFrame();
					this.storyStart = Date.now();
					this.frameViewedCount = 0;
				}
			},
			deep: true
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
	},
	mounted: function () {
		window.onbeforeunload = this.logStoryViewEvent;
	}
};
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

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
		font-family: 'Helvetica Neue', serif;

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
			--from: rgba( 0, 0, 0, 0.35 );
			--to: rgba( 0, 0, 0, 0 );
			background: linear-gradient( 180deg, var( --from ) 0%, var( --to ) 100% );
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

			&-read-more {
				position: absolute;
				top: 10%;
				left: 0;
				right: 0;
				margin: auto;
				width: 80%;
				text-align: center;
				font-style: normal;
				font-weight: bold;
				font-size: 16px;
				line-height: 22px;
				color: #fff;
				cursor: pointer;

				&-header {
					font-size: 20px;
					line-height: 28px;
					letter-spacing: 0;
					margin-bottom: 16px;
				}

				&-item {
					background-color: @background-color-base;
					margin: 10px 0;
					color: #000;

					&-info {
						display: flex;
						flex: auto;
						width: auto;
						align-items: center;
						padding: 12px;

						&-thumbnail {
							display: grid;
							place-items: center;
							min-height: 52px;
							min-width: 52px;
							border-radius: 50%;
						}

						&-title {
							font-style: normal;
							font-size: 14px;
							line-height: 21px;
							padding-left: 12px;
							text-align: left;
							color: @color-base;
							overflow: hidden;
							display: -webkit-box;
							-webkit-box-orient: vertical;
							-webkit-line-clamp: 2;
							white-space: initial;
						}
					}

					&-view {
						position: relative;
						color: @color-progressive;
						padding: 6px;
						font-size: 16px;
						line-height: 22px;
						letter-spacing: 0;
						border-top: @border-width-base @border-style-base @border-color-base;
					}
				}
			}

			&-progress {
				position: absolute;
				display: flex;
				flex-direction: row;
				padding: 8px 5%;
				z-index: @z-level-two;
				top: 3px;
				width: 100%;
				box-sizing: border-box;

				&-container {
					height: 2px;
					flex-grow: 1;
					margin: 0 2px;
					display: flex;
					background-color: #72777d;

					&-loading {
						/* Hardcoded color for both light and night modes */
						color-scheme: light;
						background-color: #eaecf0;
						height: 100%;
						width: 100%;
						animation-name: ext-wikistories-viewer-progress-loading;
						animation-iteration-count: 1;
						/* TODO - ideally the animation duration is
							set as var related to frameDuration  */
						animation-duration: 5s;
						animation-timing-function: linear;
					}

					&-loaded {
						/* Hardcoded color for both light and night modes */
						color-scheme: light;
						background-color: #eaecf0;
						height: 100%;
						width: 100%;
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

			&-share-icon {
				position: absolute;
				cursor: pointer;
				width: 48px;
				height: 48px;
				background-image: url( ./images/share.svg );
				background-position: center;
				background-repeat: no-repeat;
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
		.ext-wikistories-confirm-content-custom {
			ul {
				list-style: none;
				margin: 0;
				padding: 0;
			}

			li {
				margin-bottom: 16px;

				input {
					height: 24px;
					width: 24px;
					vertical-align: middle;
					margin-right: 4px;
				}

				label {
					vertical-align: middle;
				}
			}
		}
	}
}
</style>
