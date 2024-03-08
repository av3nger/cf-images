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
import AutoOffload from '../../modules/cloudflare/auto-offload';
import CustomId from '../../modules/cloudflare/custom-id';
import CustomDomain from '../../modules/cloudflare/custom-domain';
import DisableAsync from '../../modules/cloudflare/disable-async';
import PageParser from '../../modules/cloudflare/page-parser';
import CloudflareLogin from './login';
import CloudflareDisconnect from '../../modules/actions/cf-disconnect';
import CloudflareStats from '../../modules/cloudflare/cf-stats';
import Logging from '../../modules/cloudflare/logging';
import Service from '../../modules/cloudflare/service';
import MiscOptions from '../../modules/cloudflare/misc';

/**
 * Cloudflare Images settings routes.
 *
 * @class
 */
const CloudflareSettings = () => {
	const { cfConnected, cdnEnabled } = useContext(SettingsContext);

	if (cfConnected) {
		return (
			<div className="columns is-multiline">
				{cdnEnabled && (
					<div className="column is-full">
						<div className="notification is-warning">
							<p>
								{__(
									'CDN module is enabled. Cloudflare Images functionality has been disable.',
									'cf-images'
								)}
							</p>
						</div>
					</div>
				)}
				<CloudflareStats />
				<AutoOffload />
				<CustomId />
				<CustomDomain />
				<PageParser />
				<DisableAsync />
				<MiscOptions />
				<Service />
				<Logging />
				<CloudflareDisconnect />
			</div>
		);
	}

	return <CloudflareLogin />;
};

export default CloudflareSettings;
