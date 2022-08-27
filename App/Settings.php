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
use WP_Query;

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
	 * Remove all images from Cloudflare progress bar handler.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function ajax_remove_images() {

		check_ajax_referer( 'cf-images-nonce' );

		if ( ! current_user_can( 'manage_options' ) || ! isset( $_POST['data'] ) ) {
			wp_die();
		}

		// Data sanitized later in code.
		$progress = filter_input( INPUT_POST, 'data', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		if ( ! isset( $progress['currentStep'] ) || ! isset( $progress['totalSteps'] ) ) {
			wp_send_json_error( esc_html__( 'No current step or total steps defined', 'cf-images' ) );
		}

		$step  = (int) $progress['currentStep'];
		$total = (int) $progress['totalSteps'];

		// Progress just started.
		if ( 0 === $step && 0 === $total ) {
			$args = array(
				'post_type'   => 'attachment',
				'post_status' => 'inherit',
				'meta_key'    => '_cloudflare_image_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			);

			// Look for images that have been offloaded.
			$images = new WP_Query( $args );
			$total  = $images->found_posts;
		}

		$step++;

		// We have some data left.
		if ( $step <= $total ) {
			$args = array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'meta_key'       => '_cloudflare_image_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'posts_per_page' => 1,
			);

			// Look for images that have been offloaded.
			$images = new WP_Query( $args );

			$id = get_post_meta( $images->post->ID, '_cloudflare_image_id', true );

			$image = new Api\Image();

			try {
				$image->delete( $id );
				delete_post_meta( $images->post->ID, '_cloudflare_image_id' );
			} catch ( Exception $e ) {
				wp_send_json_error( $e->getMessage() );
			}
		}

		$response = array(
			'currentStep' => $step,
			'totalSteps'  => $total,
			'status'      => sprintf( /* translators: %1$d - current image, %2$d - total number of images */
				esc_html__( 'Removing image %1$d from %2$d...', 'cf-images' ),
				(int) $step,
				$total
			),
		);

		wp_send_json_success( $response );
	}

	/**
	 * Write key/value pair to wp-config.php file.
	 *
	 * If the $value is not set, the constant will be removed.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name   Name of the constant to add.
	 * @param string $value  Value of the constant to add.
	 *
	 * @return void
	 */
	public function write_config( string $name, string $value = '' ) {

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

			if ( ! empty( $value ) && preg_match( "/\/\* That's all, stop editing!.*/i", $line ) ) {
				$new_file_content[] = "define( '$name', '$value' );\n";
			}

			$new_file_content[] = $line;
		}

		$this->write( $path_to_wp_config, $new_file_content );

	}

	/**
	 * Filesystem write to wp-config.php file.
	 *
	 * @since 1.0.0
	 *
	 * @param string $wp_config_path  Path to wp-config.php file.
	 * @param array  $content         Array of lines to add to the file.
	 *
	 * @return void
	 */
	private function write( string $wp_config_path, array $content ) {

		$handle = fopen( $wp_config_path, 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
		fwrite( $handle, implode( '', $content ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose

		if ( ! defined( 'FS_CHMOD_FILE' ) ) {
			define( 'FS_CHMOD_FILE', ( fileperms( ABSPATH . 'index.php' ) & 0777 | 0644 ) );
		}

		chmod( $wp_config_path, FS_CHMOD_FILE );

	}

}
