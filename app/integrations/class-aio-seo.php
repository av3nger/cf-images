<?php
/**
 * Integration class for the "All in One SEO" plugin
 *
 * This class adds compatibility with the "All in One SEO" plugin.
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Integrations
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.9.3
 */

namespace CF_Images\App\Integrations;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * AIO_SEO class.
 *
 * @since 1.9.3
 */
class AIO_SEO extends Integration {
	/**
	 * Check if the integration should run.
	 *
	 * @since 1.9.3
	 *
	 * @return bool
	 */
	protected function should_run(): bool {
		return defined( 'AIOSEO_PLUGIN_NAME' );
	}

	/**
	 * Define the variables for the integration.
	 *
	 * @since 1.9.3
	 */
	protected function init() {
		$this->name = esc_html__( 'All in One SEO', 'cf-images' );
		$this->slug = 'aio_seo';

		/**
		 * Do not replace the ImageObject in application/ld+json schema.
		 *
		 * @see https://github.com/av3nger/cf-images/issues/49
		 */
		if ( ! $this->integration_option_value( false, 'image_object' ) ) {
			add_action( 'wp_head', array( $this, 'halt_offload' ), 0 );
			add_action( 'wp_head', array( $this, 'resume_offload' ), 2 );
		}
	}

	/**
	 * Define the integration options.
	 *
	 * @since 1.9.3
	 *
	 * @param array  $options Integration options.
	 * @param string $slug    Integration slug.
	 *
	 * @return array
	 */
	public function integration_options( array $options, string $slug ): array {
		if ( $this->slug !== $slug ) {
			return $options;
		}

		return array(
			array(
				'name'        => 'image_object',
				'label'       => esc_html__( 'Replace ImageObject', 'cf-images' ),
				'description' => esc_html__( 'Use Cloudflare image for the ImageObject in the application/ld+json schema. Requires the `Process images in head` option to be enabled in Settings - Misc Options.', 'cf-images' ),
				'value'       => apply_filters( 'cf_images_integration_option_value', false, 'image_object' ),
			),
		);
	}

	/**
	 * Use a local version of the image in the head section.
	 *
	 * @since 1.9.3
	 */
	public function halt_offload() {
		add_filter( 'cf_images_skip_image', '__return_true' );
	}

	/**
	 * Resume normal operations.
	 *
	 * @since 1.9.3
	 */
	public function resume_offload() {
		remove_filter( 'cf_images_skip_image', '__return_false' );
	}
}
