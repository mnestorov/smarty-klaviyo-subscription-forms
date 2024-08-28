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
        wp_enqueue_script('select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), null, true);
        wp_enqueue_style('select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');

        // Enqueue your custom script
        wp_enqueue_script('smarty-ksf-admin-js', plugin_dir_url(__FILE__) . 'smarty-ksf-admin.js', array('jquery', 'select2-js'), null, true);
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

            // Save product and form data
            update_option('smarty_klaviyo_forms', $_POST['smarty_klaviyo_forms']);
            echo '<div class="updated"><p>Settings saved.</p></div>';
        }

        // Get the saved settings
        $smarty_klaviyo_forms = get_option('smarty_klaviyo_forms', []);

        ?>
        <div class="wrap">
            <h1><?php echo __('Klaviyo Subscription Forms | Settings', 'smarty-klaviyo-subscription-forms'); ?></h1>
            <form method="POST">
                <?php wp_nonce_field('smarty_save_klaviyo_forms', 'smarty_klaviyo_nonce'); ?>

                <p><button type="button" class="button button-secondary" id="smarty-add-form-row"><?php echo __('Add New Form', 'smarty-klaviyo-subscription-forms'); ?></button></p>

                <table class="wp-list-table widefat fixed striped" id="klaviyo-forms-table">
                    <thead>
                        <tr>
                            <th><?php echo __('Products', 'smarty-klaviyo-subscription-forms'); ?></th>
                            <th><?php echo __('Klaviyo Form ID', 'smarty-klaviyo-subscription-forms'); ?></th>
                            <th><?php echo __('Enable Form', 'smarty-klaviyo-subscription-forms'); ?></th>
                            <th><?php echo __('Preview', 'smarty-klaviyo-subscription-forms'); ?></th>
                            <th><?php echo __('Action', 'smarty-klaviyo-subscription-forms'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($smarty_klaviyo_forms)): ?>
                            <?php foreach ($smarty_klaviyo_forms as $index => $form_data): ?>
                                <tr>
                                    <td>
                                        <select name="smarty_klaviyo_forms[<?php echo $index; ?>][product_ids][]" multiple="multiple" class="smarty-ksf-product-select2" style="width: 100%;">
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
                                        <label class="smarty-toggle-switch">
                                            <input type="checkbox" name="smarty_klaviyo_forms[<?php echo $index; ?>][enabled]" value="yes" <?php checked(isset($form_data['enabled']) ? $form_data['enabled'] : '', 'yes'); ?>>
                                            <span class="slider round"></span>
                                        </label>
                                    </td>
                                    <td><a href="https://www.klaviyo.com/forms/<?php echo esc_attr($form_data['form_id']); ?>" target="_blank"><?php echo __('Preview Form', 'smarty-klaviyo-subscription-forms'); ?></a></td>
                                    <td><button type="button" class="button button-secondary remove-form-row">Remove</button></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <p class="submit">
                    <input type="submit" name="smarty_save_klaviyo_forms" class="button-primary" value="Save Changes">
                </p>
            </form>
        </div>

        <style>
            /* Table styling */
            .wp-list-table.widefat.fixed.striped {
                width: 100%;
                margin-top: 20px;
                border-collapse: collapse;
            }

            .wp-list-table.widefat.fixed.striped th,
            .wp-list-table.widefat.fixed.striped td {
                padding: 10px;
                border-bottom: 1px solid #e1e1e1;
                border-right: 1px solid #e1e1e1;
            }

            .wp-list-table.widefat.fixed.striped th:last-child,
            .wp-list-table.widefat.fixed.striped td:last-child {
                border-right: none;
            }

            .wp-list-table.widefat.fixed.striped th {
                background-color: #f7f7f7;
                font-weight: bold;
                border-top: 1px solid #e1e1e1;
            }

            .wp-list-table.widefat.fixed.striped tbody tr:nth-child(even) {
                background-color: #f9f9f9;
            }

            /* Toggle switch styling */
            .smarty-toggle-switch {
                position: relative;
                display: inline-block;
                width: 60px;
                height: 34px;
            }

            .smarty-toggle-switch input {
                opacity: 0;
                width: 0;
                height: 0;
            }

            .smarty-toggle-switch .slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #ccc;
                transition: .4s;
                border-radius: 34px;
            }

            .smarty-toggle-switch .slider:before {
                position: absolute;
                content: "";
                height: 26px;
                width: 26px;
                left: 4px;
                bottom: 4px;
                background-color: white;
                transition: .4s;
                border-radius: 50%;
            }

            input:checked + .slider {
                background-color: #2196F3;
            }

            input:checked + .slider:before {
                transform: translateX(26px);
            }
        </style>
        <?php
    }
}

if (!function_exists('smarty_add_klaviyo_form_for_out_of_stock_products')) {
    /**
     * Display the Klaviyo form for out of stock products.
     */
    function smarty_add_klaviyo_form_for_out_of_stock_products() {
        global $product;

        // Check if the product is out of stock
        if (!$product->is_in_stock()) {
            // Get the product ID
            $product_id = $product->get_id();

            // Get the Klaviyo forms configuration
            $smarty_klaviyo_forms = get_option('smarty_klaviyo_forms', []);

            // Determine which Klaviyo form to display based on the product ID
            foreach ($smarty_klaviyo_forms as $form_data) {
                if (isset($form_data['product_ids']) && in_array($product_id, $form_data['product_ids']) && isset($form_data['enabled']) && $form_data['enabled'] === 'yes') {
                    // Display the form for the configured product
                    echo '<div class="klaviyo-form-' . esc_attr($form_data['form_id']) . '"></div>';
                    break; // Stop after the first matching product ID
                }
            }
        }
    }
    add_action('woocommerce_single_product_summary', 'smarty_add_klaviyo_form_for_out_of_stock_products', 6);
}

if (!function_exists('smarty_ksf_search_products')) {
    /**
     * Ajax handler for Select2 product search.
     */
    function smarty_ksf_search_products() {
        $term = wc_clean($_GET['q']);

        $args = array(
            'post_type' => 'product',
            'posts_per_page' => 10, // Limit to 10 products for now
            's' => $term, // Search term
            'post_status' => 'publish',
            'meta_query' => array(), // Get all products regardless of stock status
        );

        $query = new WP_Query($args);

        $results = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $product = wc_get_product(get_the_ID());
                $results[] = array(
                    'id' => $product->get_id(),
                    'text' => $product->get_name() . ( ! $product->is_in_stock() ? ' (Out of Stock)' : '' ),
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