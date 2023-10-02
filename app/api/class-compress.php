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

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Image compress API class.
 *
 * @since 1.5.0
 */
class Compress extends API_Multi {
	/**
	 * Fuzion API URL.
	 *
	 * @since 1.5.0
	 * @access protected
	 * @var string
	 */
	protected $api_url = 'https://images.getfuzion.io';

	/**
	 * Get arguments for request.
	 *
	 * @since 1.5.0
	 *
	 * @return array
	 */
	protected function get_args(): array {
		$args = parent::get_args();

		$args['headers']['apiKey'] = get_option( 'cf-image-ai-api-key', '' );

		return $args;
	}

	/**
	 * Compress images.
	 *
	 * @since 1.5.0
	 *
	 * @param array  $images    All image sizes for a selected attachment ID.
	 * @param string $mime_type Mime type.
	 *
	 * @return array
	 */
	public function optimize( array $images, string $mime_type ): array {
		$this->set_header( 'Content-Type', $mime_type );
		$this->set_timeout( 30 );
		$this->set_data( $images );

		$results = array();
		foreach ( $this->requests() as $id => $response ) {
			if ( ! isset( $response->success ) || ! $response->success ) {
				continue;
			}

			$results[ $id ] = array(
				'stats' => $response->headers->getValues( 'fuzion-stats' )[0] ?? '',
				'image' => $response->body,
			);
		}

		return $results;
	}
}
