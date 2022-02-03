<template>
	<div v-if="attributionReady" class="image-attribution">
		<div class="image-attribution-info">
			<div
				v-for="license in presentLicenses"
				:key="license"
				:class="`image-attribution-info-${license.toLowerCase()}`">
			</div>
			<bdi
				class="image-attribution-info-author"
				v-html="currentFrame.imgAttribution.author ||
					$i18n( 'wikistories-imageattribution-author-unknown' ).text()">
			</bdi>
		</div>
		<div class="image-attribution-more-info">
			<a
				:href="currentFrame.imgAttribution.url"
				class="image-attribution-more-info-link"
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
.image-attribution {
	position: absolute;
	bottom: 0;
	width: 100%;
	height: 44px;
	color: #f8f9fa;
	border: #f8f9fa;
	display: flex;
	justify-content: space-between;
}

.image-attribution-info {
	display: flex;
	align-items: center;
	padding-left: 14px;
}

.image-attribution-info-cc {
	background-image: url( ../images/attribution-cc.svg );
	background-repeat: no-repeat;
	width: 14px;
	height: 14px;
	margin-right: 8px;
}

.image-attribution-info-by {
	background-image: url( ../images/attribution-author.svg );
	background-repeat: no-repeat;
	width: 14px;
	height: 14px;
	margin-right: 8px;
}

.image-attribution-info-sa {
	background-image: url( ../images/attribution-icon-share-a-like.svg );
	background-repeat: no-repeat;
	width: 14px;
	height: 14px;
	margin-right: 8px;
}

.image-attribution-info-fair {
	background-image: url( ../images/attribution-license-generic-gnu-free.svg );
	background-repeat: no-repeat;
	width: 14px;
	height: 14px;
	margin-right: 8px;
}

.image-attribution-info-public {
	background-image: url( ../images/attribution-license-public-domain.svg );
	background-repeat: no-repeat;
	width: 14px;
	height: 14px;
	margin-right: 8px;
}

.image-attribution-info-author {
	max-width: 220px;
	height: 14px;
	margin-right: 4px;
	font-size: 12px;
	color: #fff;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.image-attribution-more-info {
	height: 44px;
	width: 44px;
	display: flex;
	background-image: url( ../images/attribution-icon-info.svg );
	background-repeat: no-repeat;
	background-position: center;
}

.image-attribution-more-info-link {
	display: block;
	height: 44px;
	width: 44px;
}
</style>
