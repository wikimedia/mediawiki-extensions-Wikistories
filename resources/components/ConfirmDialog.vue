<template>
	<div class="ext-wikistories-confirm">
		<div class="ext-wikistories-confirm-content" :style="style">
			<div class="ext-wikistories-confirm-content-title">
				{{ title }}
			</div>
			<div v-if="message" class="ext-wikistories-confirm-content-message">
				{{ message }}
			</div>
			<div v-else class="ext-wikistories-confirm-content-custom">
				<slot></slot>
			</div>
			<div class="ext-wikistories-confirm-content-buttons">
				<div
					class="ext-wikistories-confirm-content-buttons-cancel"
					@click="$emit( 'cancel' )"
				>
					{{ $i18n( 'wikistories-confirmdialog-cancel' ).text() }}
				</div>
				<div
					class="ext-wikistories-confirm-content-buttons-confirm"
					@click="$emit( 'confirm' )"
				>
					{{ accept }}
				</div>
			</div>
		</div>
	</div>
</template>

<script>
// @vue/component
module.exports = {
	name: 'ConfirmDialog',
	props: {
		title: { type: String, required: true },
		message: { type: String, required: false, default: null },
		accept: { type: String, required: true },
		align: { type: String, required: false, default: 'center' }
	},
	emits: [ 'confirm', 'cancel' ],
	computed: {
		style: function () {
			return {
				textAlign: [ 'left', 'center' ].indexOf( this.align ) !== -1 ? this.align : 'center'
			};
		}
	}
};
</script>

<style lang="less">
@import 'mediawiki.ui/variables.less';

.ext-wikistories-confirm {
	position: absolute;
	width: 100%;
	top: 0;
	bottom: 0;
	z-index: 400;
	background-color: rgba( 0, 0, 0, 0.7 );
	display: flex;

	&-content {
		background-color: #fff;
		z-index: 104;
		border-radius: 2px;
		width: 75%;
		margin: auto;
		display: flex;
		flex-direction: column;
		align-items: stretch;
		box-shadow: 0 2px 2px rgba( 0, 0, 0, 0.25 );

		&-title {
			margin: 16px 16px 0 16px;
			font-size: 24px;
			color: @colorGray2;
		}

		&-message,
		&-custom {
			margin: 16px 16px 0 16px;
			font-size: 16px;
			color: @colorGray2;
		}

		&-buttons {
			font-size: 16px;
			margin-top: 24px;
			width: 100%;
			text-align: center;
			font-weight: bold;
			cursor: pointer;

			&-confirm {
				background: @color-primary;
				color: @color-base--inverted;
				padding: 6px 12px;
			}

			&-cancel {
				border-top: solid 1px;
				border-color: @colorGray10;
				padding: 6px 12px;
			}
		}
	}
}
</style>
