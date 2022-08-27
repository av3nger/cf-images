<?php
/**
 * Cloudflare API class that handles variants manipulations
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
 * Cloudflare API class that handles variants manipulations.
 *
 * @since 1.0.0
 */
class Variant extends Api {

	/**
	 * Create a new image variant.
	 *
	 * Variants are used to specify how to resize images for different use cases.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name      Variant ID.
	 * @param int    $width     Maximum width in image pixels.
	 * @param int    $height    Maximum height in image pixels.
	 * @param string $fit       The fit property describes how the width and height dimensions should be interpreted.
	 *                          Valid values: scale-down, contain, cover, crop, pad.
	 * @param string $metadata  What EXIF data should be preserved in the output image.
	 *                          Valid values: keep, copyright, none.
	 *
	 * @throws Exception  Exception during API call.
	 *
	 * @return stdClass
	 */
	public function create( string $name, int $width, int $height, string $fit = 'scale-down', string $metadata = 'keep' ): stdClass {

		$data = array(
			'id'      => $name,
			'options' => array(
				'fit'      => $fit,
				'metadata' => $metadata,
				'width'    => $width,
				'height'   => $height,
			),
		);

		$this->set_method( 'POST' );
		$this->set_timeout( 2 );
		$this->set_endpoint( '/variants' );
		$this->set_body( wp_json_encode( $data ) );

		return $this->request();

	}

	/**
	 * Toggle flexible variants.
	 *
	 * Flexible variants allow you to create variants with dynamic resizing. This option is not enabled by default.
	 * Once activated, it is possible to use resizing parameters on any Cloudflare Image. For example:
	 * https://imagedelivery.net/<ACCOUNT_HASH>/<IMAGE_ID/w=400,sharpen=3
	 *
	 * @since 1.0.0
	 *
	 * @param bool $value  Accepts: true|false values.
	 *
	 * @throws Exception  Exception during API call.
	 *
	 * @return stdClass
	 */
	public function toggle_flexible( bool $value ): stdClass {

		$data = array(
			'flexible_variants' => $value,
		);

		$this->set_method( 'PATCH' );
		$this->set_timeout( 2 );
		$this->set_endpoint( '/config' );
		$this->set_body( wp_json_encode( $data ) );

		return $this->request();

	}

}
