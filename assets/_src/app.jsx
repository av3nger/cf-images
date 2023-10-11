/**
 * Internal dependencies
 */
import { createRoot } from 'react-dom/client';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * App
 * @return {JSX.Element} App component.
 * @class
 */
const App = () => {
	return (
		<h1>{ __( 'Offload. Store. Resize. Image Optimize', 'cf-images' ) }</h1>
	);
};

const container = document.getElementById( 'cf-images' );
const root = createRoot( container );
root.render( <App /> );
