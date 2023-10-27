/**
 * External dependencies
 */
import { useContext, useState } from 'react';
import Icon from '@mdi/react';
import { mdiApi, mdiEmailOutline, mdiLockOutline } from '@mdi/js';
import classNames from 'classnames';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { post } from '../../js/helpers/post';
import SettingsContext from '../../context/settings';

/**
 * Login component.
 *
 * @return {JSX.Element} Login component.
 * @class
 */
const Login = () => {
	const [ apiKey, setApiKey ] = useState( '' );
	const [ apiKeyError, setApiKeyError ] = useState( '' );
	const [ loginError, setLoginError ] = useState( '' );
	const [ loading, setLoading ] = useState( false );
	const [ apiKeyView, setApiKeyView ] = useState( false );

	const { setFuzion } = useContext( SettingsContext );

	const initSubmit = () => {
		setLoading( true );
		setApiKeyError( '' );
		setLoginError( '' );
	};

	const saveKey = () => {
		initSubmit();

		post( 'cf_images_ai_save', apiKey )
			.then( ( response ) => {
				if ( ! response.success && 'undefined' !== typeof response.data ) {
					setApiKeyError( response.data );
				} else {
					setFuzion( true );
				}
			} )
			.catch( window.console.log )
			.finally( () => setLoading( false ) );
	};

	const login = ( e ) => {
		e.preventDefault();
		initSubmit();

		const formData = new FormData( e.target );
		const args = {
			email: formData.get( 'username' ) ?? '',
			password: formData.get( 'password' ) ?? '',
		};

		post( 'cf_images_ai_login', args )
			.then( ( response ) => {
				if ( ! response.success && 'undefined' !== typeof response.data ) {
					setLoginError( response.data );
				} else {
					setFuzion( true );
				}
			} )
			.catch( window.console.log )
			.finally( () => setLoading( false ) );
	};

	return (
		<section className="hero is-halfheight">
			<div className="hero-body">
				<div className="container is-max-desktop has-text-centered">
					<form className="box mx-6 px-6 pt-6 pb-5" onSubmit={ ( e ) => login( e ) }>
						{ apiKeyView ? (
							<div className="field">
								<label className="label" htmlFor="api-key">
									{ __( 'API key', 'cf-images' ) }
								</label>
								<div className="control has-icons-left">
									<input
										className={ classNames( 'input', { 'is-danger': apiKeyError } ) }
										disabled={ loading }
										id="api-key"
										onChange={ ( e ) => setApiKey( e.target.value ) }
										placeholder={ __( 'API key', 'cf-images' ) }
										required
										type="text"
										value={ apiKey }
									/>
									<span className="icon is-small is-left">
										<Icon path={ mdiApi } size={ 1 } />
									</span>
									{ apiKeyError && <p className="help is-danger">{ apiKeyError }</p> }
								</div>
							</div>
						) : (
							<>
								<div className="field">
									<label className="label" htmlFor="username">
										{ __( 'Email address', 'cf-images' ) }
									</label>
									<div className={ classNames( 'control has-icons-left', { 'is-loading': loading } ) }>
										<input
											autoComplete="username"
											className={ classNames( 'input', { 'is-danger': loginError } ) }
											disabled={ loading }
											id="username"
											name="username"
											placeholder={ __( 'e.g. alex@example.com', 'cf-images' ) }
											required
											type="email"
										/>
										<span className="icon is-small is-left">
											<Icon path={ mdiEmailOutline } size={ 1 } />
										</span>
									</div>
								</div>

								<div className="field">
									<label className="label" htmlFor="password">
										{ __( 'Password', 'cf-images' ) }
									</label>
									<div className={ classNames( 'control has-icons-left', { 'is-loading': loading } ) }>
										<input
											autoComplete="current-password"
											className={ classNames( 'input', { 'is-danger': loginError } ) }
											disabled={ loading }
											id="password"
											name="password"
											placeholder="********"
											required
											type="password"
										/>
										<span className="icon is-small is-left">
											<Icon path={ mdiLockOutline } size={ 1 } />
										</span>
										{ loginError && <p className="help is-danger">{ loginError }</p> }
									</div>
								</div>
							</>
						) }

						{ apiKeyView ? (
							<button
								className={ classNames( 'button is-primary is-fullwidth mt-5', { 'is-loading': loading } ) }
								onClick={ ( e ) => {
									e.preventDefault();
									saveKey();
								} }
							>
								{ __( 'Save', 'cf-images' ) }
							</button>
						) : (
							<button
								className={ classNames( 'button is-primary is-fullwidth mt-5', { 'is-loading': loading } ) }
								type="submit"
							>
								{ __( 'Sign in', 'cf-images' ) }
							</button>
						) }

						<div className="is-flex is-justify-content-space-between mt-5">
							<p className="control">
								<a
									className="button is-ghost px-0"
									href="https://getfuzion.io/register"
									rel="noopener noreferrer"
									target="_blank"
								>
									{ __( 'Register for a free account', 'cf-images' ) }
								</a>
							</p>
							<p className="control">
								<button
									className="button is-ghost px-0"
									disabled={ loading }
									onClick={ ( e ) => {
										e.preventDefault();
										setApiKeyView( ! apiKeyView );
									} }
								>
									{ apiKeyView
										? __( 'Login using email/password', 'cf-images' )
										: __( 'Already have an API key', 'cf-images' )
									}
								</button>
							</p>
						</div>
					</form>
				</div>
			</div>
		</section>
	);
};

export default Login;
