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
		<story-textbox
			:on-focus="beginTextEdit"
			:on-blur="endTextEdit"
			:on-select="$event => $emit( 'select-text' )"
		></story-textbox>
		<image-attribution v-show="showImageAttribution"></image-attribution>
	</div>
</template>

<script>
const mapActions = require( 'vuex' ).mapActions;
const mapGetters = require( 'vuex' ).mapGetters;
const ImageAttribution = require( './ImageAttribution.vue' );
const StoryImage = require( '../StoryImage.vue' );
const StoryTextbox = require( './StoryTextbox.vue' );

// @vue/component
module.exports = {
	name: 'CurrentFrame',
	components: {
		'image-attribution': ImageAttribution,
		'story-image': StoryImage,
		'story-textbox': StoryTextbox
	},
	emits: [ 'select-text' ],
	computed: $.extend( mapGetters( [ 'currentFrame', 'editingText' ] ), {
		showImageAttribution: function () {
			return !this.editingText && !this.currentFrame.fileNotFound;
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

	&-overlay {
		position: absolute;
		height: 100%;
		width: 100%;
		background: rgba( 0, 0, 0, 0.5 );
		z-index: 90;
	}
}
</style>
