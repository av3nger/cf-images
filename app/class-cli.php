<?php
/**
 * WP CLI functionality
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.5.0
 */

namespace CF_Images\App;

use CF_Images\App\Traits\Ajax;
use CF_Images\App\Traits\Helpers;
use WP_CLI;
use WP_CLI_Command;
use WP_Query;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Offload images to Cloudflare Images service.
 *
 * @since 1.5.0
 */
class CLI extends WP_CLI_Command {
	use Ajax;
	use Helpers;

	/**
	 * Offload images to Cloudflare Images.
	 *
	 * ## OPTIONS
	 *
	 * [--id=<ID>]
	 * : Attachment ID to offload.
	 * ---
	 * default: 0
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 * # Offload all images.
	 * $ wp cf-images offload
	 *
	 * # Offload single image, attachment ID = 10.
	 * $ wp cf-images offload --id=10
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Arguments, defined like --key=value or --flag or --no-flag.
	 */
	public function offload( $args, $assoc_args ) {
		if ( ! $this->is_set_up() ) {
			WP_CLI::error( __( 'Plugin is not connected to Cloudflare.', 'cf-images' ) );
		}

		$attachment_id = (int) $assoc_args['id'];

		if ( $attachment_id && $attachment_id > 0 ) {
			$this->offload_single( $attachment_id );
		}

		$this->offload_all();
	}

	/**
	 * Offload single image.
	 *
	 * @since 1.5.0
	 *
	 * @param int $attachment_id Attachment ID.
	 */
	private function offload_single( int $attachment_id ) {
		WP_CLI::line(
			sprintf( /* translators: %d - attachment ID */
				esc_html__( 'Offloading image with ID %d.', 'cf-images' ),
				(int) $attachment_id
			)
		);

		$metadata = wp_get_attachment_metadata( $attachment_id );
		if ( false === $metadata ) {
			WP_CLI::error( __( 'Image metadata not found.', 'cf-images' ) );
			return;
		}

		( new Media() )->upload_image( $metadata, $attachment_id );

		if ( is_wp_error( Core::get_error() ) ) {
			WP_CLI::error( Core::get_error()->get_error_message() );
		} else {
			WP_CLI::success( __( 'Image offloaded.', 'cf-images' ) );
		}
	}

	/**
	 * Offload all images.
	 *
	 * @since 1.5.0
	 */
	private function offload_all() {
		$args = $this->get_wp_query_args( 'upload' );

		// Look for images that have been offloaded.
		$images = new WP_Query( $args );

		if ( ! $images->have_posts() ) {
			WP_CLI::success( __( 'All images have already been offloaded.', 'cf-images' ) );
			return;
		}

		$errors   = array();
		$progress = WP_CLI\Utils\make_progress_bar( __( 'Offloading images', 'cf-images' ), $images->found_posts );

		foreach ( $images->posts as $attachment ) {
			$metadata = wp_get_attachment_metadata( $attachment->ID );
			if ( false === $metadata ) {
				$errors[] = sprintf( /* translators: %d - attachment ID */
					esc_html__( 'Image metadata not found (attachment ID: %d).', 'cf-images' ),
					$attachment->ID
				);
			} else {
				( new Media() )->upload_image( $metadata, $attachment->ID );

				if ( is_wp_error( Core::get_error() ) ) {
					$errors[] = sprintf( /* translators: %1$s - error message, %2$d - attachment ID */
						esc_html__( '%1$s (attachment ID: %2$d).', 'cf-images' ),
						esc_html( Core::get_error()->get_error_message() ),
						$attachment->ID
					);
				}
			}

			$progress->tick();
		}

		$progress->finish();

		if ( empty( $errors ) ) {
			WP_CLI::success( __( 'All images offloaded', 'cf-images' ) );
		} else {
			foreach ( $errors as $error ) {
				WP_CLI::error( $error );
			}
		}
	}
}
