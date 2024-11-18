<?php
/**
 * The public functionality of the plugin.
 * 
 * Defines the plugin name, version, and two hooks for how to enqueue 
 * the public-facing stylesheet (CSS) and JavaScript code.
 * 
 * @link       https://github.com/mnestorov/smarty-klaviyo-subscription-forms
 * @since      1.0.0
 *
 * @package    Smarty_Klaviyo_Subscription_Forms
 * @subpackage Smarty_Klaviyo_Subscription_Forms/public
 * @author     Smarty Studio | Martin Nestorov
 */
class Smarty_Ksf_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.1
	 * @access   private
	 * @var      string    $plugin_name     The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.1
	 * @access   private
	 * @var      string    $version         The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.1
	 * @param    string    $plugin_name     The name of the plugin.
	 * @param    string    $version         The version of this plugin.
	 */
	public function __construct($plugin_name, $version) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.1
	 */
	public function enqueue_styles() {
		/**
		 * This function enqueues custom CSS for the WooCommerce checkout page.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Smarty_Ksf_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Smarty_Ksf_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
         
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/smarty-ksf-public.css', array(), $this->version, 'all');
    }

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.1
	 */
	public function enqueue_scripts() {
		/**
		 * This function enqueues custom JavaScript for the WooCommerce checkout page.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Smarty_Ksf_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Smarty_Ksf_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/smarty-ksf-public.js', array('jquery'), $this->version, true);
	}
}