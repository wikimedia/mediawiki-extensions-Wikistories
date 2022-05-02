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
		<div class="ext-wikistories-publishform-content">
			<input
				ref="storyTitleInput"
				v-model="storyTitle"
				type="text"
				maxlength="255"
				class="ext-wikistories-publishform-content-input-title"
				:placeholder="$i18n( 'wikistories-builder-publishform-placeholder' ).text()"
			>
			<div class="ext-wikistories-publishform-content-error">
				{{ error }}
			</div>
			<div class="ext-wikistories-publishform-content-info">
				{{ $i18n( 'wikistories-builder-publishform-info' ).text() }}
			</div>
			<div class="ext-wikistories-publishform-content-license">
				<!-- TODO: add license here-->
				Licence
			</div>
		</div>
	</div>
</template>

<script>
const mapGetters = require( 'vuex' ).mapGetters;
const Navigator = require( '../components/Navigator.vue' );
const saveStory = require( '../api/saveStory.js' );
const validateTitle = require( '../util/validateTitle.js' );
const events = require( '../contributionEvents.js' );
const NS_STORY = mw.config.get( 'wgNamespaceIds' ).story;

// @vue/component
module.exports = {
	name: 'PublishForm',
	components: {
		navigator: Navigator
	},
	data: function () {
		return {
			storyTitle: '',
			error: null
		};
	},
	computed: mapGetters( [ 'frames', 'valid', 'fromArticle', 'storyForSave' ] ),
	methods: {
		navigateToArticle: function ( storyPageId ) {
			const titleObj = mw.Title.newFromText( this.fromArticle + '#/story/' + storyPageId );
			window.location = titleObj.getUrl( { wikistories: 1 } );
		},
		onSaveClick: function () {
			this.error = null;
			validateTitle( this.storyTitle ).then( function ( validity ) {
				if ( !validity.valid ) {
					this.error = this.$i18n( validity.message ).text();
					events.logPublishFailure( this.storyTitle, this.error );
					return;
				}
				const title = mw.Title.newFromUserInput( this.storyTitle, NS_STORY );
				saveStory( title.getPrefixedDb(), this.storyForSave ).then(
					function ( response ) {
						// response is { result, title, newrevid, pageid, and more }
						if ( response.result === 'Success' ) {
							events.logPublishSuccess( this.storyTitle );
							this.navigateToArticle( response.pageid );
						} else {
							this.error = this.$i18n( 'wikistories-builder-publishform-saveerror' ).text();
							events.logPublishFailure(
								this.storyTitle,
								response.error.code || this.error
							);
						}
					}.bind( this ),
					function () {
						this.error = this.$i18n( 'wikistories-builder-publishform-saveerror' ).text();
						events.logPublishFailure( this.storyTitle, this.error );
					}.bind( this )
				);
			}.bind( this ) );
		},
		onBack: function () {
			this.$router.back();
		}
	},
	mounted: function () {
		this.$refs.storyTitleInput.focus();
	}
};
</script>

<style lang="less">
@import 'mediawiki.ui/variables.less';

.ext-wikistories-publishform {
	position: relative;
	height: 94%;

	&-content {
		display: flex;
		flex-direction: column;
		align-items: center;
		padding: 20px;
		height: 100%;

		&-input-title {
			width: 100%;
			border: 1px solid #a2a9b1;
			box-sizing: border-box;
			border-radius: 2px;
			height: 2em;
			padding: 10px;
		}

		&-error {
			color: @color-error;
			min-height: 60px;
			width: 100%;
		}

		&-info {
			font-size: 0.7em;
		}

		&-license {
			margin: auto 0 20px 0;
			font-size: 0.5em;
			background-color: #919fb9;
			padding: 20px;
			width: 100%;
		}
	}
}
</style>
