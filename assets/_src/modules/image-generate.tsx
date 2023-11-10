/**
 * External dependencies
 */
import { useContext } from 'react';
import { useNavigate } from 'react-router-dom';
import { mdiImageOutline } from '@mdi/js';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Card from '../components/card';
import SettingsContext from '../context/settings';

const ImageGenerate = () => {
	const { modules } = useContext(SettingsContext);
	const navigate = useNavigate();

	return (
		<Card
			icon={mdiImageOutline}
			id="image-generate"
			title={__('Image Generation', 'cf-images')}
		>
			<div className="content">
				<p>
					{__(
						'Use generative AI to create images based on text prompts.',
						'cf-images'
					)}
				</p>

				{'image-generate' in modules && modules['image-generate'] && (
					<button
						className="button is-small is-fullwidth"
						onClick={() => navigate('/image/generate')}
					>
						{__('Generate image', 'cf-images')}
					</button>
				)}
			</div>
		</Card>
	);
};

export default ImageGenerate;
