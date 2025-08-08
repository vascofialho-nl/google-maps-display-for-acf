<?php
/*
	Plugin Name: Google Maps Display for ACF
	Plugin URI: http://www.vascofialho.nl
	Description: Displays Advanced Custom Fields Google Map with shortcode and settings page for API key.
	Author: vascofmdc
	Version: 1.0.1
	Author URI: http://www.vascofialho.nl
	Text Domain: vjfnl-acf-map-display
*/

// Load install checks
	require_once plugin_dir_path(__FILE__) . 'includes/install.php';

// Include the GitHub-based plugin updater
	require_once plugin_dir_path(__FILE__) . 'includes/updater.php';


// Adds link to settings in the plugins list page
	add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
		$settings_link = '<a href="' . admin_url('options-general.php?page=acf-map-display-settings') . '">Settings</a>';
		array_unshift($links, $settings_link);
		return $links;
	});

/**
 * Load the Google Maps API key into Advanced Custom Fields (ACF)
 * 
 * This hook runs on 'acf/init' and retrieves the saved API key from the options table.
 * If the API key exists, it calls `acf_update_setting` to inject the API key dynamically
 * into ACF's Google Maps field configuration.
 */
	add_action('acf/init', function() {
		$api_key = get_option('acf_map_display_api_key');
		if ($api_key) {
			acf_update_setting('google_api_key', $api_key);
		}
	});


/**
 * Shortcode handler for displaying the Google Map with markers
 * 
 * Retrieves the location stored in the user-defined ACF field (required setting).
 * Outputs the necessary HTML structure including the wrapping div with class 'acf-map' and 
 * a nested div for each marker with data attributes for latitude and longitude.
 * If no location is set, returns a fallback message.
 * If the ACF field setting is missing, returns an error message.
 * 
 * Usage in WordPress content: [acfgooglemaps]
 * 
 * @return string HTML markup for the map container and markers or error/fallback message.
 */
	function vjfnl_displaygoogle_maps() {
		$field_name = get_option('acf_map_display_field_name');

		if (empty($field_name)) {
			return '<strong style="color:red;">Error: ACF Field Name setting is missing. Please set it in the plugin settings.</strong>';
		}

		$location = get_field($field_name);

		if (isset($location) && !empty($location)) {
			$ACFgooglemaps  = '<div class="acf-map" data-zoom="15">';
			$ACFgooglemaps .= '<div class="marker" data-lat="'.esc_attr($location['lat']).'" data-lng="'.esc_attr($location['lng']).'"></div>';				
			$ACFgooglemaps .= '</div>';
		} else {
			$ACFgooglemaps = 'Nothing to see here. Move along.';
		}
		return $ACFgooglemaps;
	}
	add_shortcode('acfgooglemaps', 'vjfnl_displaygoogle_maps');


/**
 * Shortcode handler for displaying only a clickable Google Maps link
 * 
 * Retrieves the location stored in the user-defined ACF field.
 * Outputs a styled hyperlink to the Google Maps location using the latitude and longitude.
 * If no location is set, returns a fallback message.
 * If the ACF field setting is missing, returns an error message.
 * 
 * Usage in WordPress content: [linktogooglemaps]
 * 
 * @return string HTML anchor element with the Google Maps URL or error/fallback message.
 */
	function vjfnl_display_googlemaps_link() {
		$field_name = get_option('acf_map_display_field_name');

		if (empty($field_name)) {
			return '<strong style="color:red;">Error: ACF Field Name setting is missing. Please set it in the plugin settings.</strong>';
		}

		$location = get_field($field_name);

		if (isset($location) && !empty($location)) {
			$ACFgooglemaps = '<a style="color: #666; font-size: 10pt;" href="https://www.google.com/maps/place/'.esc_attr($location['address']).'/@'.esc_attr($location['lat']).','.esc_attr($location['lng']).',18z" target="_blank" rel="noopener noreferrer">Klik hier voor navigatie info (opent Google Maps)</a>';
		} else {
			$ACFgooglemaps = 'Geen link informatie beschikbaar.';
		}
		return $ACFgooglemaps;
	}
	add_shortcode('linktogooglemaps', 'vjfnl_display_googlemaps_link');


/**
 * Enqueue front-end CSS and JavaScript
 * 
 * Adds the CSS and JS files necessary for rendering the ACF map on the front-end.
 * Additionally enqueues the Google Maps JavaScript API dynamically using the saved API key
 * and Map ID.
 * The Google Maps API is enqueued with `true` for `$in_footer` to load it just before </body>.
 * 
 * This function runs on the 'wp_enqueue_scripts' hook which runs on front-end page loads.
 */
	add_action('wp_enqueue_scripts', function() {
		// Enqueue plugin stylesheet for map styling
		wp_enqueue_style('acf-map-display-css', plugin_dir_url(__FILE__) . 'css/acf-map-display.css');

		// Enqueue plugin JavaScript, depends on jQuery
		wp_enqueue_script('acf-map-display-js', plugin_dir_url(__FILE__) . 'js/acf-map-display.js', array('jquery'), null, true);

		// Get the saved API key and Map ID from options
		$api_key = get_option('acf_map_display_api_key');
		$map_id = get_option('acf_map_display_map_id');

		// Build Google Maps API URL with required parameters
		$maps_api_url = 'https://maps.googleapis.com/maps/api/js?key=' . esc_attr($api_key) . '&libraries=marker';

		if ($map_id) {
			$maps_api_url .= '&map_ids=' . esc_attr($map_id);
		}

		// If API key exists, enqueue the Google Maps JavaScript API with the key and Map ID
		if ($api_key) {
			wp_enqueue_script(
				'google-maps-api',
				$maps_api_url,
				array(),
				null,
				true
			);
		}
	});


/**
 * Filter the Google Maps API script tag to add async and defer attributes
 * 
 * This removes the warning about direct loading and improves page load performance.
 */
	add_filter('script_loader_tag', function($tag, $handle) {
		if ('google-maps-api' === $handle) {
			return str_replace(' src', ' async defer src', $tag);
		}
		return $tag;
	}, 20, 2);


/**
 * Register settings fields for storing the Google Maps API key, Map ID, and ACF field name
 * 
 * Uses WordPress Settings API to register the options with sanitization and validation.
 * This runs during 'admin_init' so the options become manageable in the admin settings.
 */
	add_action('admin_init', function() {
		register_setting('acf_map_display_settings_group', 'acf_map_display_api_key', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		));
		register_setting('acf_map_display_settings_group', 'acf_map_display_map_id', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		));
		register_setting('acf_map_display_settings_group', 'acf_map_display_field_name', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => function($field_name) {
				if (empty(trim($field_name))) {
					add_settings_error(
						'acf_map_display_field_name',
						'acf_map_display_field_name_error',
						'The ACF Field Name is required. Please enter it before saving.',
						'error'
					);
					return false;
				}
				return $field_name;
			},
		));
	});


/**
 * Add a menu page for the plugin settings in the WordPress admin
 * 
 * Adds an options page under the "Settings" menu with the title 'ACF Map Display'.
 * This page calls the callback 'vjfnl_acf_map_display_render_settings_page' to output the HTML form.
 */
	add_action('admin_menu', function() {
		add_options_page(
			'ACF Map Display Settings',  // Page title
			'ACF Map Display',           // Menu title
			'manage_options',            // Capability required to view this page
			'acf-map-display-settings', // Menu slug
			'vjfnl_acf_map_display_render_settings_page'  // Callback function
		);
	});


/**
 * Render the settings page HTML
 * 
 * Outputs a form with:
 * - Text input for the Google Maps API key
 * - Text input for the Google Maps Map ID
 * - Text input for the ACF Field Name (required)
 * - Descriptive links and idiot-proof instructions to obtain API key, Map ID, and set the field name
 * - A button that tests the validity of the entered API key via loading the Google Maps API script asynchronously
 * 
 * Utilizes the WordPress Settings API functions to handle nonce fields and saving.
 * Includes inline JavaScript (jQuery) to perform the test asynchronously and show feedback.
 */
	add_action('admin_init', function() {
		register_setting('acf_map_display_settings_group', 'acf_map_display_api_key', [
			'type' => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default' => '',
		]);
		register_setting('acf_map_display_settings_group', 'acf_map_display_map_id', [
			'type' => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default' => '',
		]);
		register_setting('acf_map_display_settings_group', 'acf_map_display_field_name', [
			'type' => 'string',
			'sanitize_callback' => 'vjfnl_sanitize_acf_field_name',
			'default' => '',
		]);
	});

	/**
	 * Sanitize and validate the ACF field name input.
	 * Only allow letters, numbers, and underscores.
	 * If invalid, add settings error and save empty string.
	 */
	function vjfnl_sanitize_acf_field_name($input) {
		$input = sanitize_text_field($input);
		if (!preg_match('/^[a-zA-Z0-9_]+$/', $input)) {
			add_settings_error(
				'acf_map_display_field_name',
				'invalid_field_name',
				'ACF field name can only contain letters, numbers, and underscores.',
				'error'
			);
			return '';
		}
		return $input;
	}

	// Your existing settings page rendering function (unchanged except added settings_errors() call)
	function vjfnl_acf_map_display_render_settings_page() {
		$api_key = esc_attr(get_option('acf_map_display_api_key'));
		$map_id = esc_attr(get_option('acf_map_display_map_id'));
		$field_name = esc_attr(get_option('acf_map_display_field_name'));
		?>
		<div class="wrap">
			<h1>Google Maps Display for ACF Settings</h1>
			<form method="post" action="options.php">
				<?php
					settings_fields('acf_map_display_settings_group');
					do_settings_sections('acf_map_display_settings_group');
					settings_errors(); // Show validation errors here
				?>
				<table class="form-table">

					<tr valign="top">
						<th scope="row">Google Maps API Key</th>
						<td>
							<input type="text" name="acf_map_display_api_key" value="<?php echo $api_key; ?>" style="width: 400px;" />
							<p class="description">
								Enter your Google Maps API key here.
								<a href="https://console.cloud.google.com/google/maps-apis/credentials" target="_blank" rel="noopener noreferrer">Get an API key from Google Cloud Console</a>.
							</p>
							<p>
								<button type="button" class="button" id="acf-map-test-api">Test API Key</button>
								<span id="acf-map-test-result" style="margin-left: 10px;"></span>
							</p>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row">Google Maps Map ID</th>
						<td>
							<p>Without a Map ID, the map will display an error. You cannot skip this step.</p>
							<br>
							<p class="description" style="max-width: 600px;">
								<strong>How to get your Google Maps Map ID:</strong><br>
								1. Go to <a href="https://console.cloud.google.com/google/maps-apis/" target="_blank" rel="noopener noreferrer">https://console.cloud.google.com/google/maps-apis/</a><br>
								2. Login with your Google credentials.<br>
								3. Select your project in the top dropdown.<br>
								4. Click on the left menu on "Map Management".<br>
								5. Click <em>Create Map ID</em>.<br>
								6. Enter a name and select <strong>JavaScript</strong> as map type.<br>
								7. Choose <strong>Vector</strong> rendering (tilt & rotation optional).<br>
								8. Copy the Map ID and paste it here.<br><br>
								Using a Map ID enables advanced styling and markers support.<br>
								See <a href="https://developers.google.com/maps/documentation/javascript/mapids" target="_blank" rel="noopener noreferrer">Google's Map IDs docs</a> for more info.
							</p>
							<br>
							<p><strong>Map ID:</strong></p>
							<input type="text" name="acf_map_display_map_id" value="<?php echo $map_id; ?>" style="width: 400px;" />
						</td>
					</tr>

					<tr valign="top">
						<th scope="row">ACF Field Name <span style="color:red;">*</span></th>
						<td>
							<p>This is the name of the ACF field that stores the Google Maps location data. This field name is <strong>required</strong> and must be set correctly.</p>
							<input type="text" name="acf_map_display_field_name" value="<?php echo $field_name; ?>" style="width: 400px;" required />
							<p class="description" style="max-width: 600px;">
								Enter the exact ACF field name you use to store the map location.<br>
								For example, <code>google_maps_location</code> or <code>event_location</code>.<br>
								This must match the field name used in your ACF field group.<br>
								Without this, the plugin cannot display maps properly.
							</p>
						</td>
					</tr>

				</table>
				<?php submit_button(); ?>
			</form>
		</div>

		<script>
		(function($){
			// Click handler to test if the entered API key can load Google Maps JS successfully
			$('#acf-map-test-api').on('click', function(){
				var key = $('input[name="acf_map_display_api_key"]').val();

				if(!key){
					$('#acf-map-test-result').text('Please enter an API key first.').css('color','red');
					return;
				}

				$('#acf-map-test-result').text('Testing...').css('color','black');

				// Attempt to load Google Maps API script dynamically with entered key
				$.getScript('https://maps.googleapis.com/maps/api/js?key=' + key)
					.done(function(){
						$('#acf-map-test-result').text('API key is valid ✅').css('color','green');
					})
					.fail(function(){
						$('#acf-map-test-result').text('Invalid API key ❌').css('color','red');
					});
			});
		})(jQuery);
		</script>
	<?php
	}



