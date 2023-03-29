/**
 * Modal
 */

// Config
let visibleModal = null;

// Toggle modal
export const toggleModal = ( event ) => {
	event.preventDefault();
	const modal = document.getElementById( event.currentTarget.getAttribute( 'data-target' ) );
	if ( 'undefined' !== typeof ( modal ) && modal !== null && isModalOpen( modal ) ) {
		closeModal( modal );
	} else {
		openModal( modal );
	}
};
window.cfToggleModal = toggleModal;

// Is modal open
const isModalOpen = ( modal ) => {
	return modal.hasAttribute( 'open' ) && modal.getAttribute( 'open' ) !== 'false';
};

// Open modal
const openModal = ( modal ) => {
	if ( isScrollbarVisible() ) {
		document.documentElement.style.setProperty( '--scrollbar-width', `${ getScrollbarWidth() }px` );
	}
	setTimeout( () => {
		visibleModal = modal;
	}, 10 );
	modal.setAttribute( 'open', true );
};

// Close modal
const closeModal = ( modal ) => {
	visibleModal = null;
	document.documentElement.style.removeProperty( '--scrollbar-width' );
	setTimeout( () => {
		modal.removeAttribute( 'open' );
	}, 10 );
};

// Close with a click outside
document.addEventListener( 'click', ( event ) => {
	if ( visibleModal !== null ) {
		const modalContent = visibleModal.querySelector( 'article' );
		const isClickInside = modalContent.contains( event.target );
		if ( ! isClickInside ) {
			closeModal( visibleModal );
		}
	}
} );

// Close with Esc key
document.addEventListener( 'keydown', ( event ) => {
	if ( event.key === 'Escape' && visibleModal !== null ) {
		closeModal( visibleModal );
	}
} );

// Get scrollbar width
const getScrollbarWidth = () => {
	// Creating invisible container
	const outer = document.createElement( 'div' );
	outer.style.visibility = 'hidden';
	outer.style.overflow = 'scroll'; // forcing scrollbar to appear
	outer.style.msOverflowStyle = 'scrollbar'; // needed for WinJS apps
	document.body.appendChild( outer );

	// Creating inner element and placing it in the container
	const inner = document.createElement( 'div' );
	outer.appendChild( inner );

	// Calculating difference between container's full width and the child width
	const scrollbarWidth = ( outer.offsetWidth - inner.offsetWidth );

	// Removing temporary elements from the DOM
	outer.parentNode.removeChild( outer );

	return scrollbarWidth;
};

// Is scrollbar visible
const isScrollbarVisible = () => {
	return document.body.scrollHeight > screen.height;
};
