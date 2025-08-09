<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GitHub Plugin Updater
 * Automatically checks GitHub for new plugin releases and enables updates.
 */

// Define the main plugin file path
define( 'GMDfACF_PLUGIN_FILE', plugin_dir_path( __FILE__ ) . 'vjfnl-acf-map-display.php' );

// Hook into update checks
add_filter( 'pre_set_site_transient_update_plugins', 'gmdfacf_check_for_plugin_update' );
add_filter( 'plugins_api', 'gmdfacf_plugin_info', 20, 3 );

/**
 * Get current plugin version from plugin header
 */
function gmdfacf_get_local_version() {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	$data = get_plugin_data( WP_PLUGIN_DIR . '/vjfnl-acf-map-display/vjfnl-acf-map-display.php' );
	return $data['Version'];
}

/**
 * Get latest version tag from GitHub Releases API
 */
function gmdfacf_get_latest_github_release() {
	$response = wp_remote_get( 'https://api.github.com/repos/vascofialho-nl/google-maps-display-for-acf/releases/latest', array(
		'headers' => array( 'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) )
	) );

	if ( is_wp_error( $response ) ) {
		return false;
	}

	$data = json_decode( wp_remote_retrieve_body( $response ) );

	if ( isset( $data->tag_name ) ) {
		return ltrim( $data->tag_name, 'v' ); // Strip 'v' if used like v1.0.2
	}

	return false;
}

/**
 * Add update data to plugin transient
 */
function gmdfacf_check_for_plugin_update( $transient ) {
	if ( empty( $transient->checked ) ) {
		return $transient;
	}

	$plugin_slug     = 'vjfnl-acf-map-display/vjfnl-acf-map-display.php';
	$current_version = gmdfacf_get_local_version();
	$remote_version  = gmdfacf_get_latest_github_release();

	if ( ! $remote_version || version_compare( $current_version, $remote_version, '>=' ) ) {
		return $transient;
	}

	$update_url = 'https://github.com/vascofialho-nl/google-maps-display-for-acf/releases/download/' . $remote_version . '/vjfnl-acf-map-display.zip';

	$transient->response[ $plugin_slug ] = (object) array(
		'slug'        => 'google-maps-display-for-acf',
		'plugin'      => $plugin_slug,
		'new_version' => $remote_version,
		'url'         => 'https://github.com/vascofialho-nl/google-maps-display-for-acf',
		'package'     => $update_url,
	);

	return $transient;
}

/**
 * Provide plugin details for the updater popup
 */
function gmdfacf_plugin_info( $res, $action, $args ) {
	if ( $action !== 'plugin_information' ) return $res;
	if ( $args->slug !== 'google-maps-display-for-acf' ) return $res;

	$remote_version = gmdfacf_get_latest_github_release();
	if ( ! $remote_version ) return $res;

	$update_url = 'https://github.com/vascofialho-nl/google-maps-display-for-acf/releases/download/' . $remote_version . '/vjfnl-acf-map-display.zip';

	$res = (object) array(
		'name'           => 'Google Maps Display for ACF',
		'slug'           => 'vjfnl-acf-map-display',
		'version'        => $remote_version,
		'author'         => '<a href="https://vascofialho.nl">vascofmdc</a>',
		'homepage'       => 'https://github.com/vascofialho-nl/google-maps-display-for-acf',
		'download_link'  => $update_url,
		'trunk'          => $update_url,
		'sections'       => array(
		'description' => 'A lightweight WordPress plugin to display Google Maps on your site using Advanced Custom Fields (ACF).',
		'changelog'   => '<p><strong>' . esc_html( $remote_version ) . '</strong> – See GitHub or readme.txt for details.</p>',
		),
	);

	return $res;
}
