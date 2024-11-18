<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @link       https://github.com/mnestorov/smarty-klaviyo-subscription-forms
 * @since      1.0.1
 *
 * @package    Smarty_Klaviyo_Subscription_Forms
 * @subpackage Smarty_Klaviyo_Subscription_Forms/includes/classes
 * @author     Smarty Studio | Martin Nestorov
 */
class Smarty_Ksf_Activator {

	/**
	 * This function will be executed when the plugin is activated.
	 *
	 * @since    1.0.1
	 */
	public static function activate() {
        if (!class_exists('WooCommerce')) {
            wp_die('This plugin requires WooCommerce to be installed and active.');
        }
    }
}