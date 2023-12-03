/**
 * External dependencies
 */
import { MouseEvent, useContext, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { mdiChartBar } from '@mdi/js';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import SettingsContext from '../../context/settings';
import Card from '../../components/card';
import ProgressBar from '../../components/progress';
import { formatBytes } from '../../js/helpers/format';

const CompressionStats = () => {
	const [action, setAction] = useState('');
	const { inProgress, setInProgress, modules, stats } =
		useContext(SettingsContext);

	const navigate = useNavigate();

	const runAction = (e: MouseEvent, actionName: string) => {
		e.preventDefault();
		setAction(actionName);
		setInProgress(true);
	};

	const getFooter = () => {
		return (
			<div className="card-footer mt-auto">
				{modules['image-ai'] && (
					<p className="card-footer-item">
						<button
							className="button is-fullwidth is-small is-ghost"
							onClick={(e) => runAction(e, 'alt-tags')}
						>
							{__('Bulk add ALT tags', 'cf-images')}
						</button>
					</p>
				)}
				{modules['image-compress'] && (
					<p className="card-footer-item">
						<button
							className="button is-fullwidth is-small is-ghost"
							onClick={(e) => runAction(e, 'compress')}
						>
							{__('Bulk compress', 'cf-images')}
						</button>
					</p>
				)}
				{modules['image-generate'] && (
					<p className="card-footer-item">
						<button
							className="button is-fullwidth is-small is-ghost"
							onClick={() => navigate('/image/generate')}
						>
							{__('Generate image', 'cf-images')}
						</button>
					</p>
				)}
			</div>
		);
	};

	return (
		<Card
			icon={mdiChartBar}
			title={__('Info & stats', 'cf-images')}
			footer={getFooter()}
			wide={true}
		>
			<div className="content">
				<div className="level">
					<div className="level-item has-text-centered">
						<div>
							<p className="heading">
								{__('ALT tags generated', 'cf-images')}
							</p>
							<p className="title">{stats.alt_tags ?? 0}</p>
						</div>
					</div>
					<div className="level-item has-text-centered">
						<div>
							<p className="heading">
								{__('Images generated', 'cf-images')}
							</p>
							<p className="title">{stats.image_ai ?? 0}</p>
						</div>
					</div>
					<div className="level-item has-text-centered">
						<div>
							<p className="heading">
								{__('Compression savings', 'cf-images')}
							</p>
							<p className="title">
								{formatBytes(
									(stats.size_before ?? 0) -
										(stats.size_after ?? 0)
								)}
							</p>
						</div>
					</div>
				</div>

				{inProgress && <ProgressBar action={action} />}
			</div>
		</Card>
	);
};

export default CompressionStats;
