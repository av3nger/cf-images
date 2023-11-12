/**
 * External dependencies
 */
import { mdiMolecule } from '@mdi/js';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Card from '../components/card';

const ImageAI = () => {
	return (
		<Card
			icon={mdiMolecule}
			id="image-ai"
			title={__('Caption AI', 'cf-images')}
		>
			<div className="content">
				<p>
					{__(
						'Use the power of AI to tag and caption your images.',
						'cf-images'
					)}
				</p>
				<p>
					{__(
						'Only images that are publicly accessible can be captioned. Limit of 20 images per month.',
						'cf-images'
					)}
				</p>
			</div>
		</Card>
	);
};

export default ImageAI;
