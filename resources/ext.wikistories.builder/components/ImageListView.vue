<template>
	<div class="ext-wikistories-imagelistview">
		<div class="ext-wikistories-imagelistview__list-wrapper">
			<div class="ext-wikistories-imagelistview__list">
				<div
					v-for="item in items"
					:key="item.filename"
					:data-id="item.id"
					class="ext-wikistories-imagelistview__list-image"
					:style="{ width: `${item.width}px` }"
					@click="onSelect">
					<list-image
						:source="item.url"
						:alt="item.filename"
					></list-image>
					<div
						class="checkbox skin-invert"
						:class="{ selected: selected.indexOf( item.id ) !== -1 }">
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
const mapGetters = require( 'vuex' ).mapGetters;
const ListImage = require( './ListImage.vue' );

// @vue/component
module.exports = {
	name: 'ImageListView',
	components: {
		'list-image': ListImage
	},
	props: {
		items: {
			type: Array,
			default: () => []
		},
		select: {
			type: Function,
			default: () => {}
		},
		selected: {
			type: Array,
			default: () => []
		},
		mode: {
			type: String,
			default: 'many'
		}
	},
	emits: [ 'max-selected' ],
	computed: mapGetters( [ 'maxFramesSelected' ] ),
	methods: {
		onSelect: function ( e ) {
			const id = e.target.getAttribute( 'data-id' ) ||
				e.target.parentElement.getAttribute( 'data-id' ); // image tag element

			// @todo prop is mutated here
			/* eslint-disable vue/no-mutating-props */
			if ( this.selected.indexOf( id ) !== -1 ) {
				this.selected.splice( this.selected.indexOf( id ), 1 );
			} else if ( this.maxFramesSelected && this.mode === 'many' ) {
				this.$emit( 'max-selected' );
			} else {
				this.selected.push( id );
			}
			/* eslint-enable vue/no-mutating-props */

			this.select( this.selected );
		}
	}
};
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.ext-wikistories-imagelistview {
	height: 100%;
	background-color: @background-color-base;
	overflow: scroll;
	display: flex;
	flex-wrap: nowrap;
	margin: 0 8px 16px 8px;
	flex-grow: 1;

	&__list-wrapper {
		justify-content: space-between;
		flex: 1 1 auto;
		order: 1;
		width: 100%;
	}

	&__list {
		display: flex;
		flex-wrap: wrap;
		place-content: flex-start flex-start;
		max-width: calc( 100% + 16px );

		&-image {
			position: relative;
			width: auto;
			min-width: 300px;
			flex: 1 1 auto;
			order: 1;
			justify-content: center;
			display: flex;
			align-items: center;
			background-color: @background-color-base;
			box-sizing: border-box;
			height: 180px;
			margin: 8px;
			transition: box-shadow 100ms ease, outline 100ms ease;
			cursor: pointer;
		}

		&-image:hover,
		&-image:focus {
			box-shadow: 4px 4px 5px -2px @border-color-base;
		}

		&-image img {
			height: 100%;
			max-height: 100%;
			object-fit: cover;
			object-position: center center;
			pointer-events: none;
			width: 100%;
			border-radius: @border-radius-base;
		}

		.checkbox {
			background-image: url( ./../images/check.svg );
			width: 24px;
			height: 24px;
			/* Fix light background color to prevent double inversion
			since the parent has 'skin-invert' class */
			background-color: @background-color-base-fixed;
			background-repeat: no-repeat;
			background-position: center center;
			position: absolute;
			left: 10px;
			top: 10px;
			border: @border-base;
			box-sizing: border-box;
			border-radius: @border-radius-base;
		}

		.checkbox.selected {
			background-color: @background-color-progressive;
			border-color: @border-color-progressive;
		}
	}
}
</style>
