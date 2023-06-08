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
	</div>
</template>

<script>
const mapGetters = require( 'vuex' ).mapGetters;
const mapActions = require( 'vuex' ).mapActions;
const MAX_TEXT_LENGTH = mw.config.get( 'wgWikistoriesMaxTextLength' );

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
}
</style>
