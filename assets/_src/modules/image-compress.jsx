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
import Card from '../components/card';

const ImageCompress = () => {
	return (
		<Card
			icon={ mdiZipBoxOutline }
			id="image-compress"
			title={ __( 'Image Optimization', 'cf-images' ) }
		>
			<div className="content">
				<p>{ __( 'Compress JPEG/PNG images and reduce the file size.', 'cf-images' ) }</p>
			</div>
		</Card>
	);
};

export default ImageCompress;
