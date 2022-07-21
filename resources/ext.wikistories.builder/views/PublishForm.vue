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
		<div v-if="savingInProgress" class="ext-wikistories-publishform-saving">
			<div class="ext-wikistories-publishform-saving-spinner">
				<div class="ext-wikistories-publishform-saving-spinner-animation">
					<div class="ext-wikistories-publishform-saving-spinner-animation-bounce"></div>
				</div>
			</div>
			<div class="ext-wikistories-publishform-saving-text">
				{{ $i18n( 'wikistories-builder-publishform-saving' ).text() }}
			</div>
		</div>
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
			>
			<div v-if="knownError" class="ext-wikistories-publishform-content-error">
				{{ error }}
			</div>
			<div class="ext-wikistories-publishform-content-info">
				{{ $i18n( 'wikistories-builder-publishform-info' ).text() }}
			</div>
		</div>
		<div class="ext-wikistories-publishform-license" v-html="licenseHtml">
		</div>
	</div>
</template>

<script>
const mapGetters = require( 'vuex' ).mapGetters;
const Navigator = require( '../components/Navigator.vue' );
const Toast = require( '../components/Toast.vue' );
const saveStory = require( '../api/saveStory.js' );
const validateTitle = require( '../util/validateTitle.js' );
const events = require( '../contributionEvents.js' );
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
			error: null,
			toast: {
				show: false,
				message: '',
				mode: 'error'
			},
			titleInputDisabled: false,
			savingInProgress: false
		};
	},
	computed: $.extend( mapGetters( [ 'frames', 'valid', 'fromArticle', 'storyForSave', 'mode', 'title' ] ), {
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
		}
	} ),
	methods: {
		navigateToArticle: function ( storyPageId ) {
			const titleObj = mw.Title.newFromText( this.fromArticle + '#/story/' + storyPageId );
			window.location = titleObj.getUrl();
		},
		onSaveClick: function () {
			this.error = null;
			this.savingInProgress = true;
			const mustExist = this.mode === 'edit';
			validateTitle( this.storyTitle, mustExist ).then( function ( validity ) {
				if ( !validity.valid ) {
					this.savingInProgress = false;
					this.error = this.$i18n( validity.message ).text();
					events.logPublishFailure( this.storyTitle, this.error );
					return;
				}
				const title = mw.Title.newFromUserInput( this.storyTitle, NS_STORY );
				saveStory( title.getPrefixedDb(), this.storyForSave, this.mode ).then(
					function ( response ) {
						// response is { result, title, newrevid, pageid, and more }
						if ( response.result === 'Success' ) {
							events.logPublishSuccess( this.storyTitle );
							this.navigateToArticle( response.pageid );
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
			this.$router.back();
		},
		setErrorFeedback: function ( response ) {
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
		}
	},
	mounted: function () {
		if ( this.mode === 'edit' ) {
			this.storyTitle = this.title.replace( /_/g, ' ' );
			this.titleInputDisabled = true;
		} else {
			this.$refs.storyTitleInput.focus();
		}
	}
};
</script>

<style lang="less">
@import 'mediawiki.ui/variables.less';

.ext-wikistories-publishform {
	position: relative;
	height: 100%;
	display: flex;
	flex-direction: column;

	&-content {
		display: flex;
		flex-direction: column;
		align-items: center;
		padding: 20px;
		flex-grow: 1;

		&-input-title {
			width: 100%;
			border: 2px solid @colorGray10;
			box-sizing: border-box;
			border-radius: 2px;
			height: 36px;
			padding: 10px;

			&:focus {
				outline-color: @color-primary;
			}
		}

		&-error {
			font-size: 14px;
			color: @color-error;
			min-height: 60px;
			width: 100%;
		}

		&-info {
			font-size: 14px;
			margin-top: 45px;
		}
	}

	&-license {
		font-size: 12px;
		background-color: @colorGray15;
		padding: 20px;
	}

	&-saving {
		height: 100%;
		position: absolute;
		left: 0;
		right: 0;
		background-color: #fff;
		opacity: 0.9;
		display: flex;
		flex-direction: column;
		justify-content: center;
		text-align: center;

		&-text {
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
			&-animation:before,
			&-animation:after {
				content: '';
				display: inline-block;
				width: 12px;
				height: 12px;
				background-color: #c8ccd1;
				border-radius: 100%;
				-webkit-animation: bounce 1.4s infinite ease-in-out;
				animation: bounce 1.4s infinite ease-in-out;
				-webkit-animation-fill-mode: both;
				animation-fill-mode: both;
				-webkit-animation-delay: -0.16s;
				animation-delay: -0.16s;
			}

			&-animation:before {
				-webkit-animation-delay: -0.33s;
				animation-delay: -0.33s;
			}

			&-animation:after {
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
	}
}
</style>