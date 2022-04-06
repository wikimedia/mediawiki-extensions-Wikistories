<template>
	<div class="ext-wikistories-storybuilder-search">
		<navigator
			:next="editStory"
			:info="selection.length"
		></navigator>
		<form class="ext-wikistories-storybuilder-search-form" @submit="onSubmit( $event )">
			<input
				class="ext-wikistories-storybuilder-search-form-query"
				type="text"
				:value="query"
				:placeholder="$i18n( 'wikistories-search-inputplaceholder' ).text()"
				@input="onInput">
			<div class="ext-wikistories-storybuilder-search-form-icon"></div>
			<div
				v-if="query"
				class="ext-wikistories-storybuilder-search-form-close"
				@click="onClear"></div>
			<div v-if="loading" class="ext-wikistories-storybuilder-search-loading-bar"></div>
		</form>
		<div v-if="!query" class="ext-wikistories-storybuilder-search-empty">
			{{ $i18n( 'wikistories-search-cuetext' ).text() }}
		</div>
		<div v-if="noResults" class="ext-wikistories-storybuilder-search-empty">
			{{ $i18n( 'wikistories-search-noresultstext' ).text() }}
		</div>
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
	props: {
		mode: { type: String, default: 'many' }
	},
	computed: mapGetters( [ 'selection', 'loading', 'results', 'query', 'noResults', 'fromArticle' ] ),
	methods: $.extend( mapActions( [ 'select', 'search', 'clear', 'addFrames', 'setFrameImage' ] ), {
		onSubmit: function ( e ) { return e.preventDefault(); },
		onInput: function ( e ) {
			this.search( e.target.value );
		},
		onClear: function ( e ) {
			e.preventDefault();
			this.clear();
		},
		onItemSelect: function ( data ) {
			if ( this.mode === 'one' ) {
				this.setFrameImage( this.results.find( ( r ) => r.id === data[ 0 ] ) );
				this.$router.push( '/story' );
			} else {
				this.select( data );
			}
		},
		editStory: function () {
			const array = this.selection.map( function ( id ) {
				const item = this.results.find(
					function ( result ) { return result.id === id; }
				);
				return {
					img: item.thumb,
					imgTitle: item.title,
					attribution: item.attribution
				};
			}.bind( this ) );
			this.addFrames( array );
			this.$router.push( { name: 'Story' } );
		}
	} ),
	created: function () {
		this.search( this.fromArticle );
	}
};
</script>

<style lang="less">
.ext-wikistories-storybuilder-search {
	height: 100%;
	padding: 0 15px 0 15px;
	background-color: #fff;
	display: flex;
	flex-direction: column;

	&-form {
		position: relative;
		text-align: left;
		padding: 10px 0;

		&-query {
			height: 36px;
			border: 2px solid #36c;
			box-sizing: border-box;
			border-radius: 2px;
			padding-left: 35px;
			width: 100%;
		}

		&-icon {
			background-image: url( ../images/search.svg );
			width: 20px;
			height: 20px;
			position: absolute;
			bottom: 18px;
			left: 10px;
		}

		&-close {
			background-image: url( ../images/close.svg );
			width: 20px;
			height: 20px;
			position: absolute;
			bottom: 18px;
			right: 10px;
			padding: 0;
			cursor: pointer;
		}
	}

	&-empty {
		color: #54595d;
		align-self: center;
		margin-top: 6px;
	}

	&-loading-bar {
		position: absolute;
		height: 3px;
		width: 130px;
		border-radius: 3px;
		margin-top: 10px;
		background: #36c;
		animation-name: ext-wikistories-search-loader;
		animation-duration: 2s;
		animation-iteration-count: infinite;
		animation-timing-function: ease;
	}

	@keyframes ext-wikistories-search-loader {
		0% {
			left: 0;
		}

		50% {
			left: calc( ~'100% - 130px' );
		}

		100% {
			left: 0;
		}
	}
}
</style>
