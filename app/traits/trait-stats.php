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
	 * @since 1.2.0 Moved out to this trait from class-core.php
	 * @access private
	 * @var int[]
	 */
	private $default_stats = array(
		'synced'      => 0,
		'api_current' => 0,
		'api_allowed' => 100000,
		'size_before' => 0, // Compress module.
		'size_after'  => 0, // Compress module.
		'alt_tags'    => 0, // Alt tags generated.
		'image_ai'    => 0, // Images generated.
	);

	/**
	 * Try to get the Cloudflare Images account hash and store it for future use.
	 *
	 * @since 1.0.0
	 *
	 * @param array $variants Saved variants.
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
	 * @since 1.2.0 Moved out to this trait from class-core.php
	 *
	 * @param Image $image Image API object.
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
	 * Increment stats.
	 *
	 * @since 1.6.0
	 *
	 * @param string $stat Stat to increment.
	 */
	private function increment_stat( string $stat ) {
		$stats = get_option( 'cf-images-stats', $this->default_stats );

		if ( ! isset( $stats[ $stat ] ) ) {
			$stats[ $stat ] = 0;
		}

		++$stats[ $stat ];

		update_option( 'cf-images-stats', $stats, false );
	}

	/**
	 * Decrement stats.
	 *
	 * @since 1.6.0
	 *
	 * @param string $stat Stat to decrement.
	 */
	private function decrement_stat( string $stat ) {
		$stats = get_option( 'cf-images-stats', $this->default_stats );

		if ( ! isset( $stats[ $stat ] ) ) {
			$stats[ $stat ] = 0;
		}

		--$stats[ $stat ];

		if ( 0 > $stats[ $stat ] ) {
			$stats[ $stat ] = 0;
		}

		update_option( 'cf-images-stats', $stats, false );
	}

	/**
	 * Stats getter.
	 *
	 * @since 1.5.0
	 *
	 * @return array
	 */
	private function get_stats(): array {
		return get_option( 'cf-images-stats', $this->default_stats );
	}
}
