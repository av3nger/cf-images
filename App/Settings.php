<?php
/**
 * The file that defines the plugin settings class
 *
 * This is used to define saving settings, writing wp-config.php defines.
 *
 * @link https://vcore.ru
 *
 * @package CF_Images
 * @subpackage CF_Images/App
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.0.0
 */

namespace CF_Images\App;

use Exception;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The plugin settings class.
 *
 * @since 1.0.0
 */
class Settings {

	/**
	 * Do initial setup by storing user provided Cloudflare account ID and API key in wp-config.php file.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function ajax_do_setup() {

		check_ajax_referer( 'cf-images-nonce' );

		if ( ! current_user_can( 'manage_options' ) || ! isset( $_POST['data'] ) ) {
			wp_die();
		}

		// Data sanitized later in code.
		parse_str( wp_unslash( $_POST['data'] ), $form ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( ! isset( $form['account-id'] ) || ! isset( $form['api-key'] ) ) {
			wp_die();
		}

		$this->write_config( 'CF_IMAGES_ACCOUNT_ID', sanitize_text_field( $form['account-id'] ) );
		$this->write_config( 'CF_IMAGES_KEY_TOKEN', sanitize_text_field( $form['api-key'] ) );

		wp_send_json_success();

	}

	/**
	 * Save settings.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function ajax_save_settings() {

		check_ajax_referer( 'cf-images-nonce' );

		if ( ! current_user_can( 'manage_options' ) || ! isset( $_POST['data'] ) ) {
			wp_die();
		}

		// Data sanitized later in code.
		parse_str( wp_unslash( $_POST['data'] ), $form ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( ! isset( $form['disable-sizes'] ) ) {
			delete_option( 'cf-images-disable-generation' );
		} else {
			update_option( 'cf-images-disable-generation', (bool) $form['disable-sizes'] );
		}

		if ( ! isset( $form['custom-domain'] ) ) {
			delete_option( 'cf-images-custom-domain' );
		} else {
			update_option( 'cf-images-custom-domain', (bool) $form['custom-domain'] );
		}

		wp_send_json_success();

	}

	/**
	 * Write key/value pair to wp-config.php.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name   Name of the constant to add.
	 * @param string $value  Value of the constant to add.
	 *
	 * @return void
	 */
	public function write_config( string $name, string $value ) {

		$path_to_wp_config = ABSPATH . 'wp-config.php';

		// wp-config.php file not found - exit early.
		if ( ! file_exists( $path_to_wp_config ) ) {
			return;
		}

		$config_file = file( $path_to_wp_config );

		$new_file_content = array();
		foreach ( $config_file as $line ) {
			if ( preg_match( "/define\(\s*'$name'/i", $line ) ) {
				continue;
			}

			if ( preg_match( "/\/\* That's all, stop editing!.*/i", $line ) ) {
				$new_file_content[] = "define( '$name', '$value' );\n";
			}

			$new_file_content[] = $line;
		}

		$handle = fopen( $path_to_wp_config, 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
		fwrite( $handle, implode( '', $new_file_content ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose

		if ( ! defined( 'FS_CHMOD_FILE' ) ) {
			define( 'FS_CHMOD_FILE', ( fileperms( ABSPATH . 'index.php' ) & 0777 | 0644 ) );
		}

		chmod( $path_to_wp_config, FS_CHMOD_FILE );

	}

	public function is_image_sizes_disabled() {



	}

}
