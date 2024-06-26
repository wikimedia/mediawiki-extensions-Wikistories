<template>
	<div v-if="textSelected" class="ext-wikistories-current-frame-text">
		<textarea
			ref="textarea"
			v-model="storyText"
			class="ext-wikistories-current-frame-text-textarea-content"
			:maxlength="maxLength"
			@focus="onFocus"
			@blur="onBlur"
		></textarea>
		<div v-if="showWarningMessage" class="ext-wikistories-current-frame-text-edit-guide">
			<span
				class="ext-wikistories-current-frame-text-edit-guide-icon"
				:class="'ext-wikistories-current-frame-text-edit-guide-icon-' +
					currentFrame.warning.icon +
					( currentFrame.warning.invertIconInDarkMode ? ' skin-invert' : '' )"
			></span>
			<span class="ext-wikistories-current-frame-text-edit-guide-text">
				<span
					v-if="currentFrame.warning.replace"
					@click="onSelect"
					v-html="currentFrame.warning.message"></span>
				<span v-else>{{ currentFrame.warning.message }}</span>
			</span>
		</div>
	</div>
	<div
		v-else
		class="ext-wikistories-current-frame-textbox-select-cta"
		@click="onSelect"
	>
		<span v-html="$i18n( 'wikistories-story-selecttext' ).text()"></span>
	</div>
</template>

<script>
const mapGetters = require( 'vuex' ).mapGetters;
const mapActions = require( 'vuex' ).mapActions;
const MAX_TEXT_LENGTH = mw.config.get( 'wgWikistoriesMaxTextLength' );

// @vue/component
module.exports = {
	name: 'StoryTextbox',
	props: {
		onFocus: {
			type: Function,
			default: () => {}
		},
		onBlur: {
			type: Function,
			default: () => {}
		},
		onSelect: {
			type: Function,
			default: () => {}
		}
	},
	computed: Object.assign( mapGetters( [ 'currentFrame', 'editingText' ] ), {
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
		},
		showWarningMessage: function () {
			return this.currentFrame.warning &&
				( this.currentFrame.warning.isAlwaysShown || this.editingText );
		},
		textSelected: function () {
			return this.currentFrame.text || this.editingText;
		}
	} ),
	methods: Object.assign( mapActions( [ 'setText' ] ), {
		setHeight: function () {
			const textarea = this.$refs.textarea;
			// 'height: auto' resets the textarea height before setting the height to scrollHeight
			textarea.style.height = 'auto';
			textarea.style.height = textarea.scrollHeight + 'px';
		}
	} ),
	mounted: function () {
		if ( this.textSelected ) {
			this.setHeight();
		}
	},
	updated: function () {
		if ( this.textSelected ) {
			this.setHeight();
		}
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
	background-color: @background-color-base;
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
			color: @color-base;
			background-color: @background-color-base;

			&::-webkit-scrollbar {
				display: none;
			}
		}
	}

	&-edit-guide {
		z-index: 100;
		color: @color-subtle;
		font-size: 14px;
		margin: 0 8px;
		padding: 6px 0;
		text-align: left;
		border-top: @border-width-base @border-style-base @border-color-subtle;
		height: 24px;

		&-icon {
			display: inline-block;
			vertical-align: middle;
			width: 24px;
			height: 100%;
			background-repeat: no-repeat;
			background-position: 0 center;

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

		&-text {
			display: inline-block;
		}

		.ext-wikistories-warning-replace {
			cursor: pointer;
			font-weight: 500;
			color: @color-progressive;
		}
	}
}
</style>
