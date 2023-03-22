<?php
/**
 * The file that defines image stats traits that are used across all classes
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Traits
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.2.0
 */

namespace CF_Images\App\Traits;

use CF_Images\App\Api\Image;
use Exception;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The Stats trait class.
 *
 * @since 1.2.0
 */
trait Stats {

	/**
	 * Default stats.
	 *
	 * @since 1.1.0
	 * @since 1.2.0  Moved out to this trait from class-core.php
	 * @access private
	 * @var int[]
	 */
	private $default_stats = array(
		'synced'      => 0,
		'api_current' => 0,
		'api_allowed' => 100000,
	);

	/**
	 * Try to get the Cloudflare Images account hash and store it for future use.
	 *
	 * @since 1.0.0
	 *
	 * @param array $variants  Saved variants.
	 *
	 * @return void
	 */
	private function maybe_save_hash( array $variants ) {

		$hash = get_site_option( 'cf-images-hash', '' );

		if ( ! empty( $hash ) || ! isset( $variants[0] ) ) {
			return;
		}

		preg_match_all( '#/(.*?)/#i', $variants[0], $hash );

		if ( isset( $hash[1] ) && ! empty( $hash[1][1] ) ) {
			update_site_option( 'cf-images-hash', $hash[1][1] );
		}

	}

	/**
	 * Fetch API stats.
	 *
	 * @since 1.1.0
	 * @since 1.2.0  Moved out to this trait from class-core.php
	 *
	 * @param Image $image  Image API object.
	 *
	 * @return void
	 */
	private function fetch_stats( Image $image ) {

		try {
			$count = $image->stats();

			$stats = get_option( 'cf-images-stats', $this->default_stats );

			if ( isset( $count->current ) ) {
				$stats['api_current'] = $count->current;
			}

			if ( isset( $count->allowed ) ) {
				$stats['api_allowed'] = $count->allowed;
			}

			update_option( 'cf-images-stats', $stats, false );
		} catch ( Exception $e ) {
			do_action( 'cf_images_error', $e->getCode(), $e->getMessage() );
		}

	}

	/**
	 * Update image stats.
	 *
	 * @since 1.0.1
	 * @since 1.2.0  Moved out to this trait from class-core.php
	 *
	 * @param int  $count  Add or subtract number from `synced` image count.
	 * @param bool $add    By default, we will add the required number of images. If set to false - replace the value.
	 *
	 * @return void
	 */
	private function update_stats( int $count, bool $add = true ) {

		$stats = get_option( 'cf-images-stats', $this->default_stats );

		if ( $add ) {
			$stats['synced'] += $count;
		} else {
			$stats['synced'] = $count;
		}

		if ( $stats['synced'] < 0 ) {
			$stats['synced'] = 0;
		}

		update_option( 'cf-images-stats', $stats, false );

	}

}
