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

}
