/* to do list
(1) [to do] hold the frame, keep moving left and right,
            the frame shouldn't fall behind another frame
*/
const sortable = ( () => {
	'use strict';
	// assist variables
	const defaultOptions = {
		containerClass: '',
		draggableItemClass: '',
		callback: null,
		itemUnitWidth: null,
		reorderStartTimeout: 500,
		transitionDuration: 300
	};
	let status = 'idle'; // idle, ready, mousedown, mousemove
	let offset; // event clientX for target element
	let target; // mouse/touch target element
	let origin; // origin position of target element
	let originOffset; // origin position offset after sort of target element
	let reorderStartTimeoutId;

	// utils methods
	const muteEvent = ( event ) => {
		event.preventDefault();
		event.stopPropagation();
		return false;
	};
	function simulateMouseEvent( event ) {
		if ( event.touches.length > 1 ) {
			return;
		}
		var simulatedType = ( event.type === 'touchstart' ) ? 'mousedown' : ( event.type === 'touchend' ) ? 'mouseup' : 'mousemove';
		var simulatedEvent = new MouseEvent( simulatedType, {
			view: window,
			bubbles: true,
			cancelable: true,
			screenX: ( event.touches[ 0 ] ) ? event.touches[ 0 ].screenX : 0,
			screenY: ( event.touches[ 0 ] ) ? event.touches[ 0 ].screenY : 0,
			clientX: ( event.touches[ 0 ] ) ? event.touches[ 0 ].clientX : 0,
			clientY: ( event.touches[ 0 ] ) ? event.touches[ 0 ].clientY : 0,
			button: 0,
			buttons: 1
		} );
		var eventTarget = ( event.type === 'touchmove' ) ? document.elementFromPoint( simulatedEvent.clientX, simulatedEvent.clientY ) || document.body : event.target;
		if ( status === 'mousemove' || status === 'mousedown' ) {
			// prevent native scroll event
			if ( event.cancelable ) {
				event.preventDefault();
			}
		}
		eventTarget.dispatchEvent( simulatedEvent );
	}

	// core mouse/touch events
	const reorderFrames = () => {
		// order calculation based on the left style number
		const order = [];
		$( '.' + defaultOptions.draggableItemClass ).each( ( index, element ) => {
			order.push(
				( parseInt( element.style.left ) || 0 ) / defaultOptions.itemUnitWidth + index
			);
		} );

		// trick to prevent error order case
		if ( new Set( order ).size === order.length ) {
			setTimeout( () => {
				defaultOptions.callback( order );
			}, defaultOptions.transitionDuration );

		} else {
			// fallback to the original order
			$( '.' + defaultOptions.draggableItemClass ).each( ( index, element ) => {
				element.style.left = '0px';
			} );
		}
	};

	const onMouseMove = ( event ) => {
		const newOffset = event.clientX - offset;
		const moveThreshold = Math.floor( defaultOptions.itemUnitWidth / 8 );
		const newTarget = event.target || event.currentTarget;

		if ( status === 'ready' && Math.abs( newOffset ) >= moveThreshold ) {
			// move during ready status in within timeout 500ms
			// will cancel reorder action
			target = null;
			return;
		} else if ( status === 'mousedown' && Math.abs( newOffset ) <= moveThreshold ) {
			// threshold to prevent small movement when press,
			// Also used to handle a bug in chrome when mousemove event is fired
			// when mouse has not moved
			return;
		} else if ( status === 'mousedown' || status === 'mousemove' ) {
			// update status to move
			status = 'mousemove';

			// update current hover status position left
			target.style.left = origin + newOffset + 'px';

			// disable text selection
			window.getSelection().removeAllRanges();

			// detect when mouse touch other target
			if ( status === 'mousemove' && target !== newTarget ) {
				// when mouse in the sort container
				if ( newTarget.classList.contains( defaultOptions.containerClass ) ) {
					// execute the animation only when mouse touch the new target element
				} else if ( newTarget.classList.contains( defaultOptions.draggableItemClass ) ) {
					// when the mouse move over a certain offset, sort the target frame
					if ( Math.abs( newOffset - originOffset ) >
						( defaultOptions.itemUnitWidth - 10 )
					) {
						newTarget.style.position = 'relative';

						if ( ( newOffset - originOffset ) > 0 ) {
							newTarget.style.left =
                                ( parseInt( newTarget.style.left ) || 0 ) - defaultOptions.itemUnitWidth + 'px';
							originOffset += defaultOptions.itemUnitWidth;
						} else {
							newTarget.style.left =
                                ( parseInt( newTarget.style.left ) || 0 ) + defaultOptions.itemUnitWidth + 'px';
							originOffset -= defaultOptions.itemUnitWidth;
						}
					}
				} else {
					// when mouse touch outside of the sort container
					status = 'idle';
					target.classList.remove( defaultOptions.draggableItemClass + '-scale' );
					target.style.left = origin + originOffset + 'px';
					reorderFrames();
				}
			}
		}
	};

	const onMouseUp = () => {
		if ( status === 'mousemove' ) {
			target.classList.remove( defaultOptions.draggableItemClass + '-scale' );
			target.style.left = origin + originOffset + 'px';
			reorderFrames();
		}

		// remove all sort event listeners and reset variables
		target = null;
		clearTimeout( reorderStartTimeoutId );
		status = 'idle';
		window.removeEventListener( 'mousemove', onMouseMove, false );
		window.removeEventListener( 'mouseup', onMouseUp, false );
	};

	const onMouseDown = ( event ) => {
		if ( status === 'idle' && event.buttons === 1 ) { // mouse left click
			target = event.target || event.currentTarget;
			status = 'ready';
			offset = event.clientX;
			origin = parseInt( target.style.left ) || 0;
			originOffset = 0;

			window.addEventListener( 'mousemove', onMouseMove, false );
			window.addEventListener( 'mouseup', onMouseUp, false );

			reorderStartTimeoutId = setTimeout( () => {
				if ( target &&
					target.classList.contains( defaultOptions.draggableItemClass )
				) {
					status = 'mousedown';
					target.classList.add( defaultOptions.draggableItemClass + '-scale' );

					if ( navigator.vibrate ) {
						navigator.vibrate( 200 );
					}
				}
			}, defaultOptions.reorderStartTimeout );
		}
	};

	// initialization / kill
	return {
		set: ( el, options ) => {
			el.sortOptions = $.extend( defaultOptions, options );// create options object

			// mouse events (desktop)
			el.addEventListener( 'mousedown', onMouseDown, false );

			// prevent some default events when dragging
			document.addEventListener( 'drag', muteEvent, false );
			document.addEventListener( 'dragstart', muteEvent, false );

			// touch events (laptop)
			window.addEventListener( 'touchstart', simulateMouseEvent );
			window.addEventListener( 'touchmove', simulateMouseEvent, { passive: false } );
			window.addEventListener( 'touchend', simulateMouseEvent );
		},
		kill: () => {
			document.removeEventListener( 'drag', muteEvent, false );
			document.removeEventListener( 'dragstart', muteEvent, false );
			window.removeEventListener( 'touchstart', simulateMouseEvent );
			window.removeEventListener( 'touchmove', simulateMouseEvent, { passive: false } );
			window.removeEventListener( 'touchend', simulateMouseEvent );
		}
	};
} )();

module.exports = {
	setup: function ( containerClass, itemsClass, callback ) {
		sortable.set( document.querySelector( '.' + containerClass ), {
			containerClass: containerClass,
			draggableItemClass: itemsClass,
			callback: callback,
			itemUnitWidth: $( '.' + itemsClass ).outerWidth( true )
		} );
	},
	kill: function () {
		sortable.kill();
	}
};
