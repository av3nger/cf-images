/**
 * External dependencies
 */
import { useContext } from 'react';
import { Link } from 'react-router-dom';
import { mdiScriptTextOutline } from '@mdi/js';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Card from '../components/card';
import SettingsContext from '../context/settings';

const Logging = () => {
	const { modules } = useContext(SettingsContext);

	return (
		<Card
			icon={mdiScriptTextOutline}
			id="logging"
			title={__('Logging', 'cf-images')}
		>
			<div className="content">
				<p>
					{__(
						'Enable logging to identify issues with the plugin.',
						'cf-images'
					)}
				</p>
				{'logging' in modules && modules.logging && (
					<>
						<p>
							<Link to="/misc/logs">
								{__('View logs', 'cf-images')}
							</Link>
						</p>
						<div className="notification is-warning">
							<p>
								{__(
									'This can have a negative impact on performance, only enable during testing and debugging.',
									'cf-images'
								)}
							</p>
						</div>
					</>
				)}
			</div>
		</Card>
	);
};

export default Logging;
