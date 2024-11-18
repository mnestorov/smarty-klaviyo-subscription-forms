<?php
/**
 * The core plugin class.
 *
 * This is used to define attributes, functions, internationalization used across
 * both the admin-specific hooks, and public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @link       https://github.com/mnestorov/smarty-klaviyo-subscription-forms
 * @since      1.0.1
 *
 * @package    Smarty_Klaviyo_Subscription_Forms
 * @subpackage Smarty_Klaviyo_Subscription_Forms/includes/classes
 * @author     Smarty Studio | Martin Nestorov
 */
class Smarty_Ksf_Locator {

	/**
	 * The loader that's responsible for maintaining and registering all hooks
	 * that power the plugin.
	 *
	 * @since    1.0.1
	 * @access   protected
	 * @var      Smarty_Ksf_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.1
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.1
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
	 * @since    1.0.1
	 */
	public function __construct() {
		if (defined('KSF_VERSION')) {
			$this->version = KSF_VERSION;
		} else {
			$this->version = '1.0.1';
		}

		$this->plugin_name = 'smarty-klaviyo-subscription-forms';

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
	 * - Smarty_Ksf_Loader. Orchestrates the hooks of the plugin.
	 * - Smarty_Ksf_i18n. Defines internationalization functionality.
	 * - Smarty_Ksf_Admin. Defines all hooks for the admin area.
	 * - Smarty_Ksf_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.1
	 * @access   private
	 */
	private function load_dependencies() {
		/**
		 * The class responsible for orchestrating the actions and filters of the core plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'classes/class-smarty-ksf-loader.php';

		/**
		 * The class responsible for defining internationalization functionality of the plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'classes/class-smarty-ksf-i18n.php';

		/**
		 * The class responsible for interacting with the API.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'classes/class-smarty-ksf-api.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . '../admin/class-smarty-ksf-admin.php';

		/**
		 * The class responsible for Activity & Logging functionality in the admin area.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . '../admin/tabs/class-smarty-ksf-activity-logging.php';

		/**
		 * The class responsible for License functionality in the admin area.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . '../admin/tabs/class-smarty-ksf-license.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing side of the site.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . '../public/class-smarty-ksf-public.php';

		// Run the loader
		$this->loader = new Smarty_Ksf_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Smarty_Ksf_I18n class in order to set the domain and to
	 * register the hook with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {
		$plugin_i18n = new Smarty_Ksf_i18n();

		$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new Smarty_Ksf_Admin($this->get_plugin_name(), $this->get_version());
		
		$plugin_activity_logging = new Smarty_Ksf_Activity_Logging();
		$plugin_license = new Smarty_Ksf_License();

		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
		$this->loader->add_action('admin_menu', $plugin_admin, 'gfg_add_settings_page');
		$this->loader->add_action('admin_init', $plugin_admin, 'gfg_settings_init');
		$this->loader->add_action('admin_notices', $plugin_admin, 'gfg_success_notice');

		// Register hooks for Activity & Logging
		$this->loader->add_action('admin_init', $plugin_activity_logging, 'ksf_al_settings_init');
        $this->loader->add_action('wp_ajax_smarty_ksf_clear_logs', $plugin_activity_logging, 'ksf_handle_ajax_clear_logs');

		// Register hooks for License management
		$this->loader->add_action('admin_init', $plugin_license, 'ksf_l_settings_init');
		$this->loader->add_action('updated_option', $plugin_license, 'ksf_handle_license_status_check', 10, 3);
		$this->loader->add_action('admin_notices', $plugin_license, 'ksf_license_notice');
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.1
	 * @access   private
	 */
	private function define_public_hooks() {
		$plugin_public = new Smarty_Ksf_Public($this->get_plugin_name(), $this->get_version());
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.1
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.1
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.1
	 * @return    Smarty_Ksf_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.1
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}