=== Google Maps Display for ACF ===
Contributors: vascofmdc
Tags: maps, google maps, acf, advanced custom fields, location, shortcode
Requires at least: 5.0
Tested up to: 6.8.2
Stable tag: 1.0.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A lightweight WordPress plugin to display Google Maps on your site using Advanced Custom Fields (ACF). 
Easily show map locations with shortcodes and manage your Google Maps API key and Map ID from a dedicated settings page.

== Description ==

Google Maps Display for ACF lets you display Google Maps with markers based on Advanced Custom Fields location data.  
Includes:  
* Simple shortcode to embed maps with location markers [acfgooglemaps].  
* Shortcode to display a Google Maps navigation link [linktogooglemaps].  
* Settings page to securely save your Google Maps API key, and Map ID, and ACF maps field name.  
* Uses the latest Google Maps Advanced Markers API for improved performance and appearance.  
* Automatically centers and zooms maps to show all markers.  

Perfect for event pages, venues, or any post type where you use ACF to store location data.

== Installation ==

1. Upload the `vjfnl-acf-map-display` folder to your `/wp-content/plugins/` directory.  
2. Activate the plugin through the 'Plugins' menu in WordPress.  
3. Go to **Settings > ACF Map Display** to enter your Google Maps API Key and Map ID, and ACF maps field name.
4. Use shortcode `[acfgooglemaps]` in your posts, pages widgets pr theme builder to display the map.  
5. Use shortcode `[linktogooglemaps]` to display a clickable Google Maps link.  

== Folder Structure ==

vjfnl-acf-map-display/
	├── css/
	│ └── acf-map-display.css # Stylesheet for map container and markers
	│
	├── includes/
	│ └── install.php #installer file that takes care of plugin (de)activation when ACF is installed or (de)activated
	│ └── updater.php #updater file
	│
	├── js/
	│ └── acf-map-display.js # JavaScript to initialize Google Maps with Advanced Markers
	│
	├── readme.txt # Plugin readme file (this file)
	└── vjfnl-acf-map-display.php # Main plugin PHP file (shortcodes, settings, script enqueue)


== Frequently Asked Questions ==

= Where do I get a Google Maps API key? =  
You can get one from the Google Cloud Console at https://console.cloud.google.com/. Enable the Maps JavaScript API for your project.

= What is a Map ID and how do I create one? =  
Map IDs are created in the Google Cloud Console under the "Maps Platform > Map Management" section. Choose "JavaScript" as the map type. Enter your Map ID in the plugin settings.

= Can I use this plugin with custom post types? =  
Yes, as long as your custom post type uses ACF to store location data with the field name `google_maps_locatie`.

= Does it support multiple markers? =  
Currently it displays one marker per shortcode. You can expand the plugin if you want multiple markers.

== Changelog ==

= 1.0.3 = 2025-08-10
* several bugfixes and stability improvements

= 1.0.2 = 2025-08-09
* several bugfixes and stability improvements

= 1.0.1 = 2025-08-08 
* added updater system

= 1.0.0 = 2025-08-08 
* Initial release

== Upgrade Notice ==

No upgrades yet.
