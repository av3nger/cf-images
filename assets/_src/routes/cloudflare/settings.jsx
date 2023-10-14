/**
 * Internal dependencies
 */
import AutoOffload from '../../modules/auto-offload';
import CustomId from '../../modules/custom-id';
import CustomDomain from '../../modules/custom-domain';
import DisableAsync from '../../modules/disable-async';
import PageParser from '../../modules/page-parser';

/**
 * Cloudflare Images settings routes.
 *
 * @return {JSX.Element} Cloudflare settings component.
 * @class
 */
const CloudflareSettings = () => {
	return (
		<div className="columns is-multiline">
			<AutoOffload />
			<CustomId />
			<CustomDomain />
			<PageParser />
			<DisableAsync />
		</div>
	);
};

export default CloudflareSettings;
