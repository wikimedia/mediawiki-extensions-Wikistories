<template>
	<div class="ext-wikistories-publishform">
		<navigator
			:title="$i18n( 'wikistories-builder-publishform-navigator-title' ).text()"
			backward-button-style="back"
			:forward-button-visible="true"
			:forward-button-text="$i18n( 'wikistories-builder-publishform-publishbutton' ).text()"
			@backward="onBack"
			@forward="onSaveClick"
		></navigator>
		<toast
			v-if="toast.show"
			:message="toast.message"
			:mode="toast.mode"
			@hide-toast="hideToast">
		</toast>
		<div class="ext-wikistories-publishform-content">
			<input
				ref="storyTitleInput"
				v-model="storyTitle"
				type="text"
				maxlength="255"
				:disabled="titleInputDisabled"
				class="ext-wikistories-publishform-content-input-title"
				:placeholder="$i18n( 'wikistories-builder-publishform-placeholder' ).text()"
				@input="onInput"
			>
			<div class="ext-wikistories-publishform-content-watchlist">
				<input
					id="watchlist"
					v-model="watchlist"
					type="checkbox"
				>
				<label
					for="watchlist">
					{{ $i18n( 'wikistories-builder-publishform-watch' ).text() }}
				</label>
				<select
					v-if="watchlistExpiryEnabled"
					v-model="watchlistExpiry"
					:disabled="!watchlist"
					class="ext-wikistories-publishform-content-watchlist-select"
					:class="!watchlist ? 'ext-wikistories-publishform-content-watchlist-select-disabled' : ''"
				>
					<option
						v-for="( value, key ) in watchlistExpiryOptions.options"
						:key="key"
						:value="value"
					>
						{{ key }}
					</option>
				</select>
			</div>
			<div v-if="knownError" class="ext-wikistories-publishform-content-error">
				{{ error }}
			</div>
			<div class="ext-wikistories-publishform-content-info">
				{{ $i18n( 'wikistories-builder-publishform-info' ).text() }}
			</div>
		</div>
		<div class="ext-wikistories-publishform-license" v-html="licenseHtml">
		</div>
		<div v-if="overlay" class="ext-wikistories-publishform-saving">
			<div v-if="savingInProgress" class="ext-wikistories-publishform-saving-spinner">
				<div class="ext-wikistories-publishform-saving-spinner-animation">
					<div class="ext-wikistories-publishform-saving-spinner-animation-bounce"></div>
				</div>
			</div>
			<div v-if="savingInProgress" class="ext-wikistories-publishform-saving-text">
				{{ $i18n( 'wikistories-builder-publishform-saving' ).text() }}
			</div>
			<div v-if="savingDone" class="ext-wikistories-publishform-saving-done">
				<div class="ext-wikistories-publishform-saving-done-image">
					&check;
				</div>
				<p class="ext-wikistories-publishform-saving-done-congrats">
					{{ $i18n( 'wikistories-builder-publishform-saving-done' ).text() }}
				</p>
				<p>
					<a class="ext-wikistories-publishform-saving-done-read" :href="storyUrl">
						{{ $i18n( 'wikistories-builder-publishform-gotostory' ).text() }}
					</a>
				</p>
				<p v-if="canShare">
					<a
						class="ext-wikistories-publishform-saving-done-share"
						@click="shareStory">
						{{ $i18n( 'wikistories-builder-publishform-sharestory' ).text() }}
					</a>
				</p>
			</div>
		</div>
	</div>
</template>

<script>
const mapGetters = require( 'vuex' ).mapGetters;
const mapActions = require( 'vuex' ).mapActions;
const Navigator = require( '../components/Navigator.vue' );
const Toast = require( '../components/Toast.vue' );
const saveStory = require( '../api/saveStory.js' );
const validateTitle = require( '../util/validateTitle.js' );
const events = require( '../contributionEvents.js' );
const beforeUnloadListener = require( '../util/beforeUnloadListener.js' );
const NS_STORY = mw.config.get( 'wgNamespaceIds' ).story;

// @vue/component
module.exports = {
	name: 'PublishForm',
	components: {
		navigator: Navigator,
		toast: Toast
	},
	data: function () {
		return {
			storyTitle: '',
			watchlist: true,
			watchlistExpiry: null,
			error: null,
			toast: {
				show: false,
				message: '',
				mode: 'error'
			},
			titleInputDisabled: false,
			overlay: false,
			savingInProgress: false,
			savingDone: false
		};
	},
	computed: $.extend( mapGetters( [
		'frames', 'valid', 'fromArticle', 'storyForSave', 'mode', 'title', 'storyExists',
		'watchlistExpiryEnabled', 'watchlistExpiryOptions', 'watchDefault', 'storyUrl'
	] ), {
		licenseHtml: function () {
			const html = this.$i18n(
				'wikistories-builder-licensing-with-terms',
				'https://foundation.wikimedia.org/wiki/Terms_of_Use',
				'https://en.wikipedia.org/wiki/Wikipedia:Text_of_Creative_Commons_Attribution-ShareAlike_3.0_Unported_License',
				'https://en.wikipedia.org/wiki/Wikipedia:Text_of_the_GNU_Free_Documentation_License'
			).parse();
			const doc = new DOMParser().parseFromString( html, 'text/html' );
			for ( const a of doc.querySelectorAll( 'a' ) ) {
				a.target = '_blank';
			}
			return doc.body.outerHTML;
		},
		knownError: function () {
			return this.error && !this.toast.show;
		},
		canShare: function () {
			return !!navigator.share;
		}
	} ),
	methods: $.extend( mapActions( [ 'routeBack', 'setStoryPageId' ] ), {
		onSaveClick: function () {
			this.error = null;
			this.overlay = true;
			this.savingInProgress = true;
			const mustExist = this.mode === 'edit';
			validateTitle( this.storyTitle, mustExist ).then( function ( validity ) {
				if ( !validity.valid ) {
					this.overlay = false;
					this.savingInProgress = false;
					this.error = this.$i18n( validity.message ).text();
					events.logPublishFailure( this.storyTitle, this.storyExists, this.error );
					return;
				}
				const title = mw.Title.newFromUserInput( this.storyTitle, NS_STORY );
				const watchlistExpiry = this.watchlistExpiryEnabled ? this.watchlistExpiry : null;
				saveStory( title.getPrefixedDb(), this.storyForSave, this.mode, this.watchlist, watchlistExpiry ).then(
					function ( response ) {
						// response is { result, title, newrevid, pageid, and more }
						if ( response.result === 'Success' ) {
							events.logPublishSuccess( this.storyTitle, this.storyExists );
							window.removeEventListener( 'beforeunload', beforeUnloadListener );
							this.setStoryPageId( response.pageid );
							this.savingInProgress = false;
							this.savingDone = true;
						} else {
							this.setErrorFeedback( response );
						}
					}.bind( this ),
					function ( code, response ) {
						this.setErrorFeedback( response );
					}.bind( this )
				);
			}.bind( this ) ).catch( function ( e ) {
				this.setErrorFeedback( e );
			}.bind( this ) );
		},
		onBack: function () {
			this.routeBack();
		},
		onInput: function () {
			this.error = '';
		},
		setErrorFeedback: function ( response ) {
			this.overlay = false;
			this.savingInProgress = false;
			if ( response && response.error && response.error.info ) {
				this.error = response.error.info;
			} else {
				this.error = response;
				this.showUnknownErrorToast();
			}
			events.logPublishFailure(
				this.storyTitle,
				this.error
			);
		},
		showUnknownErrorToast: function () {
			this.toast.message = this.$i18n( 'wikistories-builder-publishform-saveerror' ).text();
			this.toast.show = true;
		},
		hideToast: function () {
			this.toast.message = '';
			this.toast.show = false;
			this.error = null;
		},
		shareStory: function () {
			const title = mw.Title.newFromUserInput( this.storyTitle, NS_STORY );
			const shareUrl = new mw.Uri( title.getUrl( { action: 'storyview' } ) );
			navigator.share( {
				title: title.getMainText(),
				url: shareUrl.toString()
			} );
		}
	} ),
	mounted: function () {
		if ( this.mode === 'edit' ) {
			this.storyTitle = this.title.replace( /_/g, ' ' );
			this.titleInputDisabled = true;
		} else {
			this.$refs.storyTitleInput.focus();
		}
		this.watchlistExpiry = this.watchlistExpiryOptions.default;
		this.watchlist = !!this.watchDefault;
	}
};
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.ext-wikistories-publishform {
	position: relative;
	height: 100%;
	display: flex;
	flex-direction: column;

	&-content {
		display: flex;
		position: relative;
		flex-direction: column;
		align-items: center;
		padding: 20px;
		flex-grow: 1;

		&-input-title {
			width: 100%;
			border: @border-width-thick @border-style-base @border-color-progressive;
			box-sizing: border-box;
			border-radius: @border-radius-base;
			height: 36px;
			padding: 10px;

			&:focus {
				outline-color: @border-color-progressive;
			}
		}

		&-watchlist {
			position: relative;
			width: 100%;
			margin: 12px 0;

			input {
				width: 23px;
				height: 23px;
				display: inline-block;
				vertical-align: middle;
			}

			label {
				display: inline-block;
				vertical-align: middle;
			}

			&-select {
				display: inline-block;
				vertical-align: middle;
				box-sizing: border-box;
				height: 2.3em;
				border: @border-base;
				border-radius: 2px;
				padding-left: 12px;
				padding-right: 1em;
				margin-left: 10px;

				&-disabled {
					color: @color-disabled;
					border-color: @border-color-disabled;
					background-color: @background-color-disabled-subtle;
				}
			}
		}

		&-error {
			font-size: 14px;
			color: @color-error;
			min-height: 60px;
			width: 100%;
		}

		&-info {
			position: absolute;
			font-size: 14px;
			top: 145px;
			width: 70%;
			text-align: center;
		}
	}

	&-license {
		font-size: 12px;
		background-color: #f8f9fa;
		padding: 20px;
	}

	&-saving {
		height: 100%;
		position: absolute;
		left: 0;
		right: 0;
		background-color: @background-color-base;
		opacity: 0.9;
		display: flex;
		flex-direction: column;
		justify-content: center;
		text-align: center;

		&-text {
			color: @color-base;
			font-weight: bold;
		}

		&-spinner {
			&-animation {
				width: 70px;
				opacity: 0.8;
				display: inline-block;
				white-space: nowrap;
			}

			&-animation &-animation-bounce,
			&-animation::before,
			&-animation::after {
				content: '';
				display: inline-block;
				width: 12px;
				height: 12px;
				background-color: #72777d;
				border-radius: 100%;
				-webkit-animation: bounce 1.4s infinite ease-in-out;
				animation: bounce 1.4s infinite ease-in-out;
				-webkit-animation-fill-mode: both;
				animation-fill-mode: both;
				-webkit-animation-delay: -0.16s;
				animation-delay: -0.16s;
			}

			&-animation::before {
				-webkit-animation-delay: -0.33s;
				animation-delay: -0.33s;
			}

			&-animation::after {
				-webkit-animation-delay: -0.01s;
				animation-delay: -0.01s;
			}

			@-webkit-keyframes bounce {
				0%,
				100%,
				80% {
					-webkit-transform: scale( 0.6 );
					transform: scale( 0.6 );
				}

				40% {
					-webkit-transform: scale( 1 );
					transform: scale( 1 );
					background-color: #bbb;
				}
			}

			@keyframes bounce {
				0%,
				100%,
				80% {
					-webkit-transform: scale( 0.6 );
					-ms-transform: scale( 0.6 );
					transform: scale( 0.6 );
				}

				40% {
					-webkit-transform: scale( 1 );
					-ms-transform: scale( 1 );
					transform: scale( 1 );
				}
			}
		}

		&-done {
			font-size: 16px;
			font-weight: bold;

			&-image {
				background-image: url( ./../images/congrats.svg );
				height: 50px;
				width: 50px;
				margin: auto;
				background-repeat: no-repeat;
				background-size: contain;
				line-height: 50px;
				font-size: 30px;
				color: @color-inverted;
			}

			&-congrats {
				margin: 10px;
			}

			&-read {
				padding: 10px;
				background-color: @background-color-progressive;
				// stylelint-disable-next-line declaration-no-important
				color: @color-inverted !important;
			}

			&-share {
				background-image: url( ./../images/share-black.svg );
				background-repeat: no-repeat;
				background-position: left;
				padding-left: 30px;
			}
		}
	}
}
</style>
