<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://orionorigin.com
 * @since      1.0.0
 *
 * @package    Orion_Task_Manager
 * @subpackage Orion_Task_Manager/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Orion_Task_Manager
 * @subpackage Orion_Task_Manager/public
 * @author     Charles GBOYOU - Orion <freelance@orionorigin.com>
 */
class Orion_Task_Manager_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Orion_Task_Manager_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Orion_Task_Manager_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/orion-task-manager-public.css', array(), $this->version, 'all' );
		wp_enqueue_style( 'bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Orion_Task_Manager_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Orion_Task_Manager_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( 'task_manager', plugin_dir_url( __FILE__ ) . 'js/orion-task-manager-public.js', array( 'jquery' ), $this->version, true );
		
		wp_enqueue_script( 'bootstrap1', 'https://code.jquery.com/jquery-3.2.1.min.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( 'bootstrap2', 'https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( 'bootstrap3', 'https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js', array( 'jquery' ), $this->version, false );
		// Envoyer une variable de PHP à JS proprement
  		wp_localize_script( 'task_manager', 'task_manager', [ 'ajaxurl' => admin_url( 'admin-ajax.php' ) ] );

	}

}
