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
		'custom-id'          => false,
		'disable-async'      => false,
		'disable-generation' => false,
		'full-offload'       => false,
		'image-ai'           => false,
		'image-compress'     => false,
		'page-parser'        => false,
	);

	public function __construct() {
		if ( wp_doing_ajax() ) {
			add_action( 'wp_ajax_cf_images_update_settings', array( $this, 'ajax_update_settings' ) );
		}
	}

	public function ajax_update_settings() {
		$this->check_ajax_request();

		// Nonce checked in check_ajax_request(), data sanitized later in code.
		$data = filter_input( INPUT_POST, 'data', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		$settings = get_option( 'cf-images-settings', self::DEFAULTS );

		foreach ( $settings as $key => $value ) {
			// Skip unsupported settings.
			if ( ! isset( $data[ $key ] ) ) {
				continue;
			}

			$settings[ $key ] = filter_var( $data[ $key ], FILTER_VALIDATE_BOOLEAN );
		}

		update_option( 'cf-images-settings', $settings, false );
		wp_send_json_success();
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
	 * Save settings.
	 *
	 * @since 1.0.0
	 */
	public function ajax_save_settings() {
		$this->check_ajax_request();

		// Nonce checked in check_ajax_request(), data sanitized later in code.
		parse_str( wp_unslash( $_POST['data'] ), $form ); // phpcs:ignore WordPress.Security

		// TODO: These have changed to a single array of settings.
		// List of settings. The key corresponds to the name of the form field, the value corresponds to the name of the option.
		$options = array(
			'auto-offload'       => 'cf-images-auto-offload',
			'auto-resize'        => 'cf-images-auto-resize',
			'custom-id'          => 'cf-images-custom-id',
			'disable-async'      => 'cf-images-disable-async',
			'disable-generation' => 'cf-images-disable-generation',
			'full-offload'       => 'cf-images-full-offload',
			'image-ai'           => 'cf-images-image-ai',
			'image-compress'     => 'cf-images-image-compress',
			'page-parser'        => 'cf-images-page-parser',
		);

		foreach ( $options as $key => $option ) {
			if ( ! isset( $form[ $key ] ) ) {
				delete_option( $option );
			} else {
				update_option( $option, (bool) $form[ $key ], false );
			}
		}

		if ( ! isset( $form['custom-domain'] ) ) {
			delete_option( 'cf-images-custom-domain' );
		} else {
			$value = (bool) $form['custom-domain'];
			if ( isset( $form['custom_domain_input'] ) ) {
				$url = esc_url( $form['custom_domain_input'] );
				if ( wp_http_validate_url( $url ) ) {
					$value = $url;
				}
			}

			update_option( 'cf-images-custom-domain', $value, false );
		}

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
}
