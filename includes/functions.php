<?php
/**
 * The plugin functions file.
 *
 * This is used to define general functions, shortcodes etc.
 * 
 * Important: Always use the `smarty_` prefix for function names.
 *
 * @link       https://github.com/mnestorov/smarty-klaviyo-subscription-forms
 * @since      1.0.1
 *
 * @package    Smarty_Klaviyo_Subscription_Forms
 * @subpackage Smarty_Klaviyo_Subscription_Forms/includes
 * @author     Smarty Studio | Martin Nestorov
 */

if (!function_exists('smarty_ksf_check_compatibility')) {
    /**
     * Helper function to check compatibility.
     * 
     * @since      1.0.1
     */
    function smarty_ksf_check_compatibility() {
        $min_wp_version = MIN_WP_VER; // Minimum WordPress version required
        $min_wc_version = MIN_WC_VER; // Minimum WooCommerce version required
        $min_php_version = MIN_PHP_VER; // Minimum PHP version required

        $wp_compatible = version_compare(get_bloginfo('version'), $min_wp_version, '>=');
        $php_compatible = version_compare(PHP_VERSION, $min_php_version, '>=');

        // Check WooCommerce version
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $plugins = get_plugins();
        $wc_version = isset($plugins['woocommerce/woocommerce.php']) ? $plugins['woocommerce/woocommerce.php']['Version'] : '0';
        $wc_compatible = version_compare($wc_version, $min_wc_version, '>=');

        if (!$wp_compatible || !$php_compatible || !$wc_compatible) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die('This plugin requires at least WordPress version ' . $min_wp_version . ', PHP ' . $min_php_version . ', and WooCommerce ' . $min_wc_version . ' to run.');
        }
        
        return array(
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'wp_compatible' => $wp_compatible,
            'php_compatible' => $php_compatible,
            'wc_version' => $wc_version,
            'wc_compatible' => $wc_compatible,
        );
    }
}

if (!function_exists('smarty_ksf_get_browser_and_device_type')) {
    /**
     * Helper function to check browser and device type.
     * 
     * @since      1.0.1
     */
    function smarty_ksf_get_browser_and_device_type() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Browser detection based on user agent (simplified)
        $browser = 'Unknown';
        if (stripos($user_agent, 'Firefox') !== false) {
            $browser = 'Firefox';
        } elseif (stripos($user_agent, 'Chrome') !== false) {
            $browser = 'Chrome';
        } elseif (stripos($user_agent, 'Safari') !== false) {
            $browser = 'Safari';
        } elseif (stripos($user_agent, 'Edge') !== false) {
            $browser = 'Edge';
        } elseif (stripos($user_agent, 'MSIE') !== false || stripos($user_agent, 'Trident') !== false) {
            $browser = 'Internet Explorer';
        }
        
        // Device type based on user agent
        $device_type = (preg_match('/Mobile|Android|iPhone|iPad/i', $user_agent)) ? 'Mobile' : 'Desktop';

        return [
            'browser' => $browser,
            'device_type' => $device_type
        ];
    }
}

if (!function_exists('smarty_ksf_get_os')) {
    /**
     * Helper function to check operating system.
     * 
     * @since      1.0.1
     */
    function smarty_ksf_get_os() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $os_platform = "Unknown OS";

        // Define an array of OS platforms to match against the user agent
        $os_array = [
            '/windows nt 10/i'     => 'Windows 10',
            '/windows nt 6.3/i'    => 'Windows 8.1',
            '/windows nt 6.2/i'    => 'Windows 8',
            '/windows nt 6.1/i'    => 'Windows 7',
            '/windows nt 6.0/i'    => 'Windows Vista',
            '/windows nt 5.1/i'    => 'Windows XP',
            '/macintosh|mac os x/i'=> 'Mac OS X',
            '/linux/i'             => 'Linux',
            '/iphone/i'            => 'iOS',
            '/android/i'           => 'Android',
        ];

        foreach ($os_array as $regex => $value) {
            if (preg_match($regex, $user_agent)) {
                $os_platform = $value;
                break;
            }
        }

        return $os_platform;
    }
}

if (!function_exists('_ksf_write_logs')) {
	/**
     * Writes logs for the plugin.
     * 
     * @since      1.0.1
     * @param string $message Message to be logged.
     * @param mixed $data Additional data to log, optional.
     */
    function _ksf_write_logs($message, $data = null) {
        $log_entry = '[' . current_time('mysql') . '] ' . $message;
    
        if (!is_null($data)) {
            $log_entry .= ' - ' . print_r($data, true);
        }

        $logs_file = fopen(KSF_BASE_DIR . DIRECTORY_SEPARATOR . "logs.txt", "a+");
        fwrite($logs_file, $log_entry . "\n");
        fclose($logs_file);
    }
}