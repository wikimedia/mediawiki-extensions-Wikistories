<template>
	<div class="ext-wikistories-storybuilder-story">
		<div class="ext-wikistories-storybuilder-story-delete" @click="showConfirmationDialog">
			X
		</div>
		<current-frame
			@select-text="showArticlePopup"
			@edit-text="handleTextEditFocus"
		></current-frame>
		<div
			v-show="viewPublishButton"
			class="ext-wikistories-storybuilder-story-publish-button"
			@click="showPublishPopup"
		></div>
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
			v-if="viewConfirmDialog"
			:title="$i18n( 'wikistories-confirmdialog-delete-title' ).text()"
			:message="$i18n( 'wikistories-confirmdialog-delete-message' ).text()"
			:accept="$i18n( 'wikistories-confirmdialog-delete-accept' ).text()"
			@cancel="hideConfirmDialog"
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
		'confirm-dialog': ConfirmDialog
	},
	data: function () {
		return {
			viewArticlePopup: false,
			viewPublishPopup: false,
			viewConfirmDialog: false,
			viewPublishButton: true,
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
		showConfirmationDialog: function () {
			this.viewConfirmDialog = true;
		},
		hideConfirmDialog: function () {
			this.viewConfirmDialog = false;
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
			this.viewPublishButton = !editing;
		},
		deleteFrame: function () {
			this.removeFrame();
			this.viewConfirmDialog = false;
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

	&-publish-button {
		position: absolute;
		top: 24px;
		right: 24px;
		background-image: url( ../images/back.svg );
		width: 26px;
		height: 26px;
		cursor: pointer;
		transform: rotate( 180deg );
		background-color: #fff;
		background-repeat: no-repeat;
		background-position: center;
		border-radius: 13px;
	}

	&-delete {
		position: absolute;
		top: 10px;
		left: 10px;
		height: 37px;
		width: 16px;
		z-index: 300;
		background-color: #fff;
	}
}
</style>
