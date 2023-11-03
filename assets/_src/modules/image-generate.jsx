/**
 * External dependencies
 */
import { mdiImageOutline } from '@mdi/js';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Card from '../components/card';

const ImageGenerate = () => {
	return (
		<Card
			icon={ mdiImageOutline }
			id="image-generate"
			title={ __( 'Image Generation', 'cf-images' ) }
		>
			<div className="content">
				<p>{ __( 'Use generative AI to create images based on text prompts. This option will add a new Gutenberg image block.', 'cf-images' ) }</p>
			</div>
		</Card>
	);
};

export default ImageGenerate;
