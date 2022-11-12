=== Offload, Store, Resize & Optimize with Cloudflare Images ===
Plugin Name: Offload, Store, Resize & Optimize with Cloudflare Images
Contributors: vanyukov
Tags: cdn, cloudflare images, offload images, cloudflare, optimize
Donate link: https://www.paypal.com/donate/?business=JRR6QPRGTZ46N&no_recurring=0&item_name=Help+support+the+development+of+the+Cloudflare+Images+plugin+for+WordPress&currency_code=AUD
Requires at least: 5.6
Requires PHP: 7.0
Tested up to: 6.1
Stable tag: %%VERSION%%
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Offload you media library images to the Cloudflare Images service. Store, resize, optimize and deliver images in a fast and secure manner.

== Description ==

Tired of using expensive CDN plugins that charge ridiculous amounts for something that should be free? Offload your media library to Cloudflare Images and let it handle everything for you - store, resize, optimize and deliver images in the best possible format to your users.

Note from the developer: The plugin is a work in progress, which I created for my personal use, because I got tired with image optimization plugins ignoring real user needs or overcharging for services. If something is not working as expected, or you want a feature added to the plugin, please create a support request, and I will do my best to make it happen.

Cloudflare, the Cloudflare logo, and Cloudflare Workers are trademarks and/or registered trademarks of Cloudflare, Inc. in the United States and other jurisdictions.

== Installation ==

1. Upload the plugin to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Follow the instructions in the setup wizard through the 'Media - Offload Settings' menu in WordPress
4. Enjoy

== Frequently Asked Questions ==

= How does this work? =

The plugin will use the Cloudflare Images service to host all supported images and serve them with the best possible settings to the user.

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

= Why are not all images being replaced in content? =

This is just a first iteration of the plugin. I have tested it over a set of projects that I deployed to my clients.
If something is not working for you, please let me know by creating a support ticket on the plugin support forums.

== Screenshots ==

1. Quick and easy setup wizard
2. Various options

== Changelog ==

= 1.1.2 =

Added:
* Allow defining a custom domain for the Cloudflare Images service
* Option to skip images in media library from offloading
* Button to disconnect from Cloudflare

Changed:
* On network installs, plugin can now only be activated on the network level
* When bulk uploading, if no metadata is found for an image - skip the image, instead of failing the whole process
* Minor UI/UX improvements

Fixed:
* Settings redirect to media library after saving

= 1.1.1 - 24.10.2022 =

Changed:
* Improved button styling
* Allow skipping the setup wizard with CF_IMAGES_ACCOUNT_ID and CF_IMAGES_KEY_TOKEN defines

Fixed:
* Argument #1 ($metadata) must be of type array, bool given error

= 1.1.0 - 09.09.2022 =

Added:
* Global API stats
* Option to disable auto offload
* New and improved UI
* New plugin icon

Fixed:
* Remove autocomplete for setup form fields
* Incorrect stats calculations
* Reset image stats, when no offloaded images found in media library
* Offloading images on servers with outdated SSL libraries

= 1.0.3 - 05.09.2022 =

Fixed:
* Fatal error in rare cases

= 1.0.2 =

Added:
* Support for scaled images
* Detect API key changes or other auth issues

Changed:
* Improve code quality

Fixed:
* Do not replace images on the editor

= 1.0.1 =

Added:
* Image statistics

Changed:
* Better handling of unsupported media types
* Improve UI and UX

Fixed:
* Failed bulk offload if an image path is not defined in metadata
* PHP fatal error with Spectra plugin
* Incorrect status during bulk offload

= 1.0.0 =
First release
* Offload images to Cloudflare Images
* Option to disable WordPress image sizes
* Support for custom domains

== Upgrade Notice ==

= 1.0.0 =
This is the first plugin release.
