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
if (!defined('WPINC')) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('ORION_TASK_MANAGER_VERSION', '1.0.0');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-orion-task-manager-activator.php
 */
function activate_orion_task_manager()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-orion-task-manager-activator.php';
	Orion_Task_Manager_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-orion-task-manager-deactivator.php
 */
function deactivate_orion_task_manager()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-orion-task-manager-deactivator.php';
	Orion_Task_Manager_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_orion_task_manager');
register_activation_hook(__FILE__, 'my_plugin_create_db');
register_deactivation_hook(__FILE__, 'deactivate_orion_task_manager');


function my_plugin_create_db()
{

	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();
	$table_project = $wpdb->prefix . 'project';
	$table_task = $wpdb->prefix . 'task';
	$table_subtask = $wpdb->prefix . 'subtask';
	$table_users = $wpdb->prefix . 'users';

	$sql = "CREATE TABLE $table_project(
			id bigint NOT NULL,
			title varchar(255) NOT NULL,
			slug varchar(255),
			project_manager bigint UNSIGNED NOT NULL,
			collaborator varchar(255),
			FOREIGN KEY  (project_manager) REFERENCES $table_users(id),
			PRIMARY KEY  (id)
		);
	
		CREATE TABLE $table_task (
			id bigint NOT NULL,
			author_id bigint UNSIGNED NOT NULL,
			project_id bigint NOT NULL,
			title varchar(255) NOT NULL,
			description text,
			assigne bigint NOT NULL,
			duedate datetime NOT NULL,
			valuetemplate text,
			etat varchar(50),
			created_at datetime NOT NULL,
			FOREIGN KEY  (author_id) REFERENCES $table_users(id),
			FOREIGN KEY  (project_id) REFERENCES $table_project(id),
			PRIMARY KEY  (id)
		);
		
		CREATE TABLE $table_subtask(
			id bigint NOT NULL,
			id_task_parent bigint NOT NULL,
			FOREIGN KEY  (id_task_parent) REFERENCES $table_task(id), 
			PRIMARY KEY  (id)
		)$charset_collate;";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);

	add_role( 'developper', 'developper', array( 'read' => true, 'level_0' => true ) );
	add_role( 'tester', 'Tester', array( 'read' => true, 'level_0' => true ) );
	add_role( 'project_manager', 'Project Manager', array( 'read' => true, 'level_0' => true ) );
	add_role( 'macketer', 'Macketer', array( 'read' => true, 'level_0' => true ) );
	add_role( 'supporter', 'Supporter', array( 'read' => true, 'level_0' => true ) );
}

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-orion-task-manager.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_orion_task_manager()
{

	$plugin = new Orion_Task_Manager();
	$plugin->run();
}

/**
 * Loads all the necessary files needed by the plugin
 */
function load_resources()
{

	require_once plugin_dir_path(__FILE__) . '/includes/requires.php';
	require_once plugin_dir_path(__FILE__) . 'includes/function.php';
	require_once plugin_dir_path(__FILE__) . 'includes/asana/asana.php';
}

load_resources();
run_orion_task_manager();
