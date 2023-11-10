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
use CF_Images\App\Traits;
use Exception;
use WP_Error;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Image_Ai class.
 *
 * @since 1.4.0
 */
class Image_Ai extends Module {
	use Traits\Ajax;
	use Traits\Stats;

	/**
	 * Init the module.
	 *
	 * @since 1.4.0
	 */
	public function init() {
		// Bulk compress actions.
		add_filter( 'cf_images_bulk_actions', array( $this, 'add_bulk_action' ) );
		add_filter( 'cf_images_wp_query_args', array( $this, 'add_wp_query_args' ), 10, 2 );
		add_action( 'cf_images_bulk_step', array( $this, 'bulk_step' ), 10, 2 );

		if ( wp_doing_ajax() ) {
			add_action( 'wp_ajax_cf_images_ai_caption', array( $this, 'ajax_caption_image' ) );
		}
	}

	/**
	 * Init the module.
	 *
	 * @since 1.4.1
	 */
	public function pre_init() {
		if ( wp_doing_ajax() ) {
			add_action( 'wp_ajax_cf_images_ai_login', array( $this, 'ajax_login' ) );
			add_action( 'wp_ajax_cf_images_ai_disconnect', array( $this, 'ajax_disconnect' ) );
			add_action( 'wp_ajax_cf_images_ai_save', array( $this, 'ajax_save_key' ) );
		}
	}

	/**
	 * Login to Image AI service.
	 *
	 * @since 1.4.0
	 */
	public function ajax_login() {
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
	 */
	public function ajax_disconnect() {
		$this->check_ajax_request( true );
		delete_option( 'cf-image-ai-api-key' );
		wp_send_json_success();
	}

	/**
	 * Caption image.
	 *
	 * @since 1.4.0
	 */
	public function ajax_caption_image() {
		$this->check_ajax_request();

		$attachment_id = (int) filter_input( INPUT_POST, 'data', FILTER_SANITIZE_NUMBER_INT );

		if ( ! $attachment_id ) {
			wp_send_json_error( __( 'Attachment ID not defined.', 'cf-images' ) );
			return;
		}

		$results = $this->caption_image( $attachment_id );
		if ( is_wp_error( $results ) ) {
			wp_send_json_error( $results->get_error_message() );
			return;
		}

		wp_send_json_success( $results );
	}

	/**
	 * Cation image.
	 *
	 * @param int $attachment_id Attachment ID.
	 *
	 * @return string|void|WP_Error
	 */
	private function caption_image( int $attachment_id ) {
		list( $hash, $cloudflare_image_id ) = Cloudflare_Images::get_hash_id_url_string( $attachment_id );

		if ( empty( $cloudflare_image_id ) || empty( $hash ) ) {
			$image = wp_get_original_image_url( $attachment_id );
		} else {
			$image = $this->get_cdn_domain() . "/$hash/$cloudflare_image_id/w=9999";
		}

		try {
			$image_ai = new Ai();
			$caption  = $image_ai->caption( $image );

			if ( ! empty( $caption ) ) {
				update_post_meta( $attachment_id, '_wp_attachment_image_alt', $caption );

				$this->increment_stat( 'alt_tags' );

				return sprintf( /* translators: %s - alt text */
					esc_html__( 'Alt text: %s', 'cf-images' ),
					esc_html( $caption )
				);
			}
		} catch ( Exception $e ) {
			do_action( 'cf_images_error', $e->getCode(), $e->getMessage() );
			return new WP_Error( 'caption_error', $e->getMessage() );
		}
	}

	/**
	 * Save API key.
	 *
	 * @since 1.5.0
	 */
	public function ajax_save_key() {
		$this->check_ajax_request();

		$data = filter_input( INPUT_POST, 'data' );

		if ( empty( $data ) ) {
			wp_send_json_error( __( 'API key cannot be empty.', 'cf-images' ) );
		}

		update_option( 'cf-image-ai-api-key', sanitize_text_field( $data ), false );

		wp_send_json_success();
	}

	/**
	 * Extend bulk action so that the AJAX callback accepts the bulk request.
	 *
	 * @since 1.5.0
	 * @see Media::ajax_bulk_process()
	 *
	 * @param array $actions Supported actions.
	 *
	 * @return array
	 */
	public function add_bulk_action( array $actions ): array {
		if ( ! in_array( 'alt-tags', $actions, true ) ) {
			$actions[] = 'alt-tags';
		}

		return $actions;
	}

	/**
	 * Adjust the WP_Query args for bulk compress action.
	 *
	 * @since 1.5.0
	 * @see Ajax::get_wp_query_args()
	 *
	 * @param array  $args   WP_Query args.
	 * @param string $action Executing action.
	 *
	 * @return array
	 */
	public function add_wp_query_args( array $args, string $action ): array {
		if ( 'alt-tags' !== $action ) {
			return $args;
		}

		$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			array(
				'key'     => '_wp_attachment_image_alt',
				'compare' => 'NOT EXISTS',
			),
		);

		return $args;
	}

	/**
	 * Perform bulk step.
	 *
	 * @since 1.5.0
	 * @see Media::ajax_bulk_process()
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $action        Executing action.
	 */
	public function bulk_step( int $attachment_id, string $action ) {
		if ( 'alt-tags' !== $action ) {
			return;
		}

		$this->caption_image( $attachment_id );
	}
}
