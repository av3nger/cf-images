<?php
/**
 * Fuzion AI API class
 *
 * This class defines all code necessary to communicate with the Fuzion API.
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
use stdClass;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Fuzion API class.
 *
 * @since 1.4.0
 */
class Fuzion extends Api {

	/**
	 * Fuzion API URL.
	 *
	 * @since 1.4.0
	 * @access protected
	 * @var string
	 */
	protected $api_url = 'https://getfuzion.io/api/';

	/**
	 * Get arguments for request.
	 *
	 * @since 1.4.0
	 *
	 * @return array
	 */
	protected function get_args(): array {

		$args = parent::get_args();

		$args['headers'] = array(
			'Accept'        => 'application/json',
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . get_option( 'cf-image-ai-api-key', '' ),
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
	private function get_url(): string {

		$url = $this->api_url;
		if ( defined( 'FUZION_API_URL' ) && constant( 'FUZION_API_URL' ) ) {
			$url = trailingslashit( constant( 'FUZION_API_URL' ) );
		}

		return $url . $this->endpoint;

	}

	/**
	 * Do API request.
	 *
	 * @since 1.4.0
	 *
	 * @throws Exception  Exception during API call.
	 *
	 * @return stdClass
	 */
	protected function request(): stdClass {

		$url  = $this->get_url();
		$args = $this->get_args();

		if ( 'GET' === $args['method'] ) {
			$response = wp_remote_get( $url, $args );
		} elseif ( 'POST' === $args['method'] ) {
			$response = wp_remote_post( $url, $args );
		} else {
			throw new Exception( __( 'Unsupported API call method', 'cf-images' ) );
		}

		if ( is_wp_error( $response ) ) {
			throw new Exception( $response->get_error_message() );
		}

		$code = wp_remote_retrieve_response_code( $response );

		$response = wp_remote_retrieve_body( $response );
		$response = json_decode( $response );

		if ( 200 === $code || 201 === $code ) {
			return $response;
		}

		if ( 422 === $code && isset( $response->message ) ) {
			throw new Exception( $response->message );
		}

		if ( isset( $response->message ) ) {
			// Invalid API key.
			if ( str_contains( $response->message, 'Unauthenticated' ) ) {
				$message = __( 'Expired or invalid API key. Please update your API key on the setting page.', 'cf-images' );
			} else {
				$message = $response->message;
			}

			throw new Exception( $message );
		}

		throw new Exception( __( 'Error doing API call. Please try again.', 'cf-images' ) );

	}

}
