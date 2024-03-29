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
import CloudflareSettings from './routes/cloudflare/settings';
import CloudflareExperimental from './routes/cloudflare/experimental';
import Support from './routes/support';
import ToolsSettings from './routes/tools/settings';
import Logs from './routes/misc/logs';
import ImageGenerateRoute from './routes/image/generate';
import ToolsPremium from './routes/tools/premium';

/**
 * App
 *
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
							<Route index element={<CloudflareSettings />} />
							<Route
								path="/cf/experimental"
								element={<CloudflareExperimental />}
							/>
							<Route
								path="/tools/settings"
								element={<ToolsSettings />}
							/>
							<Route
								path="/tools/premium"
								element={<ToolsPremium />}
							/>
							<Route
								path="/image/generate"
								element={<ImageGenerateRoute />}
							/>
							<Route path="/misc/logs" element={<Logs />} />
							<Route path="/misc/support" element={<Support />} />
							<Route
								path="*"
								element={<Navigate to="/" replace />}
							/>
						</Routes>
					</div>
				</div>
			</SettingsProvider>
		</HashRouter>
	);
};

const container = document.getElementById('cf-images');
const root = createRoot(container);
root.render(<App />);
