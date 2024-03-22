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
	const {
		browserTTL,
		cfStatus,
		fuzion,
		hideSidebar,
		isNetworkAdmin,
		settings,
	} = window.CFImages;

	const [stats, setStats] = useState(window.CFImages.stats);
	const [modules, setModules] = useState(settings);
	const [noticeHidden, hideNotice] = useState(hideSidebar);
	const [hasFuzion, setFuzion] = useState(fuzion);
	const [cfConnected, setCfConnected] = useState(cfStatus);
	const [inProgress, setInProgress] = useState(false);
	const [domain, setDomain] = useState(window.CFImages.domain);
	const [cdnEnabled, setCdnEnabled] = useState(window.CFImages.cdnEnabled);

	const setModule = (module: string, value: boolean) => {
		const newSettings = { ...modules };
		newSettings[module] = value;

		if ('cdn' === module) {
			setCdnEnabled(value);
		}

		post('cf_images_update_settings', newSettings)
			.then(() => setModules(newSettings))
			.catch(window.console.log);
	};

	return (
		<SettingsContext.Provider
			value={{
				browserTTL,
				modules,
				setModule,
				noticeHidden,
				hideNotice,
				hasFuzion,
				isNetworkAdmin,
				setFuzion,
				cfConnected,
				setCfConnected,
				inProgress,
				setInProgress,
				stats,
				setStats,
				domain,
				setDomain,
				cdnEnabled,
				setCdnEnabled,
			}}
		>
			{children}
		</SettingsContext.Provider>
	);
};

export default SettingsProvider;
