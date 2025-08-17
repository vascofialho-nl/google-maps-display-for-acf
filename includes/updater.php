<?php
/* ============================================================================================================== */
/* Ensure WordPress environment — exit if accessed directly
/* ============================================================================================================== */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/* ============================================================================================================== */


/* ============================================================================================================== */
/* Load plugin data to extract Text Domain
/* ============================================================================================================== */
if ( ! function_exists( 'get_plugin_data' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

$vjfnl_plugin_data   = get_plugin_data( __DIR__ . '/' . basename( __FILE__ ) );
$vjfnl_textdomain    = ! empty( $vjfnl_plugin_data['TextDomain'] ) ? $vjfnl_plugin_data['TextDomain'] : basename( __DIR__ );
$vjfnl_prefix        = strtolower( preg_replace( '/[^a-z0-9_]/i', '_', $vjfnl_textdomain ) );
$prefix_const        = strtoupper( $vjfnl_prefix );
/* ============================================================================================================== */


/* ============================================================================================================== */
/* Setup: Configuration constants for the GitHub plugin updater
/*
/* INSTRUCTIONS:
*   1. Change the values below to match your plugin and repo details.
*   2. Keep this section at the top for easy configuration in future projects.
*   3. If you have a private repo, set *_GITHUB_TOKEN to your GitHub Personal Access Token.
*      (Leave empty '' if repo is public.)
/* ============================================================================================================== */

define( $vjfnl_prefix_const . '_PLUGIN_MAP_NAME',        'vjfnl-acf-map-display' );          // Plugin folder name
define( $vjfnl_prefix_const . '_PLUGIN_FILE_NAME',       'vjfnl-acf-map-display.php' );      // Main plugin file name
define( $vjfnl_prefix_const . '_PLUGIN_NAME',            'Google Maps Display for ACF' );    // Human-readable plugin name

define( $vjfnl_prefix_const . '_PACKAGE_FILE',           'vjfnl-acf-map-display.zip' );      // Release zip file name

define( $vjfnl_prefix_const . '_PLUGIN_FILE',            plugin_dir_path( __FILE__ ) . constant( $vjfnl_prefix_const . '_PLUGIN_FILE_NAME' ) );
define( $vjfnl_prefix_const . '_PLUGIN_SLUG',            constant( $vjfnl_prefix_const . '_PLUGIN_MAP_NAME' ) . '/' . constant( $vjfnl_prefix_const . '_PLUGIN_FILE_NAME' ) );

define( $vjfnl_prefix_const . '_GITHUB_REPOSITORY_NAME', 'google-maps-display-for-acf' );   // GitHub repo name
define( $vjfnl_prefix_const . '_GITHUB_USER',            'vascofialho-nl' );                 // GitHub username or org
define( $vjfnl_prefix_const . '_GITHUB_API_URL',         'https://api.github.com/repos/' . constant( $vjfnl_prefix_const . '_GITHUB_USER' ) . '/' . constant( $vjfnl_prefix_const . '_GITHUB_REPOSITORY_NAME' ) . '/releases/latest' );
define( $vjfnl_prefix_const . '_GITHUB_REPO_URL',        'https://github.com/' . constant( $vjfnl_prefix_const . '_GITHUB_USER' ) . '/' . constant( $vjfnl_prefix_const . '_GITHUB_REPOSITORY_NAME' ) );
define( $vjfnl_prefix_const . '_GITHUB_TOKEN',           '' ); // Optional: GitHub Personal Access Token for private repos


/* ============================================================================================================== */
/* Helper variables — for easier usage in updater code
/* ============================================================================================================== */
$PLUGIN_MAP_NAME        = constant( $prefix_const . '_PLUGIN_MAP_NAME' );
$PLUGIN_FILE_NAME       = constant( $prefix_const . '_PLUGIN_FILE_NAME' );
$PLUGIN_NAME            = constant( $prefix_const . '_PLUGIN_NAME' );
$PACKAGE_FILE           = constant( $prefix_const . '_PACKAGE_FILE' );
$PLUGIN_SLUG            = constant( $prefix_const . '_PLUGIN_SLUG' );
$GITHUB_REPOSITORY_NAME = constant( $prefix_const . '_GITHUB_REPOSITORY_NAME' );
$GITHUB_USER            = constant( $prefix_const . '_GITHUB_USER' );
$GITHUB_API_URL         = constant( $prefix_const . '_GITHUB_API_URL' );
$GITHUB_REPO_URL        = constant( $prefix_const . '_GITHUB_REPO_URL' );
$GITHUB_TOKEN           = constant( $prefix_const . '_GITHUB_TOKEN' );
/* ============================================================================================================== */


/* ============================================================================================================== */
/* Hook into update checks and plugin info
/* ============================================================================================================== */
add_filter( 'pre_set_site_transient_update_plugins', 'generic_plugin_check_for_update' );
add_filter( 'plugins_api', 'generic_plugin_info', 20, 3 );
/* ============================================================================================================== */


/* ============================================================================================================== */
/* Check if GitHub repo exists before proceeding with updater
/* ============================================================================================================== */
function generic_plugin_github_repo_exists() {
	global $GITHUB_API_URL, $GITHUB_TOKEN;

	$args = array(
		'headers' => array(
			'User-Agent' => 'WordPress/' . get_bloginfo( 'version' )
		),
		'timeout' => 5
	);

	if ( ! empty( $GITHUB_TOKEN ) ) {
		$args['headers']['Authorization'] = 'token ' . $GITHUB_TOKEN;
	}

	$response = wp_remote_get( $GITHUB_API_URL, $args );

	if ( is_wp_error( $response ) ) {
		return false;
	}

	return wp_remote_retrieve_response_code( $response ) === 200;
}
/* ============================================================================================================== */


/* ============================================================================================================== */
/* Get current plugin version from plugin header
/* ============================================================================================================== */
function generic_plugin_get_local_version() {
	global $PLUGIN_SLUG;

	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	$data = get_plugin_data( WP_PLUGIN_DIR . '/' . $PLUGIN_SLUG );
	return $data['Version'];
}
/* ============================================================================================================== */


/* ============================================================================================================== */
/* Get latest version tag from GitHub Releases API
/* ============================================================================================================== */
function generic_plugin_get_latest_github_release() {
	global $GITHUB_API_URL, $GITHUB_TOKEN;

	$args = array(
		'headers' => array(
			'User-Agent' => 'WordPress/' . get_bloginfo( 'version' )
		)
	);

	if ( ! empty( $GITHUB_TOKEN ) ) {
		$args['headers']['Authorization'] = 'token ' . $GITHUB_TOKEN;
	}

	$response = wp_remote_get( $GITHUB_API_URL, $args );

	if ( is_wp_error( $response ) ) {
		return false;
	}

	$data = json_decode( wp_remote_retrieve_body( $response ) );

	if ( isset( $data->tag_name ) ) {
		return ltrim( $data->tag_name, 'v' ); // Remove "v" prefix if present
	}

	return false;
}
/* ============================================================================================================== */


/* ============================================================================================================== */
/* Add update data to plugin transient
/* ============================================================================================================== */
function generic_plugin_check_for_update( $transient ) {
	global $PLUGIN_SLUG, $GITHUB_REPO_URL, $PACKAGE_FILE;

	if ( empty( $transient->checked ) ) {
		return $transient;
	}

	if ( ! generic_plugin_github_repo_exists() ) {
		return $transient;
	}

	$current_version = generic_plugin_get_local_version();
	$remote_version  = generic_plugin_get_latest_github_release();

	if ( ! $remote_version || version_compare( $current_version, $remote_version, '>=' ) ) {
		return $transient;
	}

	$update_url = $GITHUB_REPO_URL . '/releases/download/' . $remote_version . '/' . $PACKAGE_FILE;

	$transient->response[ $PLUGIN_SLUG ] = (object) array(
		'slug'        => $PLUGIN_SLUG,
		'plugin'      => $PLUGIN_SLUG,
		'new_version' => $remote_version,
		'url'         => $GITHUB_REPO_URL,
		'package'     => $update_url,
	);

	return $transient;
}
/* ============================================================================================================== */


/* ============================================================================================================== */
/* Provide plugin details for the updater popup
/* ============================================================================================================== */
function generic_plugin_info( $res, $action, $args ) {
	global $PLUGIN_NAME, $GITHUB_REPOSITORY_NAME, $GITHUB_REPO_URL, $PACKAGE_FILE;

	if ( $action !== 'plugin_information' ) return $res;
	if ( $args->slug !== $GITHUB_REPOSITORY_NAME ) return $res;

	if ( ! generic_plugin_github_repo_exists() ) return $res;

	$remote_version = generic_plugin_get_latest_github_release();
	if ( ! $remote_version ) return $res;

	$update_url = $GITHUB_REPO_URL . '/releases/download/' . $remote_version . '/' . $PACKAGE_FILE;

	$res = (object) array(
		'name'           => $PLUGIN_NAME,
		'slug'           => $GITHUB_REPOSITORY_NAME,
		'version'        => $remote_version,
		'author'         => '<a href="https://example.com">Author Name</a>',
		'homepage'       => $GITHUB_REPO_URL,
		'download_link'  => $update_url,
		'trunk'          => $update_url,
		'sections'       => array(
			'description' => 'Plugin description goes here.',
			'changelog'   => '<p><strong>' . esc_html( $remote_version ) . '</strong> – See GitHub for details.</p>',
		),
	);

	return $res;
}
/* ============================================================================================================== */
