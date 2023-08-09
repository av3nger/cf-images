<?php
/**
 * The file that defines Ajax traits that are used across all classes
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Traits
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.2.0
 */

namespace CF_Images\App\Traits;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The Ajax trait class.
 *
 * @since 1.2.0
 */
trait Ajax {

	/**
	 * Check if this is a valid AJAX request coming from the user.
	 *
	 * @since 1.0.1
	 * @since 1.2.0  Moved out to this trait from class-core.php
	 *
	 * @param bool $no_data  Request has no data.
	 *
	 * @return void
	 */
	private function check_ajax_request( $no_data = false ) {

		check_ajax_referer( 'cf-images-nonce' );

		if ( ! current_user_can( 'manage_options' ) || ( ! $no_data && ! isset( $_POST['data'] ) ) ) {
			wp_die();
		}

	}

	/**
	 * Get arguments for WP_Query call.
	 *
	 * @since 1.0.1
	 * @since 1.2.0  Moved out to this trait from class-core.php
	 *
	 * @param string $action  Action name. Accepts: upload|remove.
	 * @param bool   $single  Fetch single entry? Default: fetch all.
	 *
	 * @return string[]
	 */
	private function get_wp_query_args( string $action, bool $single = false ): array {

		do_action( 'cf_images_before_wp_query' );

		$args = array(
			'post_type'   => 'attachment',
			'post_status' => 'inherit',
		);

		if ( $single ) {
			$args['posts_per_page'] = 1;
		}

		if ( 'upload' === $action ) {
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => '_cloudflare_image_id',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_cloudflare_image_skip',
					'compare' => 'NOT EXISTS',
				),
			);
		}

		if ( 'remove' === $action ) {
			$args['meta_key'] = '_cloudflare_image_id'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		}

		return $args;

	}

}
