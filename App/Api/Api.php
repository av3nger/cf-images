<?php
/**
 * Cloudflare API class.
 *
 * @link       https://vcore.ru
 * @since      1.0.0
 *
 * @package    CF_Images
 * @subpackage CF_Images/App/Api
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
 * This class defines all code necessary to communicate with the Cloudflare API.
 *
 * @since      1.0.0
 * @package    CF_Images
 * @subpackage CF_Images/App/Api
 * @author     Anton Vanyukov <a.vanyukov@vcore.ru>
 */
class Api {

	/**
	 * Cloudflare API URL.
	 *
	 * @var string
	 */
	protected $url = 'https://api.cloudflare.com/client/v4/accounts/';

	/**
	 * Endpoint for API call.
	 *
	 * @var string
	 */
	private $endpoint = '';

	/**
	 * Body for API call.
	 *
	 * @var null|string|array
	 */
	private $body = null;

	/**
	 * Method used to do API call.
	 *
	 * @var string
	 */
	private $method = 'POST';

	/**
	 * Setter for $endpoint.
	 *
	 * @since 1.0.0
	 *
	 * @param string $endpoint  Endpoint.
	 */
	protected function set_endpoint( string $endpoint ) {
		$this->endpoint = $endpoint;
	}

	/**
	 * Setter for $body.
	 *
	 * @since 1.0.0
	 *
	 * @param null|string|array $data  JSON-encoded data or array for image uploads.
	 */
	protected function set_body( $data ) {
		$this->body = $data;
	}

	/**
	 * Setter for $method.
	 *
	 * @since 1.0.0
	 *
	 * @param string $method  Method.
	 */
	protected function set_method( string $method ) {
		$this->method = $method;
	}

	/**
	 * Get arguments for request.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private function get_args(): array {

		$args = array(
			'method'  => $this->method,
			'headers' => array(
				'Authorization' => 'Bearer ' . CF_IMAGES_KEY_TOKEN,
			),
		);

		if ( isset( $this->body ) && in_array( $args['method'], array( 'POST', 'UPLOAD' ), true ) ) {
			$args['body'] = $this->body;
		}

		return $args;

	}

	/**
	 * Do API request.
	 *
	 * @since 1.0.0
	 *
	 * @throws Exception  Exception during API call.
	 *
	 * @return stdClass
	 */
	protected function request(): stdClass {

		$url  = $this->url . CF_IMAGES_ACCOUNT_ID . '/images/v1' . $this->endpoint;
		$args = $this->get_args();

		if ( 'POST' === $args['method'] || 'DELETE' === $args['method'] ) {
			$response = wp_remote_post( $url, $args );
		} elseif ( 'UPLOAD' === $args['method'] ) {
			/**
			 * Not using post_request(), because it uses wp_remote_post(), which does not allow file uploads.
			 *
			 * @see https://core.trac.wordpress.org/ticket/41608
			 */
			$args['method'] = 'POST';

			$curl = new \WP_Http_Curl();

			$response = $curl->request( $url, $args );
		} else {
			throw new Exception( __( 'Unsupported API call method', 'cf-images' ), '404' );
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( is_wp_error( $response ) ) {
			throw new Exception( $response, $code );
		}

		$body = wp_remote_retrieve_body( $response );

		// Duplicate entry.
		if ( 409 === (int) $code ) {
			return new stdClass();
		}

		if ( 200 !== (int) $code ) {
			throw new Exception( $body, $code );
		}

		return json_decode( $body );

	}

}
