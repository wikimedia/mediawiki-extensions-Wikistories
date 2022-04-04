<template>
	<div class="ext-wikistories-storybuilder-story">
		<current-frame
			@select-text="showArticlePopup"
			@edit-text="handleTextEditFocus"
		></current-frame>
		<div class="ext-wikistories-storybuilder-story-topbar"></div>
		<dots-menu v-show="!isEditingText" class="ext-wikistories-storybuilder-story-menu">
			<dots-menu-item
				:text="$i18n( 'wikistories-story-replaceimage' ).text()"
				route-to="/search/one"
			></dots-menu-item>
			<dots-menu-item
				:text="$i18n( 'wikistories-story-deleteframe' ).text()"
				@click="showDeleteFrameConfirmationDialog"
			></dots-menu-item>
			<!-- Not localizing the "publish" string since it will be replaced by an arrow -->
			<dots-menu-item text="Publish" @click="showPublishPopup">
			</dots-menu-item>
		</dots-menu>
		<frames></frames>

		<popup v-if="viewArticlePopup" @overlay-click="hideArticlePopup">
			<article-view @text-selected="hideArticlePopup"></article-view>
		</popup>
		<popup v-if="viewPublishPopup" @overlay-click="hidePublishPopup">
			<publish-form @cancel-publish="hidePublishPopup"></publish-form>
		</popup>
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
	</div>
</template>

<script>
const mapGetters = require( 'vuex' ).mapGetters;
const mapActions = require( 'vuex' ).mapActions;
const CurrentFrame = require( '../components/CurrentFrame.vue' );
const Article = require( '../components/Article.vue' );
const Frames = require( '../components/Frames.vue' );
const Popup = require( '../components/Popup.vue' );
const PublishForm = require( '../components/PublishForm.vue' );
const Alert = require( '../components/Alert.vue' );
const ConfirmDialog = require( '../components/ConfirmDialog.vue' );
const DotsMenu = require( '../components/DotsMenu.vue' );
const DotsMenuItem = require( '../components/DotsMenuItem.vue' );

// @vue/component
module.exports = {
	name: 'Story',
	components: {
		'current-frame': CurrentFrame,
		'article-view': Article,
		frames: Frames,
		popup: Popup,
		'publish-form': PublishForm,
		alert: Alert,
		'confirm-dialog': ConfirmDialog,
		// x-menu because 'menu' is a reserved HTML word
		'dots-menu': DotsMenu,
		'dots-menu-item': DotsMenuItem
	},
	data: function () {
		return {
			viewArticlePopup: false,
			viewPublishPopup: false,
			viewDeleteFrameConfirmDialog: false,
			isEditingText: false,
			alert: {
				show: false,
				title: '',
				message: ''
			}
		};
	},
	computed: mapGetters( [ 'currentFrame', 'missingFrames', 'framesWithoutText' ] ),
	methods: $.extend( mapActions( [ 'removeFrame' ] ), {
		showArticlePopup: function () {
			this.viewArticlePopup = true;
		},
		hideArticlePopup: function () {
			this.viewArticlePopup = false;
		},
		showPublishPopup: function () {
			if ( this.missingFrames ) {
				const minFrames = this.getConfig( 'wgWikistoriesMinFrames' );
				this.showNotEnoughFrameAlert( minFrames, this.missingFrames );
				return;
			}
			if ( this.framesWithoutText > 0 ) {
				this.showFramesWithoutTextAlert( this.framesWithoutText );
				return;
			}
			this.viewPublishPopup = true;
		},
		hidePublishPopup: function () {
			this.viewPublishPopup = false;
		},
		showDeleteFrameConfirmationDialog: function () {
			this.viewDeleteFrameConfirmDialog = true;
		},
		hideDeleteFrameConfirmDialog: function () {
			this.viewDeleteFrameConfirmDialog = false;
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

	&-topbar {
		position: absolute;
		top: 0;
		right: 0;
		left: 0;
		height: 40px;
		background: linear-gradient( to bottom, rgba( 0, 0, 0, 0.5 ), rgba( 255, 255, 255, 0 ) );
	}

	&-menu {
		position: absolute;
		top: 0;
		right: 0;
	}
}
</style>
