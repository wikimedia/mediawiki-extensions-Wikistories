<template>
	<div
		class="ext-wikistories-navigator">
		<div
			:class="`ext-wikistories-navigator-button ${backwardButtonStyle}`"
			@click="$emit( 'backward' )">
		</div>
		<div
			class="ext-wikistories-navigator-main">
			<div class="ext-wikistories-navigator-main-title">
				{{ title }}
			</div>
		</div>
		<div
			v-if="forwardButtonVisible"
			:class="forwardButtonClassName"
			@click="$emit( 'forward' )">
			<div class="ext-wikistories-navigator-withtext-content">
				{{ forwardButtonText }}
			</div>
		</div>
	</div>
</template>

<script>
// @vue/component
module.exports = {
	name: 'Navigator',
	props: {
		title: { type: String, default: '' },
		backwardButtonStyle: { type: String, default: 'close' },
		forwardButtonVisible: { type: Boolean, default: false },
		forwardButtonText: { type: String, default: '' }
	},
	emits: [ 'backward', 'forward' ],
	computed: {
		forwardButtonClassName: function () {
			return this.forwardButtonText ? 'ext-wikistories-navigator-withtext' : 'ext-wikistories-navigator-button next';
		}
	}
};
</script>

<style lang="less">
@import 'mediawiki.ui/variables.less';

.ext-wikistories-navigator {
	height: 48px;
	display: flex;
	align-items: center;
	box-shadow: 0 1px 1px rgba( 0, 0, 0, 0.1 ), inset 0 -1px 0 #c8ccd1;

	&-button {
		background-repeat: no-repeat;
		background-position: center;
		height: 48px;
		width: 48px;
		box-shadow: inset -1px 0 0 #c8ccd1;
		cursor: pointer;
	}

	.close {
		background-image: url( ../images/close.svg );
	}

	.back {
		background-image: url( ../images/back.svg );
	}

	.next {
		background-image: url( ../images/next.svg );
		background-color: @color-primary;
		box-shadow: none;
	}

	&-main {
		flex-grow: 1;
		height: 100%;
		display: flex;
		align-items: center;
		padding-left: 10px;

		&-title {
			font-size: 16px;
		}

		&-info {
			margin: 15px 0;
			font-size: 16px;
			line-height: 22px;
			color: #000;
		}
	}

	&-withtext {
		max-width: 20%;
		height: 48px;
		background-color: @color-primary;
		cursor: pointer;
		display: flex;
		align-items: center;
		padding: 0 10px 0 10px;
		text-align: center;
		color: #fff;
		justify-content: center;
		font-weight: bold;

		&-content {
			overflow: hidden;
			text-overflow: ellipsis;
			white-space: nowrap;
		}
	}
}
</style>
