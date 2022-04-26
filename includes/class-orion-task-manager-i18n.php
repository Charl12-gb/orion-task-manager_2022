<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://orionorigin.com
 * @since      1.0.0
 *
 * @package    Orion_Task_Manager
 * @subpackage Orion_Task_Manager/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Orion_Task_Manager
 * @subpackage Orion_Task_Manager/includes
 * @author     Charles GBOYOU - Orion <freelance@orionorigin.com>
 */
class Orion_Task_Manager_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'orion-task-manager',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
