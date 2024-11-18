<?php
/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://github.com/mnestorov/smarty-klaviyo-subscription-forms
 * @since      1.0.1
 *
 * @package    Smarty_Klaviyo_Subscription_Forms
 * @subpackage Smarty_Klaviyo_Subscription_Forms/includes/classes
 * @author     Smarty Studio | Martin Nestorov
 */
class Smarty_Ksf_I18n {

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.1
	 */
	public function load_plugin_textdomain() {
        load_plugin_textdomain('smarty-klaviyo-subscription-forms', false, dirname(dirname(plugin_basename(__FILE__))) . '/languages/');
    }
}