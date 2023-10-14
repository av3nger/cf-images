/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

const Home = () => {
	return (
		<div className="content">
			<nav className="level">
				<div className="level-item has-text-centered">
					<div>
						<p className="heading">{ __( 'Offloaded', 'cf-images' ) }</p>
						<p className="title">3,456</p>
					</div>
				</div>
				<div className="level-item has-text-centered">
					<div>
						<p className="heading">{ __( 'Compressed', 'cf-images' ) }</p>
						<p className="title">123</p>
					</div>
				</div>
				<div className="level-item has-text-centered">
					<div>
						<p className="heading">{ __( 'Saved', 'cf-images' ) }</p>
						<p className="title">456KB</p>
					</div>
				</div>
				<div className="level-item has-text-centered">
					<div>
						<p className="heading">{ __( 'Alt`s generated', 'cf-images' ) }</p>
						<p className="title">789</p>
					</div>
				</div>
			</nav>
		</div>
	);
};

export default Home;
