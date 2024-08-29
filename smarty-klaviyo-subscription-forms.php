<?php
/**
 * Plugin Name: SM - Klaviyo Subscription Forms for WooCommerce
 * Plugin URI:  https://github.com/smartystudio/smarty-klaviyo-subscription-forms
 * Description: A plugin to manage Klaviyo subscription forms for specific products in WooCommerce, with support for multisite environments.
 * Version:     1.2.0
 * Author:      Smarty Studio | Martin Nestorov
 * Author URI:  https://smartystudio.net
 * Text Domain: smarty-klaviyo-subscription-forms
 * WC requires at least: 3.5.0
 * WC tested up to: 9.0.2
 * Requires Plugins: woocommerce
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

if (!function_exists('smarty_ksf_register_settings_page')) {
    /**
     * Add settings page for Klaviyo forms management.
     */
    function smarty_ksf_register_settings_page() {
        add_submenu_page(
            'woocommerce',                     // Parent slug
            'Klaviyo Subscription Forms',      // Page title
            'Klaviyo Subscription Forms',      // Menu title
            'manage_options',                  // Capability
            'smarty-ksf-settings',             // Menu slug
            'smarty_ksf_settings_page_content' // Callback function
        );
    }
    add_action( 'admin_menu', 'smarty_ksf_register_settings_page' );
}

if (!function_exists('smarty_ksf_enqueue_admin_scripts')) {
    /**
     * Enqueue Select2 and custom scripts/styles.
     */
    function smarty_ksf_enqueue_admin_scripts($hook) {
        if ($hook !== 'woocommerce_page_smarty-ksf-settings') {
            return;
        }

        // Enqueue Select2
        wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array('jquery'), '4.0.13', true);
        wp_enqueue_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css');
        
        // Enqueue your custom script
        wp_enqueue_script('smarty-ksf-admin-js', plugin_dir_url(__FILE__) . 'js/smarty-ksf-admin.js', array('jquery', 'select2'), null, true);
        
        // Enqueue your custom CSS
        wp_enqueue_style('smarty-ksf-admin-css', plugin_dir_url(__FILE__) . 'css/smarty-ksf-admin.css', array(), null);
    }
    add_action('admin_enqueue_scripts', 'smarty_ksf_enqueue_admin_scripts');
}

if (!function_exists('smarty_ksf_settings_page_content')) {
    /**
     * Render the settings page for Klaviyo forms.
     */
    function smarty_ksf_settings_page_content() {
        if (isset($_POST['smarty_save_klaviyo_forms'])) {
            check_admin_referer('smarty_save_klaviyo_forms', 'smarty_klaviyo_nonce');

            // Iterate through the forms and add the current date and time if it's missing
            foreach ($_POST['smarty_klaviyo_forms'] as $index => $form_data) {
                if (empty($form_data['created'])) {
                    $_POST['smarty_klaviyo_forms'][$index]['created'] = current_time('mysql');
                }
            }

            // Save product and form data
            update_option('smarty_klaviyo_forms', $_POST['smarty_klaviyo_forms']);
            echo '<div id="smarty-updated" class="updated"><p>' . __('Settings saved.', 'smarty-klaviyo-subscription-forms') . '</p></div>';
        }

        // Get the saved settings
        $smarty_klaviyo_forms = get_option('smarty_klaviyo_forms', []);

        ?>
        <div class="wrap">
            <h1><?php echo __('Klaviyo Subscription Forms | Settings', 'smarty-klaviyo-subscription-forms'); ?></h1>
            
            <h2><?php echo __('Products', 'smarty-klaviyo-subscription-forms'); ?></h2>
            <p><?php echo __('Manage Klaviyo subscription forms for specific WooCommerce products.', 'smarty-klaviyo-subscription-forms'); ?></p>

            <form method="POST" id="smarty-klaviyo-form">
                <?php wp_nonce_field('smarty_save_klaviyo_forms', 'smarty_klaviyo_nonce'); ?>

                <table id="smarty-klaviyo-forms-table" class="wp-list-table widefat striped">
                    <thead>
                        <tr>
                            <th><?php echo __('Products', 'smarty-klaviyo-subscription-forms'); ?></th>
                            <th><?php echo __('Klaviyo Form ID', 'smarty-klaviyo-subscription-forms'); ?></th>
                            <th><?php echo __('Display Hook', 'smarty-klaviyo-subscription-forms'); ?></th>
                            <th><?php echo __('Enable Form', 'smarty-klaviyo-subscription-forms'); ?></th>
                            <th><?php echo __('Created', 'smarty-klaviyo-subscription-forms'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($smarty_klaviyo_forms)): ?>
                            <?php foreach ($smarty_klaviyo_forms as $index => $form_data): ?>
                                <tr>
                                    <td style="position: relative; width:35%">
                                        <?php if ($index === array_key_last($smarty_klaviyo_forms)): ?>
                                            <button type="button" id="smarty-add-form-row" class="add-form-row">
												<span class="dashicons dashicons-plus"></span>
											</button>
                                        <?php endif; ?>
                                        <select name="smarty_klaviyo_forms[<?php echo $index; ?>][product_ids][]" multiple="multiple" class="smarty-ksf-product-search" style="width: 100%;">
                                            <?php
                                            foreach ($form_data['product_ids'] as $product_id) {
                                                $product = wc_get_product($product_id);
                                                if ($product) {
                                                    echo '<option value="' . esc_attr($product_id) . '" selected="selected">' . esc_html($product->get_name()) . '</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    </td>
                                    <td><input type="text" name="smarty_klaviyo_forms[<?php echo $index; ?>][form_id]" value="<?php echo esc_attr($form_data['form_id']); ?>" /></td>
                                    <td>
                                        <select name="smarty_klaviyo_forms[<?php echo $index; ?>][hook]">
                                            <?php
                                            $hooks = array(
                                                'woocommerce_before_main_content' => 'Before Main Content',
                                                'woocommerce_before_single_product' => 'Before Single Product',
                                                'woocommerce_single_product_summary' => 'Before Single Product Summary',
                                                'woocommerce_after_single_product_summary' => 'After Single Product Summary',
                                                'woocommerce_after_single_product' => 'After Single Product',
                                                // Add other WooCommerce hooks here
                                            );

                                            foreach ($hooks as $hook => $label) {
                                                $selected = selected($form_data['hook'], $hook, false);
                                                echo "<option value='{$hook}' {$selected}>{$label}</option>";
                                            }
                                            ?>
                                        </select>
                                    </td>
                                    <td>
                                        <label class="smarty-toggle-switch">
                                            <input type="checkbox" name="smarty_klaviyo_forms[<?php echo $index; ?>][enabled]" value="yes" <?php checked(isset($form_data['enabled']) ? $form_data['enabled'] : '', 'yes'); ?>>
                                            <span class="slider round"></span>
                                        </label>
                                    </td>
                                    <td style="position: relative;">
                                        <?php echo !empty($form_data['created']) ? esc_html($form_data['created']) : 'N/A'; ?>
                                        <button type="button" class="remove-form-row">
											<span class="dashicons dashicons-no"></span>
										</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <p class="submit">
                    <input type="submit" name="smarty_save_klaviyo_forms" class="button-primary" value="Save Changes">
                </p>
            </form>
        </div><?php
    }
}

if (!function_exists('smarty_add_klaviyo_form_for_out_of_stock_products')) {
    /**
     * Display the Klaviyo form for out of stock products based on the selected hook.
     */
    function smarty_add_klaviyo_form_for_out_of_stock_products() {
        // Get the Klaviyo forms configuration
        $smarty_klaviyo_forms = get_option('smarty_klaviyo_forms', []);

        if (!$smarty_klaviyo_forms) {
            return;
        }

        foreach ($smarty_klaviyo_forms as $form_data) {
            if (isset($form_data['enabled']) && $form_data['enabled'] === 'yes') {
                add_action($form_data['hook'], function() use ($form_data) {
                    global $product;

                    // Ensure we have a valid product object
                    if (!$product || !is_a($product, 'WC_Product')) {
                        return;
                    }

                    $product_id = $product->get_id();

                    // Check if the current product ID matches any in the form data
                    if (in_array($product_id, $form_data['product_ids'])) {
                        // Check if the product is out of stock
                        if (!$product->is_in_stock()) {
                            // Display the form for the configured product
                            echo '<div class="klaviyo-form-' . esc_attr($form_data['form_id']) . '"></div>';
                        }
                    }
                });
            }
        }
    }
    add_action('wp', 'smarty_add_klaviyo_form_for_out_of_stock_products');
}

if (!function_exists('smarty_ksf_search_products')) {
    /**
     * Ajax handler for Select2 product search.
     */
    function smarty_ksf_search_products() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        $term = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';

        $query_args = array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            's'              => $term,
            'posts_per_page' => -1,
        );

        $query = new WP_Query($query_args);
        $results = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $results[] = array(
                    'id'    => get_the_ID(),
                    'text'  => get_the_title() . ' (ID: ' . get_the_ID() . ')',
                );
            }
        } else {
            wp_send_json_error('No products found');
        }

        wp_reset_postdata();

        wp_send_json($results);
    }
    add_action('wp_ajax_smarty_ksf_search_products', 'smarty_ksf_search_products');
}