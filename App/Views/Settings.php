<?php
/**
 * Settings view
 *
 * Various Cloudflare Images settings.
 *
 * @link https://vcore.ru
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Views
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.0.0
 */

namespace CF_Images\App\Views;

if ( ! defined( 'WPINC' ) ) {
	die;
}

$wp_sizes = get_intermediate_image_sizes();
$variants = get_option( 'cf-images-variants', array() );
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Offload Images to Cloudflare', 'cf-images' ); ?></h1>

	<h2><?php esc_html_e( 'Image variants', 'cf-images' ); ?></h2>
	<p><?php esc_html_e( 'Syncing up image sizes is required to make sure Cloudflare Images have all the WordPress registered image sizes.', 'cf-images' ); ?></p>
	<p><?php esc_html_e( 'This action is only required once.', 'cf-images' ); ?></p>

	<h4><?php esc_html_e( 'Synced image sizes:', 'cf-images' ); ?></h4>
	<table class="widefat">
		<thead>
		<tr>
			<th><?php esc_html_e( 'WordPress image size ID', 'cf-images' ); ?></th>
			<th><?php esc_html_e( 'Cloudflare Images variant', 'cf-images' ); ?></th>
		</tr>
		</thead>
		<tbody>
		<?php foreach ( $wp_sizes as $registered_size ) : ?>
			<tr data-id="<?php echo esc_attr( $registered_size ); ?>">
				<td><?php echo esc_html( $registered_size ); ?></td>
				<td>
					<?php
					if ( isset( $variants[ $registered_size ] ) ) {
						echo esc_html( $variants[ $registered_size ]['variant'] );
					} else {
						esc_html_e( 'Unregistered size', 'cf-images' );
					}
					?>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>

	<p>
		<input class="button" type="button" value="<?php esc_attr_e( 'Sync image sizes', 'cf-images' ); ?>" id="cf-images-sync-image-sizes" />
		<span class="spinner"></span>
	</p>
</div>
