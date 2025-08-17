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

$vjfnl_plugin_data   = get_plugin_data( __DIR__ . '/' . basename( __FILE__ ) );
$vjfnl_textdomain    = ! empty( $vjfnl_plugin_data['TextDomain'] ) ? $vjfnl_plugin_data['TextDomain'] : basename( __DIR__ );
$vjfnl_prefix        = strtolower( preg_replace( '/[^a-z0-9_]/i', '_', $vjfnl_textdomain ) );
$vjfnl_prefix_const  = strtoupper( $vjfnl_prefix );

/* ============================================================================================================== */
/* Setup: Configuration constants for the GitHub plugin updater
/* ============================================================================================================== */
define( $prefix_const . '_PLUGIN_MAP_NAME',        'vjfnl-acf-map-display' );          // Plugin folder name
define( $prefix_const . '_PLUGIN_FILE_NAME',       'vjfnl-acf-map-display.php' );      // Main plugin file name
define( $prefix_const . '_PLUGIN_NAME',            'Google Maps Display for ACF' );    // Human-readable plugin name

define( $prefix_const . '_PACKAGE_FILE',           'vjfnl-acf-map-display.zip' );      // Release zip file name

define( $prefix_const . '_PLUGIN_SLUG',            constant( $prefix_const . '_PLUGIN_MAP_NAME' ) . '/' . constant( $prefix_const . '_PLUGIN_FILE_NAME' ) );

define( $prefix_const . '_GITHUB_REPOSITORY_NAME', 'google-maps-display-for-acf' );   // GitHub repo name
define( $prefix_const . '_GITHUB_USER',            'vascofialho-nl' );                 // GitHub username or org
define( $prefix_const . '_GITHUB_API_URL',         'https://api.github.com/repos/' . constant( $prefix_const . '_GITHUB_USER' ) . '/' . constant( $prefix_const . '_GITHUB_REPOSITORY_NAME' ) . '/releases/latest' );
define( $prefix_const . '_GITHUB_REPO_URL',        'https://github.com/' . constant( $prefix_const . '_GITHUB_USER' ) . '/' . constant( $prefix_const . '_GITHUB_REPOSITORY_NAME' ) );
define( $prefix_const . '_GITHUB_TOKEN',           '' ); // Optional: GitHub Personal Access Token for private repos

/* ============================================================================================================== */
/* Dynamic function naming to prevent collisions
/* ============================================================================================================== */
$vjfnl_func_repo_exists  = $vjfnl_prefix . '_github_repo_exists';
$vjfnl_func_local_ver    = $vjfnl_prefix . '_get_local_version';
$vjfnl_func_latest_rel   = $vjfnl_prefix . '_get_latest_github_release';
$vjfnl_func_check_update = $vjfnl_prefix . '_check_for_plugin_update';
$vjfnl_func_plugin_info  = $vjfnl_prefix . '_plugin_info';

/* ============================================================================================================== */
/* Define functions dynamically
/* ============================================================================================================== */
if ( ! function_exists( $vjfnl_func_repo_exists ) ) {
	eval('
	function ' . $vjfnl_func_repo_exists . '() {
		$args = array(
			"headers" => array(
				"User-Agent" => "WordPress/" . get_bloginfo( "version" )
			),
			"timeout" => 5
		);

		if ( ! empty( ' . $vjfnl_prefix_const . '_GITHUB_TOKEN ) ) {
			$args["headers"]["Authorization"] = "token " . ' . $vjfnl_prefix_const . '_GITHUB_TOKEN;
		}

		$response = wp_remote_get( ' . $vjfnl_prefix_const . '_GITHUB_API_URL, $args );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		return wp_remote_retrieve_response_code( $response ) === 200;
	}
	');
}

if ( ! function_exists( $vjfnl_func_local_ver ) ) {
	eval('
	function ' . $vjfnl_func_local_ver . '() {
		require_once ABSPATH . "wp-admin/includes/plugin.php";
		$data = get_plugin_data( WP_PLUGIN_DIR . "/" . ' . $vjfnl_prefix_const . '_PLUGIN_SLUG );
		return $data["Version"];
	}
	');
}

if ( ! function_exists( $vjfnl_func_latest_rel ) ) {
	eval('
	function ' . $vjfnl_func_latest_rel . '() {
		$args = array(
			"headers" => array(
				"User-Agent" => "WordPress/" . get_bloginfo( "version" )
			)
		);

		if ( ! empty( ' . $vjfnl_prefix_const . '_GITHUB_TOKEN ) ) {
			$args["headers"]["Authorization"] = "token " . ' . $vjfnl_prefix_const . '_GITHUB_TOKEN;
		}

		$response = wp_remote_get( ' . $vjfnl_prefix_const . '_GITHUB_API_URL, $args );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ) );

		if ( isset( $data->tag_name ) ) {
			return ltrim( $data->tag_name, "v" );
		}

		return false;
	}
	');
}

if ( ! function_exists( $vjfnl_func_check_update ) ) {
	eval('
	function ' . $vjfnl_func_check_update . '( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		if ( ! call_user_func("' . $vjfnl_func_repo_exists . '") ) {
			return $transient;
		}

		$current_version = call_user_func("' . $vjfnl_func_local_ver . '");
		$remote_version  = call_user_func("' . $vjfnl_func_latest_rel . '");

		if ( ! $remote_version || version_compare( $current_version, $remote_version, ">=" ) ) {
			return $transient;
		}

		$update_url = ' . $vjfnl_prefix_const . '_GITHUB_REPO_URL . "/releases/download/" . $remote_version . "/" . ' . $vjfnl_prefix_const . '_PACKAGE_FILE;

		$transient->response[ ' . $vjfnl_prefix_const . '_PLUGIN_SLUG ] = (object) array(
			"slug"        => ' . $vjfnl_prefix_const . '_GITHUB_REPOSITORY_NAME,
			"plugin"      => ' . $vjfnl_prefix_const . '_PLUGIN_SLUG,
			"new_version" => $remote_version,
			"url"         => ' . $vjfnl_prefix_const . '_GITHUB_REPO_URL,
			"package"     => $update_url,
		);

		return $transient;
	}
	');
}

if ( ! function_exists( $vjfnl_func_plugin_info ) ) {
	eval('
	function ' . $vjfnl_func_plugin_info . '( $res, $action, $args ) {
		if ( $action !== "plugin_information" ) return $res;
		if ( $args->slug !== ' . $vjfnl_prefix_const . '_GITHUB_REPOSITORY_NAME ) return $res;

		if ( ! call_user_func("' . $vjfnl_func_repo_exists . '") ) return $res;

		$remote_version = call_user_func("' . $vjfnl_func_latest_rel . '");
		if ( ! $remote_version ) return $res;

		$update_url = ' . $vjfnl_prefix_const . '_GITHUB_REPO_URL . "/releases/download/" . $remote_version . "/" . ' . $vjfnl_prefix_const . '_PACKAGE_FILE;

		$res = (object) array(
			"name"           => ' . $vjfnl_prefix_const . '_PLUGIN_NAME,
			"slug"           => ' . $vjfnl_prefix_const . '_GITHUB_REPOSITORY_NAME,
			"version"        => $remote_version,
			"author"         => "<a href=\"https://vascofialho.nl\">vascofmdc</a>",
			"homepage"       => ' . $vjfnl_prefix_const . '_GITHUB_REPO_URL,
			"download_link"  => $update_url,
			"trunk"          => $update_url,
			"sections"       => array(
				"description" => "Describe what the current plugin does. Generally you can copy this from the plugin description header.",
				"changelog"   => "<p><strong>" . esc_html( $remote_version ) . "</strong> – See GitHub for details.</p>",
			),
		);

		return $res;
	}
	');
}

/* ============================================================================================================== */
/* Hook into update checks and plugin info
/* ============================================================================================================== */
add_filter( 'pre_set_site_transient_update_plugins', $vjfnl_func_check_update );
add_filter( 'plugins_api', $vjfnl_func_plugin_info, 20, 3 );
