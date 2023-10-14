/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

const Home = () => {
	return (
		<div className="columns is-multiline">
			<div className="column is-half-tablet is-one-third-desktop">
				<div className="card is-flex is-flex-direction-column">
					<div className="card-content">
						<div className="media is-align-content-center is-align-items-center">
							<div className="media-left">
								Icon
							</div>
							<div className="media-content">
								<p className="title is-4">
									{ __( 'Cloudflare status', 'cf-images' ) }
								</p>
							</div>
						</div>

						<p>connected</p>
					</div>
				</div>
			</div>

			<div className="column is-half-tablet is-one-third-desktop">
				<div className="card is-flex is-flex-direction-column">
					<div className="card-content">
						<div className="media is-align-content-center is-align-items-center">
							<div className="media-left">
								Icon
							</div>
							<div className="media-content">
								<p className="title is-4">
									{ __( 'AI & Optimization API', 'cf-images' ) }
								</p>
							</div>
						</div>

						<p>connected</p>
					</div>
				</div>
			</div>

			<div className="column is-full">
				<div className="level">
					<div className="level-item has-text-centered">
						<div>
							<p className="heading">{ __( 'Images offloaded', 'cf-images' ) }</p>
							<p className="title">3,456</p>
						</div>
					</div>
					<div className="level-item has-text-centered">
						<div>
							<p className="heading">{ __( 'Images compressed', 'cf-images' ) }</p>
							<p className="title">123</p>
						</div>
					</div>
					<div className="level-item has-text-centered">
						<div>
							<p className="heading">{ __( 'Space saved', 'cf-images' ) }</p>
							<p className="title">456KB</p>
						</div>
					</div>
					<div className="level-item has-text-centered">
						<div>
							<p className="heading">{ __( 'Alt`s generated', 'cf-images' ) }</p>
							<p className="title">789</p>
						</div>
					</div>
				</div>
			</div>
		</div>
	);
};

export default Home;
