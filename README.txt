=== Offload, Store, Resize & Optimize with Cloudflare Images ===
Plugin Name: Offload, Store, Resize & Optimize with Cloudflare Images
Contributors: vanyukov
Tags: cdn, cloudflare images, offload images, cloudflare, optimize
Donate link: https://www.paypal.com/donate/?business=JRR6QPRGTZ46N&no_recurring=0&item_name=Help+support+the+development+of+the+Cloudflare+Images+plugin+for+WordPress&currency_code=AUD
Requires at least: 5.6
Requires PHP: 7.0
Tested up to: 6.3
Stable tag: 1.5.0
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

Image AI - tag and caption your images using AI.

= A Developer's Promise =

Born from personal need, this plugin represents a developer's dedication to the community. While it's still a work in progress, remember – it's crafted with real user needs in mind, not profit.

= Your Feedback Makes Us Better =

Found a hiccup? Yearning for a feature? Just shoot us a support request. Our commitment is to continually evolve to serve you better. Your wish is our command!

= Disclaimer =

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

By default, the plugin relies on WordPress core functions to process images.
Some themes and plugins can implement their own image processing functions, which can prevent the plugin from replacing the image URLs in content. If you are experiencing this, try to enable the `Parse page for images` module in the plugin settings.
If something is still not working for you, please let me know by creating a support ticket on the plugin support forums.

== Screenshots ==

1. Plugin options and settings
2. Quick and easy setup wizard

== Changelog ==

= 1.5.0 =

Added:
* New and improved React-based UI
* Image compression module: optimize the size of your media library images
* WP CLI support via the "wp cf-images" commands (bulk & individual offload)
* Compatibility with the "Enable Media Replace" plugin
* Option to bulk add image captions
* Allow viewing a page with original images, using a "?cf-images-disable=true" URL query

Changed:
* The "Auto resize images on front-end" module has been refactored to prevent double loading of images

Fixed:
* Cropped image detection
* Compatibility with latest WordPress coding standards
* PHP warnings with page parser module on pages with no images
* Link for adding API key for AI module was not working

= 1.4.1 - 21.08.2023 =

Fixed:
* Cannot log in Image AI module without activating it first
* Handling of images with "scaled" in the file name

= 1.4.0 - 09.08.2023 =

Added:
* Image AI - tag and caption your images with AI
* Page parser module
* Compatibility with Breakdance builder (via page parser module)
* Compatibility with WMPL

Changed:
* Improved detection of image sizes
* Media UI refactored for better accessibility

Fixed:
* Issues with lazy loading
* When full offload is enabled, images are not displaying on front-end
* Removing of scaled images

= 1.3.0 - 24.06.2023 =

Added:
* Full offload and restore for images from the WordPress media library [beta]
* ACF integration
* Option to remove selected images from Cloudflare
* Preconnect to CDN URL

Changed:
* Improved internal plugin structure
* Improved media library status layout
* Improved media library UI

Fixed:
* Scaled images offloaded instead of originals
* Fatal error when uninstalling the plugin
* Error when image metadata is not an array
* Images not served via CDN if a custom size is used

= 1.2.0 - 29.03.2023 =

Added:
* Auto image sizes on front-end
* Option to use custom paths for images
* Confirmation modal for bulk remove action
* Offload status to media library grid mode
* Detailed setup guide link in the setup wizard

Changed:
* Improved descriptions for the plugin settings
* Improve settings layout
* Intentionally sleep for a second after setup to allow the PHP cache to expire on setup/disconnect
* Various UI/UX improvements

Fixed:
* Properly handle already uploaded images and duplicates
* Prevent replacing images in wp-admin, because WordPress does not respect is_admin() checks
* Scaled images having an empty 'w' parameter

= 1.1.5 - 28.02.2023 =

Added:
* Integration with RankMath Image SEO module
* Integration with Multisite Global Media plugin
* Option to disable async image offloading

Changed:
* Store the Cloudflare image hash in network options on multisite installs
* Code refactor to be fully compatible with WordPress coding standards

Fixed:
* RankMath image titles not working properly with Cloudflare images
* TypeError in get_attachment_image_src method
* Removed debug code

= 1.1.4 - 29.01.2023 =

Fixed:
* Links in readme.txt file

= 1.1.3 - 29.01.2023 =

Added:
* Compatibility with "Spectra – WordPress Gutenberg Blocks" plugin
* cf_images_upload_meta_data filter to allow customizing the metadata sent to Cloudflare Images

Changed:
* Improve detection of image sizes, fallback to scaled image dimensions
* Improved compatibility with RankMath - og:image tags will not be converted to Cloudflare Image URLs

Fixed:
* Image file names on subdirectory multisite installs

= 1.1.2 - 19.11.2022 =

Added:
* Support for WooCommerce bulk product uploads
* Allow defining a custom domain for the Cloudflare Images service
* Option to skip images in media library from offloading
* Button to disconnect from Cloudflare

Changed:
* On network installs, plugin can now only be activated on the network level
* When bulk uploading, if no metadata is found for an image - skip the image, instead of failing the whole process
* Minor UI/UX improvements
* Improved compatibility with WordPress 6.1

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

= 1.4.0 =
Image AI - tag and caption your images using AI. Page parser module - improved compatibility with themes and plugins.

= 1.2.0 =
Big update with lots of improvements and new features.

= 1.0.0 =
This is the first plugin release.
