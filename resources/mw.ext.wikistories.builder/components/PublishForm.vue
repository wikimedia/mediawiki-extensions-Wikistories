<template>
	<div class="ext-wikistories-publishform">
		<div class="ext-wikistories-publishform-header">
			<span @click="$emit( 'cancel-publish' )">X</span>
			<span>{{ $i18n( 'wikistories-builder-publishform-header' ).text() }}</span>
		</div>
		<textarea
			ref="storyTitleTextarea"
			v-model="storyTitle"
			:placeholder="$i18n( 'wikistories-builder-publishform-placeholder' ).text()"
		></textarea>
		<div class="ext-wikistories-publishform-error">
			{{ error }}
		</div>
		<primary-button
			:text="$i18n( 'wikistories-builder-publishform-publishbutton' ).text()"
			@click="onSaveClick"
		></primary-button>
		<div class="ext-wikistories-publishform-info">
			{{ $i18n( 'wikistories-builder-publishform-info' ).text() }}
		</div>
		<div class="ext-wikistories-publishform-license">
			<!-- TODO: add license here-->
			Licence
		</div>
	</div>
</template>

<script>
const mapGetters = require( 'vuex' ).mapGetters;
const PrimaryButton = require( '../components/PrimaryButton.vue' );
const saveStory = require( '../api/saveStory.js' );
const validateTitle = require( '../util/validateTitle.js' );
const NS_STORY = mw.config.get( 'wgNamespaceIds' ).story;

// @vue/component
module.exports = {
	name: 'PublishForm',
	components: {
		'primary-button': PrimaryButton
	},
	emits: [ 'cancel-publish' ],
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
		}
	},
	mounted: function () {
		this.$refs.storyTitleTextarea.focus();
	}
};
</script>

<style lang="less">
.ext-wikistories-publishform {
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 20px;
	height: 100%;
	gap: 20px;

	&-header {
		border-bottom: 1px solid #000;
		padding-bottom: 5px;
		width: 100%;
	}

	textarea {
		width: 100%;
		border: 0;
	}

	&-error {
		color: #a22;
		min-height: 40px;
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
</style>
