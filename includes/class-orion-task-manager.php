<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://orionorigin.com
 * @since      1.0.0
 *
 * @package    Orion_Task_Manager
 * @subpackage Orion_Task_Manager/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Orion_Task_Manager
 * @subpackage Orion_Task_Manager/includes
 * @author     Charles GBOYOU - Orion <freelance@orionorigin.com>
 */
class Orion_Task_Manager {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Orion_Task_Manager_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'ORION_TASK_MANAGER_VERSION' ) ) {
			$this->version = ORION_TASK_MANAGER_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'orion-task-manager';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Orion_Task_Manager_Loader. Orchestrates the hooks of the plugin.
	 * - Orion_Task_Manager_i18n. Defines internationalization functionality.
	 * - Orion_Task_Manager_Admin. Defines all hooks for the admin area.
	 * - Orion_Task_Manager_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-orion-task-manager-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-orion-task-manager-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-orion-task-manager-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-orion-task-manager-public.php';

		$this->loader = new Orion_Task_Manager_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Orion_Task_Manager_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Orion_Task_Manager_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Orion_Task_Manager_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		
		$this->loader->add_action( 'admin_menu', 'Task_Manager_Builder', 'add_menu_Task_Table_List_page' );
		
		$this->loader->add_action('wp', 'Task_Manager_Builder', 'login_redirect');
		$this->loader->add_action('template_redirect', 'Task_Manager_Builder', '_taitement_form');
		$this->loader->add_action('wp_ajax_nopriv_create_new_projet', 'Task_Manager_Builder', 'create_new_projet_');
		$this->loader->add_action('wp_ajax_create_new_projet', 'Task_Manager_Builder', 'create_new_projet_');
		$this->loader->add_action('wp_ajax_nopriv_create_template', 'Task_Manager_Builder', 'create_template_');
		$this->loader->add_action('wp_ajax_create_template',  'Task_Manager_Builder','create_template_');
		$this->loader->add_action('wp_ajax_nopriv_get_template_choose', 'Task_Manager_Builder', 'get_template_choose_');
		$this->loader->add_action('wp_ajax_get_template_choose', 'Task_Manager_Builder', 'get_template_choose_');
		$this->loader->add_action('wp_ajax_nopriv_sent_worklog_mail', 'Task_Manager_Builder', 'sent_worklog_mail_');
		$this->loader->add_action('wp_ajax_sent_worklog_mail', 'Task_Manager_Builder', 'sent_worklog_mail_');

		add_shortcode('orion_task', 'orion_task_shortcode');
		add_shortcode('task_evaluation', 'orion_task_evaluation_shortcode');
		
		
		add_action('wp_ajax_nopriv_get_user_role', 'settings_function');
		add_action('wp_ajax_get_user_role', 'settings_function');
		
		add_action('wp_ajax_nopriv_get_option_add', 'settings_function');
		add_action('wp_ajax_get_option_add', 'settings_function');
		
		add_action('wp_ajax_nopriv_get_option_add_template', 'settings_function');
		add_action('wp_ajax_get_option_add_template', 'settings_function');
		
		add_action('wp_ajax_nopriv_get_first_form', 'settings_function');
		add_action('wp_ajax_get_first_form', 'settings_function');
		
		add_action('wp_ajax_create_new_task', 'settings_function');
		add_action('wp_ajax_nopriv_create_new_task', 'settings_function');
		
		add_action('wp_ajax_get_template_card', 'settings_function');
		add_action('wp_ajax_nopriv_get_template_card', 'settings_function');
		
		add_action('wp_ajax_delete_template_', 'settings_function');
		add_action('wp_ajax_nopriv_delete_template_', 'settings_function');
		
		add_action('wp_ajax_update_template', 'settings_function');
		add_action('wp_ajax_nopriv_update_template', 'settings_function');
		
		add_action('wp_ajax_worklog_update', 'settings_function');
		add_action('wp_ajax_nopriv_worklog_update', 'settings_function');
		
		add_action('wp_ajax_get_calendar', 'settings_function');
		add_action('wp_ajax_nopriv_get_calendar', 'settings_function');
		
		add_action('wp_ajax_save_mail_form', 'settings_function');
		add_action('wp_ajax_nopriv_save_mail_form', 'settings_function');
		
		add_action('wp_ajax_get_email_card', 'settings_function');
		add_action('wp_ajax_nopriv_get_email_card', 'settings_function');
		
		add_action('wp_ajax_save_criteria_evaluation', 'settings_function');
		add_action('wp_ajax_nopriv_save_criteria_evaluation', 'settings_function');

		add_action('wp_ajax_save_categories', 'settings_function');
		add_action('wp_ajax_nopriv_save_categories', 'settings_function');

		add_action('wp_ajax_edit_template_mail', 'settings_function');
		add_action('wp_ajax_nopriv_edit_template_mail', 'settings_function');

		add_action('wp_ajax_delete_email_', 'settings_function');
		add_action('wp_ajax_nopriv_delete_email_', 'settings_function');

		add_action('wp_ajax_update_categorie_', 'settings_function');
		add_action('wp_ajax_nopriv_update_categorie_', 'settings_function');

		add_action('wp_ajax_delete_categorie_', 'settings_function');
		add_action('wp_ajax_nopriv_delete_categorie_', 'settings_function');

		add_action('wp_ajax_send_mail_test', 'settings_function');
		add_action('wp_ajax_nopriv_send_mail_test', 'settings_function');

		add_action('wp_ajax_get_option_section', 'settings_function');
		add_action('wp_ajax_nopriv_get_option_section', 'settings_function');

		add_action('wp_ajax_synchronisation_time', 'settings_function');
		add_action('wp_ajax_nopriv_synchronisation_time', 'settings_function');

		add_action('wp_ajax_project_card', 'settings_function');
		add_action('wp_ajax_nopriv_project_card', 'settings_function');
		
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Orion_Task_Manager_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Orion_Task_Manager_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
