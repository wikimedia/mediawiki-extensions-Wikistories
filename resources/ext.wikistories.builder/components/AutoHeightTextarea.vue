<template>
	<div class="ext-wikistories-current-frame-text">
		<textarea
			ref="textarea"
			v-model="storyText"
			class="ext-wikistories-current-frame-text-textarea-content"
			:maxlength="maxLength"
			@focus="onFocus"
			@blur="onBlur"
		></textarea>
		<div
			v-if="editingText"
			class="ext-wikistories-current-frame-text-edit-guide"
			:class="editGuideIcon ? 'ext-wikistories-current-frame-text-edit-guide-icon-' + editGuideIcon : ''">
			{{ editGuideMessage }}
		</div>
	</div>
</template>

<script>
const mapGetters = require( 'vuex' ).mapGetters;
const mapActions = require( 'vuex' ).mapActions;
const MAX_TEXT_LENGTH = mw.config.get( 'wgWikistoriesMaxTextLength' );
const TEXT_EDIT_THRESHOLD = mw.config.get( 'wgWikistoriesUnmodifiedTextThreshold' );
const calculateUnmodifiedContent = require( '../util/calculateUnmodifiedContent.js' );

// @vue/component
module.exports = {
	name: 'AutoHeightTextarea',
	props: {
		onFocus: {
			type: Function,
			default: () => {}
		},
		onBlur: {
			type: Function,
			default: () => {}
		}
	},
	data: function () {
		return {
			editGuideMessage: this.$i18n( 'wikistories-story-edittext-initial' ).text(),
			editGuideIcon: 'edit_reference'
		};
	},
	computed: $.extend( mapGetters( [ 'currentFrame', 'editingText' ] ), {
		storyText: {
			get: function () {
				return this.currentFrame.text;
			},
			set: function ( value ) {
				return this.setText( value );
			}
		},
		maxLength: function () {
			return MAX_TEXT_LENGTH;
		}
	} ),
	methods: $.extend( mapActions( [ 'setText' ] ), {
		setHeight: function () {
			const textarea = this.$refs.textarea;
			// 'height: auto' resets the textarea height before setting the height to scrollHeight
			textarea.style.height = 'auto';
			textarea.style.height = textarea.scrollHeight + 'px';
		}
	} ),
	watch: {
		storyText: function () {
			if ( !this.storyText ) {
				return;
			}

			const unmodified = calculateUnmodifiedContent( this.currentFrame.textFromArticle, this.storyText );

			if ( unmodified === 1 ) {
				this.editGuideMessage = this.$i18n( 'wikistories-story-edittext-initial' ).text();
				this.editGuideIcon = 'edit_reference';
			} else if ( unmodified < TEXT_EDIT_THRESHOLD ) {
				this.editGuideMessage = this.$i18n( 'wikistories-story-edittext-last' ).text();
				this.editGuideIcon = 'alert';
			} else {
				this.editGuideMessage = this.$i18n( 'wikistories-story-edittext-medium' ).text();
				this.editGuideIcon = 'alert';
			}
		}
	},
	mounted: function () {
		this.setHeight();
	},
	updated: function () {
		this.setHeight();
	}
};
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.ext-wikistories-current-frame-text {
	position: absolute;
	bottom: 60px;
	left: 16px;
	right: 16px;
	width: calc( ~'100% - 32px' );
	height: auto;
	border-radius: @border-radius-base;
	background: linear-gradient( 0deg, #fff, #fff, #fff );
	box-shadow: @box-shadow-drop-medium;
	z-index: 92;
	box-sizing: border-box;

	&-textarea {
		&-content {
			width: calc( ~'100% - 32px' );
			padding: 8px 20px 0 8px;
			margin: auto;
			max-height: 350px;
			min-height: 10%;
			outline: 0;
			border: 0;
			resize: none;
			text-align: left;
			font-size: 18px;
			line-height: 27px;
			-webkit-appearance: none;

			&::-webkit-scrollbar {
				display: none;
			}
		}
	}

	&-edit-guide {
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
		}
	}
}
</style>
