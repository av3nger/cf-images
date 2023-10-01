<?php
/**
 * Image compress API class that handles image optimization
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Api
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.5.0
 */

namespace CF_Images\App\Api;

use Exception;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Image compress API class.
 *
 * @since 1.5.0
 */
class Compress extends Api {
	/**
	 * Fuzion API URL.
	 *
	 * @since 1.5.0
	 * @access protected
	 * @var string
	 */
	protected $api_url = 'https://images.getfuzion.io';

	/**
	 * Headers.
	 *
	 * @since 1.5.0
	 * @access private
	 * @var array
	 */
	private $headers = array();

	/**
	 * Get arguments for request.
	 *
	 * @since 1.5.0
	 *
	 * @return array
	 */
	protected function get_args(): array {
		$args = parent::get_args();

		$args['headers'] = array_merge(
			array(
				'apiKey' => get_option( 'cf-image-ai-api-key', '' ),
			),
			$this->headers
		);

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
	private function set_header( string $header, string $value ) {
		$this->headers[ $header ] = $value;
	}

	/**
	 * Compress an image.
	 *
	 * @since 1.5.0
	 *
	 * @param string $image_path Image path.
	 * @param string $mime_type  Image mime type.
	 *
	 * @return string
	 * @throws Exception If error.
	 */
	public function optimize( string $image_path, string $mime_type ): string {
		$this->set_header( 'Content-Type', $mime_type );
		$this->set_request_body( file_get_contents( $image_path ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$this->set_timeout( 30 );

		wp_raise_memory_limit( 'image' );
		return $this->request( false );
	}
}
