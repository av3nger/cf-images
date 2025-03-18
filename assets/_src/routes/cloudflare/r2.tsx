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
import CloudflareLogin from './login';
import R2Stats from '../../modules/cloudflare/r2-stats';

/**
 * Cloudflare R2 settings routes.
 *
 * @class
 */
const R2Settings = () => {
	const { cfConnected } = useContext(SettingsContext);

	if (cfConnected) {
		return (
			<div className="columns is-multiline">
				<R2Stats />
			</div>
		);
	}

	return <CloudflareLogin />;
};

export default R2Settings;
