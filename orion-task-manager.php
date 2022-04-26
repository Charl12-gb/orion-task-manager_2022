<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://orionorigin.com
 * @since             1.0.0
 * @package           Orion_Task_Manager
 *
 * @wordpress-plugin
 * Plugin Name:       Task Manager
 * Plugin URI:        https://orionorigin.com
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            Charles GBOYOU - Orion
 * Author URI:        https://orionorigin.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       orion-task-manager
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'ORION_TASK_MANAGER_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-orion-task-manager-activator.php
 */
function activate_orion_task_manager() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-orion-task-manager-activator.php';
	Orion_Task_Manager_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-orion-task-manager-deactivator.php
 */
function deactivate_orion_task_manager() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-orion-task-manager-deactivator.php';
	Orion_Task_Manager_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_orion_task_manager' );
register_deactivation_hook( __FILE__, 'deactivate_orion_task_manager' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-orion-task-manager.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_orion_task_manager() {

	$plugin = new Orion_Task_Manager();
	$plugin->run();

}

/**
 * Loads all the necessary files needed by the plugin
 */
function load_resources() {

    require_once plugin_dir_path( __FILE__ ) . '/includes/requires.php';
    require_once plugin_dir_path( __FILE__ ) . 'includes/function.php';
    require_once plugin_dir_path( __FILE__ ) . 'includes/asana/asana.php';
}

load_resources();
run_orion_task_manager();
