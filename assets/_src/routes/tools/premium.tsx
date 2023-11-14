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
import CustomPaths from '../../modules/premium/custom-paths';

/**
 * Premium modules.
 *
 * @class
 */
const ToolsPremium = () => {
	const { hasFuzion, setFuzion } = useContext(SettingsContext);

	if (!hasFuzion) {
		return <Login />;
	}

	return (
		<div className="columns is-multiline">
			<CustomPaths />
			<FuzionDisconnect />
		</div>
	);
};

export default ToolsPremium;
