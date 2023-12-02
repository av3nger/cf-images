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
	 * @param array $data Login data.
	 *
	 * @throws Exception Exception if unable to generate an API token.
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

	/**
	 * Caption image.
	 *
	 * @since 1.4.0
	 *
	 * @param string $image Image URL.
	 *
	 * @throws Exception Exception if unable to generate caption.
	 *
	 * @return string
	 */
	public function caption( string $image ): string {
		$this->set_method( 'POST' );
		$this->set_endpoint( 'wp/image/caption' );
		$this->set_request_body( wp_json_encode( array( 'image' => $image ) ) );

		$response = $this->request();

		if ( isset( $response->text ) ) {
			return ucfirst( $response->text );
		}

		throw new Exception( esc_html__( 'Unable to caption image.', 'cf-images' ) );
	}

	/**
	 * Generate image.
	 *
	 * @since 1.6.0
	 *
	 * @param array $params Request arguments.
	 *
	 * @throws Exception Exception if unable to generate image.
	 *
	 * @return string
	 */
	public function generate( array $params ): string {
		$this->set_method( 'POST' );
		$this->set_timeout( 60 );
		$this->set_endpoint( 'images/generate' );
		$this->set_request_body( wp_json_encode( $params ) );

		$response = $this->request();

		if ( isset( $response->data ) ) {
			return $response->data;
		}

		throw new Exception( esc_html__( 'Unable to generate image.', 'cf-images' ) );
	}

	/**
	 * Get Cloudflare worker status.
	 *
	 * @since 1.7.0
	 *
	 * @throws Exception Exception if unable to get status.
	 *
	 * @return array
	 */
	public function get_cf_status(): array {
		$this->set_method( 'GET' );
		$this->set_endpoint( 'cf/status' );

		return (array) $this->request();
	}

	/**
	 * Enable CDN.
	 *
	 * @since 1.7.0
	 *
	 * @param string $site Site URL.
	 *
	 * @return array
	 * @throws Exception Exception on error.
	 */
	public function enable_cdn( string $site ): array {
		$this->set_method( 'POST' );
		$this->set_endpoint( 'cdn/enable' );
		$this->set_request_body( wp_json_encode( array( 'site' => $site ) ) );

		return (array) $this->request();
	}

	/**
	 * Purge CDN cache.
	 *
	 * @since 1.7.0
	 *
	 * @param string $site Site URL.
	 *
	 * @return array
	 * @throws Exception Exception on error.
	 */
	public function purge_cdn_cache( string $site ): array {
		$this->set_method( 'POST' );
		$this->set_endpoint( 'cdn/purge' );
		$this->set_request_body( wp_json_encode( array( 'site' => $site ) ) );

		return (array) $this->request();
	}

	/**
	 * Get CDN status.
	 *
	 * @since 1.7.0
	 *
	 * @param string $site Site URL.
	 *
	 * @return array
	 * @throws Exception Exception on error.
	 */
	public function get_cdn_status( string $site ): array {
		$this->set_method( 'POST' );
		$this->set_endpoint( 'cdn/status' );
		$this->set_request_body( wp_json_encode( array( 'site' => $site ) ) );

		return (array) $this->request();
	}
}
