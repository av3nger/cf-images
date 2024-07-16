<?php
/**
 * WPBakery page builder integration class
 *
 * This class adds compatibility with the WPBakery page builder plugin.
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Integrations
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.9.2
 */

namespace CF_Images\App\Integrations;

use CF_Images\App\Modules\Cloudflare_Images;
use CF_Images\App\Traits\Helpers;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * JS_Composer class.
 *
 * @since 1.9.2
 */
class JS_Composer {
	use Helpers;

	/**
	 * Class constructor.
	 *
	 * @since 1.9.2
	 */
	public function __construct() {
		add_filter( 'vc_wpb_getimagesize', array( $this, 'fix_getimagesize_paths' ), 10, 3 );
	}

	/**
	 * When using custom image sizes on gallery images, WPBakery strips out Cloudflare Images parameters,
	 * breaking the images. This fixes the images, by appending the required image parameters.
	 *
	 * @since 1.9.2
	 *
	 * @param array|bool $image_data    Array with image data.
	 * @param string|int $attachment_id Attachment ID.
	 * @param array      $params        Image parameters.
	 *
	 * @return array
	 */
	public function fix_getimagesize_paths( $image_data, $attachment_id, array $params ): array {
		if ( ! isset( $image_data['thumbnail'] ) || ! isset( $image_data['p_img_large'] ) || ! is_array( $image_data['p_img_large'] ) ) {
			return $image_data;
		}

		$pattern = '/<(?:img|source)\b(?>\s+(?:src=[\'"]([^\'"]*)[\'"]|srcset=[\'"]([^\'"]*)[\'"])|[^\s>]+|\s+)*>/i';
		if ( ! preg_match_all( $pattern, $image_data['thumbnail'], $images ) ) {
			do_action( 'cf_images_log', 'Running fix_getimagesize_paths(), `src` not found, returning image. Attachment ID: %s. Image: %s', $attachment_id, $image_data['thumbnail'] );
			return $image_data;
		}

		// Check if the image has the 'w' or 'h' attribute set.
		if ( preg_match( '/[?&]([wh])=\d+/', $images[1][0] ) ) {
			return $image_data;
		}

		// The image is not on Cloudflare, exit.
		if ( false === strpos( $image_data['p_img_large'][0], $this->get_cdn_domain() ) ) {
			return $image_data;
		}

		// Now let's add the correct parameters to the original image.
		if ( ! isset( $params['thumb_size'] ) || ! is_string( $params['thumb_size'] ) || ! preg_match( '/(\d+)x(\d+)/', $params['thumb_size'], $size ) ) {
			return $image_data;
		}

		list( $hash, $cloudflare_image_id ) = Cloudflare_Images::get_hash_id_url_string( (int) $attachment_id );

		if ( empty( $cloudflare_image_id ) || ( empty( $hash ) && ! apply_filters( 'cf_images_module_enabled', false, 'custom-path' ) ) ) {
			return $image_data;
		}

		$image_url = trailingslashit( $this->get_cdn_domain() . "/$hash" ) . "$cloudflare_image_id/w=$size[1],h=$size[2]";
		if ( $size[1] === $size[2] ) {
			$image_url .= ',fit=crop';
		}

		$image_data['thumbnail'] = str_replace( $images[1][0], $image_url, $image_data['thumbnail'] );

		return $image_data;
	}
}
