<template>
	<div class="ext-wikistories-storybuilder-story" v-on="toast.show ? { click: hideToast } : {}">
		<navigator
			:title="messages.navTitle"
			:forward-button-visible="true"
			:forward-button-text="messages.forwardButtonText"
			@backward="onBackward"
			@forward="onForward"
		></navigator>
		<current-frame
			@select-text="onSelectText"
		></current-frame>
		<div class="ext-wikistories-storybuilder-story-topbar"></div>
		<dots-menu v-show="!editingText" class="ext-wikistories-storybuilder-story-menu">
			<dots-menu-item
				icon="replace"
				:text="$i18n( 'wikistories-story-replaceimage' ).text()"
				@click="replaceImage"
			></dots-menu-item>
			<dots-menu-item
				icon="delete"
				:text="$i18n( 'wikistories-story-deleteframe' ).text()"
				@click="showDeleteFrameConfirmationDialog"
			></dots-menu-item>
			<dots-menu-item
				icon="talk"
				:text="$i18n( 'wikistories-story-feedback' ).text()"
				@click="goToWikistoriesTalkPage"
			></dots-menu-item>
		</dots-menu>
		<frames v-show="!editingText" @max-limit="showMaxFramesToast"></frames>
		<toast
			v-if="toast.show"
			:message="toast.message"
			@hide-toast="hideToast">
		</toast>
		<notice
			v-if="isUserBlockedFromCurrentArticle"
			class="ext-wikistories-storybuilder-story-notice"
			:message="notice.message"
			:mode="notice.mode">
		</notice>
		<alert
			v-if="alert.show"
			:title="alert.title"
			:message="alert.message"
			@dismiss="hideAlert"
		></alert>
		<confirm-dialog
			v-if="viewDeleteFrameConfirmDialog"
			:title="$i18n( 'wikistories-confirmdialog-delete-title' ).text()"
			:message="$i18n( 'wikistories-confirmdialog-delete-message' ).text()"
			:accept="$i18n( 'wikistories-confirmdialog-delete-accept' ).text()"
			@cancel="hideDeleteFrameConfirmDialog"
			@confirm="deleteFrame">
		</confirm-dialog>
		<confirm-dialog
			v-if="viewDiscardStoryConfirmDialog"
			:title="messages.discardTitle"
			:message="messages.discardMessage"
			:accept="$i18n( 'wikistories-confirmdialog-discardstory-accept' ).text()"
			@cancel="hideDiscardStoryConfirmDialog"
			@confirm="onDiscard">
		</confirm-dialog>
	</div>
</template>

<script>
const mapGetters = require( 'vuex' ).mapGetters;
const mapActions = require( 'vuex' ).mapActions;
const CurrentFrame = require( '../components/CurrentFrame.vue' );
const Frames = require( '../components/Frames.vue' );
const Alert = require( '../components/Alert.vue' );
const ConfirmDialog = require( '../ConfirmDialog.vue' );
const Navigator = require( '../components/Navigator.vue' );
const DotsMenu = require( '../DotsMenu.vue' );
const DotsMenuItem = require( '../DotsMenuItem.vue' );
const Toast = require( '../components/Toast.vue' );
const Notice = require( '../components/Notice.vue' );
const beforeUnloadListener = require( '../util/beforeUnloadListener.js' );

// @vue/component
module.exports = {
	name: 'Story',
	components: {
		'current-frame': CurrentFrame,
		frames: Frames,
		alert: Alert,
		'confirm-dialog': ConfirmDialog,
		navigator: Navigator,
		'dots-menu': DotsMenu,
		'dots-menu-item': DotsMenuItem,
		toast: Toast,
		notice: Notice
	},
	data: function () {
		return {
			viewDeleteFrameConfirmDialog: false,
			viewDiscardStoryConfirmDialog: false,
			alert: {
				show: false,
				title: '',
				message: ''
			},
			toast: {
				show: false,
				message: ''
			},
			notice: {
				message: this.$i18n( 'wikistories-notice-user-block' ).text(),
				mode: 'error'
			}
		};
	},
	computed: $.extend( mapGetters( [ 'currentFrame', 'missingFrames', 'framesWithoutText', 'fromArticle', 'mode', 'frameCount', 'editingText', 'isUserBlockedFromCurrentArticle' ] ), {
		messages: function () {
			// EDIT TEXT MODE
			if ( this.editingText ) {
				return {
					navTitle: this.$i18n( 'wikistories-story-navigator-title-edittext' ).text(),
					forwardButtonText: this.$i18n( 'wikistories-story-edittext-done' ).text(),
					discardTitle: this.$i18n( 'wikistories-confirmdialog-discardedits-title' ).text(),
					discardMessage: this.$i18n( 'wikistories-confirmdialog-discardedits-message' ).text()
				};
			}

			// EDIT AND CREATE STORY MODE
			return this.mode === 'edit' ?
				{
					navTitle: this.$i18n( 'wikistories-story-navigator-title-edit' ).text(),
					discardTitle: this.$i18n( 'wikistories-confirmdialog-discardedits-title' ).text(),
					discardMessage: this.$i18n( 'wikistories-confirmdialog-discardedits-message' ).text()
				} : {
					navTitle: this.$i18n( 'wikistories-story-navigator-title' ).text(),
					discardTitle: this.$i18n( 'wikistories-confirmdialog-discardstory-title' ).text(),
					discardMessage: this.$i18n( 'wikistories-confirmdialog-discardstory-message' ).text()
				};
		}
	} ),
	methods: $.extend( mapActions( [ 'removeFrame', 'fetchImgAttribution', 'routePush', 'routeReplace', 'setText', 'setEditingText', 'checkWarningStatus' ] ), {
		showDeleteFrameConfirmationDialog: function () {
			this.viewDeleteFrameConfirmDialog = true;
		},
		hideDeleteFrameConfirmDialog: function () {
			this.viewDeleteFrameConfirmDialog = false;
		},
		showDiscardStoryConfirmationDialog: function () {
			this.viewDiscardStoryConfirmDialog = true;
		},
		hideDiscardStoryConfirmDialog: function () {
			this.viewDiscardStoryConfirmDialog = false;
		},
		onBackward: function () {
			if ( !this.editingText ) {
				this.showDiscardStoryConfirmationDialog();
			} else {
				this.setEditingText( false );
				this.setText( this.currentFrame.lastEditedText );
			}
		},
		onForward: function () {
			if ( !this.editingText ) {
				if ( this.missingFrames ) {
					const minFrames = this.getConfig( 'wgWikistoriesMinFrames' );
					this.showNotEnoughFrameAlert( minFrames, this.missingFrames );
					return;
				}
				if ( this.framesWithoutText > 0 ) {
					this.showFramesWithoutTextAlert( this.framesWithoutText );
					return;
				}

				if ( this.isUserBlockedFromCurrentArticle ) {
					return;
				}

				this.routePush( 'publish' );
			} else {
				this.setEditingText( false );
			}
		},
		goToWikistoriesTalkPage: function () {
			window.location = 'https://www.mediawiki.org/wiki/Talk:Wikistories';
		},
		showMaxFramesToast: function () {
			const maxFrames = this.getConfig( 'wgWikistoriesMaxFrames' );
			this.toast.message = this.$i18n( 'wikistories-toast-maxframes-addingmore' )
				.params( [ maxFrames ] ).text();
			this.toast.show = true;
		},
		hideToast: function () {
			this.toast.message = '';
			this.toast.show = false;
		},
		showNotEnoughFrameAlert: function ( min, missing ) {
			this.alert.title = this.$i18n( 'wikistories-error-notenoughframes-title' ).text();
			this.alert.message = this.$i18n( 'wikistories-error-notenoughframes-message' )
				.params( [ min, missing ] ).text();
			this.alert.show = true;
		},
		showFramesWithoutTextAlert: function ( nb ) {
			this.alert.title = this.$i18n( 'wikistories-error-frameswithouttext-title' ).text();
			this.alert.message = this.$i18n( 'wikistories-error-frameswithouttext-message' )
				.params( [ nb ] ).text();
			this.alert.show = true;
		},
		hideAlert: function () {
			this.alert.title = '';
			this.alert.message = '';
			this.alert.show = false;
		},
		deleteFrame: function () {
			this.removeFrame();
			if ( this.frameCount === 0 ) {
				this.routeReplace( 'searchMany' );
			}
			this.viewDeleteFrameConfirmDialog = false;
		},
		replaceImage: function () {
			this.routePush( 'searchOne' );
		},
		onDiscard: function () {
			const titleObj = mw.Title.newFromText( this.fromArticle );
			window.removeEventListener( 'beforeunload', beforeUnloadListener );
			window.location = titleObj.getUrl();
		},
		onSelectText: function () {
			this.routePush( 'article' );
			this.setEditingText( false );
		}
	} ),
	mounted: function () {
		window.onpopstate = () => {
			this.routePush( 'story' );

			if ( this.viewDiscardStoryConfirmDialog ) {
				this.hideDiscardStoryConfirmDialog();
			} else if ( this.viewDeleteFrameConfirmDialog ) {
				this.hideDeleteFrameConfirmDialog();
			} else if ( this.alert.show ) {
				this.hideAlert();
			} else {
				this.showDiscardStoryConfirmationDialog();
			}
		};

		// show duplicate story text frames
		this.checkWarningStatus();
	}
};
</script>

<style lang="less">
.ext-wikistories-storybuilder-story {
	font-size: 24px;
	height: 100%;
	overflow: hidden;
	position: relative;
	display: flex;
	flex-direction: column;

	&-topbar {
		position: absolute;
		top: 48px;
		right: 0;
		left: 0;
		height: 140px;
		background: linear-gradient( 180deg, rgba( 0, 0, 0, 0.35 ) 0%, rgba( 0, 0, 0, 0 ) 100% );
		pointer-events: none;
	}

	&-menu {
		position: absolute;
		top: 48px;
		right: 0;
	}

	&-notice {
		position: absolute;
		margin: 0 15px;
		top: 103px;
		left: 0;
		right: 0;
		z-index: 100;
	}
}
</style>
