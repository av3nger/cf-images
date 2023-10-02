<?php
/**
 * API multi-request class that is used to send bulk requests to the API
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Api
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.5.0
 */

namespace CF_Images\App\Api;

use WpOrg\Requests\Requests;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * API_Multi class.
 *
 * @since 1.5.0
 */
abstract class API_Multi extends API {
	/**
	 * Headers.
	 *
	 * @since 1.5.0
	 * @access private
	 * @var array
	 */
	private $headers = array();

	/**
	 * Data.
	 *
	 * @since 1.5.0
	 * @access private
	 * @var array
	 */
	private $data = array();

	/**
	 * Get arguments for request.
	 *
	 * @since 1.5.0
	 *
	 * @return array
	 */
	protected function get_args(): array {
		global $wp_version;

		$args = parent::get_args();

		$args['headers'] = array_merge(
			array(
				'User-Agent' => 'WordPress/' . $wp_version . '; ' . home_url( '/' ),
			),
			$this->headers
		);

		unset( $args['timeout'] );

		// The multi request requires a `type` value, instead of a `method`.
		unset( $args['method'] );
		$args['type'] = $this->method;

		return $args;
	}

	/**
	 * Set additional headers.
	 *
	 * @since 1.5.0
	 *
	 * @param string $header Header.
	 * @param string $value  Header value.
	 */
	protected function set_header( string $header, string $value ) {
		$this->headers[ $header ] = $value;
	}

	/**
	 * Data setter.
	 *
	 * @since 1.5.0
	 *
	 * @param array $data Array of data.
	 */
	protected function set_data( array $data ) {
		$this->data = $data;
	}

	/**
	 * Perform multiple requests.
	 *
	 * @since 1.5.0
	 *
	 * @return array
	 */
	protected function requests(): array {
		global $wp_version;

		$options = array(
			'timeout' => $this->timeout,
		);

		$requests = array();
		foreach ( $this->data as $id => $data ) {
			$requests[ $id ] = array_merge(
				$this->get_args(),
				array(
					'url'  => $this->get_url(),
					'data' => is_string( $data ) ? file_get_contents( $data ) : $data, // phpcs:ignore WordPress.WP.AlternativeFunctions
				)
			);
		}

		wp_raise_memory_limit( 'image' );

		if ( version_compare( $wp_version, '6.2.0', '>=' ) ) {
			return Requests::request_multiple( $requests, $options );
		}

		return \Requests::request_multiple( $requests, $options );
	}
}
