<?php
/**
 * Install / dependency checks for Google Maps Display for ACF
 *
 * This file handles:
 * - Checking if ACF plugin is active on activation
 * - Preventing plugin activation if ACF is not active
 * - Automatically deactivating this plugin if ACF is deactivated later
 *
 * @package Google_Maps_Display_for_ACF
 */

// Prevent direct access for security
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Check if Advanced Custom Fields (ACF) plugin is active.
 *
 * Uses WordPress `is_plugin_active()` function to check both free and Pro versions.
 *
 * @return bool True if ACF is active, false otherwise.
 */
function vjfnl_acf_map_display_is_acf_active() {
    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

    return is_plugin_active( 'advanced-custom-fields/acf.php' )
        || is_plugin_active( 'advanced-custom-fields-pro/acf.php' );
}

/**
 * Deactivate this plugin programmatically.
 *
 * Uses `plugin_basename()` with main plugin file path to ensure correct plugin is deactivated.
 *
 * @return void
 */
function vjfnl_acf_map_display_self_deactivate() {
    deactivate_plugins( plugin_basename( dirname( __DIR__ ) . '/vjfnl-acf-map-display.php' ) );
}

/**
 * Runs on plugin activation.
 *
 * Checks if ACF is active.
 * If not active, immediately deactivates this plugin and stops activation with an error message.
 *
 * This prevents fatal errors due to missing ACF dependency.
 *
 * @return void
 */
function vjfnl_acf_map_display_activation_check() {
    if ( ! vjfnl_acf_map_display_is_acf_active() ) {
        vjfnl_acf_map_display_self_deactivate();

        wp_die(
            '<p><strong>Google Maps Display for ACF</strong> requires the <a href="https://wordpress.org/plugins/advanced-custom-fields/" target="_blank" rel="noopener noreferrer">Advanced Custom Fields (ACF)</a> plugin to be installed and activated.</p>' .
            '<p>Please install and activate ACF before activating this plugin.</p>' .
            '<p><a href="' . admin_url( 'plugins.php' ) . '">&laquo; Return to Plugins page</a></p>',
            'Plugin Activation Error',
            array( 'back_link' => true )
        );
    }
}
register_activation_hook(
    dirname( __DIR__ ) . '/vjfnl-acf-map-display.php',
    'vjfnl_acf_map_display_activation_check'
);

/**
 * Detects when ACF plugin is deactivated while this plugin remains active.
 *
 * Hooks into `deactivated_plugin` action.
 * If ACF (free or Pro) is deactivated, sets a transient flag to deactivate this plugin on the next admin page load.
 *
 * @param string $plugin Plugin path relative to plugins directory being deactivated.
 * @param bool $network_deactivating Whether the plugin is network deactivated (multisite).
 * @return void
 */
function vjfnl_acf_map_display_flag_if_acf_deactivated( $plugin, $network_deactivating ) {
    $acf_plugins = array(
        'advanced-custom-fields/acf.php',
        'advanced-custom-fields-pro/acf.php'
    );

    if ( in_array( $plugin, $acf_plugins, true ) ) {
        // Set a transient flag to trigger plugin deactivation on next admin page load
        set_transient( 'vjfnl_acf_map_display_deactivate_next_load', true, 60 );
    }
}
add_action( 'deactivated_plugin', 'vjfnl_acf_map_display_flag_if_acf_deactivated', 10, 2 );

/**
 * On admin init, checks if the deactivation flag is set and deactivates this plugin.
 *
 * This avoids trying to deactivate a plugin during the `deactivated_plugin` hook,
 * which can cause unexpected behavior.
 *
 * @return void
 */
function vjfnl_acf_map_display_check_deactivation_flag() {
    if ( get_transient( 'vjfnl_acf_map_display_deactivate_next_load' ) ) {
        delete_transient( 'vjfnl_acf_map_display_deactivate_next_load' );
        vjfnl_acf_map_display_self_deactivate();
    }
}
add_action( 'admin_init', 'vjfnl_acf_map_display_check_deactivation_flag' );
