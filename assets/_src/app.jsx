/**
 * External dependencies
 */
import { createRoot } from 'react-dom/client';
import { HashRouter, Navigate, Routes, Route } from 'react-router-dom';

/**
 * Internal dependencies
 */
import './app.scss';
import Nav from './components/nav';
import SettingsProvider from './context/provider';
import CloudflareSettings from './routes/cloudflare-settings';
import CloudflareExperimental from './routes/cloudflare-experimental';
import Support from './routes/support';

/**
 * App
 * @return {JSX.Element} App component.
 * @class
 */
const App = () => {
	return (
		<HashRouter>
			<SettingsProvider>
				<div className="column is-one-fifth">
					<Nav />
				</div>

				<div className="column">
					<div className="box">
						<Routes>
							<Route index element={ <CloudflareSettings /> } />
							<Route path="/main/experimental" element={ <CloudflareExperimental /> } />
							<Route path="/misc/support" element={ <Support /> } />
							<Route path="*" element={ <Navigate to="/" replace /> } />
						</Routes>
					</div>
				</div>
			</SettingsProvider>
		</HashRouter>
	);
};

const container = document.getElementById( 'cf-images' );
const root = createRoot( container );
root.render( <App /> );
