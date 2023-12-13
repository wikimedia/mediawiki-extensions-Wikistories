<template>
	<div class="ext-wikistories-notice" :class="{ 'ext-wikistories-notice-error': mode === 'error' }">
		<div class="ext-wikistories-notice-icon"></div>
		<div class="ext-wikistories-notice-message">
			{{ message }}
		</div>
	</div>
</template>

<script>
// @vue/component
module.exports = {
	name: 'Notice',
	compatConfig: { MODE: 3 },
	compilerOptions: { whitespace: 'condense' },
	props: {
		message: { type: String, required: true },
		mode: { type: String, required: false, default: 'warning' }
	}
};
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.ext-wikistories-notice {
	background-color: @background-color-warning-subtle;
	border: @border-width-base @border-style-base @border-color-warning;
	border-radius: @border-radius-base;
	display: flex;
	padding: 20px;

	&-icon {
		background-image: url( ./../images/union.svg );
		background-repeat: no-repeat;
		height: 18px;
		width: 26px;
		margin-right: 10px;
		margin-top: 4px;
	}

	&-message {
		font-size: 16px;
		width: 90%;
		line-height: 22px;
	}
}

.ext-wikistories-notice.ext-wikistories-notice-error {
	background-color: @background-color-error-subtle;
	border: @border-width-base @border-style-base @border-color-error;
	top: 60px;

	.ext-wikistories-notice-icon {
		background-image: url( ./../images/error.svg );
		height: 20px;
	}

	.ext-wikistories-notice-message {
		color: @color-emphasized;
	}
}
</style>
