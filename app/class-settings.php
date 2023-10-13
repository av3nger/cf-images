<?php
/**
 * The file that defines the plugin settings class
 *
 * This is used to define saving settings, writing wp-config.php defines.
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.0.0
 */

namespace CF_Images\App;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The plugin settings class.
 *
 * @since 1.0.0
 */
class Settings {
	use Traits\Ajax;

	/**
	 * Default settings.
	 *
	 * @since 1.5.0
	 */
	const DEFAULTS = array(
		'auto-offload'       => false,
		'auto-resize'        => false,
		'custom-domain'      => false,
		'custom-id'          => false,
		'disable-async'      => false,
		'disable-generation' => false,
		'full-offload'       => false,
		'image-ai'           => false,
		'image-compress'     => false,
		'page-parser'        => false,
	);

	/**
	 * Class constructor.
	 *
	 * @since 1.5.0
	 */
	public function __construct() {
		if ( wp_doing_ajax() ) {
			add_action( 'wp_ajax_cf_images_update_settings', array( $this, 'ajax_update_settings' ) );
			add_action( 'wp_ajax_cf_images_set_custom_domain', array( $this, 'ajax_set_custom_domain' ) );
		}
	}

	/**
	 * Do initial setup by storing user provided Cloudflare account ID and API key in wp-config.php file.
	 *
	 * @since 1.0.0
	 */
	public function ajax_do_setup() {
		$this->check_ajax_request();

		// Nonce checked in check_ajax_request(), data sanitized later in code.
		parse_str( wp_unslash( $_POST['data'] ), $form ); // phpcs:ignore WordPress.Security

		if ( ! isset( $form['account-id'] ) || ! isset( $form['api-key'] ) ) {
			wp_die();
		}

		$this->write_config( 'CF_IMAGES_ACCOUNT_ID', sanitize_text_field( $form['account-id'] ) );
		$this->write_config( 'CF_IMAGES_KEY_TOKEN', sanitize_text_field( $form['api-key'] ) );

		// Remove any auth errors.
		delete_option( 'cf-images-auth-error' );

		wp_send_json_success();
	}

	/**
	 * Write key/value pair to wp-config.php file.
	 *
	 * If the $value is not set, the constant will be removed.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name      Name of the constant to add.
	 * @param string $value     Value of the constant to add.
	 * @param bool   $overwrite Overwrite the current value. Default: false.
	 */
	public function write_config( string $name, string $value = '', bool $overwrite = false ) {
		$path_to_wp_config = ABSPATH . 'wp-config.php';

		// wp-config.php file not found - exit early.
		if ( ! file_exists( $path_to_wp_config ) ) {
			return;
		}

		$config_file = file( $path_to_wp_config );

		$new_file_content = array();
		foreach ( $config_file as $line ) {
			if ( preg_match( "/define\(\s*'$name'/i", $line ) && ! $overwrite ) {
				continue;
			}

			if ( ! empty( $value ) && preg_match( "/\/\* That's all, stop editing!.*/i", $line ) ) {
				$new_file_content[] = "define( '$name', '$value' );\n";

				// Exit early, if we are overwriting.
				if ( $overwrite ) {
					continue;
				}
			}

			$new_file_content[] = $line;
		}

		$this->write( $path_to_wp_config, $new_file_content );

		update_option( 'cf-images-config-written', ! empty( $value ), false );

		// On some hosts, the wp-config.php updates take several seconds to "un-cache".
		sleep( 2 );
	}

	/**
	 * Filesystem write to wp-config.php file.
	 *
	 * @since 1.0.0
	 *
	 * @param string $wp_config_path Path to wp-config.php file.
	 * @param array  $content        Array of lines to add to the file.
	 */
	private function write( string $wp_config_path, array $content ) {
		$handle = fopen( $wp_config_path, 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		fwrite( $handle, implode( '', $content ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}

	/**
	 * Disconnect from Cloudflare.
	 *
	 * @since 1.1.2
	 */
	public function ajax_disconnect() {
		delete_site_option( 'cf-images-hash' );
		delete_option( 'cf-images-setup-done' );
		delete_option( 'cf-images-config-written' );
		delete_option( 'cf-images-auth-error' );

		// Remove defines from wp-config.php file.
		$this->write_config( 'CF_IMAGES_ACCOUNT_ID' );
		$this->write_config( 'CF_IMAGES_KEY_TOKEN' );

		wp_send_json_success();
	}

	/**
	 * Hide sidebar.
	 *
	 * @since 1.3.0
	 */
	public function ajax_hide_sidebar() {
		update_site_option( 'cf-images-hide-sidebar', true );
	}

	/**
	 * Update settings from React app.
	 *
	 * @since 1.5.0
	 */
	public function ajax_update_settings() {
		$this->check_ajax_request();

		$data = filter_input( INPUT_POST, 'data', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		$settings = get_option( 'cf-images-settings', self::DEFAULTS );

		// Make sure we add any options that have been added to the DEFAULTS array.
		$settings = wp_parse_args( $settings, self::DEFAULTS );

		foreach ( $settings as $key => $value ) {
			// Skip unsupported settings.
			if ( ! isset( $data[ $key ] ) ) {
				continue;
			}

			$settings[ $key ] = filter_var( $data[ $key ], FILTER_VALIDATE_BOOLEAN );
		}

		// Remove custom domain option, if the module is disabled.
		if ( ! isset( $data['custom-domain'] ) ) {
			delete_option( 'cf-images-custom-domain' );
		}

		update_option( 'cf-images-settings', $settings, false );
		wp_send_json_success();
	}

	/**
	 * Update custom domain value.
	 *
	 * @since 1.5.0
	 */
	public function ajax_set_custom_domain() {
		$this->check_ajax_request();

		$data = filter_input( INPUT_POST, 'data', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		if ( ! isset( $data['domain'] ) ) {
			delete_option( 'cf-images-custom-domain' );
		} else {
			$url = esc_url( $data['domain'] );
			if ( ! wp_http_validate_url( $url ) ) {
				wp_send_json_error( esc_html__( 'Please enter a valid domain', 'cf-images' ) );
			}

			update_option( 'cf-images-custom-domain', $url, false );
		}

		wp_send_json_success();
	}
}
