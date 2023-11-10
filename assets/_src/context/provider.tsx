/**
 * External dependencies
 */
import { ReactElement, useState } from 'react';

/**
 * Internal dependencies
 */
import { post } from '../js/helpers/post';
import SettingsContext from './settings';

/**
 * Settings provider
 *
 * @param {Object}       props
 * @param {ReactElement} props.children
 * @class
 */
const SettingsProvider = ({ children }: { children: ReactElement[] }) => {
	const { cfStatus, fuzion, hideSidebar, settings } = window.CFImages;
	const [stats, setStats] = useState(window.CFImages.stats);
	const [modules, setModules] = useState(settings);
	const [noticeHidden, hideNotice] = useState(hideSidebar);
	const [hasFuzion, setFuzion] = useState(fuzion);
	const [cfConnected, setCfConnected] = useState(cfStatus);
	const [inProgress, setInProgress] = useState(false);

	const setModule = (module: string, value: boolean) => {
		const newSettings = { ...modules };
		newSettings[module] = value;

		post('cf_images_update_settings', newSettings)
			.then(() => setModules(newSettings))
			.catch(window.console.log);
	};

	return (
		<SettingsContext.Provider
			value={{
				modules,
				setModule,
				noticeHidden,
				hideNotice,
				hasFuzion,
				setFuzion,
				cfConnected,
				setCfConnected,
				inProgress,
				setInProgress,
				stats,
				setStats,
			}}
		>
			{children}
		</SettingsContext.Provider>
	);
};

export default SettingsProvider;
