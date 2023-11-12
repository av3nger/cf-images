/**
 * External dependencies
 */
import { useContext } from 'react';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import SettingsContext from '../../context/settings';
import DisableGeneration from '../../modules/disable-generation';
import FullOffload from '../../modules/full-offload';
import Disconnect from './disconnect';
import CloudflareLogin from './login';

/**
 * Cloudflare Images experimental settings routes.
 *
 * @class
 */
const CloudflareExperimental = () => {
	const { cfConnected } = useContext(SettingsContext);

	if (!cfConnected) {
		return <CloudflareLogin />;
	}

	return (
		<div className="columns is-multiline">
			<div className="column is-full">
				<div className="notification is-danger">
					<p>
						{__(
							'These features are experimental and have undergone only limited testing.',
							'cf-images'
						)}
					</p>
					<p>
						{__(
							'Please make sure you have a backup of all your files, before enabling any of these features.',
							'cf-images'
						)}
					</p>
				</div>
			</div>
			<DisableGeneration />
			<FullOffload />
			<Disconnect />
		</div>
	);
};

export default CloudflareExperimental;
