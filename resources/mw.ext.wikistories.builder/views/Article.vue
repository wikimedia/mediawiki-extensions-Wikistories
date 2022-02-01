<template>
	<div class="article-view">
		<router-link to="/story">
			Back
		</router-link>
		<div
			v-if="currentArticle.html"
			class="article-view-content"
			v-html="currentArticle.html"></div>
		<div v-else class="article-view-loading">
			<h1>Loading...</h1>
		</div>
		<div class="article-view-toolbar">
			<div
				class="article-view-toolbar-button"
				@touchstart="onUseText"
				@mousedown="onUseText">
				Use text
			</div>
			<div class="article-view-toolbar-button" @click="onDismiss">
				Cancel
			</div>
		</div>
	</div>
</template>

<script>
const mapActions = require( 'vuex' ).mapActions;
const mapGetters = require( 'vuex' ).mapGetters;

// @vue/component
module.exports = {
	name: 'Article', // eslint-disable-line vue/no-reserved-component-names
	props: {
		article: { type: String, required: true }
	},
	data: function () {
		return {
			selectedText: null
		};
	},
	computed: mapGetters( [ 'currentArticle' ] ),
	methods: $.extend( mapActions( [ 'fetchArticle', 'setText' ] ), {
		setToolbarDisplay: function () {
			// TODO: toggle between infobar and toolbar
		},
		showSelectionToolbar: function () {
			this.setToolbarDisplay( 'tools' );
		},
		hideSelectionToolbar: function () {
			this.setToolbarDisplay( 'info' );
		},
		onSelectionChange: function () {
			const s = document.getSelection();
			if ( s.isCollapsed ) {
				this.hideSelectionToolbar();
			} else {
				this.selectedText = s.toString();
				this.showSelectionToolbar();
			}
		},
		onUseText: function ( e ) {
			e.preventDefault();
			e.stopPropagation();
			this.hideSelectionToolbar();
			this.setText( this.selectedText );
			this.$router.replace( { name: 'Story' } );
		},
		onDismiss: function () {
			this.hideSelectionToolbar();
		}
	} ),
	created: function () {
		this.fetchArticle( this.article || mw.config.get( 'wgTitle' ) );
	},
	mounted: function () {
		document.addEventListener( 'selectionchange', this.onSelectionChange );
	},
	beforeUnmount: function () {
		document.removeEventListener( 'selectionchange', this.onSelectionChange );
	}
};
</script>

<style lang="less">
.article-view {
	width: 100%;
	height: 100%;
	display: flex;
	flex-direction: column;

	&-content {
		overflow: scroll;
		padding: 16px;

		p {
			position: inherit;
		}

		section {
			display: block !important; /* stylelint-disable-line declaration-no-important */
		}

		figure,
		table,
		sup,
		.pcs-collapse-table-container,
		.hatnote {
			display: none;
		}
	}

	&-content,
	&-loading {
		flex-grow: 1;
	}

	&-toolbar {
		display: flex;
		flex-direction: row;
		align-content: stretch;
		align-items: center;
		background-color: #000;
		opacity: 0.75;

		& > &-button {
			flex: auto;
			margin: 0;
			padding: 10px;
			color: #fff;
			cursor: pointer;
			text-align: center;
		}

		& > &-button:nth-of-type( 1 ) {
			border-right: solid #fff 1px;
		}
	}
}
</style>
