/**
 * External dependencies
 */
import { useContext } from 'react';

/**
 * Internal dependencies
 */
import SettingsContext from '../../context/settings';
import AutoOffload from '../../modules/auto-offload';
import CustomId from '../../modules/custom-id';
import CustomDomain from '../../modules/custom-domain';
import DisableAsync from '../../modules/disable-async';
import PageParser from '../../modules/page-parser';
import CloudflareLogin from './login';
import Disconnect from './disconnect';

/**
 * Cloudflare Images settings routes.
 *
 * @return {JSX.Element} Cloudflare settings component.
 * @class
 */
const CloudflareSettings = () => {
	const { cfConnected } = useContext( SettingsContext );

	if ( cfConnected ) {
		return (
			<div className="columns is-multiline">
				<AutoOffload />
				<CustomId />
				<CustomDomain />
				<PageParser />
				<DisableAsync />
				<Disconnect />
			</div>
		);
	}

	return (
		<CloudflareLogin />
	);
};

export default CloudflareSettings;
