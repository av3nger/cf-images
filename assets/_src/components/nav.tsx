/**
 * External dependencies
 */
import { useContext } from 'react';
import { NavLink } from 'react-router-dom';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import SettingsContext from '../context/settings';

const Nav = () => {
	const { modules, noticeHidden } = useContext(SettingsContext);

	const getClass = (status: boolean) => {
		return status ? 'is-active' : '';
	};

	return (
		<aside className="menu">
			<span className="is-size-4 pb-3 cf-plugin-title">
				{__('Offload. Store. Resize.', 'cf-images')}
				<br />
				{__('Image Optimize', 'cf-images')}
			</span>

			<p className="menu-label">{__('Cloudflare Images', 'cf-images')}</p>

			<ul className="menu-list">
				<li>
					<NavLink
						to="/"
						className={({ isActive }) => getClass(isActive)}
					>
						{__('Settings', 'cf-images')}
					</NavLink>
				</li>
				<li>
					<NavLink
						to="/cf/experimental"
						className={({ isActive }) => getClass(isActive)}
					>
						{__('Experimental', 'cf-images')}
					</NavLink>
				</li>
			</ul>

			<p className="menu-label">{__('Image Tools', 'cf-images')}</p>

			<ul className="menu-list">
				<li>
					<NavLink
						to="/tools/settings"
						className={({ isActive }) => getClass(isActive)}
					>
						{__('AI & Compression', 'cf-images')}
					</NavLink>
				</li>
				<li>
					<NavLink
						to="/tools/premium"
						className={({ isActive }) => getClass(isActive)}
					>
						{__('Pro Features', 'cf-images')}
					</NavLink>
				</li>
			</ul>

			{(!noticeHidden || ('logging' in modules && modules.logging)) && (
				<>
					<p className="menu-label">{__('Misc', 'cf-images')}</p>
					{'logging' in modules && modules.logging && (
						<ul className="menu-list">
							<li>
								<NavLink
									to="/misc/logs"
									className={({ isActive }) =>
										getClass(isActive)
									}
								>
									{__('Logs', 'cf-images')}
								</NavLink>
							</li>
						</ul>
					)}

					{!noticeHidden && (
						<ul className="menu-list">
							<li>
								<NavLink
									to="/misc/support"
									className={({ isActive }) =>
										getClass(isActive)
									}
								>
									{__('Support', 'cf-images')}
								</NavLink>
							</li>
						</ul>
					)}
				</>
			)}
		</aside>
	);
};

export default Nav;
