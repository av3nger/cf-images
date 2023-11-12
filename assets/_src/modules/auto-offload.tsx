/**
 * External dependencies
 */
import { mdiAutoUpload } from '@mdi/js';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Card from '../components/card';

const AutoOffload = () => {
	return (
		<Card
			icon={mdiAutoUpload}
			id="auto-offload"
			title={__('Auto offload new images', 'cf-images')}
		>
			<div className="content">
				<p>
					{__(
						'Enable this option if you want to enable automatic offloading for newly uploaded images.',
						'cf-images'
					)}
				</p>
				<p>
					{__(
						'By default, new images will not be auto offloaded to Cloudflare Images.',
						'cf-images'
					)}
				</p>
			</div>
		</Card>
	);
};

export default AutoOffload;
