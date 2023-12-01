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
	const { cdnEnabled, modules, setModule } = useContext(SettingsContext);

	const [activating, setActivating] = useState(false);
	const [error, setError] = useState('');
	const [done, setDone] = useState(false);
	const [purgeSuccess, setPurgeSuccess] = useState(false);
	const [loading, setLoading] = useState(false);

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

	/**
	 * Activate the CDN or update the status.
	 *
	 * @param refresh Is this a status update?
	 */
	const activate = (refresh: boolean = false) => {
		setLoading(true);

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

					if (!refresh) {
						setDone(true);
						setTimeout(() => setDone(false), 5000);
					}
				}

				// Still activating. Retry in 5 seconds.
				if (202 === response.data && !refresh) {
					setActivating(true);
					setTimeout(() => activate(), 5000);
				}
			})
			.catch(window.console.log)
			.finally(() => setLoading(false));
	};

	// Run on module status change.
	useEffectAfterMount(() => {
		if ('cdn' in modules && modules.cdn) {
			setError('');
			activate();
		}
	}, [modules]);

	useEffect(() => {
		// Update the status on each refresh, but only if cdn is active (not just enabled).
		if ('cdn' in modules && modules.cdn && cdnEnabled && !loading) {
			setLoading(true);
			activate(true);
		}
	}, []);

	const purgeCache = () => {
		setError('');
		setLoading(true);

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
			.finally(() => setLoading(false));
	};

	// Disable module if full-offload is enabled.
	const id =
		'full-offload' in modules && modules['full-offload'] ? null : 'cdn';

	return (
		<Card icon={mdiWeb} id={id} title={__('Image CDN', 'cf-images')}>
			<div className="content">
				<p>
					{__(
						'Serve your images from a next-generation CDN with lightning fast performance.',
						'cf-images'
					)}
				</p>

				<p>
					{__('Status:', 'cf-images')}&nbsp;
					{activating && !loading && __('Activating...', 'cf-images')}
					{loading && __('Updating status...', 'cf-images')}
					{!activating &&
						!loading &&
						!done &&
						!error &&
						__('Active', 'cf-images')}
				</p>

				{error && (
					<div className="notification is-warning">
						<p>{error}</p>
					</div>
				)}

				{!id && (
					<div className="notification is-warning">
						<p>
							{__(
								'CDN will not work when the experimental "Full offload" feature is enabled.',
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
							'is-loading': loading,
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
