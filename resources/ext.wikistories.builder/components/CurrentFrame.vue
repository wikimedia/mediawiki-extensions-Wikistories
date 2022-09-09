<template>
	<div class="ext-wikistories-current-frame" :style="currentFrame.style">
		<div v-show="editingText" class="ext-wikistories-current-frame-overlay">
			<div class="ext-wikistories-current-frame-overlay-done">
				{{ $i18n( 'wikistories-story-editdone' ).text() }}
			</div>
		</div>
		<editable-textarea
			v-if="currentFrame.text || editingText"
			class="ext-wikistories-current-frame-textbox-content"
			:on-focus="beginTextEdit"
			:on-blur="endTextEdit"
		></editable-textarea>
		<div
			v-else
			class="ext-wikistories-current-frame-textbox-select-cta"
			@click="$emit( 'select-text' )"
		>
			<span v-html="$i18n( 'wikistories-story-selecttext' ).text()"></span>
		</div>
		<image-attribution v-show="showImageAttribution"></image-attribution>
	</div>
</template>

<script>
const mapActions = require( 'vuex' ).mapActions;
const mapGetters = require( 'vuex' ).mapGetters;
const ImageAttribution = require( './ImageAttribution.vue' );
const AutoHeightTextarea = require( './AutoHeightTextarea.vue' );

// @vue/component
module.exports = {
	name: 'CurrentFrame',
	components: {
		'image-attribution': ImageAttribution,
		'editable-textarea': AutoHeightTextarea
	},
	emits: [ 'select-text', 'edit-text' ],
	data: function () {
		return {
			editingText: false
		};
	},
	computed: $.extend( mapGetters( [ 'currentFrame' ] ), {
		showImageAttribution: function () {
			return !this.editingText && !this.currentFrame.fileNotFound;
		}
	} ),
	methods: $.extend( mapActions( [ 'setText' ] ), {
		beginTextEdit: function () {
			this.editingText = true;
			this.$emit( 'edit-text', true );
		},
		endTextEdit: function () {
			this.editingText = false;
			this.setText( this.currentFrame.text.trim() );
			this.$emit( 'edit-text', false );
		}
	} )
};
</script>

<style lang="less">
@import 'mediawiki.ui/variables.less';

.ext-wikistories-current-frame {
	flex-grow: 1;
	position: relative;
	text-align: center;

	&-textbox-content {
		position: absolute;
		bottom: 60px;
		left: 0;
		right: 0;
		padding: 10px 13px 10px 13px;
		margin: auto;
		max-height: 40%;
		min-height: 10%;
		border-radius: 2px;
		background: linear-gradient( 0deg, #fff, #fff, #fff );
		box-shadow: 0 2px 2px rgba( 0, 0, 0, 0.25 );
		outline: 0;
		border: 0;
		width: 90%;
		resize: none;
		z-index: 92;
		box-sizing: border-box;
		text-align: left;
		font-size: 18px;
		line-height: 27px;

		&::-webkit-scrollbar {
			display: none;
		}
	}

	&-textbox-select-cta {
		position: absolute;
		bottom: 60px;
		left: 0;
		right: 0;
		margin: auto;
		border-radius: 2px;
		background: linear-gradient( 0deg, #fff, #fff, #fff );
		box-shadow: 0 2px 2px rgba( 0, 0, 0, 0.25 );
		display: flex;
		align-items: center;
		width: 85%;
		height: 70px;
		text-align: left;
		padding: 10px 15px;
		font-size: 14px;

		.ext-wikistories-wikipedia {
			color: @color-primary;
			margin-left: 4px;
			font-weight: 500;
		}
	}

	&-overlay {
		position: absolute;
		height: 100%;
		width: 100%;
		background: rgba( 0, 0, 0, 0.5 );
		z-index: 90;

		&-done {
			position: absolute;
			color: #fff;
			z-index: 91;
			top: 0;
			right: 0;
			padding: 16px;
			font-size: 16px;
			cursor: pointer;
		}
	}
}
</style>
