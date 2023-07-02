<?php
/**
 * Image AI module
 *
 * Add AI-based functionality for tagging and captioning images.
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Modules
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 *
 * @since 1.4.0
 */

namespace CF_Images\App\Modules;

use CF_Images\App\Api\Ai;
use CF_Images\App\Traits\Ajax;
use Exception;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Image_Ai class.
 *
 * @since 1.4.0
 */
class Image_Ai extends Module {

	use Ajax;

	/**
	 * Register UI components.
	 *
	 * @since 1.4.0
	 *
	 * @return void
	 */
	protected function register_ui() {
		$this->icon  = 'format-image';
		$this->new   = true;
		$this->title = esc_html__( 'Image AI', 'cf-images' );
	}

	/**
	 * Render module description.
	 *
	 * @since 1.4.0
	 *
	 * @param string $module  Module ID.
	 *
	 * @return void
	 */
	public function render_description( string $module ) {

		if ( $module !== $this->module ) {
			return;
		}
		?>
		<p>
			<?php esc_html_e( 'Use the power of AI to tag and caption your images.', 'cf-images' ); ?>
		</p>
		<?php if ( ! get_option( 'cf-image-ai-api-key', false ) ) : ?>
			<div class="cf-images-ai-settings" <?php echo $this->is_enabled() ? '' : 'style="display: none"'; ?>>
				<p>
					<?php
					printf( /* translators: %1$s - register link, %2$s - closing tag, %3$s - add API key link */
						esc_html__( "Don't have an account? %1\$sRegister for a free account%2\$s. Already have an API key? %3\$sAdd it here%2\$s.", 'cf-images' ),
						'<a href="https://getfuzion.io/register" target="_blank" rel="noopener">',
						'</a>',
						'<a href="#" id="js-add-image-ai-api-key">'
					)
					?>
				</p>

				<div id="cf-images-ai-email">
					<p>
						<label class="screen-reader-text" for="cf-ai-email-address"><?php esc_html_e( 'Email address', 'cf-images' ); ?></label>
						<input type="email" id="cf-ai-email-address" placeholder="<?php esc_attr_e( 'Email address', 'cf-images' ); ?>">

						<label class="screen-reader-text" for="cf-ai-password"><?php esc_html_e( 'Password', 'cf-images' ); ?></label>
						<input type="password" id="cf-ai-password" placeholder="<?php esc_attr_e( 'Password', 'cf-images' ); ?>">
					</p>
					<p>
						<a href="#" role="button" class="outline" aria-busy="false" id="image-ai-login">
							<?php esc_html_e( 'Login', 'cf-images' ); ?>
						</a>
					</p>
				</div>

				<div id="cf-images-ai-api-key">
					<p>
						<label class="screen-reader-text" for="cf-ai-api-key"><?php esc_html_e( 'API key', 'cf-images' ); ?></label>
						<input type="text" id="cf-ai-api-key" placeholder="<?php esc_attr_e( 'API key', 'cf-images' ); ?>">
					</p>
					<p>
						<a href="#" role="button" class="outline" aria-busy="false">
							<?php esc_html_e( 'Save', 'cf-images' ); ?>
						</a>
					</p>
				</div>
			</div>
		<?php else : ?>
			<p>
				<?php
				printf( /* translators: %1$s - disconnect link, %2$s - closing tag */
					esc_html__( '%1$sDisconnect%2$s from API.', 'cf-images' ),
					'<a href="#" id="image-ai-disconnect">',
					'</a>'
				)
				?>
			</p>
		<?php endif; ?>
		<?php

	}

	/**
	 * Init the module.
	 *
	 * @since 1.4.0
	 */
	public function init() {

		if ( wp_doing_ajax() ) {
			add_action( 'wp_ajax_cf_images_ai_login', array( $this, 'login' ) );
			add_action( 'wp_ajax_cf_images_ai_disconnect', array( $this, 'disconnect' ) );
		}

	}

	/**
	 * Login to Image AI service.
	 *
	 * @since 1.4.0
	 *
	 * @return void
	 */
	public function login() {

		$this->check_ajax_request();

		$data = filter_input( INPUT_POST, 'data', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		if ( empty( $data['email'] ) || ! is_email( $data['email'] ) ) {
			wp_send_json_error( __( 'Please provide a valid email address.', 'cf-images' ) );
		}

		if ( empty( $data['password'] ) ) {
			wp_send_json_error( __( 'Password cannot be empty.', 'cf-images' ) );
		}

		$args = array(
			'email'    => sanitize_email( $data['email'] ),
			'password' => htmlentities( $data['password'] ),
			'site'     => wp_parse_url( site_url(), PHP_URL_HOST ),
		);

		try {
			$ai_api = new Ai();
			$ai_api->login( $args );
			wp_send_json_success();
		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}

	}

	/**
	 * Disconnect from API.
	 *
	 * @since 1.4.0
	 *
	 * @return void
	 */
	public function disconnect() {

		$this->check_ajax_request( true );
		delete_option( 'cf-image-ai-api-key' );
		wp_send_json_success();

	}

}
