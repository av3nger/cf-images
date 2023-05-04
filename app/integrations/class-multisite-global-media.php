<?php
/**
 * Multisite Global Media integration class
 *
 * This class adds compatibility with the Multisite Global Media plugin.
 *
 * @link https://vcore.au
 * @see https://github.com/bueltge/multisite-global-media
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Integrations
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.1.5
 */

namespace CF_Images\App\Integrations;

use MultisiteGlobalMedia\Site;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Multisite_Global_Media class.
 *
 * @since 1.1.5
 */
class Multisite_Global_Media {

	/**
	 * Class constructor.
	 *
	 * @since 1.1.5
	 */
	public function __construct() {

		if ( ! is_multisite() ) {
			return;
		}

		add_filter( 'cf_images_attachment_meta', array( $this, 'attachment_meta' ), 10, 2 );
		add_filter( 'wp_get_attachment_metadata', array( $this, 'attachment_metadata' ), 10, 2 );

	}

	/**
	 * Multisite Global Media plugin will append the attachment ID o predefined site ID. For example, if the image with
	 * and attachment ID of 7 is attached to the multisite site with ID 2, the class will be `wp-image-1000007`:
	 * <Site ID><Site::SITE_ID_PREFIX_RIGHT_PAD><Attachment ID>
	 * Because of the above, we need to fetch the Cloudflare image ID from the global media site.
	 *
	 * @since 1.1.5
	 *
	 * @param mixed $cloudflare_image_id  Cloudflare image ID.
	 * @param int   $attachment_id        Attachment ID.
	 *
	 * @return mixed
	 */
	public function attachment_meta( $cloudflare_image_id, int $attachment_id ) {

		if ( ! empty( $cloudflare_image_id ) || ! class_exists( '\\MultisiteGlobalMedia\\Site' ) ) {
			return $cloudflare_image_id;
		}

		if ( ! defined( '\\MultisiteGlobalMedia\\Site::SITE_ID_PREFIX_RIGHT_PAD' ) ) {
			return $cloudflare_image_id;
		}

		$site_id = (int) apply_filters( 'global_media.site_id', 1 ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
		$prefix  = $site_id . Site::SITE_ID_PREFIX_RIGHT_PAD;

		if ( false !== strpos( (string) $attachment_id, $prefix ) ) {
			$global_id = (int) str_replace( $prefix, '', (string) $attachment_id );

			$cloudflare_image_id = $this->get_image_id_from_site( $global_id );
		}

		return $cloudflare_image_id;

	}

	/**
	 * Replace the image meta 'file' value with Cloudflare image ID.
	 *
	 * When processing content, the Multisite Global Media plugin will replace the <img> tag with the generated
	 * value from wp_image_add_srcset_and_sizes() function. But because we've replaced the images using the
	 * adjust_meta() method above, WordPress is not able to replace the main `src` value in
	 * wp_image_add_srcset_and_sizes(), because the wp_image_src_get_dimensions() method will use the image meta 'file'
	 * value to compare against the actual image src. And the 'file' value will never be part of the Cloudflare image URL,
	 * because of the Cloudflare images format.
	 *
	 * @since 1.1.5
	 *
	 * @see \MultisiteGlobalMedia\Attachment::makeContentImagesResponsive()
	 * @see wp_image_src_get_dimensions()
	 *
	 * @param array $data           Array of metadata for the given attachment.
	 * @param int   $attachment_id  Attachment post ID.
	 *
	 * @return array
	 */
	public function attachment_metadata( array $data, int $attachment_id ): array {

		if ( ! doing_filter( 'the_content' ) || ! class_exists( '\\MultisiteGlobalMedia\\Site' ) ) {
			return $data;
		}

		$image = $this->get_image_id_from_site( $attachment_id );

		if ( $image ) {
			$data['file'] = $image;
		}

		return $data;

	}

	/**
	 * Fetch Cloudflare image ID for the selected image from the selected blog in multisite network.
	 *
	 * @since 1.1.5
	 *
	 * @param int $attachment_id  Attachment ID.
	 *
	 * @return mixed  Image ID if found, false otherwise.
	 */
	private function get_image_id_from_site( int $attachment_id ) {

		$site_id = (int) apply_filters( 'global_media.site_id', 1 ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

		switch_to_blog( $site_id );
		$image = get_post_meta( $attachment_id, '_cloudflare_image_id', true );
		restore_current_blog();

		return $image;

	}

}
