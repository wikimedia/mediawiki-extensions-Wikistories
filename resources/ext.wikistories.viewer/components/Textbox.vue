<template>
	<div v-if="content">
		<div
			ref="frameTextbox"
			class="ext-wikistories-viewer-container-content-story-text"
			:style="textStyle"
			@scroll="onScroll">
			{{ content }}
		</div>
		<div
			:class="`ext-wikistories-viewer-container-content-text-fade ${scrollCueClassName}`">
		</div>
	</div>
</template>

<script>
// @vue/component
module.exports = {
	name: 'Textbox',
	props: {
		isPaused: {
			type: Boolean,
			default: false
		},
		textsize: {
			type: String,
			required: true
		},
		textsizes: {
			type: Object,
			required: true
		},
		content: {
			type: String,
			default: null
		}
	},
	emits: [ 'scroll-pause' ],
	data: function () {
		return {
			isRefElementScrollable: false,
			scrollCueClassName: ''
		};
	},
	computed: {
		textStyle: function () {
			return {
				fontSize: this.textsizes[ this.textsize ] + '%'
			};
		}
	},
	methods: {
		onScroll: function ( e ) {
			const scrollTop = e.target.scrollTop;
			const scrollHeight = e.target.scrollHeight;
			const clientHeight = e.target.clientHeight;

			if ( !this.isPaused && scrollTop > 0 ) {
				this.$emit( 'scroll-pause' );
			}

			if ( this.isRefElementScrollable && scrollTop === 0 ) {
				this.scrollCueClassName = 'ext-wikistories-viewer-container-content-text-fade-bottom';
			} else if ( this.isRefElementScrollable && scrollTop + clientHeight >= scrollHeight ) {
				this.scrollCueClassName = 'ext-wikistories-viewer-container-content-text-fade-top';
			} else {
				this.scrollCueClassName = '';
			}
		},
		updateRefElement: function () {
			const textBoxRef = this.$refs.frameTextbox;
			if ( textBoxRef ) {
				this.isRefElementScrollable = textBoxRef.scrollHeight > textBoxRef.clientHeight;
			}
		},
		resetRefElementScrollTop: function () {
			const textBoxRef = this.$refs.frameTextbox;
			if ( textBoxRef ) {
				textBoxRef.scrollTo( 0, 0 );
			}
		}
	},
	watch: {
		isRefElementScrollable: function () {
			// Initialize text fade cue
			if ( this.isRefElementScrollable ) {
				this.scrollCueClassName = 'ext-wikistories-viewer-container-content-text-fade-bottom';
			} else {
				this.scrollCueClassName = '';
			}
		},
		content: function () {
			this.resetRefElementScrollTop();
		}
	},
	mounted: function () {
		this.updateRefElement();
	},
	updated: function () {
		this.updateRefElement();
	}
};
</script>

<style lang="less">
	@import 'mediawiki.skin.variables.less';

	.ext-wikistories-viewer-container-content-story-text {
		position: absolute;
		bottom: 60px;
		left: 16px;
		right: 16px;
		border-radius: @border-radius-base;
		background: @background-color-base;
		box-shadow: @box-shadow-drop-medium;
		margin: 0;
		padding: 8px 20px 8px 8px;
		max-height: 25vh;
		overflow: scroll;
	}

	.ext-wikistories-viewer-container-content-text-fade {
		position: absolute;
		bottom: 60px;
		left: 16px;
		right: 16px;
		height: 40px;
		z-index: 93;
		margin: auto;
		border-radius: @border-radius-base;

		&-top {
			bottom: calc( ~'25vh + 36px' );
			background: linear-gradient( to top, transparent 0%, @background-color-base 100% );
		}

		&-bottom {
			background: linear-gradient( to bottom, transparent 0%, @background-color-base 100% );
		}
	}
</style>
