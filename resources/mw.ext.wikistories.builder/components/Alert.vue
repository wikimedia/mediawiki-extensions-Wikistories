<template>
	<div class="ext-wikistories-alert">
		<div class="ext-wikistories-alert-content">
			<div class="ext-wikistories-alert-content-header">
				{{ title }}
			</div>
			<div class="ext-wikistories-alert-content-message">
				<slot></slot>
			</div>
			<div class="ext-wikistories-alert-content-button">
				<primary-button
					:text="$i18n( 'wikistories-alert-okbuttontext' ).text()"
					@click="$emit( 'ok-click' )"
				></primary-button>
			</div>
		</div>
	</div>
</template>

<script>
const PrimaryButton = require( '../components/PrimaryButton.vue' );

// @vue/component
module.exports = {
	name: 'Alert',
	components: {
		'primary-button': PrimaryButton
	},
	props: {
		title: { type: String, required: true }
	},
	emits: [ 'ok-click' ]
};
</script>

<style lang="less">
.ext-wikistories-alert {
	position: absolute;
	width: 100%;
	top: 0;
	bottom: 0;
	z-index: 102;
	background-color: rgba( 0, 0, 0, 0.7 );
	display: flex;

	&-content {
		background-color: #fff;
		z-index: 104;
		border-radius: 4px;
		max-width: 70%;
		margin: auto;
		padding: 20px;
		font-size: 16px;

		&-header {
			font-weight: bold;
			margin-bottom: 10px;
		}

		&-message {
			margin-bottom: 10px;
		}

		&-button {
			text-align: right;
		}
	}
}
</style>
