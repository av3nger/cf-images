/**
 * External dependencies
 */
import {
	DependencyList,
	EffectCallback,
	useContext,
	useEffect,
	useRef,
	useState,
} from 'react';
import { mdiWeb } from '@mdi/js';
import * as classNames from 'classnames';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Card from '../../components/card';
import SettingsContext from '../../context/settings';
import { post } from '../../js/helpers/post';

const CDN = () => {
	const { modules, setModule } = useContext(SettingsContext);

	const [activating, setActivating] = useState(false);
	const [error, setError] = useState('');
	const [done, setDone] = useState(false);
	const [purgeSuccess, setPurgeSuccess] = useState(false);
	const [purging, setPurging] = useState(false);

	// We need to skip the initial mount.
	function useEffectAfterMount(
		callback: EffectCallback,
		dependencies: DependencyList
	) {
		const hasMounted = useRef(false);

		useEffect(() => {
			if (hasMounted.current) {
				return callback();
			}
			hasMounted.current = true;
		}, dependencies);
	}

	const activate = () => {
		post('cf_image_enable_cdn')
			.then((response: ApiResponse) => {
				if (!response.success && response.data) {
					setModule('cdn', false);
					setError(response.data);
					setTimeout(() => setError(''), 10000);
					return;
				}

				// Zone is active.
				if (201 === response.data) {
					setActivating(false);
					setDone(true);
					setTimeout(() => setDone(false), 5000);
				}

				// Still activating. Retry in 5 seconds.
				if (202 === response.data) {
					setActivating(true);
					setTimeout(() => activate(), 5000);
				}
			})
			.catch(window.console.log);
	};

	// Run on module status change.
	useEffectAfterMount(() => {
		if ('cdn' in modules && modules.cdn) {
			setError('');
			activate();
		}
	}, [modules]);

	const purgeCache = () => {
		setError('');
		setPurging(true);

		post('cf_image_purge_cdn_cache')
			.then((response: ApiResponse) => {
				if (!response.success && response.data) {
					setError(response.data);
					setTimeout(() => setError(''), 10000);
				} else {
					setPurgeSuccess(true);
					setTimeout(() => setPurgeSuccess(false), 5000);
				}
			})
			.catch(window.console.log)
			.finally(() => setPurging(false));
	};

	return (
		<Card icon={mdiWeb} id="cdn" title={__('Image CDN', 'cf-images')}>
			<div className="content">
				<p>
					{__(
						'Serve your images from a next-generation CDN with lightning fast performance.',
						'cf-images'
					)}
				</p>

				{error && (
					<div className="notification is-warning">
						<p>{error}</p>
					</div>
				)}

				{activating && (
					<div className="notification is-info">
						<p>
							{__(
								'CDN is activating. Might take a minute...',
								'cf-images'
							)}
						</p>
					</div>
				)}

				{done && (
					<div className="notification is-success">
						<p>{__('CDN activated successfully', 'cf-images')}</p>
					</div>
				)}

				{purgeSuccess && (
					<div className="notification is-success">
						<p>{__('Cache cleared', 'cf-images')}</p>
					</div>
				)}

				{'cdn' in modules && modules.cdn && (
					<button
						className={classNames('button is-small', {
							'is-loading': purging,
						})}
						onClick={purgeCache}
					>
						{__('Purge cache', 'cf-images')}
					</button>
				)}
			</div>
		</Card>
	);
};

export default CDN;
