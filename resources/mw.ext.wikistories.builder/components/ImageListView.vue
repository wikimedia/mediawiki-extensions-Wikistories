<template>
	<div class="ext-wikistories-imagelistview">
		<div class="ext-wikistories-imagelistview__list-wrapper">
			<div class="ext-wikistories-imagelistview__list">
				<div
					v-for="item in items"
					:key="item.title"
					:data-id="item.id"
					class="ext-wikistories-imagelistview__list-image"
					:style="{ width: `${item.width}px` }"
					@click="onSelect">
					<list-image
						:source="item.thumb"
						:alt="item.title"
					></list-image>
					<div
						class="checkbox"
						:class="{ selected: selected.indexOf( item.id ) !== -1 }">
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
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
		}
	},
	methods: {
		onSelect: function ( e ) {
			const id = e.target.getAttribute( 'data-id' ) ||
				e.target.parentElement.getAttribute( 'data-id' ); // image tag element

			// @todo prop is mutated here
			/* eslint-disable vue/no-mutating-props */
			if ( this.selected.indexOf( id ) !== -1 ) {
				this.selected.splice( this.selected.indexOf( id ), 1 );
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
@import 'mediawiki.ui/variables.less';

.ext-wikistories-imagelistview {
	height: 100%;
	background-color: #fff;
	overflow: scroll;
	display: -ms-flexbox;
	display: flex;
	-ms-flex-wrap: nowrap;
	flex-wrap: nowrap;
	margin: 6px 10px 16px 10px;
	flex-grow: 1;

	&__list-wrapper {
		-ms-flex-pack: justify;
		justify-content: space-between;
		-ms-flex: 1 1 auto;
		flex: 1 1 auto;
		-ms-flex-order: 1;
		order: 1;
		width: 100%;
	}

	&__list {
		display: -ms-flexbox;
		display: flex;
		-ms-flex-wrap: wrap;
		flex-wrap: wrap;
		align-content: flex-start;
		justify-content: flex-start;
		max-width: calc( 100% + 16px );

		&-image {
			position: relative;
			-ms-flex-pack: justify;
			width: auto;
			min-width: 300px;
			-ms-flex: 1 1 auto;
			flex: 1 1 auto;
			-ms-flex-order: 1;
			order: 1;
			justify-content: center;
			display: -ms-flexbox;
			display: flex;
			align-items: center;
			background-color: @colorGray14;
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
		}

		.checkbox {
			background-image: url( ../../images/check.svg );
			width: 20px;
			height: 20px;
			background-color: #fff;
			background-repeat: no-repeat;
			background-position: center center;
			position: absolute;
			left: 10px;
			top: 10px;
			border: 1px solid @border-color-base;
			box-sizing: border-box;
			border-radius: 2px;
		}

		.checkbox.selected {
			background-color: @border-color-base;
		}
	}
}
</style>
