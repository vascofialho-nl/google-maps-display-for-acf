<?php

/* ============================================================================================================== */
/* Ensure WordPress environment — exit if accessed directly
/* ============================================================================================================== */
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}
/* ============================================================================================================== */


/* ============================================================================================================== */
/* Setup: Configuration constants for the GitHub plugin updater
/*
/* INSTRUCTIONS:
*   1. Change the constants below to match your plugin and repo details.
*   2. Keep this section at the top for easy configuration in future projects.
*   3. If you have a private repo, set VASCOFIALHONL_GITHUB_TOKEN to your GitHub Personal Access Token.
*      (Leave empty '' if repo is public.)
/* ============================================================================================================== */
	define( 'VASCOFIALHONL_PLUGIN_MAP_NAME',         'vjfnl-acf-map-display' );          // Plugin folder name
	define( 'VASCOFIALHONL_PLUGIN_FILE_NAME',        'vjfnl-acf-map-display.php' );      // Main plugin file name
	define( 'VASCOFIALHONL_PLUGIN_NAME',             'Google Maps Display for ACF' );    // Human-readable plugin name

	define( 'VASCOFIALHONL_PACKAGE_FILE',            'vjfnl-acf-map-display.zip' ); // Release zip file name

	define( 'VASCOFIALHONL_PLUGIN_SLUG',             VASCOFIALHONL_PLUGIN_MAP_NAME . '/' . VASCOFIALHONL_PLUGIN_FILE_NAME );

	define( 'VASCOFIALHONL_GITHUB_REPOSITORY_NAME', 'google-maps-display-for-acf' );   // GitHub repo name
	define( 'VASCOFIALHONL_GITHUB_USER',             'vascofialho-nl' );                 // GitHub username or org
	define( 'VASCOFIALHONL_GITHUB_API_URL',          'https://api.github.com/repos/' . VASCOFIALHONL_GITHUB_USER . '/' . VASCOFIALHONL_GITHUB_REPOSITORY_NAME . '/releases/latest' );
	define( 'VASCOFIALHONL_GITHUB_REPO_URL',         'https://github.com/' . VASCOFIALHONL_GITHUB_USER . '/' . VASCOFIALHONL_GITHUB_REPOSITORY_NAME );
	define( 'VASCOFIALHONL_GITHUB_TOKEN',            '' ); // Optional: GitHub Personal Access Token for private repos
/* ============================================================================================================== */


/* ============================================================================================================== */
/* Hook into update checks and plugin info
/* ============================================================================================================== */
	add_filter( 'pre_set_site_transient_update_plugins', 'vascofialhonl_check_for_plugin_update' );
	add_filter( 'plugins_api', 'vascofialhonl_plugin_info', 20, 3 );
/* ============================================================================================================== */


/* ============================================================================================================== */
/* Check if GitHub repo exists before proceeding with updater
/* ============================================================================================================== */
	function vascofialhonl_github_repo_exists() {
		$args = array(
			'headers' => array(
				'User-Agent' => 'WordPress/' . get_bloginfo( 'version' )
			),
			'timeout' => 5
		);

		if ( ! empty( VASCOFIALHONL_GITHUB_TOKEN ) ) {
			$args['headers']['Authorization'] = 'token ' . VASCOFIALHONL_GITHUB_TOKEN;
		}

		$response = wp_remote_get( VASCOFIALHONL_GITHUB_API_URL, $args );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		return wp_remote_retrieve_response_code( $response ) === 200;
	}
/* ============================================================================================================== */


/* ============================================================================================================== */
/* Get current plugin version from plugin header
/* ============================================================================================================== */
	function vascofialhonl_get_local_version() {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		$data = get_plugin_data( WP_PLUGIN_DIR . '/' . VASCOFIALHONL_PLUGIN_SLUG );
		return $data['Version'];
	}
/* ============================================================================================================== */


/* ============================================================================================================== */
/* Get latest version tag from GitHub Releases API
/* ============================================================================================================== */
	function vascofialhonl_get_latest_github_release() {
		$args = array(
			'headers' => array(
				'User-Agent' => 'WordPress/' . get_bloginfo( 'version' )
			)
		);

		if ( ! empty( VASCOFIALHONL_GITHUB_TOKEN ) ) {
			$args['headers']['Authorization'] = 'token ' . VASCOFIALHONL_GITHUB_TOKEN;
		}

		$response = wp_remote_get( VASCOFIALHONL_GITHUB_API_URL, $args );

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
	function vascofialhonl_check_for_plugin_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		// Skip if repo does not exist or is inaccessible
		if ( ! vascofialhonl_github_repo_exists() ) {
			return $transient;
		}

		$current_version = vascofialhonl_get_local_version();
		$remote_version  = vascofialhonl_get_latest_github_release();

		if ( ! $remote_version || version_compare( $current_version, $remote_version, '>=' ) ) {
			return $transient;
		}

		$update_url = VASCOFIALHONL_GITHUB_REPO_URL . '/releases/download/' . $remote_version . '/' . VASCOFIALHONL_PACKAGE_FILE;

		$transient->response[ VASCOFIALHONL_PLUGIN_SLUG ] = (object) array(
			'slug'        => VASCOFIALHONL_GITHUB_REPOSITORY_NAME,
			'plugin'      => VASCOFIALHONL_PLUGIN_SLUG,
			'new_version' => $remote_version,
			'url'         => VASCOFIALHONL_GITHUB_REPO_URL,
			'package'     => $update_url,
		);

		return $transient;
	}
/* ============================================================================================================== */


/* ============================================================================================================== */
/* Provide plugin details for the updater popup
/* ============================================================================================================== */
	function vascofialhonl_plugin_info( $res, $action, $args ) {
		if ( $action !== 'plugin_information' ) return $res;
		if ( $args->slug !== VASCOFIALHONL_GITHUB_REPOSITORY_NAME ) return $res;

		// Skip if repo does not exist
		if ( ! vascofialhonl_github_repo_exists() ) return $res;

		$remote_version = vascofialhonl_get_latest_github_release();
		if ( ! $remote_version ) return $res;

		$update_url = VASCOFIALHONL_GITHUB_REPO_URL . '/releases/download/' . $remote_version . '/' . VASCOFIALHONL_PACKAGE_FILE;

		$res = (object) array(
			'name'           => VASCOFIALHONL_PLUGIN_NAME, // Change to your plugin's name
			'slug'           => VASCOFIALHONL_GITHUB_REPOSITORY_NAME,
			'version'        => $remote_version,
			'author'         => '<a href="https://vascofialho.nl">vascofmdc</a>',
			'homepage'       => VASCOFIALHONL_GITHUB_REPO_URL,
			'download_link'  => $update_url,
			'trunk'          => $update_url,
			'sections'       => array(
				'description' => 'Describe what the current plugin does. Generally you can copy this from the plugin description header.',
				'changelog'   => '<p><strong>' . esc_html( $remote_version ) . '</strong> – See GitHub for details.</p>',
			),
		);

		return $res;
	}
/* ============================================================================================================== */
