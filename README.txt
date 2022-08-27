=== Plugin Name ===
Contributors: vanyukov
Tags: cdn, images, offload, cloudflare
Donate link: https://www.paypal.com/donate/?business=JRR6QPRGTZ46N&no_recurring=0&item_name=Help+support+the+development+of+the+Cloudflare+Images+plugin+for+WordPress&currency_code=AUD
Requires at least: 5.6
Requires PHP: 7.0
Tested up to: 6.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Offload you media library images to the Cloudflare Images service. Store, resize, optimize and deliver images in a fast and secure manner.

== Description ==

Tired of using expensive CDN plugins that charge ridiculous amounts for something that should be free? Offload your media
library to Cloudflare Images and let it handle everything for you - store, resize, optimize and deliver images in the
best possible format to your users.

Note from the developer. The plugin is a work in progress, if something is not working as expected, or you want a feature
added to the plugin, please create a support request.

== Installation ==

1. Upload the plugin to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Follow the instructions in the setup wizard through the 'Media - Offload Settings' menu in WordPress
4. Enjoy

== Frequently Asked Questions ==

= How does this work? =

The plugin will use the Cloudflare Images service to host all supported images and serve them with the best possible
settings to the user.

= Is this free? =

Yes, the plugin is 100% free. A Cloudflare account with activated Cloudflare Images option is required.
Cloudflare may charge a fee for the use of this feature, depending on the plan used.

= What are the supported image formats? =

You can upload the following image formats to Cloudflare Images:
* PNG
* GIF
* JPEG
* WebP

= Are there any other limitations? =

These are the maximum allowed sizes and dimensions Cloudflare Images supports:

* Images' height and width are limited to 10,000 pixels.
* Image metadata is limited to 1024 bytes.
* Images have a 10 megabyte (MB) size limit.
* Animated GIFs, including all frames, are limited to 100 megapixels (MP).

== Screenshots ==

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the /assets directory or the directory that contains the stable readme.txt (tags or trunk). Screenshots in the /assets
directory take precedence. For example, `/assets/screenshot-1.png` would win over `/tags/4.3/screenshot-1.png`
(or jpg, jpeg, gif).
2. This is the second screen shot

== Changelog ==

= 1.0.0 =
First release
* New: Offload images to Cloudflare Images
* New: Option to disable WordPress image sizes
* New: Support for custom domains

== Upgrade Notice ==

= 1.0.0 =
This is the first plugin release.
