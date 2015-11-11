<?php
/*
Plugin Name: Uncanny LearnDash Functionality
Version: 1.0
Description: There is a lot
Author: uncannyowl.com
Author URI: uncannyowl.com
Plugin URI: uncannyowl.com
Text Domain: uncanny_learndash_public
Domain Path: /languages
*/

// All Class instance are store in Global Variable $uncanny_learndash_public
global $uncanny_learndash_public;

if ( ! isset( $uncanny_learndash_public ) ) {
    $uncanny_learndash_public = new \stdClass();
}

// Plugins Configurations File
include_once( dirname(__FILE__). '/src/config.php');

// Load all plugin classes(functionality)
include_once( dirname(__FILE__). '/src/boot.php');
$uncanny_learndash_public = \uncanny_learndash_public\Boot::get_instance();

// Add a simple settings link to our page from the plugins list
add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'link_to_plugins_page', 10, 4);

/*
 * @param Array  $actions     An array of plugin action links.
 * @param String $plugin_file Path to the plugin file.
 * @param Array  $plugin_data An array of plugin data.
 * @param String $context     The plugin context. Defaults are 'All', 'Active',
 *                                                             'Inactive', 'Recently Activated', 'Upgrade',
 *                                                             'Must-Use', 'Drop-ins', 'Search'.
 *
 * return Array $actions
 */
function link_to_plugins_page( $actions, $plugin_file, $plugin_data, $context ) {
    array_unshift($actions, '<a href="'.menu_page_url('uo-menu-slug', false).'">'.__('Settings').'</a>');
    return $actions;
}