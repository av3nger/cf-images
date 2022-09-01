<?php
/**
 * Setup view
 *
 * Shown, when the Cloudflare account ID or API key are not defined.
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

?>

<div class="wrap">
	<h1><?php esc_html_e( 'Offload Images to Cloudflare Setup', 'cf-images' ); ?></h1>

	<p><?php esc_html_e( 'For proper functionality, the plugin requires access to Cloudflare Images API.', 'cf-images' ); ?></p>

	<p>
		<?php
		printf( /* translators: %1$s - opening <code> tag, %2$s - closing </code> tag */
			esc_html__( "To do this, either set %1\$sdefine( 'CF_IMAGES_ACCOUNT_ID', '<ACCOUNT ID>' );%2\$s and %1\$sdefine( 'CF_IMAGES_KEY_TOKEN', '<API KEY>' );%2\$s manually in wp-config.php file or use the form with instructions below.", 'cf-images' ),
			'<code>',
			'</code>'
		);
		?>
	</p>

	<p><?php esc_html_e( 'Note: The form will attempt to automatically set the required defines in wp-config.php file.', 'cf-images' ); ?></p>

	<?php if ( get_option( 'cf-images-config-written', false ) ) : ?>
		<div class="notice notice-warning inline">
			<p>
				<?php esc_html_e( 'It appears there is something wrong with the API token, and the plugin is not able to authenticate on the Cloudflare API. Please check the details below and update the API token and/or account ID.', 'cf-images' ); ?>
			</p>
		</div>
	<?php endif; ?>

	<form id="cf-images-form" data-type="setup">
		<table class="form-table">
			<caption class="screen-reader-text"><?php esc_html_e( 'Setup table', 'cf-images' ); ?></caption>
			<tbody>
			<tr>
				<th scope="row">
					<label for="account_id">
						<?php esc_html_e( 'Cloudflare Account ID', 'cf-images' ); ?>
					</label>
				</th>
				<td>
					<input name="account-id" type="text" id="account_id" value="<?php echo defined( 'CF_IMAGES_ACCOUNT_ID' ) ? esc_attr( CF_IMAGES_ACCOUNT_ID ) : ''; ?>" placeholder="<?php esc_attr_e( 'Paste your Cloudflare ID here', 'cf-images' ); ?>" class="regular-text">
					<p class="description">1.&nbsp;
						<?php
						printf( /* translators: %1$s - opening <a> tag, %2$s - closing </a> tag */
							esc_html__( 'Log in to the %1$sCloudflare dashboard%2$s, and select your account and website.', 'cf-images' ),
							'<a href="https://dash.cloudflare.com/login" target="_blank">',
							'</a>'
						);
						?>
					</p>
					<p class="description">2.&nbsp;
						<?php
						printf( /* translators: %1$s - opening <strong> tag, %2$s - closing </strong> tag */
							esc_html__( 'In %1$sOverview%2$s, scroll down to find your Account ID.', 'cf-images' ),
							'<strong>',
							'</strong>'
						);
						?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="api_key">
						<?php esc_html_e( 'Cloudflare API Token', 'cf-images' ); ?>
					</label>
				</th>
				<td>
					<input name="api-key" type="text" id="api_key" value="" placeholder="<?php esc_attr_e( 'Paste your Cloudflare API key here', 'cf-images' ); ?>" class="regular-text">
					<p class="description">
						<?php
						printf( /* translators: %1$s - opening <code> tag, %2$s - closing </code> tag */
							esc_html__( 'To use Cloudflare Images you need to create a custom token with the correct %1$sRead%2$s and %1$sUpdate%2$s permissions:', 'cf-images' ),
							'<code>',
							'</code>'
						);
						?>
					</p>
					<p class="description">1.&nbsp;
						<?php
						printf( /* translators: %1$s - opening <a> tag, %2$s - closing </a> tag, %3$s - opening <strong> tag, %4$s - closing </strong> tag */
							esc_html__( 'In the Cloudflare dashboard, locate %1$sAPI Tokens%2$s under %3$sMy Profile > API Tokens%4$s.', 'cf-images' ),
							'<a href="https://dash.cloudflare.com/profile/api-tokens" target="_blank">',
							'</a>',
							'<strong>',
							'</strong>'
						);
						?>
					</p>
					<p class="description">2.&nbsp;
						<?php
						printf( /* translators: %1$s - opening <strong> tag, %2$s - closing </strong> tag */
							esc_html__( 'Select %1$sCreate Token%2$s.', 'cf-images' ),
							'<strong>',
							'</strong>'
						);
						?>
					</p>
					<p class="description">3.&nbsp;
						<?php
						printf( /* translators: %1$s - opening <strong> tag, %2$s - closing </strong> tag */
							esc_html__( 'In Custom token, select %1$sGet started%2$s.', 'cf-images' ),
							'<strong>',
							'</strong>'
						);
						?>
					</p>
					<p class="description">4.&nbsp;
						<?php esc_html_e( 'Give your custom token a name.', 'cf-images' ); ?>
					</p>
					<p class="description">5.&nbsp;
						<?php
						printf( /* translators: %1$s - opening <strong> tag, %2$s - closing </strong> tag */
							esc_html__( 'Scroll to %1$sPermissions%2$s.', 'cf-images' ),
							'<strong>',
							'</strong>'
						);
						?>
					</p>
					<p class="description">6.&nbsp;
						<?php
						printf( /* translators: %1$s - opening <em> tag, %2$s - closing </em> tag */
							esc_html__( 'On the %1$sSelect item…%2$s drop-down menu, choose %1$sCloudflare Images%2$s.', 'cf-images' ),
							'<em>',
							'</em>'
						);
						?>
					</p>
					<p class="description">7.&nbsp;
						<?php
						printf( /* translators: %1$s - opening <em> tag, %2$s - closing </em> tag */
							esc_html__( 'In the next drop-down menu, choose %1$sEdit%2$s.', 'cf-images' ),
							'<em>',
							'</em>'
						);
						?>
					</p>
					<p class="description">
						<img src="<?php echo esc_url( CF_IMAGES_DIR_URL . 'assets/images/step-02-custom-token-setup.jpg' ); ?>" alt="<?php esc_attr_e( 'How to create a custom token for Cloudflare images', 'cf-images' ); ?>" width="650" />
					</p>
					<p class="description">8.&nbsp;
						<?php
						printf( /* translators: %1$s - opening <strong> tag, %2$s - closing </strong> tag */
							esc_html__( 'Select %1$sContinue to summary > Create Token%2$s.', 'cf-images' ),
							'<strong>',
							'</strong>'
						);
						?>
					</p>
					<p class="description">
						<?php esc_html_e( 'Your token for Cloudflare Images is now created.', 'cf-images' ); ?>
					</p>
				</td>
			</tr>
			</tbody>
		</table>

		<p class="submit">
			<input type="submit" name="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Changes', 'cf-images' ); ?>">
			<span class="spinner"></span>
		</p>
	</form>

</div>
