<template>
	<textarea
		ref="textarea"
		v-model="storyText"
		:maxlength="maxLength"
		@focus="onFocus"
		@blur="onBlur"
	></textarea>
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
	computed: $.extend( mapGetters( [ 'currentFrame' ] ), {
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
			const scrollHeight = textarea.scrollHeight;

			textarea.style.height = scrollHeight + 'px';
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
