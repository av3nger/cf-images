/**
 * External dependencies
 */
import { createRoot } from 'react-dom/client';
import { HashRouter, Routes, Route } from 'react-router-dom';

/**
 * Internal dependencies
 */
import './app.scss';
import Nav from './components/nav';
import SettingsProvider from './context/provider';
import CloudflareSettings from './routes/cloudflare-settings';
import CloudflareExperimental from './routes/cloudflare-experimental';

/**
 * App
 * @return {JSX.Element} App component.
 * @class
 */
const App = () => {
	return (
		<HashRouter>
			<div className="column is-one-fifth">
				<Nav />
			</div>

			<div className="column">
				<div className="box">
					<SettingsProvider>
						<Routes>
							<Route index element={ <CloudflareSettings /> } />
							<Route path="/main/experimental" element={ <CloudflareExperimental /> } />
						</Routes>
					</SettingsProvider>
				</div>
			</div>
		</HashRouter>
	);
};

const container = document.getElementById( 'cf-images' );
const root = createRoot( container );
root.render( <App /> );
