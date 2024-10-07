= 1.9.3 - 07.10.2024 =

Added:
* Integration with Smart Slider 3
* Integration with All in One SEO: allow controlling application/ld+json schema image URLs
* Integration with Rank Math: allow controlling application/ld+json schema image URLs
* Filter cf_images_disable_crop to disable auto cropping for registered crop images
* Background image support in Spectra plugin when styles are inlined (props @josephdsouza86)

Changed:
* Improve performance processing external images
* Rename 'cf_images_can_run' filter to 'cf_images_skip_image' so it's more clear what it does

Fixed:
* Images being replaced in RSS feeds, regardless of the settings
* Fatal error when a registered image size does not have height or width defined

= 1.9.2 - 17.07.2024 =

Added:
* Integration with WPBakery page builder image galleries
* Integration with Elementor Pro Gallery
* Integration with Flatsome theme gallery
* cf_images_upload_host filter to adjust the image host ID

Changed:
* Improve image AI modules
* Improve performance when Rank Math image SEO is active

Fixed:
* Only allow generating image alt text for supported formats (JPEG, PNG, GIF, BMP)
* Duplicate queries for images that are not part of the media library
* Rank Math image SEO module not working with custom domains

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

= 1.5.1 - 28.10.2023 =

Fixed:
* Do not replace images on wp-admin if full offload module is disabled

= 1.5.0 - 27.10.2023 =

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

= 1.0.2 - 05.09.2022 =

Added:
* Support for scaled images
* Detect API key changes or other auth issues

Changed:
* Improve code quality

Fixed:
* Do not replace images on the editor

= 1.0.1 - 30.08.2022 =

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
