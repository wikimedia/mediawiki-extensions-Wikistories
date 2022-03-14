<template>
	<div v-if="attributionReady" class="ext-wikistories-image-attribution">
		<div class="ext-wikistories-image-attribution-info">
			<div
				v-for="license in presentLicenses"
				:key="license"
				:class="`ext-wikistories-image-attribution-info-${license.toLowerCase()}`">
			</div>
			<bdi
				class="ext-wikistories-image-attribution-info-author"
				v-html="currentFrame.imgAttribution.author ||
					$i18n( 'wikistories-imageattribution-author-unknown' ).text()">
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
	data: function () {
		return {
			licenseTypes: [ 'CC', 'BY', 'SA', 'Fair', 'Public' ]
		};
	},
	computed: $.extend( mapGetters( [ 'currentFrame' ] ), {
		presentLicenses: function () {
			return this.licenseTypes.filter(
				( license ) => this.currentFrame.imgAttribution.license.indexOf( license ) !== -1
			);
		},
		attributionReady: function () {
			return this.currentFrame.imgAttribution;
		}
	} )
};
</script>

<style lang="less">
.ext-wikistories-image-attribution {
	position: absolute;
	bottom: 0;
	width: 100%;
	height: 44px;
	color: #f8f9fa;
	border: #f8f9fa;
	display: flex;
	justify-content: space-between;

	&-info {
		display: flex;
		align-items: center;
		padding-left: 14px;

		&-cc {
			background-image: url( ../images/attribution-cc.svg );
			background-repeat: no-repeat;
			width: 14px;
			height: 14px;
			margin-right: 8px;
		}

		&-by {
			background-image: url( ../images/attribution-author.svg );
			background-repeat: no-repeat;
			width: 14px;
			height: 14px;
			margin-right: 8px;
		}

		&-sa {
			background-image: url( ../images/attribution-icon-share-a-like.svg );
			background-repeat: no-repeat;
			width: 14px;
			height: 14px;
			margin-right: 8px;
		}

		&-fair {
			background-image: url( ../images/attribution-license-generic-gnu-free.svg );
			background-repeat: no-repeat;
			width: 14px;
			height: 14px;
			margin-right: 8px;
		}

		&-public {
			background-image: url( ../images/attribution-license-public-domain.svg );
			background-repeat: no-repeat;
			width: 14px;
			height: 14px;
			margin-right: 8px;
		}

		&-author {
			max-width: 220px;
			height: 14px;
			margin-right: 4px;
			font-size: 12px;
			color: #fff;
			overflow: hidden;
			text-overflow: ellipsis;
			white-space: nowrap;
		}
	}

	&-more-info {
		height: 44px;
		width: 44px;
		display: flex;
		background-image: url( ../images/attribution-icon-info.svg );
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
