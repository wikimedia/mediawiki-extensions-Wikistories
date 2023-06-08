<template>
	<div class="ext-wikistories-frames">
		<div ref="thumbnails" class="ext-wikistories-frames-thumbnails">
			<div
				v-for="( frame, index ) in thumbnails"
				:key="frame.key || index"
				class="ext-wikistories-frames-thumbnails-frame"
				:class="{ 'ext-wikistories-frames-thumbnails-frame-selected': frame.selected }"
				:style="frame.style"
				@click="selectFrame( index )"
			>
				<div v-if="frame.warning && frame.warning.isAlwaysShown" class="ext-wikistories-frames-thumbnails-frame-warning"></div>
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

// @vue/component
module.exports = {
	name: 'Frames',
	emits: [ 'max-limit' ],
	computed: mapGetters( [ 'thumbnails', 'maxFrames' ] ),
	methods: $.extend( mapActions( [ 'selectFrame', 'reorderFrames', 'routePush' ] ), {
		addFrames: function () {
			if ( this.maxFrames ) {
				this.$emit( 'max-limit' );
			} else {
				this.routePush( 'searchMany' );
			}
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
		border-top: @border-width-base @border-style-base #000;

		&-thumbnails {
			padding: 12px 0;
			flex-grow: 1;
			overflow-x: auto;
			display: flex;
			flex-direction: row;

			&-frame {
				width: 28px;
				height: 36px;
				margin-left: 16px;
				cursor: pointer;
				flex-shrink: 0;
				background-color: #eaecf0;
				border-radius: @border-radius-base;

				&:last-of-type {
					margin-right: 16px;
				}

				&-selected {
					outline: @border-color-progressive auto 4px;
					outline-offset: 4px;
				}

				// style for the sortable used
				position: relative;
				left: 0;
				transition: all 0.3s;
				z-index: 100;

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
				}
			}
		}

		&-btn-add {
			width: 28px;
			height: 36px;
			margin: 12px 16px;
			cursor: pointer;
			border: 1px dashed #000;
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
	}
}
</style>
