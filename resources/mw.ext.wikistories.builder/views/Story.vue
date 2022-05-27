<template>
	<div class="ext-wikistories-storybuilder-story" v-on="toast.show ? { click: hideToast } : {}">
		<navigator
			:title="$i18n( 'wikistories-story-navigator-title' ).text()"
			:forward-button-visible="true"
			@backward="showDiscardStoryConfirmationDialog"
			@forward="onNext"
		></navigator>
		<current-frame
			@select-text="onSelectText"
			@edit-text="handleTextEditFocus"
		></current-frame>
		<div class="ext-wikistories-storybuilder-story-topbar"></div>
		<dots-menu v-show="!isEditingText" class="ext-wikistories-storybuilder-story-menu">
			<dots-menu-item
				icon="replace"
				:text="$i18n( 'wikistories-story-replaceimage' ).text()"
				route-to="/search/one"
			></dots-menu-item>
			<dots-menu-item
				icon="delete"
				:text="$i18n( 'wikistories-story-deleteframe' ).text()"
				@click="showDeleteFrameConfirmationDialog"
			></dots-menu-item>
		</dots-menu>
		<frames @max-limit="showMaxFramesToast"></frames>
		<toast
			v-if="toast.show"
			:message="toast.message"
			@hide-toast="hideToast">
		</toast>
		<alert
			v-if="alert.show"
			:title="alert.title"
			@ok-click="hideAlert">
			{{ alert.message }}
		</alert>
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
			:title="$i18n( 'wikistories-confirmdialog-discardstory-title' ).text()"
			:message="$i18n( 'wikistories-confirmdialog-discardstory-message' ).text()"
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
const ConfirmDialog = require( '../components/ConfirmDialog.vue' );
const Navigator = require( '../components/Navigator.vue' );
const DotsMenu = require( '../components/DotsMenu.vue' );
const DotsMenuItem = require( '../components/DotsMenuItem.vue' );
const Toast = require( '../components/Toast.vue' );

// @vue/component
module.exports = {
	name: 'Story',
	components: {
		'current-frame': CurrentFrame,
		frames: Frames,
		alert: Alert,
		'confirm-dialog': ConfirmDialog,
		navigator: Navigator,
		// x-menu because 'menu' is a reserved HTML word
		'dots-menu': DotsMenu,
		'dots-menu-item': DotsMenuItem,
		toast: Toast
	},
	data: function () {
		return {
			viewDeleteFrameConfirmDialog: false,
			viewDiscardStoryConfirmDialog: false,
			isEditingText: false,
			alert: {
				show: false,
				title: '',
				message: ''
			},
			toast: {
				show: false,
				message: ''
			}
		};
	},
	computed: mapGetters( [ 'currentFrame', 'missingFrames', 'framesWithoutText', 'fromArticle' ] ),
	methods: $.extend( mapActions( [ 'removeFrame', 'fetchImgAttribution' ] ), {
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
		handleTextEditFocus: function ( editing ) {
			this.isEditingText = editing;
		},
		deleteFrame: function () {
			this.removeFrame();
			this.viewDeleteFrameConfirmDialog = false;
		},
		onDiscard: function () {
			const titleObj = mw.Title.newFromText( this.fromArticle );
			window.location = titleObj.getUrl();
		},
		onNext: function () {
			if ( this.missingFrames ) {
				const minFrames = this.getConfig( 'wgWikistoriesMinFrames' );
				this.showNotEnoughFrameAlert( minFrames, this.missingFrames );
				return;
			}
			if ( this.framesWithoutText > 0 ) {
				this.showFramesWithoutTextAlert( this.framesWithoutText );
				return;
			}

			this.$router.push( { name: 'PublishForm' } );
		},
		onSelectText: function () {
			this.$router.push( { name: 'Article' } );
		}
	} )
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
	}

	&-menu {
		position: absolute;
		top: 48px;
		right: 0;
	}
}
</style>
