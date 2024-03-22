<?php
/**
 * Browser cache TTL module
 *
 * This class controls how long an image stays in a browser’s cache and specifically configures
 * the cache-control response header.
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Modules
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.8.1
 */

namespace CF_Images\App\Modules;

use CF_Images\App\Api;
use CF_Images\App\Traits;
use Exception;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Cache_TTL class.
 *
 * @since 1.8.1
 */
class Cache_TTL extends Module {
	use Traits\Ajax;
	use Traits\Empty_Init;
	use Traits\Helpers;

	/**
	 * Run everything regardless of module status.
	 *
	 * @since 1.8.1
	 */
	public function pre_init() {
		if ( wp_doing_ajax() ) {
			add_action( 'wp_ajax_cf_images_set_ttl', array( $this, 'ajax_set_ttl' ) );
		}
	}

	/**
	 * Set browser cache TTL.
	 *
	 * @since 1.8.1
	 */
	public function ajax_set_ttl() {
		$this->check_ajax_request();

		$data = filter_input( INPUT_POST, 'data', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		$ttl  = $data['ttl'] ? (int) $data['ttl'] : 172800;

		try {
			( new Api\Variant() )->set_cache_ttl( $ttl );
			update_site_option( 'cf-images-browser-ttl', $ttl );
		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}
	}
}
