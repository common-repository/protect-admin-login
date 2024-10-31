<?php
/**
 * Plugin Name:  Protect Admin Login
 * Plugin URI: #
 * Description: This plugin allows to add custom URL to login in WordPress backend. It overwrites wp-admin URL and secures website backend.
 * Version: 3.0.0
 * Author: ViitorCloud
 * Author URI: http://viitorcloud.com/
 * Text Domain: protect-admin-login
 *
 * @package protect-admin-login
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

if ( ! defined( 'PROTECT_ADMIN_LOGIN_DIR' ) ) {
	define( 'PROTECT_ADMIN_LOGIN_DIR', __DIR__ ); // plugin dir.
}

if ( ! defined( 'PROTECT_ADMIN_LOGIN_URL' ) ) {
	define( 'PROTECT_ADMIN_LOGIN_URL', plugin_dir_url( __FILE__ ) ); // plugin url.
}
if ( ! defined( 'PROTECT_ADMIN_LOGIN_IMG_URL' ) ) {
	define( 'PROTECT_ADMIN_LOGIN_IMG_URL', PROTECT_ADMIN_LOGIN_URL . '/images' ); // plugin images url.
}
if ( ! defined( 'PROTECT_ADMIN_LOGIN_TEXT_DOMAIN' ) ) {
	define( 'PROTECT_ADMIN_LOGIN_TEXT_DOMAIN', 'protect_admin_login' ); // text domain for doing language translation.
}

/**
 * Load Text Domain
 *
 * This gets the plugin ready for translation.
 *
 * @package Protect Admin Login
 * @since 3.0.0
 */
load_plugin_textdomain( 'protect_admin_login', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
/**
 * Activation hook
 *
 * Register plugin activation hook.
 *
 * @package Protect Admin Login
 *@since 3.0.0
 */
register_activation_hook( __FILE__, 'protect_admin_login_install' );

/**
 * Deactivation hook
 *
 * Register plugin deactivation hook.
 *
 * @package Protect Admin Login
 * @since 3.0.0
 */
register_deactivation_hook( __FILE__, 'protect_admin_login_uninstall' );

/**
 * Plugin Setup Activation hook call back
 *
 * Initial setup of the plugin setting default options
 * and database tables creations.
 *
 * @package Protect Admin Login
 * @since 3.0.0
 */
function protect_admin_login_install() {

	global $wpdb;
}
/**
 * Plugin Setup (On Deactivation)
 *
 * Does the drop tables in the database and
 * delete  plugin options.
 *
 * @package Protect Admin Login
 * @since 3.0.0
 */
function protect_admin_login_uninstall() {

	global $wpdb;
}
/**
 * Plugin Settings
 *
 * Add setting option to plugin page
 *
 * @param array $pr_links The array of links.
 * @return array The updated array of links.
 * @package Protect Admin Login
 * @since 3.0.0
 */
function protect_admin_login_settings_link( $pr_links ) {
	$protect_settings_link = '<a href="options-permalink.php">' . __( 'Settings' ) . '</a>';
	array_push( $pr_links, $protect_settings_link );
	return $pr_links;
}
$protect_plugin = plugin_basename( __FILE__ );

add_filter( "plugin_action_links_$protect_plugin", 'protect_admin_login_settings_link' );

/**
 * Includes
 *
 * Includes all the needed files for plugin
 *
 * @package Protect Admin Login
 * @since 3.0.0
 */

// require_once options file.
require_once PROTECT_ADMIN_LOGIN_DIR . '/change-url-options.php';
