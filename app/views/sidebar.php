<?php
/**
 * Sidebar view
 *
 * @link https://vcore.au
 *
 * @package CF_Images
 * @subpackage CF_Images/App/Views
 * @author Anton Vanyukov <a.vanyukov@vcore.ru>
 * @since 1.4.0
 */

namespace CF_Images\App\Views;

if ( ! defined( 'WPINC' ) ) {
	die;
}

?>

<article class="cf-images-sidebar">
	<h3><?php esc_html_e( 'Additional resources', 'cf-images' ); ?></h3>
	<?php esc_html_e( 'Below is a list of links to resources that will help you get started or get additional help:', 'cf-images' ); ?>
	<ul>
		<li>&mdash;
			<a href="https://vcore.au/tutorials/how-to-setup-cloudflare-images-plugin/" target="_blank" rel="noopener">
				<?php esc_html_e( 'A detailed guide with screenshots on how to setup the plugin.', 'cf-images' ); ?>
			</a>
		</li>
		<li>&mdash;
			<a href="https://wordpress.org/support/plugin/cf-images/" target="_blank" rel="noopener">
				<?php esc_html_e( 'WordPress support forums.', 'cf-images' ); ?>
			</a>
		</li>
		<li>&mdash;
			<?php
			printf( /* translators: %1$s - opening <a> tag, %2$s - closing </a> tag */
				esc_html__( 'Feel free to send me a message directly via my %1$scontact form%2$s.', 'cf-images' ),
				'<a href="https://vcore.au/contact-us/" target="_blank" rel="noopener">',
				'</a>'
			);
			?>
		</li>
	</ul>

	<h4><?php esc_html_e( 'Support the project', 'cf-images' ); ?></h4>
	<?php esc_html_e( 'This is a free plugin, if you find it useful, please consider supporting it by:', 'cf-images' ); ?>
	<ul>
		<li>&mdash;
			<?php
			printf( /* translators: %1$s - opening <a> tag, %2$s - closing </a> tag */
				esc_html__( 'Sharing your ideas and feedback on the %1$ssupport forums%2$s, it helps me make the plugin better.', 'cf-images' ),
				'<a href="https://wordpress.org/support/plugin/cf-images/" target="_blank" rel="noopener">',
				'</a>'
			);
			?>
		</li>
		<li>&mdash;
			<?php
			printf( /* translators: %1$s - opening <a> tag, %2$s - closing </a> tag */
				esc_html__( 'Trying out my %1$sFuzion AI plugin%2$s and subscribing to one of the plans.', 'cf-images' ),
				'<a href="https://wordpress.org/plugins/fuzion/" target="_blank" rel="noopener">',
				'</a>'
			);
			?>
		</li>
		<li>&mdash;
			<?php
			printf( /* translators: %1$s - opening <a> tag, %2$s - closing </a> tag */
				esc_html__( 'Buy me a coffee via %1$sPayPal%2$s.', 'cf-images' ),
				'<a href="https://www.paypal.com/donate/?business=JRR6QPRGTZ46N&no_recurring=0&item_name=Help+support+the+development+of+the+Cloudflare+Images+plugin+for+WordPress&currency_code=AUD" target="_blank" rel="noopener">',
				'</a>'
			);
			?>
		</li>
	</ul>

	<?php
	printf( /* translators: %1$s - opening <a> tag, %2$s - closing </a> tag */
		esc_html__( 'Or, if you prefer to never see this sidebar again, just %1$sclick here%2$s.', 'cf-images' ),
		'<a href="#" id="hide-the-sidebar">',
		'</a>'
	);
	?>
</article>
