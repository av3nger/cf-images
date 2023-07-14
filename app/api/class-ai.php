<?php
/**
 * Image AI API class that handles image captioning and tagging
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Api
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.4.0
 */

namespace CF_Images\App\Api;

use Exception;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Image AI API class.
 *
 * @since 1.4.0
 */
class Ai extends Fuzion {

	/**
	 * Login to Fuzion AI and generate an API token.
	 *
	 * @since 1.4.0
	 *
	 * @param array $data  Login data.
	 *
	 * @throws Exception  Exception if unable to generate an API token.
	 *
	 * @return void
	 */
	public function login( array $data ) {

		$this->set_method( 'POST' );
		$this->set_endpoint( 'user/api-tokens' );
		$this->set_request_body( wp_json_encode( $data ) );

		$response = $this->request();

		if ( isset( $response->token ) ) {
			update_option( 'cf-image-ai-api-key', $response->token, false );
		}

	}

	/**
	 * Caption image.
	 *
	 * @since 1.4.0
	 *
	 * @param string $image  Image URL.
	 *
	 * @throws Exception  Exception if unable to generate caption.
	 *
	 * @return string
	 */
	public function caption( string $image ): string {

		$this->set_method( 'POST' );
		$this->set_endpoint( 'wp/image/caption' );
		$this->set_request_body( wp_json_encode( array( 'image' => $image ) ) );

		$response = $this->request();

		if ( isset( $response->text ) ) {
			return ucfirst( $response->text );
		}

		throw new Exception( esc_html__( 'Unable to caption image.', 'cf-images' ) );

	}

}
