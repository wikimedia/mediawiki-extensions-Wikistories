<template>
	<div class="ext-wikistories-article-view">
		<navigator
			:title="$i18n( 'wikistories-article-navigator-title' ).text()"
			:forward-button-visible="false"
			backward-button-style="back"
			@backward="onBack"
		></navigator>
		<div
			v-if="currentArticle.html"
			class="ext-wikistories-article-view-content"
			@click="onClick"
			v-html="currentArticle.html"></div>
		<div v-else-if="error" class="ext-wikistories-article-view-error">
			{{ error }}
		</div>
		<div v-else class="ext-wikistories-article-view-loading">
			<h1>{{ $i18n( 'wikistories-article-loading' ).text() }}</h1>
		</div>
		<div v-if="showInfoBar" class="ext-wikistories-article-view-info">
			<div class="ext-wikistories-article-view-info-banner">
				<div
					class="ext-wikistories-article-view-info-banner-icon"
				></div>
				<span class="ext-wikistories-article-view-info-banner-text">
					{{ $i18n( 'wikistories-article-info-banner-sentence' ).text() }}
				</span>
				<div
					class="ext-wikistories-article-view-info-banner-close-icon"
					@click="dismissInfo"
				></div>
			</div>
		</div>
		<div v-if="showToolBar" class="ext-wikistories-article-view-toolbar">
			<div
				class="ext-wikistories-article-view-toolbar-confirm-button"
				@touchstart="onUseText"
				@mousedown="onUseText">
				{{ $i18n( 'wikistories-article-usetext' ).text() }}
			</div>
		</div>
	</div>
</template>

<script>
const mapActions = require( 'vuex' ).mapActions;
const mapGetters = require( 'vuex' ).mapGetters;
const Navigator = require( '../components/Navigator.vue' );

const SENTENCE_CLASS = 'ext-wikistories-article-view-content-sentence';
const SENTENCE_SELECTED_CLASS = 'ext-wikistories-article-view-content-sentence-selected';

// @vue/component
module.exports = {
	name: 'Article', // eslint-disable-line vue/no-reserved-component-names
	components: {
		navigator: Navigator
	},
	props: {
		article: { type: String, required: false, default: '' }
	},
	data: function () {
		return {
			error: false,
			infoDismissed: false,
			selectedSentences: []
		};
	},
	computed: $.extend( mapGetters( [ 'currentArticle', 'fromArticle' ] ), {
		showInfoBar: function () {
			return !this.selectedSentences.length && !this.infoDismissed;
		},
		showToolBar: function () {
			return !!this.selectedSentences.length;
		},
		selectedText: function () {
			return this.selectedSentences.map( function ( s ) {
				return s.textContent;
			} ).join( ' ' );
		}
	} ),
	methods: $.extend( mapActions( [ 'fetchArticle', 'setText', 'setTextFromArticle', 'routeBack' ] ), {
		onClick: function ( e ) {
			// Find the sentence that was clicked
			const sentence = e.target.closest( '.' + SENTENCE_CLASS );
			if ( !sentence ) {
				return;
			}

			// Add or remove from the list of selected sentences
			const index = this.selectedSentences.indexOf( sentence );
			if ( index === -1 ) {
				sentence.classList.add( SENTENCE_SELECTED_CLASS );
				this.selectedSentences.push( sentence );
			} else {
				sentence.classList.remove( SENTENCE_SELECTED_CLASS );
				this.selectedSentences.splice( index, 1 );
				delete sentence.dataset.selectedOrder;
			}

			// Renumber the selected sentences
			this.selectedSentences.forEach( function ( s, i ) {
				s.dataset.selectedOrder = String( i + 1 );
			} );
		},
		dismissInfo: function () {
			this.infoDismissed = true;
		},
		onUseText: function ( e ) {
			e.preventDefault();
			e.stopPropagation();
			this.setTextFromArticle( this.selectedText );
			this.setText( this.selectedText );
			this.routeBack();
		},
		onBack: function () {
			this.routeBack();
		}
	} ),
	created: function () {
		this.fetchArticle( this.article || this.fromArticle ).catch( () => {
			// remove the functionality of selection when detect error from article
			this.error = mw.msg( 'wikistories-builder-article-not-available' );
		} );
	}
};
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.ext-wikistories-article-view {
	width: 100%;
	height: 100%;
	display: flex;
	flex-direction: column;
	font-size: 14px;

	&-content {
		position: relative;
		flex-grow: 1;
		overflow: scroll;
		padding: 16px;
		margin-top: 10px;
		font-size: 18px;

		p {
			position: inherit;
		}

		section {
			display: block !important; /* stylelint-disable-line declaration-no-important */
		}

		&-sentence {
			cursor: pointer;

			&-selected {
				// Could not find a design token for the highlight yellow
				background-color: #fc3;
				position: relative;

				&::before {
					content: attr( data-selected-order );
					color: @color-inverted;
					background-color: @color-subtle;
					width: 16px;
					height: 16px;
					line-height: 16px;
					font-size: 12px;
					display: inline-block;
					text-align: center;
					border-radius: 100%;
					position: absolute;
					top: -8px;
					left: -8px;
				}
			}
		}
	}

	&-error {
		position: relative;
		flex-grow: 1;
		padding: 16px;
		margin-top: 10px;
		font-size: 18px;
	}

	&-loading {
		display: flex;
		flex-grow: 1;
		justify-content: center;
		align-items: center;
	}

	&-info {
		display: flex;
		flex-direction: row;
		align-content: stretch;
		align-items: center;
		background-color: @background-color-notice-subtle;
		border: @border-base;

		&-banner {
			flex: auto;
			margin: 0;
			padding: 16px;
			position: relative;

			&-icon {
				position: absolute;
				width: 20px;
				height: 20px;
				background-image: url( ./../images/info.svg );
				background-repeat: no-repeat;
				left: 10px;
			}

			&-text {
				margin-left: 25px;
			}

			&-close-icon {
				position: absolute;
				cursor: pointer;
				width: 18px;
				height: 18px;
				background-image: url( ./../images/close.svg );
				background-repeat: no-repeat;
				right: 10px;
				top: 18px;
			}
		}
	}

	&-toolbar {
		display: flex;
		flex-direction: row;
		align-items: center;
		cursor: pointer;
		height: 34px;
		font-weight: bold;
		position: absolute;
		bottom: 0;
		width: 100%;

		& > &-confirm-button {
			flex: auto;
			margin: 0;
			padding: 10px;
			color: #fff;
			text-align: center;
			background-color: @background-color-progressive;
			border: @border-width-base @border-style-base @border-color-progressive;
		}
	}
}
</style>
