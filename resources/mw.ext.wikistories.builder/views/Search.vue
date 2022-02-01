<template>
	<div class="storybuilder-search">
		<navigator
			:next="editStory"
			:info="selection.length"
		></navigator>
		<form @submit="onSubmit( $event )">
			<div class="label">
				Search
			</div>
			<input
				class="query"
				type="text"
				:value="query"
				@input="onInput">
			<div class="icon"></div>
			<div
				v-if="query"
				class="close"
				@click="onClear"></div>
			<div v-if="loading" class="loading-bar"></div>
		</form>
		<image-list
			:items="results"
			:select="onItemSelect"
			:selected="selection"></image-list>
	</div>
</template>

<script>
const mapGetters = require( 'vuex' ).mapGetters;
const mapActions = require( 'vuex' ).mapActions;
const ImageListView = require( '../components/ImageListView.vue' );
const Navigator = require( '../components/Navigator.vue' );

// @vue/component
module.exports = {
	name: 'Search',
	components: {
		'image-list': ImageListView,
		navigator: Navigator
	},
	computed: mapGetters( [ 'selection', 'loading', 'results', 'query' ] ),
	methods: $.extend( mapActions( [ 'select', 'search', 'clear', 'resetFrame' ] ), {
		onSubmit: function ( e ) { return e.preventDefault(); },
		onInput: function ( e ) {
			e.preventDefault();
			this.search( e.target.value );
		},
		onClear: function ( e ) {
			e.preventDefault();
			this.clear();
		},
		onItemSelect: function ( data ) {
			this.select( data );
		},
		editStory: function () {
			const array = this.selection.map( function ( id, index ) {
				const item = this.results.find(
					function ( result ) { return result.id === id; }
				);
				return {
					id: index + 1,
					img: item.thumb,
					text: item.desc,
					imgTitle: item.title,
					attribution: item.attribution
				};
			}.bind( this ) );
			this.resetFrame( array );
			this.$router.push( { name: 'Story' } );
		}
	} )
};
</script>

<style lang="less">
		.storybuilder-search {
			height: 100%;
			padding: 0 15px 0 15px;
			background-color: #fff;
		}

		form {
			position: relative;
			text-align: left;
			padding: 10px 0;
		}

		.label {
			font-size: 18px;
			font-style: normal;
			font-weight: bold;
			line-height: 25px;
			letter-spacing: 0;
			margin: 5px 10px;
		}

		.query {
			height: 36px;
			border: 2px solid #36c;
			box-sizing: border-box;
			border-radius: 2px;
			padding-left: 35px;
			width: 100%;
		}

		.icon {
			background-image: url( ../images/search.svg );
			width: 20px;
			height: 20px;
			position: absolute;
			bottom: 18px;
			left: 10px;
		}

		.close {
			background-image: url( ../images/close.svg );
			width: 20px;
			height: 20px;
			position: absolute;
			bottom: 18px;
			right: 10px;
			padding: 0;
			cursor: pointer;
		}

		.loading-bar {
			position: absolute;
			height: 3px;
			width: 130px;
			border-radius: 3px;
			margin-top: 10px;
			background: #36c;
			animation-name: loader;
			animation-duration: 2s;
			animation-iteration-count: infinite;
			animation-timing-function: ease;
		}

		@keyframes loader {
			0% {
				transform: translateX( 0 );
			}

			50% {
				transform: translateX( calc( 100vw - 40px ) );
			}

			100% {
				transform: translateX( 0 );
			}
		}

		@keyframes loader-desktop {
			0% {
				transform: translateX( 0 );
			}

			50% {
				transform: translateX( calc( 500px - 175px ) );
			}

			100% {
				transform: translateX( 0 );
			}
		}

		@media screen and ( min-width: 500px ) {
			.loading-bar {
				animation-name: loader-desktop;
			}
		}
</style>
