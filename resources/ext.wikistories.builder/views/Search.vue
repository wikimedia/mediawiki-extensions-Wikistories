<template>
	<div class="ext-wikistories-storybuilder-search" v-on="toast.show ? { click: hideToast } : {}">
		<navigator
			:title="navigatorMessage"
			:forward-button-visible="forwardVisibility"
			@backward="onClose"
			@forward="editStory"
		></navigator>
		<form class="ext-wikistories-storybuilder-search-form" @submit="onSubmit( $event )">
			<input
				class="ext-wikistories-storybuilder-search-form-query"
				type="text"
				:value="query"
				:placeholder="$i18n( 'wikistories-search-inputplaceholder' ).text()"
				@input="onInput">
			<div class="ext-wikistories-storybuilder-search-form-icon skin-invert"></div>
			<div
				v-if="query"
				class="ext-wikistories-storybuilder-search-form-clear skin-invert"
				@click="onClear"></div>
			<div v-if="loading" class="ext-wikistories-storybuilder-search-loading-bar"></div>
		</form>
		<div v-if="!query" class="ext-wikistories-storybuilder-search-empty">
			{{ $i18n( 'wikistories-search-cuetext' ).text() }}
		</div>
		<div v-if="noResults" class="ext-wikistories-storybuilder-search-empty">
			{{ $i18n( 'wikistories-search-noresultstext' ).text() }}
		</div>
		<toast
			v-if="toast.show"
			:message="toast.message"
			@hide-toast="hideToast">
		</toast>
		<image-list
			:items="results"
			:select="onItemSelect"
			:selected="selection"
			:mode="mode"
			@max-selected="showMaxSelectedToast"></image-list>
	</div>
</template>

<script>
const mapGetters = require( 'vuex' ).mapGetters;
const mapActions = require( 'vuex' ).mapActions;
const ImageListView = require( '../components/ImageListView.vue' );
const Navigator = require( '../components/Navigator.vue' );
const Toast = require( '../components/Toast.vue' );

// @vue/component
module.exports = {
	name: 'Search',
	components: {
		'image-list': ImageListView,
		navigator: Navigator,
		toast: Toast
	},
	props: {
		mode: { type: String, default: 'many' }
	},
	data: function () {
		return {
			toast: {
				show: false,
				message: ''
			}
		};
	},
	computed: Object.assign( mapGetters( [ 'selection', 'loading', 'results', 'query', 'noResults', 'fromArticle', 'isBuilderRouteAvailable' ] ), {
		navigatorMessage: function () {
			if ( this.selection.length === 0 ) {
				return this.$i18n( 'wikistories-search-navigator-title' ).text();
			}
			return this.$i18n( 'wikistories-search-navigator-title-selected-info' )
				.params( [ this.selection.length ] ).text();
		},
		forwardVisibility: function () {
			return this.selection.length > 0;
		}
	} ),
	methods: Object.assign( mapActions( [ 'select', 'search', 'clear', 'addFrames', 'setFrameImage', 'routeReplace', 'routeBack' ] ), {
		onSubmit: function ( e ) {
			return e.preventDefault();
		},
		onInput: function ( e ) {
			this.search( e.target.value );
		},
		onClear: function ( e ) {
			e.preventDefault();
			this.clear();
		},
		onClose: function () {
			if ( !this.isBuilderRouteAvailable ) {
				const titleObj = mw.Title.newFromText( this.fromArticle );
				window.location = titleObj.getUrl();
			} else {
				this.routeBack();
			}
		},
		onItemSelect: function ( data ) {
			if ( this.mode === 'one' ) {
				this.setFrameImage( this.results.find( ( r ) => r.id === data[ 0 ] ) );
				this.routeBack();
			} else {
				this.select( data );
			}
		},
		showMaxSelectedToast: function () {
			const maxFrames = this.getConfig( 'wgWikistoriesMaxFrames' );
			this.toast.message = this.$i18n( 'wikistories-toast-maxframes-selecting' )
				.params( [ maxFrames ] ).text();
			this.toast.show = true;
		},
		hideToast: function () {
			this.toast.message = '';
			this.toast.show = false;
		},
		editStory: function () {
			const array = this.selection.map( ( id ) => {
				const item = this.results.find(
					( result ) => result.id === id
				);
				return {
					url: item.url,
					title: item.title,
					filename: item.filename,
					attribution: item.attribution
				};
			} );
			this.addFrames( array );
			this.routeReplace( 'story' );
		}
	} ),
	created: function () {
		const titleObj = mw.Title.newFromText( this.fromArticle );
		this.search( titleObj.getMainText() );
	}
};
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.ext-wikistories-storybuilder-search {
	height: 100%;
	background-color: @background-color-base;
	display: flex;
	flex-direction: column;

	&-form {
		position: relative;
		text-align: left;
		padding: 10px 0;
		margin: 0 16px;

		input {
			color: @color-base;
			background-color: @background-color-base;
		}

		&-query {
			height: 36px;
			border: @border-width-thick @border-style-base @border-color-progressive;
			box-sizing: border-box;
			border-radius: @border-radius-base;
			padding-left: 35px;
			width: 100%;
		}

		&-icon {
			background-image: url( ./../images/search.svg );
			width: 20px;
			height: 20px;
			position: absolute;
			bottom: 18px;
			left: 10px;
		}

		&-clear {
			background-image: url( ./../images/clear.svg );
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
		color: @color-subtle;
		align-self: center;
		margin-top: 6px;
	}

	&-loading-bar {
		position: absolute;
		height: 3px;
		width: 130px;
		border-radius: @border-radius-pill;
		margin-top: 4px;
		background: @background-color-progressive;
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
