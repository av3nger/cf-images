<?php
/**
 * Cloudflare API class
 *
 * This class defines all code necessary to communicate with the Cloudflare API.
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Api
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.0.0
 */

namespace CF_Images\App\Api;

use Exception;
use stdClass;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Cloudflare API class.
 *
 * @since 1.0.0
 */
class Cloudflare extends Api {

	/**
	 * Cloudflare API URL.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var string
	 */
	protected $api_url = 'https://api.cloudflare.com/client/v4/accounts/';

	/**
	 * Get arguments for request.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	protected function get_args(): array {

		$args = parent::get_args();

		$args['headers'] = array(
			'Authorization' => 'Bearer ' . constant( 'CF_IMAGES_KEY_TOKEN' ),
		);

		return $args;

	}

	/**
	 * Get API URL.
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	protected function get_url(): string {
		return $this->api_url . constant( 'CF_IMAGES_ACCOUNT_ID' ) . '/images/v1' . $this->endpoint;
	}

	/**
	 * Process API response.
	 *
	 * @since 1.0.0
	 * @since 1.2.1 Added $decode parameter.
	 * @since 1.4.0 Abstracted from request().
	 *
	 * @param string $body    Response body.
	 * @param int    $code    Response code.
	 * @param bool   $decode  JSON decode the response.
	 * @param array  $args    Arguments array.
	 *
	 * @throws Exception  Exception during API call.
	 *
	 * @return stdClass|string
	 */
	protected function process_response( string $body, int $code, bool $decode, array $args ) {

		/**
		 * We can skip these statuses and consider them success.
		 * 404 - Image not found (when removing an image).
		 */
		if ( 404 === $code ) {
			return new stdClass();
		}

		// Authentication error.
		if ( 401 === $code ) {
			update_option( 'cf-images-auth-error', true, false );
		}

		// Resource already exists.
		if ( 409 === $code ) {
			$body             = new StdClass();
			$body->id         = $args['body']['id'];
			$body->variants[] = '';
			return $body;
		}

		if ( 200 !== $code ) {
			throw new Exception( $body, $code );
		}

		return $decode ? json_decode( $body ) : $body;

	}

}
