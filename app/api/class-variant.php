<?php
/**
 * Cloudflare API class that handles variants manipulations
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

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Cloudflare API class that handles variants manipulations.
 *
 * @since 1.0.0
 */
class Variant extends Cloudflare {

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
		$this->set_request_body( wp_json_encode( $data ) );

		return $this->request();

	}

}
