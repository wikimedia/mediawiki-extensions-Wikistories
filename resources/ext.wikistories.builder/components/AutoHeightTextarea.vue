<template>
	<div>
		<textarea
			ref="textarea"
			v-model="storyText"
			class="ext-wikistories-current-frame-textarea-content"
			:maxlength="maxLength"
			@focus="onFocus"
			@blur="onBlur"
			@scroll="onScroll"
		></textarea>
		<div :class="`ext-wikistories-current-frame-textarea-fade ${scrollCueClassName}`"></div>
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
	data: function () {
		return {
			isRefElementScrollable: false,
			scrollCueClassName: ''
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
		},
		onScroll: function ( e ) {
			const scrollTop = e.target.scrollTop;
			const scrollHeight = e.target.scrollHeight;
			const clientHeight = e.target.clientHeight;

			if ( this.editingText ) {
				this.scrollCueClassName = '';
				return;
			}

			if ( this.isRefElementScrollable && scrollTop === 0 ) {
				this.scrollCueClassName = 'ext-wikistories-current-frame-textarea-fade-bottom';
			} else if ( this.isRefElementScrollable && scrollTop + clientHeight >= scrollHeight ) {
				this.scrollCueClassName = 'ext-wikistories-current-frame-textarea-fade-top';
			} else {
				this.scrollCueClassName = '';
			}
		},
		updateRefElement: function () {
			const textareaRef = this.$refs.textarea;
			if ( textareaRef ) {
				this.isRefElementScrollable = textareaRef.scrollHeight > textareaRef.clientHeight;
			}
		}
	} ),
	watch: {
		isRefElementScrollable: function () {
			// Initialize text fade cue
			if ( this.isRefElementScrollable ) {
				this.scrollCueClassName = 'ext-wikistories-current-frame-textarea-fade-bottom';
			} else {
				this.scrollCueClassName = '';
			}
		}
	},
	mounted: function () {
		this.setHeight();
		this.updateRefElement();
	},
	updated: function () {
		this.setHeight();
		this.updateRefElement();
	}
};
</script>

<style lang="less">
	.ext-wikistories-current-frame-textarea {
		&-content {
			position: absolute;
			bottom: 60px;
			left: 16px;
			right: 16px;
			padding: 8px 20px 8px 8px;
			margin: auto;
			max-height: 350px;
			min-height: 10%;
			border-radius: 2px;
			background: linear-gradient( 0deg, #fff, #fff, #fff );
			box-shadow: 0 2px 2px rgba( 0, 0, 0, 0.25 );
			outline: 0;
			border: 0;
			resize: none;
			z-index: 92;
			box-sizing: border-box;
			text-align: left;
			font-size: 18px;
			line-height: 27px;
			-webkit-appearance: none;

			&::-webkit-scrollbar {
				display: none;
			}
		}

		&-fade {
			position: absolute;
			display: none;
			bottom: 90px;
			left: 16px;
			right: 16px;
			height: 35px;
			z-index: 93;
			margin: auto;
			border-radius: 2px;

			&-top {
				display: block;
				bottom: 375px;
				background: linear-gradient( to top, rgba( 255, 255, 255, 0 ) 0%, #fff 50% );
			}

			&-bottom {
				display: block;
				bottom: 60px;
				background: linear-gradient( to bottom, rgba( 255, 255, 255, 0 ) 0%, #fff 50% );
			}
		}
	}
</style>
