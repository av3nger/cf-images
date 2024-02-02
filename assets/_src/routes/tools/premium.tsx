/**
 * External dependencies
 */
import { useContext } from 'react';

/**
 * Internal dependencies
 */
import SettingsContext from '../../context/settings';
import Login from './login';
import FuzionDisconnect from '../../modules/actions/ai-disconnect';
import CustomPath from '../../modules/premium/custom-path';
import CDN from '../../modules/premium/cdn';

/**
 * Premium modules.
 *
 * @class
 */
const ToolsPremium = () => {
	const { hasFuzion } = useContext(SettingsContext);

	if (!hasFuzion) {
		return <Login />;
	}

	return (
		<div className="columns is-multiline">
			<CustomPath />
			<CDN />
			<FuzionDisconnect />
		</div>
	);
};

export default ToolsPremium;
