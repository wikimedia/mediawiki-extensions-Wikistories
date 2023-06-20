<template>
	<div
		ref="containerRef"
		class="ext-wikistories-image-container"
	>
		<img
			ref="imageRef"
			class="ext-wikistories-image"
			:style="style"
			:src="src"
			:alt="alt"
			@touchstart="onTouchStart"
			@touchmove="onTouchMove"
			@touchend="onTouchEnd"
			@touchcancel="onTouchEnd"
			@click="onClick"
			@load="onImgLoad"
		>
	</div>
</template>

<script>
// @vue/component
module.exports = {
	name: 'StoryImage',
	props: {
		src: { type: String, required: true },
		alt: { type: String, required: true },
		rect: { type: Object, required: false, default: null },
		thumbnail: { type: Boolean, required: true },
		allowGestures: { type: Boolean, required: true }
	},
	emits: [ 'update-focal-rect' ],
	data: function () {
		return {
			mouseDown: false,
			tapped: false,
			imageSize: null,
			containerSize: null,
			scale: 1,
			scales: [],
			position: {
				x: null,
				y: null
			},
			tempProp: {},
			localRect: {}
		};
	},
	computed: {
		style: function () {
			const blank = {
				backgroundColor: this.thumbnail ? '#eaecf0' : '#fff'
			};
			if ( !this.src ) {
				return blank;
			}
			if ( !this.imageSize ) {
				return blank;
			}
			return {
				transform: `translate3d(${this.position.x}px, ${this.position.y}px, 0px) scale(${this.scale})`,
				transformOrigin: 'left top'
			};
		}
	},
	methods: {
		onTouchStart: function ( e ) {
			if ( !this.allowGestures ) {
				return;
			}

			this.mouseDown = true;

			// mouse down cursor x,y
			this.tempProp.cursorPosBefore = { x: e.touches[ 0 ].clientX, y: e.touches[ 0 ].clientY };

			// Get current image position
			this.tempProp.imagePosBefore = { x: this.position.x, y: this.position.y };
		},
		onTouchMove: function ( e ) {
			e.preventDefault();
			if ( !this.mouseDown || !this.allowGestures ) {
				return;
			}

			const horizontalMax = this.imageSize.width * this.scale - this.containerSize.width;
			const verticalMax = this.imageSize.height * this.scale - this.containerSize.height;
			const updatedX = this.tempProp.imagePosBefore.x + ( e.touches[ 0 ].clientX - this.tempProp.cursorPosBefore.x );
			const updatedY = this.tempProp.imagePosBefore.y + ( e.touches[ 0 ].clientY - this.tempProp.cursorPosBefore.y );
			const withinHorizontalBound = updatedX <= 0 && Math.abs( updatedX ) < ( horizontalMax );
			const withinVerticalBound = updatedY <= 0 && Math.abs( updatedY ) < ( verticalMax );

			if ( withinHorizontalBound ) {
				this.position.x = updatedX;
			}
			if ( withinVerticalBound ) {
				this.position.y = updatedY;
			}
		},
		onTouchEnd: function () {
			if ( !this.allowGestures ) {
				return;
			}
			if ( this.mouseDown ) {
				this.$emit( 'update-focal-rect', this.makeRect() );
				this.mouseDown = false;
			}
		},
		onClick: function ( e ) {
			if ( !this.allowGestures ) {
				return;
			}
			if ( !this.tapped ) {
				this.tapped = setTimeout( () => {
					this.tapped = null;
				}, 300 );
			} else {
				// Double tap
				clearTimeout( this.tapped );
				this.tapped = null;
				this.onDblClick( e );
			}
		},
		onDblClick: function () {
			this.scale = this.getNextScale();
			this.position = this.getImagePosition();
			this.$emit( 'update-focal-rect', this.makeRect() );
		},
		getImageSize: function () {
			const imageRef = this.$refs.imageRef;
			return {
				width: imageRef.clientWidth,
				height: imageRef.clientHeight
			};
		},
		onImgLoad: function () {
			this.imageSize = this.getImageSize();
			this.imageInit();
		},
		getContainerSize: function () {
			const rect = this.$refs.containerRef.getBoundingClientRect();
			return {
				width: rect.width,
				height: rect.height
			};
		},
		toPercentage: function ( position, size ) {
			return this.minMax( position / size, 0, 1 );
		},
		toPixels: function ( percentage, size ) {
			return percentage * size;
		},
		getScale: function () {
			return Math.max(
				this.containerSize.height / this.localRect.height,
				this.containerSize.width / this.localRect.width
			);
		},
		getScales: function () {
			const minScale = Math.max(
				this.containerSize.height / this.imageSize.height,
				this.containerSize.width / this.imageSize.width
			);
			return [ 1, 1.33, 1.66, 2 ].map( function ( f ) {
				return minScale * f;
			} );
		},
		getNextScale: function () {
			const deltas = this.scales.map( function ( s ) {
				return Math.abs( this.scale - s );
			}.bind( this ) );
			let index = deltas.indexOf( Math.min.apply( null, deltas ) );
			index = ( index === deltas.length - 1 ) ? 0 : index + 1;
			return this.scales[ index ];
		},
		getImagePosition: function () {
			const scaledRect = this.getScaledRect();

			const minX = -( this.imageSize.width * this.scale - this.containerSize.width );
			const minY = -( this.imageSize.height * this.scale - this.containerSize.height );

			const x = ( this.containerSize.width / 2 ) - ( scaledRect.x + ( scaledRect.width / 2 ) );
			const y = ( this.containerSize.height / 2 ) - ( scaledRect.y + ( scaledRect.height / 2 ) );

			return {
				x: this.minMax( x, minX, 0 ),
				y: this.minMax( y, minY, 0 )
			};
		},
		minMax: function ( value, min, max ) {
			return Math.min( max, Math.max( min, value ) );
		},
		makeRect: function () {
			return {
				x: this.toPercentage( Math.abs( this.position.x ) / this.scale, this.imageSize.width ),
				y: this.toPercentage( Math.abs( this.position.y ) / this.scale, this.imageSize.height ),
				width: this.toPercentage( this.containerSize.width / this.scale, this.imageSize.width ),
				height: this.toPercentage( this.containerSize.height / this.scale, this.imageSize.height )
			};
		},
		initialStateRect: function () {
			return {
				x: 0,
				y: 0,
				width: this.imageSize.width,
				height: this.imageSize.height
			};
		},
		getScaledRect: function () {
			const scaledX = this.localRect.x * this.scale;
			const scaledY = this.localRect.y * this.scale;
			const scaledWidth = this.localRect.width * this.scale;
			const scaledHeight = this.localRect.height * this.scale;

			return { x: scaledX, y: scaledY, width: scaledWidth, height: scaledHeight };
		},
		getLocalRect: function () {
			// localRect is a proxy for when this.rect is undefined
			if ( this.rect ) {
				return {
					x: this.toPixels( this.rect.x, this.imageSize.width ),
					y: this.toPixels( this.rect.y, this.imageSize.height ),
					width: this.toPixels( this.rect.width, this.imageSize.width ),
					height: this.toPixels( this.rect.height, this.imageSize.height )
				};
			} else {
				return this.initialStateRect();
			}
		},
		imageInit: function () {
			if ( this.src ) {
				this.containerSize = this.getContainerSize();
				this.localRect = this.getLocalRect();
				this.scale = this.getScale();
				this.scales = this.getScales();
				this.position = this.getImagePosition();
			}
		}
	},
	watch: {
		rect: function () {
			this.imageInit();
		}
	}
};
</script>

<style>
.ext-wikistories-image-container {
	background-color: #000;
	height: 100%;
	width: 100%;
	overflow: hidden;
	touch-action: none;
	position: absolute;
}
</style>
