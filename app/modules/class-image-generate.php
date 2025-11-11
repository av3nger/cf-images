<?php
/**
 * Image generation module.
 *
 * Allow generating images via the Fuzion API.
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Modules
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 *
 * @since 1.6.0
 */

namespace CF_Images\App\Modules;

use CF_Images\App\Api\Ai;
use CF_Images\App\Traits;
use Exception;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Image_Generate class.
 *
 * @since 1.6.0
 */
class Image_Generate extends Module {
	use Traits\Ajax;
	use Traits\Stats;

	/**
	 * Init the module.
	 *
	 * @since 1.6.0
	 */
	public function init() {
		if ( wp_doing_ajax() ) {
			add_action( 'wp_ajax_cf_images_ai_generate', array( $this, 'ajax_generate_image' ) );
		}
	}

	/**
	 * Generate image.
	 *
	 * @since 1.6.0
	 */
	public function ajax_generate_image() {
		$this->check_ajax_request();

		$data = filter_input( INPUT_POST, 'data', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		if ( empty( $data['prompt'] ) ) {
			wp_send_json_error( __( 'Prompt cannot be empty.', 'cf-images' ) );
		}

		$args = array(
			'prompt' => sanitize_text_field( $data['prompt'] ),
			'site'   => wp_parse_url( site_url(), PHP_URL_HOST ),
		);

		try {
			$ai_api = new Ai();
			$image  = $ai_api->generate( $args );

			$this->increment_stat( 'image_ai' );

			$attachment_id = $this->save_image( $image );
			$image_data    = wp_get_attachment_image_src( $attachment_id, 'large' );
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', $args['prompt'] );

			$response = array(
				'id'    => $attachment_id,
				'url'   => $image_data[0],
				'media' => admin_url( "post.php?post=$attachment_id&action=edit" ),
			);

			wp_send_json_success( $response );
		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}
	}

	/**
	 * Save image in media library.
	 *
	 * @since 1.6.0
	 *
	 * @param string $file    The URL of the image to download.
	 * @param int    $post_id Optional. The post ID the media is to be associated with.
	 *
	 * @return int
	 * @throws Exception Error from saving image in media library.
	 */
	private function save_image( string $file, int $post_id = 0 ): int {
		$result = media_sideload_image( $file, $post_id, null, 'id' );

		if ( is_wp_error( $result ) ) {
			throw new Exception( esc_html( $result->get_error_message() ), (int) $result->get_error_code() );
		}

		return $result;
	}
}
