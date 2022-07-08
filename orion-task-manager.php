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
 * Description:       This plugin allows you to more effectively manage the performance of your employees through the tasks they perform each month. It allows project managers to create and manage the creation of tasks more easily.
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
register_activation_hook(__FILE__, 'orion_task_manager_create_db');
register_deactivation_hook(__FILE__, 'deactivate_orion_task_manager');


function orion_task_manager_create_db()
{

	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();
	$table_project = $wpdb->prefix . 'project';
	$table_section = $wpdb->prefix . 'sections';
	$table_task = $wpdb->prefix . 'task';
	$table_subtask = $wpdb->prefix . 'subtask';
	$table_categories = $wpdb->prefix . 'categories';
	$table_objectives = $wpdb->prefix . 'objectives';
	$table_worklog = $wpdb->prefix . 'worklog';
	$table_mail = $wpdb->prefix . 'mails';
	$table_users = $wpdb->prefix . 'users';

	$sql = "CREATE TABLE $table_project(
			id bigint NOT NULL,
			title varchar(255) NOT NULL,
			description text,
			slug varchar(255),
			permalink text NOT NULL,
			project_manager bigint UNSIGNED NULL,
			collaborator varchar(255),
			FOREIGN KEY  (project_manager) REFERENCES $table_users(id),
			PRIMARY KEY  (id)
		);
		CREATE TABLE $table_section(
			id bigint NOT NULL,
			project_id bigint NOT NULL,
			section_name varchar(255) NOT NULL,
			FOREIGN KEY  (project_id) REFERENCES $table_project(id),
			PRIMARY KEY  (id)
		);
		CREATE TABLE $table_categories(
			id bigint NOT NULL,
			categories_key varchar(255) UNIQUE,
			categories_name varchar(255) NOT NULL,
			PRIMARY KEY  (id)
		);
		CREATE TABLE $table_objectives(
			id_objective bigint NOT NULL,
			id_user bigint UNSIGNED NOT NULL,
			id_section bigint NOT NULL,
			month_section varchar(255) NOT NULL,
			year_section varchar(255) NOT NULL,
			duedate_section datetime NOT NULL,
			objective_section text NOT NULL,
			section_permalink text,
			modify_date datetime NOT NULL,
			FOREIGN KEY  (id_user) REFERENCES $table_users(id),
			FOREIGN KEY  (id_section) REFERENCES $table_section(id),
			PRIMARY KEY  (id_objective,id_user,id_section,month_section,year_section)
		);
		CREATE TABLE $table_task(
			id bigint NOT NULL,
			author_id bigint UNSIGNED NOT NULL,
			project_id bigint NOT NULL,
			section_id bigint NULL,
			title varchar(255) NOT NULL,
			permalink_url text NOT NULL,
			type_task varchar(50) NULL,
			categorie varchar(50),
			dependancies bigint,
			description text,
			assigne bigint UNSIGNED,
			duedate datetime,
			created_at datetime NOT NULL,
			FOREIGN KEY  (author_id) REFERENCES $table_users(id),
			FOREIGN KEY  (categorie) REFERENCES $table_categories(categories_key),
			FOREIGN KEY  (section_id) REFERENCES $table_section(id),
			FOREIGN KEY  (assigne) REFERENCES $table_users(id),
			FOREIGN KEY  (project_id) REFERENCES $table_project(id),
			PRIMARY KEY  (id)
		);
		CREATE TABLE $table_subtask(
			id bigint NOT NULL,
			id_task_parent bigint NOT NULL,
			FOREIGN KEY  (id_task_parent) REFERENCES $table_task(id), 
			PRIMARY KEY  (id)
		);
		CREATE TABLE $table_mail(
			id bigint AUTO_INCREMENT,
			type_task varchar(255) UNIQUE,
			subject varchar(255),
			content text,
			PRIMARY KEY  (id)
		);
		CREATE TABLE $table_worklog(
			id_task bigint NOT NULL,
			finaly_date datetime,
			status varchar(50),
			evaluation text,
			evaluation_date varchar(20) NULL,
			mail_status varchar(10) NULL,
			FOREIGN KEY  (id_task) REFERENCES $table_task(id), 
			PRIMARY KEY  (id_task)
		)$charset_collate;";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);

	//Période de synchronisation
	$syn = get_option( '_synchronisation_time' );
	if( $syn == null){
		update_option('_synchronisation_time', 'twicedaily');
	}

	//Période d'envoi des mails
	$sent_info = get_option('_report_sent_info');
	if( $sent_info == null ){
		$array = serialize( array( 'email_manager' => '', 'send_date' => 'last_day_month', 'sent_cp' => '') );
		update_option( '_report_sent_info', $array );
	}

	$upload = wp_upload_dir();
    $upload_dir = $upload['basedir'];
    $upload_dir = $upload_dir . '/worklog_evaluation';
    if (! is_dir($upload_dir)) {
       mkdir( $upload_dir, 0700 );
    }
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
