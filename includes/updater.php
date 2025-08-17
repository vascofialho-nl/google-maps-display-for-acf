<?php
/* ============================================================================================================== */
/* Ensure WordPress environment — exit if accessed directly
/* ============================================================================================================== */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ============================================================================================================== */
/* Load plugin data to extract Text Domain
/* ============================================================================================================== */
if ( ! function_exists( 'get_plugin_data' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

$plugin_data   = get_plugin_data( __DIR__ . '/' . basename( __FILE__ ) );
$textdomain    = ! empty( $plugin_data['TextDomain'] ) ? $plugin_data['TextDomain'] : basename( __DIR__ );
$prefix        = strtolower( preg_replace( '/[^a-z0-9_]/i', '_', $textdomain ) );
$prefix_const  = strtoupper( $prefix );

/* ============================================================================================================== */
/* Configuration constants (update these for your plugin)
/* ============================================================================================================== */
define( $prefix_const . '_PLUGIN_MAP_NAME',        'vjfnl-acf-map-display' );       // Plugin folder name
define( $prefix_const . '_PLUGIN_FILE_NAME',       'vjfnl-acf-map-display.php' );     // Main plugin file
define( $prefix_const . '_PLUGIN_NAME',            'Google Maps Display for ACF' ); // Plugin name

define( $prefix_const . '_PACKAGE_FILE',           'vjfnl-acf-map-display.zip' );       // Release zip file
define( $prefix_const . '_PLUGIN_FILE',            plugin_dir_path( __FILE__ ) . constant( $prefix_const . '_PLUGIN_FILE_NAME' ) );
define( $prefix_const . '_PLUGIN_SLUG',            constant( $prefix_const . '_PLUGIN_MAP_NAME' ) . '/' . constant( $prefix_const . '_PLUGIN_FILE_NAME' ) );

define( $prefix_const . '_GITHUB_REPOSITORY_NAME', 'google-maps-display-for-acf' );        // GitHub repo name
define( $prefix_const . '_GITHUB_USER',            'vascofialho-nl' );         // GitHub user/org
define( $prefix_const . '_GITHUB_API_URL',         'https://api.github.com/repos/' . constant( $prefix_const . '_GITHUB_USER' ) . '/' . constant( $prefix_const . '_GITHUB_REPOSITORY_NAME' ) . '/releases/latest' );
define( $prefix_const . '_GITHUB_REPO_URL',        'https://github.com/' . constant( $prefix_const . '_GITHUB_USER' ) . '/' . constant( $prefix_const . '_GITHUB_REPOSITORY_NAME' ) );
define( $prefix_const . '_GITHUB_TOKEN',           '' ); // Optional: GitHub Personal Access Token for private repos

/* ============================================================================================================== */
/* Helper variables — easier usage in functions
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
/* Dynamic function names
/* ============================================================================================================== */
$func_repo_exists  = $prefix . '_github_repo_exists';
$func_local_ver    = $prefix . '_get_local_version';
$func_latest_rel   = $prefix . '_get_latest_github_release';
$func_check_update = $prefix . '_check_for_update';
$func_plugin_info  = $prefix . '_plugin_info';

/* ============================================================================================================== */
/* Define updater functions
/* ============================================================================================================== */
if ( ! function_exists( $func_repo_exists ) ) {
	function generic_github_repo_exists() {
		global $GITHUB_API_URL, $GITHUB_TOKEN;
		$args = [ 'headers' => ['User-Agent' => 'WordPress/' . get_bloginfo( 'version' )], 'timeout' => 5 ];
		if ( $GITHUB_TOKEN ) $args['headers']['Authorization'] = 'token ' . $GITHUB_TOKEN;
		$response = wp_remote_get( $GITHUB_API_URL, $args );
		return !is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200;
	}
}

if ( ! function_exists( $func_local_ver ) ) {
	function generic_get_local_version() {
		global $PLUGIN_SLUG;
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		$data = get_plugin_data( WP_PLUGIN_DIR . '/' . $PLUGIN_SLUG );
		return $data['Version'];
	}
}

if ( ! function_exists( $func_latest_rel ) ) {
	function generic_get_latest_github_release() {
		global $GITHUB_API_URL, $GITHUB_TOKEN;
		$args = [ 'headers' => ['User-Agent' => 'WordPress/' . get_bloginfo( 'version' )] ];
		if ( $GITHUB_TOKEN ) $args['headers']['Authorization'] = 'token ' . $GITHUB_TOKEN;
		$response = wp_remote_get( $GITHUB_API_URL, $args );
		if ( is_wp_error( $response ) ) return false;
		$data = json_decode( wp_remote_retrieve_body( $response ) );
		return isset( $data->tag_name ) ? ltrim( $data->tag_name, 'v' ) : false;
	}
}

if ( ! function_exists( $func_check_update ) ) {
	function generic_check_for_update( $transient ) {
		global $PLUGIN_SLUG, $GITHUB_REPO_URL, $PACKAGE_FILE;
		if ( empty( $transient->checked ) ) return $transient;
		if ( ! generic_github_repo_exists() ) return $transient;
		$current_version = generic_get_local_version();
		$remote_version  = generic_get_latest_github_release();
		if ( ! $remote_version || version_compare( $current_version, $remote_version, '>=' ) ) return $transient;
		$update_url = $GITHUB_REPO_URL . '/releases/download/' . $remote_version . '/' . $PACKAGE_FILE;
		$transient->response[ $PLUGIN_SLUG ] = (object) [
			'slug'        => $PLUGIN_SLUG,
			'plugin'      => $PLUGIN_SLUG,
			'new_version' => $remote_version,
			'url'         => $GITHUB_REPO_URL,
			'package'     => $update_url,
		];
		return $transient;
	}
}

if ( ! function_exists( $func_plugin_info ) ) {
	function generic_plugin_info( $res, $action, $args ) {
		global $PLUGIN_NAME, $GITHUB_REPOSITORY_NAME, $GITHUB_REPO_URL, $PACKAGE_FILE;
		if ( $action !== 'plugin_information' || $args->slug !== $GITHUB_REPOSITORY_NAME ) return $res;
		if ( ! generic_github_repo_exists() ) return $res;
		$remote_version = generic_get_latest_github_release();
		if ( ! $remote_version ) return $res;
		$update_url = $GITHUB_REPO_URL . '/releases/download/' . $remote_version . '/' . $PACKAGE_FILE;
		$res = (object) [
			'name'          => $PLUGIN_NAME,
			'slug'          => $GITHUB_REPOSITORY_NAME,
			'version'       => $remote_version,
			'author'        => '<a href="#">Plugin Author</a>',
			'homepage'      => $GITHUB_REPO_URL,
			'download_link' => $update_url,
			'trunk'         => $update_url,
			'sections'      => [
				'description' => 'Plugin description goes here.',
				'changelog'   => '<p><strong>' . esc_html( $remote_version ) . '</strong> – See GitHub for details.</p>',
			],
		];
		return $res;
	}
}

/* ============================================================================================================== */
/* Hook updater functions
/* ============================================================================================================== */
add_filter( 'pre_set_site_transient_update_plugins', $func_check_update );
add_filter( 'plugins_api', $func_plugin_info, 20, 3 );
