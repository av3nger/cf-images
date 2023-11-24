/**
 * External dependencies
 */
import { mdiZipBoxOutline } from '@mdi/js';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Card from '../../components/card';

const ImageCompress = () => {
	return (
		<Card
			icon={mdiZipBoxOutline}
			id="image-compress"
			title={__('Image Optimization', 'cf-images')}
		>
			<div className="content">
				<p>
					{__(
						'Optimize JPEG/PNG images to significantly decrease file size without compromising visual quality.',
						'cf-images'
					)}
				</p>
			</div>
		</Card>
	);
};

export default ImageCompress;
