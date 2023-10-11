/**
 * External dependencies
 */
import { createRoot } from 'react-dom/client';

/**
 * Internal dependencies
 */
import './app.scss';
import Card from './components/card/index.jsx';
import Nav from './components/nav/index.jsx';

/**
 * App
 * @return {JSX.Element} App component.
 * @class
 */
const App = () => {
	return (
		<div className="columns">
			<div className="column is-one-fifth">
				<Nav />
			</div>

			<div className="column">
				<Card />
			</div>
		</div>
	);
};

const container = document.getElementById( 'cf-images' );
const root = createRoot( container );
root.render( <App /> );
