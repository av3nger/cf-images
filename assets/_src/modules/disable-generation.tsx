/**
 * External dependencies
 */
import { mdiImageMultipleOutline } from '@mdi/js';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Card from '../components/card';

const DisableGeneration = () => {
	return (
		<Card
			icon={mdiImageMultipleOutline}
			id="disable-generation"
			title={__('Disable WordPress image sizes', 'cf-images')}
		>
			<div className="content">
				<p>
					{__(
						'Setting this option will disable generation of `-scaled` images and other image sizes. Only the original image will be stored in the media library. Only for newly uploaded files, current images will not be affected.',
						'cf-images'
					)}
				</p>
				<p>
					{__(
						'Note: This feature is experimental. All the image sizes can be restored with the `Regenerate Thumbnails` plugin.',
						'cf-images'
					)}
				</p>
			</div>
		</Card>
	);
};

export default DisableGeneration;
