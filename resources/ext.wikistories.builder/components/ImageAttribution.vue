<template>
	<div class="ext-wikistories-image-attribution">
		<div class="ext-wikistories-image-attribution-info">
			<div
				v-for="license in currentFrame.imgAttribution.license"
				:key="license"
				:class="
					`ext-wikistories-image-attribution-info-${license.toLowerCase()}
					ext-wikistories-image-attribution-info-license`
				">
			</div>
			<bdi
				class="ext-wikistories-image-attribution-info-author">
				{{ presentAuthor }}
			</bdi>
		</div>
		<div class="ext-wikistories-image-attribution-more-info">
			<a
				:href="currentFrame.imgAttribution.url"
				class="ext-wikistories-image-attribution-more-info-link"
				target="_blank"></a>
		</div>
	</div>
</template>

<script>
const mapGetters = require( 'vuex' ).mapGetters;

// @vue/component
module.exports = {
	name: 'ImageAttribution',
	compatConfig: { MODE: 3 },
	compilerOptions: { whitespace: 'condense' },
	computed: $.extend( mapGetters( [ 'currentFrame' ] ), {
		presentAuthor: function () {
			const author = this.currentFrame.imgAttribution.author;

			// show empty when no attribution initially
			if ( author === undefined ) {
				return '';
			}

			return author ||
				this.$i18n( 'wikistories-imageattribution-author-unknown' ).text();
		}
	} )
};
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.ext-wikistories-image-attribution {
	position: absolute;
	bottom: 0;
	width: 100%;
	height: 44px;
	color: #f8f9fa;
	border: #f8f9fa;
	display: flex;
	justify-content: space-between;
	background: linear-gradient( 180deg, rgba( 0, 0, 0, 0 ) -108.75%, rgba( 0, 0, 0, 0.6 ) 100% );

	&-info {
		display: flex;
		align-items: center;
		padding-left: 14px;

		&-license {
			background-repeat: no-repeat;
			width: 14px;
			height: 14px;
			margin-right: 8px;
		}

		&-cc {
			background-image: url( ./../images/attribution-cc.svg );
		}

		&-by {
			background-image: url( ./../images/attribution-author.svg );
		}

		&-sa {
			background-image: url( ./../images/attribution-icon-share-a-like.svg );
		}

		&-fair {
			background-image: url( ./../images/attribution-license-generic-gnu-free.svg );
		}

		&-public {
			background-image: url( ./../images/attribution-license-public-domain.svg );
		}

		&-author {
			max-width: 220px;
			margin-right: 4px;
			font-size: 12px;
			color: @color-inverted;
			overflow: hidden;
			text-overflow: ellipsis;
			white-space: nowrap;
		}
	}

	&-more-info {
		height: 44px;
		width: 44px;
		display: flex;
		background-image: url( ./../images/attribution-icon-info.svg );
		background-repeat: no-repeat;
		background-position: center;

		&-link {
			display: block;
			height: 44px;
			width: 44px;
		}
	}
}
</style>
