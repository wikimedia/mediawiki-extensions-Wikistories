<template>
	<div class="ext-wikistories-frames">
		<div ref="thumbnails" class="ext-wikistories-frames-thumbnails">
			<div
				v-for="( frame, index ) in thumbnails"
				:key="frame.key || index"
				class="ext-wikistories-frames-thumbnails-frame"
				:class="{ 'ext-wikistories-frames-thumbnails-frame-selected': frame.selected }"
				@click="onClick( $event, index )"
			>
				<div
					v-if="frame.warning && frame.warning.isAlwaysShown"
					class="ext-wikistories-frames-thumbnails-frame-warning skin-invert"
				></div>
				<story-image
					:src="frame.url"
					:rect="frame.focalRect"
					:alt="frame.filename"
					:thumbnail="true"
					:allow-gestures="false"
					class="ext-wikistories-frames-image-container"
				></story-image>
			</div>
		</div>
		<div class="ext-wikistories-frames-btn-add" @click="addFrames">
			+
		</div>
	</div>
</template>

<script>
const mapGetters = require( 'vuex' ).mapGetters;
const mapActions = require( 'vuex' ).mapActions;
const sortable = require( '../util/sortableFrames.js' );
const StoryImage = require( '../StoryImage.vue' );

// @vue/component
module.exports = {
	name: 'Frames',
	components: {
		'story-image': StoryImage
	},
	emits: [ 'max-limit' ],
	computed: mapGetters( [ 'thumbnails', 'maxFrames' ] ),
	methods: $.extend( mapActions( [ 'selectFrame', 'reorderFrames', 'routePush' ] ), {
		addFrames: function () {
			if ( this.maxFrames ) {
				this.$emit( 'max-limit' );
			} else {
				this.routePush( 'searchMany' );
			}
		},
		preventContextMenuOnElement: function ( event ) {
			event.target.oncontextmenu = function ( e ) {
				e.preventDefault();
				e.stopPropagation();
				e.stopImmediatePropagation();
				return false;
			};
		},
		onClick: function ( e, index ) {
			this.preventContextMenuOnElement( e );
			this.selectFrame( index );
		}
	} ),
	mounted: function () {
		sortable.setup(
			'ext-wikistories-frames-thumbnails',
			'ext-wikistories-frames-thumbnails-frame',
			this.reorderFrames
		);
		const selected = this.$refs.thumbnails.querySelector(
			'.ext-wikistories-frames-thumbnails-frame-selected'
		);
		if ( selected ) {
			selected.scrollIntoView( { block: 'end', inline: 'center' } );
		}
	},
	unmounted: function () {
		sortable.kill();
	}
};
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.ext-wikistories {
	&-frames {
		width: 100%;
		display: flex;
		flex-direction: row;
		border-top: @border-base;

		&-thumbnails {
			padding: 12px 0;
			flex-grow: 1;
			overflow-x: auto;
			display: flex;
			flex-direction: row;

			&-frame {
				position: relative;
				width: 28px;
				height: 36px;
				margin-left: 16px;
				cursor: pointer;
				flex-shrink: 0;
				border-radius: @border-radius-base;

				&:last-of-type {
					margin-right: 16px;
				}

				&-selected::before {
					content: '';
					position: absolute;
					top: -5px;
					right: -5px;
					bottom: -5px;
					left: -5px;
					border: 2px solid @border-color-progressive;
					border-radius: 7px;
				}

				// style for the sortable used
				left: 0;
				transition: all 0.3s;
				z-index: 100;
				/* stylelint-disable-next-line declaration-no-important */
				-webkit-touch-callout: none !important;
				/* stylelint-disable-next-line declaration-no-important */
				-webkit-user-select: none !important;

				&-scale {
					transform: scale( 1.3 );
					transition: unset;
					z-index: 99;
					cursor: move !important; /* stylelint-disable-line declaration-no-important */
				}
				// end style for the sortable used

				&-warning {
					position: absolute;
					right: -5px;
					bottom: -5px;
					width: 13px;
					height: 13px;
					background-size: contain;
					background-repeat: no-repeat;
					background-position: 0 center;
					background-image: url( ../images/warning.svg );
					background-color: @background-color-base;
					border-radius: 100%;
					z-index: 101;
				}
			}
		}

		&-btn-add {
			width: 28px;
			height: 36px;
			margin: 12px 16px;
			cursor: pointer;
			border: @border-width-base dashed @border-color-subtle;
			border-radius: @border-radius-base;
			font-size: 24px;
			line-height: 32px;
			text-align: center;
			flex-shrink: 0;

			&:active,
			&:hover {
				text-decoration: none;
			}
		}

		// style for the sortable used
		&-image-container {
			pointer-events: none;
			/* stylelint-disable-next-line declaration-no-important */
			background-color: #eaecf0 !important;
		}
		// end style for the sortable used
	}
}
</style>
