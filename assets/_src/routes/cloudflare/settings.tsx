/**
 * External dependencies
 */
import { useContext } from 'react';

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

/**
 * Cloudflare Images settings routes.
 *
 * @class
 */
const CloudflareSettings = () => {
	const { cfConnected } = useContext(SettingsContext);

	if (cfConnected) {
		return (
			<div className="columns is-multiline">
				<CloudflareStats />
				<AutoOffload />
				<CustomId />
				<CustomDomain />
				<PageParser />
				<DisableAsync />
				<Service />
				<Logging />
				<CloudflareDisconnect />
			</div>
		);
	}

	return <CloudflareLogin />;
};

export default CloudflareSettings;
