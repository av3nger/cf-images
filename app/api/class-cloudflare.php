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
use WP_Http_Curl;

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

		if ( ! defined( 'CF_IMAGES_ACCOUNT_ID' ) ) {
			return new stdClass();
		}

		$url  = $this->api_url . constant( 'CF_IMAGES_ACCOUNT_ID' ) . '/images/v1' . $this->endpoint;
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

		/**
		 * We can skip these statuses and consider them success.
		 * 404 - Image not found (when removing an image).
		 */
		if ( 404 === (int) $code ) {
			return new stdClass();
		}

		// Authentication error.
		if ( 401 === (int) $code ) {
			update_option( 'cf-images-auth-error', true, false );
		}

		// Resource already exists.
		if ( 409 === (int) $code ) {
			$body             = new StdClass();
			$body->id         = $args['body']['id'];
			$body->variants[] = '';
			return $body;
		}

		if ( 200 !== (int) $code ) {
			throw new Exception( $body, $code );
		}

		if ( $decode ) {
			return json_decode( $body );
		}

		return $body;

	}

}
