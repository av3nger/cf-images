/**
 * External dependencies
 */
import { useContext } from 'react';

/**
 * Internal dependencies
 */
import SettingsContext from '../../context/settings';
import ImageAI from '../../modules/fuzion/image-ai';
import ImageCompress from '../../modules/fuzion/image-compress';
import Login from './login';
import CompressionStats from '../../modules/fuzion/ai-stats';
import ImageGenerate from '../../modules/fuzion/image-generate';
import UpsellModule from '../../modules/fuzion/upsell';
import FuzionDisconnect from '../../modules/actions/ai-disconnect';

/**
 * Cloudflare Images settings routes.
 *
 * @class
 */
const ToolsSettings = () => {
	const { hasFuzion, setFuzion } = useContext(SettingsContext);

	if (!hasFuzion) {
		return <Login />;
	}

	return (
		<div className="columns is-multiline">
			<CompressionStats />
			<ImageAI />
			<ImageCompress />
			<ImageGenerate />
			<UpsellModule />
			<FuzionDisconnect />
		</div>
	);
};

export default ToolsSettings;
