=== Offload, AI & Optimize with Cloudflare Images ===
Plugin Name: Offload, AI & Optimize with Cloudflare Images
Contributors: vanyukov
Tags: cdn, cloudflare images, image ai, compress, optimize
Donate link: https://www.paypal.com/donate/?business=JRR6QPRGTZ46N&no_recurring=0&item_name=Help+support+the+development+of+the+Cloudflare+Images+plugin+for+WordPress&currency_code=AUD
Requires at least: 5.6
Requires PHP: 7.0
Tested up to: 6.5
Stable tag: 1.9.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Offload you media library images to the Cloudflare Images service. Store, resize, optimize and deliver images in a fast and secure manner.

== Description ==

Offload your media library to Cloudflare Images and let it handle everything for you - store, resize, optimize and deliver images in the best possible format to your users.

= Why Overpay for CDN Plugins? =

With the avalanche of expensive plugins out there, it's time to switch to a smarter choice. Why should you pay more for something that deserves to be free?

= Simplify Your Image Management =

Offload your media library to Cloudflare Images! Let our plugin take charge:

* Store your images securely;
* Resize images to perfection without any manual hassle;
* Optimize them to ensure they load blazingly fast;
* Deliver in the most user-friendly format, ensuring satisfaction at every user's end.

= But wait, there's more! =

Image CDN - deliver images from a global network of servers.
Image AI - tag, caption and generate new images using AI.
Compression - optimize JPEG/PNG images to decrease file size without compromising visual quality.

= A Developer's Promise =

Born from personal need, this plugin represents a developer's dedication to the community. While it's still a work in progress, remember – it's crafted with real user needs in mind, not profit.

= Your Feedback Makes Us Better =

Found a hiccup? Yearning for a feature? Just shoot us a support request. Our commitment is to continually evolve to serve you better. Your wish is our command!

= Disclaimer =

Cloudflare, the Cloudflare logo, and Cloudflare Workers are trademarks and/or registered trademarks of Cloudflare, Inc. in the United States and other jurisdictions.

= Sponsors =

Special thanks to the plugin sponsors:

<a href="https://thismatters.agency">WordPress Agency this:matters</a>

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

By default, the plugin relies on WordPress core functions to process images.
Some themes and plugins can implement their own image processing functions, which can prevent the plugin from replacing the image URLs in content. If you are experiencing this, try to enable the `Parse page for images` module in the plugin settings.
If something is still not working for you, please let me know by creating a support ticket on the plugin support forums.

== Screenshots ==

1. Plugin options and settings
2. Plugin AI modules
3. Quick and easy setup wizard

== Changelog ==

= 1.9.2 =

Added:
* Integration with Elementor Pro Gallery

= 1.9.1 - 23.04.2024 =

Added:
* Integration with Elementor lightbox

Fixed:
* NaN undefined error in compression savings stats
* AI image captioning when custom image paths are set
* WPML compatibility
* "Disable WordPress image sizes" option causing issues with image URLs

= 1.9.0 - 22.03.2024 =

Added:
* Set browser TTL for images
* Option to serve originals for logged-in users
* Option to apply settings network wide in multisite

Changed:
* Disable logging in wp-admin
* Improve detection of cropped images
* Fallback to scaled images if original image is larger than 20 Mb

Fixed:
* Image size can now be changed in the Gutenberg image block for fully offloaded images
* Full size images not replaced in the gallery block on expand
* Multiple fixes and improvements with the WPML integration

= 1.8.0 - 16.02.2024 =

Added:
* Support for RSS feeds
* Auto crop option. If an image width matches the image height - auto crop the image.
* Use img width size. New option that allows using the img width attribute value for the image size, if the value is smaller than the requested image. 
* Bulk remove files from the media library.
* Bulk restore files to the media library.

Fixed:
* Page parser will now add the wp-image-* class to images that do not have it
* Page parser not detecting images that have a custom title set in the media library

= 1.7.1 - 31.12.2023 =

Fixed:
* Fatal error on plugin uninstall
* Do not bulk offload SVG images
* Compatibility with Gutenberg Interactivity API
* Type error when fetching image hashes

= 1.7.0 - 03.12.2023 =

Added:
* Bunny CDN integration
* Service tools module - reset ignored images
* Custom URLs module - control your image links
* Track stats for images served via Cloudflare Images
* REST API integration module

Changed:
* UI/UX improvements

Fixed:
* Notices getting injected into plugin navigation
* Do not expose internal methods to WP CLI commands
* WP CLI command only processing the first 10 images
* Auto resize feature using incorrect descriptors
* Page parser not replacing all images
* Expand on click functionality

= 1.6.0 - 12.11.2023 =

Added:
* Generate images with AI
* Logging module
* Images in media library can now be sorted by offload status
* Integration with ShortPixel
* Compatibility option to store credentials in the database

Changed:
* Increase timeout to 15 seconds when offloading images

Fixed:
* Bulk processing stops if an image triggers an error during upload
* Settings resetting on update after using a beta version

[Full changelog](https://github.com/av3nger/cf-images/blob/master/CHANGELOG.md).

== Upgrade Notice ==

= 1.7.0 =
EOY update. Bunny CDN integration, custom URLs and stats with Cloudflare workers and much more.

= 1.5.0 =
Huge update with lots of improvements and new features, new UI and image compression module.
