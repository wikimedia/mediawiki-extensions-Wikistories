<template>
	<div class="ext-wikistories-frames">
		<div class="ext-wikistories-frames-thumbnails">
			<div
				v-for="( frame, index ) in thumbnails"
				:key="frame.key || index"
				class="ext-wikistories-frames-thumbnails-frame"
				:class="{ 'ext-wikistories-frames-thumbnails-frame-selected': frame.selected }"
				:style="frame.style"
				@click="selectFrame( index )"
			></div>
		</div>
		<router-link to="/search/many" class="ext-wikistories-frames-btn-add">
			+
		</router-link>
	</div>
</template>

<script>
const mapGetters = require( 'vuex' ).mapGetters;
const mapActions = require( 'vuex' ).mapActions;
const RouterLink = require( '../../lib/vue-router/vue-router.common.js' ).RouterLink;
const sortable = require( '../util/sortableFrames.js' );

// @vue/component
module.exports = {
	name: 'Frames',
	components: {
		'router-link': RouterLink
	},
	computed: mapGetters( [ 'thumbnails' ] ),
	methods: mapActions( [ 'selectFrame', 'reorderFrames' ] ),
	mounted: function () {
		sortable.setup(
			'ext-wikistories-frames-thumbnails',
			'ext-wikistories-frames-thumbnails-frame',
			this.reorderFrames
		);
	},
	unmounted: function () {
		sortable.kill();
	}
};
</script>

<style lang="less">
.ext-wikistories {
	&-frames {
		width: 100%;
		display: flex;
		flex-direction: row;
		border-top: solid #000 1px;

		&-thumbnails {
			padding: 12px 0;
			flex-grow: 1;
			overflow-x: auto;
			display: flex;
			flex-direction: row;

			&-frame {
				width: 28px;
				height: 36px;
				margin-left: 12px;
				cursor: pointer;
				flex-shrink: 0;

				&:last-of-type {
					margin-right: 12px;
				}

				&-selected {
					outline: #36c auto 4px;
					outline-offset: 2px;
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
			}
		}

		&-btn-add {
			width: 28px;
			height: 36px;
			margin: 12px;
			cursor: pointer;
			border: 1px dashed #000;
			border-radius: 2px;
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
