<template>
	<div class="ext-wikistories-confirm">
		<div class="ext-wikistories-confirm-content">
			<div class="ext-wikistories-confirm-content-title">
				{{ title }}
			</div>
			<div class="ext-wikistories-confirm-content-message">
				{{ message }}
			</div>
			<div class="ext-wikistories-confirm-content-buttons">
				<div
					class="ext-wikistories-confirm-content-buttons-cancel"
					@click="$emit( 'cancel' )"
				>
					{{ $i18n( 'wikistories-confirmdialog-delete-cancel' ).text() }}
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
		message: { type: String, required: true },
		accept: { type: String, required: true }
	},
	emits: [ 'confirm', 'cancel' ]
};
</script>

<style lang="less">
@import 'mediawiki.ui/variables.less';

.ext-wikistories-confirm {
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
		max-width: 85%;
		margin: auto;
		font-size: 16px;
		display: flex;
		flex-direction: column;
		align-items: center;

		&-title {
			margin: 10px 0 10px 0;
			font-size: 24px;
		}

		&-message {
			text-align: center;
			margin-bottom: 10px;
			padding-left: 20px;
			padding-right: 20px;
		}

		&-buttons {
			width: 100%;
			text-align: center;
			font-weight: bold;
			cursor: pointer;

			&-confirm {
				background: @color-primary;
				border-bottom-left-radius: 4px;
				border-bottom-right-radius: 4px;
				color: #fff;
				padding: 6px 0 6px 0;
			}

			&-cancel {
				border-top: solid 1px;
				border-color: #a2a9b1;
				padding: 6px 0 6px 0;
			}
		}
	}
}
</style>
