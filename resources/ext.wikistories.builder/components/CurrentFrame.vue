<template>
	<div class="ext-wikistories-current-frame">
		<story-image
			:src="currentFrame.imageSrc"
			:rect="currentFrame.imgFocalRect"
			:alt="currentFrame.filename"
			:thumbnail="false"
			:allow-gestures="true"
			@update-focal-rect="onUpdateFocalRect"></story-image>
		<div v-show="editingText" class="ext-wikistories-current-frame-overlay"></div>
		<editable-textarea
			v-if="currentFrame.text || editingText"
			:on-focus="beginTextEdit"
			:on-blur="endTextEdit"
			:class="showWarningMessage ? 'ext-wikistories-current-frame-text-extra-padding' : ''"
		></editable-textarea>
		<div
			v-else
			class="ext-wikistories-current-frame-textbox-select-cta"
			@click="$emit( 'select-text' )"
		>
			<span v-html="$i18n( 'wikistories-story-selecttext' ).text()"></span>
		</div>
		<div
			v-if="showWarningMessage"
			class="ext-wikistories-current-frame-edit-guide"
			:class="'ext-wikistories-current-frame-edit-guide-icon-' + currentFrame.warning.icon">
			<span
				v-if="currentFrame.warning.replace"
				@click="$event => $emit( 'select-text' )"
				v-html="currentFrame.warning.message"></span>
			<span v-else>{{ currentFrame.warning.message }}</span>
		</div>
		<image-attribution v-show="showImageAttribution"></image-attribution>
	</div>
</template>

<script>
const mapActions = require( 'vuex' ).mapActions;
const mapGetters = require( 'vuex' ).mapGetters;
const ImageAttribution = require( './ImageAttribution.vue' );
const StoryImage = require( '../StoryImage.vue' );
const AutoHeightTextarea = require( './AutoHeightTextarea.vue' );

// @vue/component
module.exports = {
	name: 'CurrentFrame',
	components: {
		'image-attribution': ImageAttribution,
		'story-image': StoryImage,
		'editable-textarea': AutoHeightTextarea
	},
	emits: [ 'select-text' ],
	computed: $.extend( mapGetters( [ 'currentFrame', 'editingText' ] ), {
		showImageAttribution: function () {
			return !this.editingText && !this.currentFrame.fileNotFound;
		},
		showWarningMessage: function () {
			return this.currentFrame.warning &&
				( this.currentFrame.warning.isAlwaysShown || this.editingText );
		}
	} ),
	methods: $.extend( mapActions( [ 'setText', 'setEditingText', 'setLastEditedText', 'setImageFocalRect' ] ), {
		beginTextEdit: function () {
			this.setEditingText( true );
			this.setLastEditedText( this.currentFrame.text );
		},
		endTextEdit: function () {
			// setEditingText is set by navigator discard button
			this.setText( this.currentFrame.text.trim() );
		},
		onUpdateFocalRect: function ( rect ) {
			this.setImageFocalRect( rect );
		}
	} )
};
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.ext-wikistories-current-frame {
	flex-grow: 1;
	position: relative;

	&-textbox-select-cta {
		position: absolute;
		bottom: 60px;
		left: 0;
		right: 0;
		margin: auto;
		border-radius: @border-radius-base;
		background: linear-gradient( 0deg, #fff, #fff, #fff );
		box-shadow: @box-shadow-drop-medium;
		display: flex;
		align-items: center;
		width: 85%;
		height: 72px;
		text-align: left;
		padding: 10px 15px;
		font-size: 14px;
		color: #72777d;

		.ext-wikistories-wikipedia {
			color: @color-progressive;
			margin-left: 4px;
			font-weight: 500;
		}
	}

	&-text-extra-padding {
		padding-bottom: 38px;
	}

	&-edit-guide {
		position: absolute;
		bottom: 60px;
		left: 16px;
		right: 16px;
		z-index: 100;
		color: @color-subtle;
		font-size: 14px;
		margin: 0 8px;
		padding: 6px 0;
		padding-left: 24px;
		text-align: left;
		border-top: @border-width-base @border-style-base @border-color-subtle;
		background-repeat: no-repeat;
		background-position: 0 center;

		&-icon {
			&-alert {
				background-image: url( ../images/alert.svg );
			}

			&-edit_reference {
				background-image: url( ../images/edit_reference.svg );
			}

			&-warning {
				background-image: url( ../images/warning.svg );
			}
		}

		.ext-wikistories-warning-replace {
			cursor: pointer;
			font-weight: 500;
			color: @color-progressive;
		}
	}

	&-overlay {
		position: absolute;
		height: 100%;
		width: 100%;
		background: rgba( 0, 0, 0, 0.5 );
		z-index: 90;
	}
}
</style>
