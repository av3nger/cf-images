/**
 * External dependencies
 */
import { useContext } from 'react';
import Icon from '@mdi/react';
import { mdiClose } from '@mdi/js';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import SettingsContext from '../../context/settings';
import { post } from '../../js/helpers/post';

const EluxoUpsell = () => {
	const { eluxoHidden, hideEluxo } = useContext(SettingsContext);

	if (eluxoHidden) {
		return null;
	}

	const dismiss = () => {
		hideEluxo(true);
		post('cf_images_hide_eluxo').catch(window.console.log);
	};

	return (
		<div className="box mt-5 cf-eluxo-notice">
			<button
				onClick={dismiss}
				className="cf-eluxo-notice__close"
				aria-label={__('Dismiss', 'cf-images')}
			>
				<Icon path={mdiClose} size={0.7} />
			</button>
			<p className="has-text-weight-semibold mb-2">
				{__("We're building Eluxo.ai", 'cf-images')}
			</p>
			<p className="mb-3">
				{__(
					'AI-powered image editing, multi-angle shots, scene presets & brand-consistent visuals for your store. Sync directly with WooCommerce.',
					'cf-images'
				)}
			</p>
			<p className="cf-eluxo-notice__links">
				<a
					href="https://eluxo.ai/?utm_source=cf-images"
					target="_blank"
					rel="noopener noreferrer"
					className="has-text-link"
				>
					{__('Learn more →', 'cf-images')}
				</a>
				<a
					href="#dismiss-eluxo"
					className="has-text-grey"
					onClick={(e) => {
						e.preventDefault();
						dismiss();
					}}
				>
					{__('Dismiss', 'cf-images')}
				</a>
			</p>
		</div>
	);
};

export default EluxoUpsell;
