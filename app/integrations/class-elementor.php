<?php
/**
 * Integration class for the Elementor page builder
 *
 * This class adds compatibility with the Elementor plugin.
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Integrations
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.9.1
 */

namespace CF_Images\App\Integrations;

use CF_Images\App\Traits;
use Elementor\Widget_Base;
use Elementor\Widget_Image_Carousel;
use ElementorPro\Modules\Gallery\Widgets\Gallery;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Elementor class.
 *
 * @since 1.9.1
 */
class Elementor {
	use Traits\Helpers;

	/**
	 * Class constructor.
	 *
	 * @since 1.9.1
	 */
	public function __construct() {
		add_filter( 'elementor/widget/render_content', array( $this, 'add_lightbox_support' ), 10, 2 );
	}

	/**
	 * Fix lightbox widgets in Elementor.
	 *
	 * The frontend.js script in Elementor has the isLightboxLink() function which checks if the lightbox link is valid.
	 * This function checks if a link ends with a supported extension (png|jpe?g|gif|svg|webp). Cloudflare Images links,
	 * on the other hand, do not end with an extension, but rather with a set of parameters. To fix this we can append
	 * a hash #.jpg to the end of all image URLs.
	 *
	 * @since 1.9.1
	 *
	 * @param string      $widget_content The content of the widget.
	 * @param Widget_Base $widget         The widget.
	 */
	public function add_lightbox_support( string $widget_content, Widget_Base $widget ): string {
		if ( ! $widget instanceof Widget_Image_Carousel && ! $widget instanceof Gallery ) {
			return $widget_content;
		}

		// Regular expression to find <a> tags with data-elementor-open-lightbox="yes" and Cloudflare Images links.
		$pattern = '/(<a\s[^>]*href="' . preg_quote( $this->get_cdn_domain(), '/' ) . '[^"#]*)(#[^"]*)?(".*?data-elementor-open-lightbox="yes".*?>)/i';

		// Callback function to append '#.jpg' to the href attribute.
		$callback = function ( $matches ) {
			// Check if '#.jpg' is not already appended, and append if necessary.
			$new_url = $matches[1] . ( isset( $matches[2] ) && strpos( $matches[2], '#.jpg' ) !== false ? $matches[2] : '#.jpg' );
			return $new_url . $matches[3];
		};

		return preg_replace_callback( $pattern, $callback, $widget_content );
	}
}
