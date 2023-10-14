/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import DisableGeneration from '../../modules/disable-generation';
import FullOffload from '../../modules/full-offload';
import Disconnect from './disconnect';

/**
 * Cloudflare Images experimental settings routes.
 *
 * @return {JSX.Element} Cloudflare experimental component.
 * @class
 */
const CloudflareExperimental = () => {
	return (
		<div className="columns is-multiline">
			<div className="column is-full">
				<div className="notification is-danger">
					<p>{ __( 'These features are experimental and have undergone only limited testing.', 'cf-images' ) }</p>
					<p>{ __( 'Please make sure you have a backup of all your files, before enabling any of these features.', 'cf-images' ) }</p>
				</div>
			</div>
			<DisableGeneration />
			<FullOffload />
			<Disconnect />
		</div>
	);
};

export default CloudflareExperimental;
