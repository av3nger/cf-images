<?php
/**
 * Cloudflare API class
 *
 * This class defines all code necessary to communicate with the Cloudflare API.
 *
 * @link https://vcore.ru
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
class Api {

	/**
	 * Cloudflare API URL.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var string
	 */
	protected $api_url = 'https://api.cloudflare.com/client/v4/accounts/';

	/**
	 * Endpoint for API call.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var string
	 */
	private $endpoint = '';

	/**
	 * Body for API call.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var null|string|array
	 */
	private $request_body = null;

	/**
	 * Method used to do API call.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var string
	 */
	private $method = 'POST';

	/**
	 * Request timeout (in seconds).
	 *
	 * @since 1.0.0
	 * @access private
	 * @var int
	 */
	private $timeout = 5;

	/**
	 * Setter for $endpoint.
	 *
	 * @since 1.0.0
	 *
	 * @param string $endpoint  Endpoint.
	 *
	 * @return void
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
	 *
	 * @return void
	 */
	protected function set_request_body( $data ) {
		$this->request_body = $data;
	}

	/**
	 * Setter for $method.
	 *
	 * @since 1.0.0
	 *
	 * @param string $method  Method.
	 *
	 * @return void
	 */
	protected function set_method( string $method ) {
		$this->method = $method;
	}

	/**
	 * Setter for $timeout.
	 *
	 * @since 1.0.0
	 *
	 * @param int $timeout  Timeout.
	 *
	 * @return void
	 */
	protected function set_timeout( int $timeout ) {
		$this->timeout = $timeout;
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
			'timeout' => $this->timeout,
			'headers' => array(
				'Authorization' => 'Bearer ' . CF_IMAGES_KEY_TOKEN,
			),
		);

		if ( isset( $this->request_body ) && in_array( $args['method'], array( 'POST', 'UPLOAD', 'PATCH' ), true ) ) {
			$args['body'] = $this->request_body;
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

		$url  = $this->api_url . CF_IMAGES_ACCOUNT_ID . '/images/v1' . $this->endpoint;
		$args = $this->get_args();

		if ( 'POST' === $args['method'] || 'DELETE' === $args['method'] || 'PATCH' === $args['method'] ) {
			$response = wp_remote_post( $url, $args );
		} elseif ( 'UPLOAD' === $args['method'] ) {
			/**
			 * Not using post_request(), because it uses wp_remote_post(), which does not allow file uploads.
			 *
			 * We also need to set a few defaults to avoid PHP warnings and errors.
			 *
			 * @see https://core.trac.wordpress.org/ticket/41608
			 */
			$args['method']     = 'POST';
			$args['user-agent'] = apply_filters( 'http_headers_useragent', 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ), $url );
			$args['decompress'] = true;
			$args['stream']     = false;
			$args['filename']   = null;

			$curl = new \WP_Http_Curl();

			$response = $curl->request( $url, $args );
		} else {
			throw new Exception( __( 'Unsupported API call method', 'cf-images' ), '404' );
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( is_wp_error( $response ) ) {
			throw new Exception( $response->get_error_message(), (int) $code );
		}

		$body = wp_remote_retrieve_body( $response );

		/**
		 * We can skip these statuses and consider them success.
		 * 404 - Image not found (when removing an image).
		 * 409 - Duplicate entry (when creating a variation).
		 */
		if ( 409 === (int) $code || 404 === (int) $code ) {
			return new stdClass();
		}

		// Authentication error.
		if ( 401 === (int) $code ) {
			update_option( 'cf-images-auth-error', true, false );
		}

		if ( 200 !== (int) $code ) {
			throw new Exception( $body, $code );
		}

		return json_decode( $body );

	}

}
