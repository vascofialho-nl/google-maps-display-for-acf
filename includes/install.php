<?php
// Prevent direct access
if (!defined('WPINC')) {
    die;
}

/**
 * Check if ACF plugin is active
 * @return bool
 */
function vjfnl_acf_map_display_is_acf_active() {
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
    return is_plugin_active('advanced-custom-fields/acf.php') || is_plugin_active('advanced-custom-fields-pro/acf.php');
}

/**
 * Plugin activation callback: prevent activation if ACF not active
 */
function vjfnl_acf_map_display_activation_check() {
    if (!vjfnl_acf_map_display_is_acf_active()) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            '<p><strong>ACF Map Display</strong> requires the <a href="https://wordpress.org/plugins/advanced-custom-fields/" target="_blank">Advanced Custom Fields (ACF)</a> plugin to be installed and active.</p>' .
            '<p>Please install and activate ACF first before activating this plugin.</p>' .
            '<p><a href="' . admin_url('plugins.php') . '">&laquo; Return to Plugins page</a></p>',
            'Plugin Activation Error',
            array('back_link' => true)
        );
    }
}
register_activation_hook(__FILE__, 'vjfnl_acf_map_display_activation_check');

/**
 * Admin notice shown if ACF is not active
 */
function vjfnl_acf_map_display_admin_notice() {
    if (!vjfnl_acf_map_display_is_acf_active()) {
        ?>
        <div class="notice notice-error is-dismissible">
            <p><strong>ACF Map Display plugin requires Advanced Custom Fields (ACF) to be installed and activated.</strong></p>
            <p>Please install and activate <a href="https://wordpress.org/plugins/advanced-custom-fields/" target="_blank">ACF</a> before using this plugin.</p>
        </div>
        <?php
    }
}
add_action('admin_notices', 'vjfnl_acf_map_display_admin_notice');
