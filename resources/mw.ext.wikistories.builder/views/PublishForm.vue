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
			<textarea
				ref="storyTitleTextarea"
				v-model="storyTitle"
				class="ext-wikistories-publishform-content-textarea"
				:placeholder="$i18n( 'wikistories-builder-publishform-placeholder' ).text()"
			></textarea>
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
			window.location = titleObj.getUrl();
		},
		onSaveClick: function () {
			this.error = null;
			validateTitle( this.storyTitle ).then( function ( validity ) {
				if ( !validity.valid ) {
					this.error = this.$i18n( validity.message ).text();
					return;
				}
				const title = mw.Title.newFromUserInput( this.storyTitle, NS_STORY );
				saveStory( title.getPrefixedDb(), this.storyForSave ).then(
					function ( response ) {
						// response is { result, title, newrevid, pageid, and more }
						if ( response.result === 'Success' ) {
							this.navigateToArticle( response.pageid );
						} else {
							this.error = this.$i18n( 'wikistories-builder-publishform-saveerror' ).text();
						}
					}.bind( this ),
					function () {
						this.error = this.$i18n( 'wikistories-builder-publishform-saveerror' ).text();
					}.bind( this )
				);
			}.bind( this ) );
		},
		onBack: function () {
			this.$router.back();
		}
	},
	mounted: function () {
		this.$refs.storyTitleTextarea.focus();
	}
};
</script>

<style lang="less">
.ext-wikistories-publishform {
	position: relative;
	height: 94%;

	&-content {
		display: flex;
		flex-direction: column;
		align-items: center;
		padding: 20px;
		height: 100%;

		&-textarea {
			width: 100%;
			border: 0;
			border: 1px solid #a2a9b1;
			box-sizing: border-box;
			border-radius: 2px;
			resize: none;
		}

		&-error {
			color: #a22;
			min-height: 40px;
			text-align: center;
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
