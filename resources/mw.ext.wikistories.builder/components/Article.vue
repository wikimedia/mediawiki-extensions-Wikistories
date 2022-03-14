<template>
	<div class="ext-wikistories-article-view">
		<div
			v-if="currentArticle.html"
			class="ext-wikistories-article-view-content"
			v-html="currentArticle.html"></div>
		<div v-else class="ext-wikistories-article-view-loading">
			<h1>{{ $i18n( 'wikistories-article-loading' ).text() }}</h1>
		</div>
		<div v-if="display === 'info'" class="ext-wikistories-article-view-info">
			<div v-if="!expanded" class="ext-wikistories-article-view-info-banner">
				<div
					class="ext-wikistories-article-view-info-banner-icon"
					@click="toggleExpandInfo"
				></div>
				<span>{{ $i18n( 'wikistories-article-info-banner' ).text() }}</span>
			</div>
			<div v-if="expanded" class="ext-wikistories-article-view-info-expanded-banner">
				<div
					class="ext-wikistories-article-view-info-expanded-banner-close-icon"
					@click="toggleExpandInfo">
				</div>
				<span>{{ $i18n( 'wikistories-article-info-expanded-banner' ).text() }}</span>
			</div>
		</div>
		<div v-if="display === 'tools'" class="ext-wikistories-article-view-toolbar">
			<div
				class="ext-wikistories-article-view-toolbar-confirm-button"
				@touchstart="onUseText"
				@mousedown="onUseText">
				{{ $i18n( 'wikistories-article-usetext' ).text() }}
			</div>
			<div class="ext-wikistories-article-view-toolbar-discard-button" @click="onDismiss">
				{{ $i18n( 'wikistories-article-cancelselection' ).text() }}
			</div>
		</div>
	</div>
</template>

<script>
const mapActions = require( 'vuex' ).mapActions;
const mapGetters = require( 'vuex' ).mapGetters;

const isNodeWithinArticleView = ( node ) => {
	return document.querySelector( '.ext-wikistories-article-view-content' ).contains( node );
};

// @vue/component
module.exports = {
	name: 'Article', // eslint-disable-line vue/no-reserved-component-names
	props: {
		article: { type: String, required: false, default: '' }
	},
	emits: [ 'textSelected' ],
	data: function () {
		return {
			selectedText: null,
			display: 'info',
			expanded: false
		};
	},
	computed: mapGetters( [ 'currentArticle', 'fromArticle' ] ),
	methods: $.extend( mapActions( [ 'fetchArticle', 'setText' ] ), {
		setToolbarDisplay: function ( status ) {
			this.display = status;
		},
		showSelectionToolbar: function () {
			this.setToolbarDisplay( 'tools' );
		},
		hideSelectionToolbar: function () {
			this.setToolbarDisplay( 'info' );
		},
		toggleExpandInfo: function () {
			this.expanded = !this.expanded;
		},
		onSelectionChange: function () {
			const s = document.getSelection();
			if ( s.isCollapsed ) {
				this.hideSelectionToolbar();
			} else if ( s.type === 'Range' &&
				isNodeWithinArticleView( s.anchorNode ) &&
				isNodeWithinArticleView( s.focusNode )
			) {
				this.selectedText = s.toString().trim();
				if ( this.selectedText ) {
					this.showSelectionToolbar();
				}
			}
		},
		onUseText: function ( e ) {
			e.preventDefault();
			e.stopPropagation();
			this.hideSelectionToolbar();
			this.setText( this.selectedText );
			this.$emit( 'textSelected' );
		},
		onDismiss: function () {
			this.hideSelectionToolbar();
		}
	} ),
	created: function () {
		this.fetchArticle( this.article || this.fromArticle );
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
.ext-wikistories-article-view {
	width: 100%;
	height: 100%;
	display: flex;
	flex-direction: column;
	font-size: 14px;

	&-content {
		overflow: scroll;
		padding: 16px;
		margin-top: 10px;

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

	&-info {
		display: flex;
		flex-direction: row;
		align-content: stretch;
		align-items: center;
		background-color: #202122;
		color: #fff;
		margin-bottom: 13px;

		&-banner {
			flex: auto;
			margin: 0;
			padding: 16px;

			&-icon {
				position: absolute;
				cursor: pointer;
				width: 20px;
				height: 20px;
				background-image: url( ../images/attribution-icon-info.svg );
				background-repeat: no-repeat;
				right: 10px;
			}
		}

		&-expanded-banner {
			position: relative;
			flex: auto;
			margin: 0;
			padding: 30px 16px;

			&-close-icon {
				position: absolute;
				cursor: pointer;
				width: 18px;
				height: 18px;
				background-image: url( ../images/close-white.svg );
				background-repeat: no-repeat;
				right: 10px;
				top: 10px;
			}
		}
	}

	&-toolbar {
		display: flex;
		flex-direction: row;
		align-content: stretch;
		align-items: center;
		cursor: pointer;
		margin-bottom: 13px;

		& > &-confirm-button {
			flex: auto;
			margin: 0;
			padding: 10px;
			color: #fff;
			text-align: center;
			background-color: #36c;
		}

		& > &-discard-button {
			flex: auto;
			margin: 0;
			padding: 10px;
			color: #fff;
			text-align: center;
			background-color: #d33;
		}
	}
}
</style>
