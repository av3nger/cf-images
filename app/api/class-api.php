<?php
/**
 * API class
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
use WP_Http_Curl;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Cloudflare API class.
 *
 * @since 1.0.0
 */
abstract class Api {

	/**
	 * API URL.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var string
	 */
	protected $api_url = '';

	/**
	 * Endpoint for API call.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var string
	 */
	protected $endpoint = '';

	/**
	 * Body for API call.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var null|string|array
	 */
	protected $request_body = null;

	/**
	 * Method used to do API call.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var string
	 */
	protected $method = 'POST';

	/**
	 * Request timeout (in seconds).
	 *
	 * @since 1.0.0
	 * @access private
	 * @var int
	 */
	protected $timeout = 5;

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
	protected function get_args(): array {

		$args = array(
			'method'  => $this->method,
			'timeout' => $this->timeout,
		);

		if ( isset( $this->request_body ) && in_array( $args['method'], array( 'POST', 'UPLOAD', 'PATCH' ), true ) ) {
			$args['body'] = $this->request_body;
		}

		return $args;

	}

	/**
	 * Get URL for API call.
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	protected function get_url(): string {
		return $this->api_url . $this->endpoint;
	}

	/**
	 * Do API request.
	 *
	 * @since 1.0.0
	 * @since 1.2.1 Added $decode parameter.
	 *
	 * @param bool $decode  Return object. If false, will return string. Used for image blobs.
	 *
	 * @throws Exception  Exception during API call.
	 *
	 * @return stdClass|string
	 */
	protected function request( bool $decode = true ) {

		$url  = $this->get_url();
		$args = $this->get_args();

		if ( 'GET' === $args['method'] ) {
			$response = wp_remote_get( $url, $args );
		} elseif ( 'POST' === $args['method'] || 'DELETE' === $args['method'] || 'PATCH' === $args['method'] ) {
			$response = wp_remote_post( $url, $args );
		} elseif ( 'UPLOAD' === $args['method'] ) {
			/**
			 * Not using post_request(), because it uses wp_remote_post(), which does not allow file uploads.
			 *
			 * We also need to set a few defaults to avoid PHP warnings and errors.
			 *
			 * @see https://core.trac.wordpress.org/ticket/41608
			 */
			$args['method']      = 'POST';
			$args['user-agent']  = apply_filters( 'http_headers_useragent', 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ), $url );
			$args['decompress']  = true;
			$args['stream']      = false;
			$args['filename']    = null;
			$args['httpversion'] = '1.1';

			$curl = new WP_Http_Curl();

			$response = $curl->request( $url, $args );
		} else {
			throw new Exception( __( 'Unsupported API call method', 'cf-images' ), '404' );
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( is_wp_error( $response ) ) {
			throw new Exception( $response->get_error_message(), (int) $code );
		}

		$body = wp_remote_retrieve_body( $response );

		return $this->process_response( $body, (int) $code, $decode, $args );

	}

	/**
	 * Process response.
	 *
	 * @since 1.4.0
	 *
	 * @param string $body    Response body.
	 * @param int    $code    Response code.
	 * @param bool   $decode  JSON decode the response.
	 * @param array  $args    Arguments array.
	 *
	 * @return stdClass|string
	 */
	protected function process_response( string $body, int $code, bool $decode, array $args ) {
		return $decode ? json_decode( $body ) : $body;
	}

}
