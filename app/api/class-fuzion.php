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
	protected function get_url(): string {

		$url = $this->api_url;
		if ( defined( 'FUZION_API_URL' ) && constant( 'FUZION_API_URL' ) ) {
			$url = trailingslashit( constant( 'FUZION_API_URL' ) );
		}

		return $url . $this->endpoint;

	}

	/**
	 * Process API response.
	 *
	 * @since 1.4.0
	 *
	 * @param string $body    Response body.
	 * @param int    $code    Response code.
	 * @param bool   $decode  JSON decode the response.
	 * @param array  $args    Arguments array.
	 *
	 * @throws Exception  Exception during API call.
	 *
	 * @return stdClass
	 */
	protected function process_response( string $body, int $code, bool $decode, array $args ): stdClass {

		$body = json_decode( $body );

		if ( 200 === $code || 201 === $code ) {
			return $body;
		}

		if ( 422 === $code && isset( $body->message ) ) {
			throw new Exception( $body->message );
		}

		if ( isset( $body->message ) ) {
			// Invalid API key.
			if ( str_contains( $body->message, 'Unauthenticated' ) ) {
				$message = __( 'Expired or invalid API key. Please update your API key on the setting page.', 'cf-images' );
			} else {
				$message = $this->maybe_extract_message( $body->message );
			}

			throw new Exception( $message );
		}

		throw new Exception( __( 'Error doing API call. Please try again.', 'cf-images' ) );

	}

	/**
	 * Extract message from error.
	 *
	 * Some errors can contain a JSON within a JSON object. For example:
	 * {"message":"HTTP request returned status code 400:\n{\"error\":{\"code\":\"InvalidRequest\",\"message\":\"The provided image url is not accessible.\"}}\n"}
	 * For such messages we need to extract the inner JSON and decode it.
	 *
	 * @since 1.4.0
	 *
	 * @param string $message  Error message.
	 *
	 * @return string
	 */
	private function maybe_extract_message( string $message ): string {

		// Extract the JSON part from the message.
		$json_start = strpos( $message, '{' );
		$inner_json = substr( $message, $json_start );

		// Decode the inner JSON.
		$data = json_decode( $inner_json, true );

		if ( $data && isset( $data['error'] ) && ! empty( $data['error']['message'] ) ) {
			return $data['error']['message'];
		}

		return $message;

	}

}
