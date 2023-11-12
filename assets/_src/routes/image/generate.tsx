/**
 * External dependencies
 */
import { FormEvent, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import Icon from '@mdi/react';
import { mdiArrowLeft } from '@mdi/js';
import * as classNames from 'classnames';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { post } from '../../js/helpers/post';

/**
 * Login component.
 *
 * @class
 */
const ImageGenerateRoute = () => {
	const [error, setError] = useState('');
	const [image, setImage] = useState('');
	const [loading, setLoading] = useState(false);
	const [mediaLink, setMediaLink] = useState('');

	const navigate = useNavigate();

	const resetState = () => {
		setError('');
		setImage('');
		setLoading(true);
		setMediaLink('');
	};

	const generate = (e: FormEvent<HTMLFormElement>) => {
		e.preventDefault();
		resetState();

		const formData = new FormData(e.currentTarget);
		const args = {
			prompt: formData.get('prompt') ?? '',
		};

		post('cf_images_ai_generate', args)
			.then((response: ApiResponse) => {
				if (!response.success && 'undefined' !== typeof response.data) {
					setError(response.data);
				} else {
					setImage(response.data?.url ?? '');
					setMediaLink(response.data?.media ?? '');
				}
			})
			.catch(window.console.log)
			.finally(() => setLoading(false));
	};

	return (
		<section className="hero is-halfheight">
			<div className="hero-body">
				<div className="container is-max-desktop">
					{image ? (
						<div>
							<div className="buttons are-small is-flex is-justify-content-space-between">
								<button
									className="button"
									onClick={() => navigate('/tools/settings')}
								>
									<span className="icon">
										<Icon path={mdiArrowLeft} size={0.5} />
									</span>
									<span>{__('Go back', 'cf-images')}</span>
								</button>

								{mediaLink && (
									<a href={mediaLink} className="button">
										{__(
											'Open in media library',
											'cf-images'
										)}
									</a>
								)}
							</div>
							<figure className="image">
								<img
									src={image}
									alt={__('Generated image', 'cf-images')}
								/>
							</figure>
						</div>
					) : (
						<form
							className="box mx-6 px-6 pt-6 pb-5"
							onSubmit={(e) => generate(e)}
						>
							<div className="field">
								<label className="label" htmlFor="api-key">
									{__('Prompt', 'cf-images')}
								</label>
								<div
									className={classNames('control', {
										'is-loading': loading,
									})}
								>
									<textarea
										className={classNames('textarea', {
											'is-danger': error,
										})}
										disabled={loading}
										name="prompt"
										placeholder="A picture of a brown dog on a skateboard"
									></textarea>
									{error && (
										<p className="help is-danger">
											{error}
										</p>
									)}
								</div>
							</div>

							<button
								className={classNames(
									'button is-primary is-fullwidth mt-5',
									{ 'is-loading': loading }
								)}
								type="submit"
							>
								{__('Generate', 'cf-images')}
							</button>
						</form>
					)}
				</div>
			</div>
		</section>
	);
};

export default ImageGenerateRoute;
