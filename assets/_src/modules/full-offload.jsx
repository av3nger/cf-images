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

const FullOffload = () => {
	return (
		<Card
			icon={ mdiImageMultipleOutline }
			id="full-offload"
			title={ __( 'Full offload', 'cf-images' ) }
		>
			<div className="content">
				<p>{ __( 'Setting this option will allow removing original images from the media library.', 'cf-images' ) }</p>
				<p>{ __( 'By enabling this feature, you understand the potential risks of removing media files from the media library. Please ensure you have a backup of your media library.', 'cf-images' ) }</p>
			</div>
		</Card>
	);
};

export default FullOffload;
