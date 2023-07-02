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

}
