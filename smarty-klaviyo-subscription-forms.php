<?php
/**
 * Plugin Name:          SM - Klaviyo Subscription Forms for WooCommerce
 * Plugin URI:           https://github.com/mnestorov/smarty-klaviyo-subscription-forms
 * Description:          A plugin to manage Klaviyo subscription forms for specific products in WooCommerce, with support for multisite environments.
 * Version:              1.0.1
 * Author:               Martin Nestorov
 * Author URI:           https://github.com/mnestorov
 * Text Domain:          smarty-klaviyo-subscription-forms
 * WC requires at least: 3.5.0
 * WC tested up to:      9.6.0
 * Requires Plugins:     woocommerce
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

/**
 * HPOS Compatibility Declaration.
 *
 * This ensures that the plugin explicitly declares compatibility with 
 * WooCommerce's High-Performance Order Storage (HPOS).
 * 
 * HPOS replaces the traditional `wp_posts` and `wp_postmeta` storage system 
 * for orders with a dedicated database table structure, improving scalability 
 * and performance.
 * 
 * More details:
 * - WooCommerce HPOS Documentation: 
 *   https://developer.woocommerce.com/2022/09/12/high-performance-order-storage-in-woocommerce/
 * - Declaring Plugin Compatibility: 
 *   https://github.com/woocommerce/woocommerce/wiki/High-Performance-Order-Storage-Upgrade-Recipe-Book#how-to-declare-compatibility
 */
add_action('before_woocommerce_init', function() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

if (!function_exists('smarty_ksf_get_nonce')) {
    /**
     * Generate and return the same nonce for both admin and front-end scripts.
     *
     * @return string The generated nonce.
     */
    function smarty_ksf_get_nonce() {
        return wp_create_nonce('smarty_ksf_events_nonce');
    }
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
        wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array('jquery'), '4.0.13.' . time(), true);
        wp_enqueue_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css');
        
        // Enqueue your custom script
        wp_enqueue_script('smarty-ksf-admin-js', plugin_dir_url(__FILE__) . 'js/smarty-ksf-admin.js', array('jquery', 'select2'), null, true);

        // Localize the script for AJAX and translations
        wp_localize_script(
            'smarty-ksf-admin-js', 
            'smartyKsfEvents', 
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'siteUrl' => site_url(),
                'nonce'   => smarty_ksf_get_nonce(),
            )
        );
        
        // Enqueue your custom CSS
        wp_enqueue_style('smarty-ksf-admin-css', plugin_dir_url(__FILE__) . 'css/smarty-ksf-admin.css', array(), null);
    }
    add_action('admin_enqueue_scripts', 'smarty_ksf_enqueue_admin_scripts');
}

if (!function_exists('smarty_ksf_register_settings')) {
    /**
     * Register settings for Klaviyo forms.
     */
    function smarty_ksf_register_settings() {
        register_setting('smarty_ksf_settings_group', 'smarty_ksf_general_settings');

        add_settings_section(
            'smarty_ksf_general_section',
            __('General', 'smarty-klaviyo-subscription-forms'),
            'smarty_ksf_general_section_callback',
            'smarty_ksf_settings'
        );

        add_settings_field(
            'smarty_ksf_form_display_condition',
            __('Global Display Conditions', 'smarty-klaviyo-subscription-forms'),
            'smarty_ksf_form_display_condition_callback',
            'smarty_ksf_settings',
            'smarty_ksf_general_section'
        );

        add_settings_field(
            'smarty_ksf_form_display_categories',
            __('Global Select Categories', 'smarty-klaviyo-subscription-forms'),
            'smarty_ksf_form_display_categories_callback',
            'smarty_ksf_settings',
            'smarty_ksf_general_section'
        );

        // Register Klaviyo forms
        register_setting('smarty_ksf_settings_group', 'smarty_klaviyo_forms');
    }
    add_action('admin_init', 'smarty_ksf_register_settings');
}

function smarty_ksf_general_section_callback() {
    echo '<p>' . __('Configure general settings for Klaviyo forms display conditions.', 'smarty-klaviyo-subscription-forms') . '</p>';
}

function smarty_ksf_form_display_condition_callback() {
    $options = get_option('smarty_ksf_general_settings');
    $selected = isset($options['display_condition']) ? (array) $options['display_condition'] : [];
    ?>
    <select id="smarty_ksf_display_condition" name="smarty_ksf_general_settings[display_condition][]" multiple="multiple" class="form-display-condition-select select2" style="width: 100%;">
        <option value="out_of_stock" <?php echo in_array('out_of_stock', $selected) ? 'selected' : ''; ?>><?php _e('Out of Stock', 'smarty-klaviyo-subscription-forms'); ?></option>
        <option value="low_stock" <?php echo in_array('low_stock', $selected) ? 'selected' : ''; ?>><?php _e('Low Stock (<5)', 'smarty-klaviyo-subscription-forms'); ?></option>
        <option value="categories" <?php echo in_array('categories', $selected) ? 'selected' : ''; ?>><?php _e('Specific Categories', 'smarty-klaviyo-subscription-forms'); ?></option>
    </select>
    <?php
}

function smarty_ksf_form_display_categories_callback() {
    $options = get_option('smarty_ksf_general_settings');

    // Ensure that 'display_condition' is treated as an array
    $display_condition = isset($options['display_condition']) ? (array) $options['display_condition'] : [];
    $selected_categories = isset($options['categories']) ? (array) $options['categories'] : [];
    
    // Get the categories
    $categories = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => false));

    ?>
    <select id="smarty_ksf_select_categories" name="smarty_ksf_general_settings[categories][]" multiple="multiple" class="form-category-select select2" style="width: 100%; display: <?php echo in_array('categories', is_array($display_condition) ? $display_condition : []) ? 'block' : 'none'; ?>;">
        <?php foreach ($categories as $category): ?>
            <option value="<?php echo esc_attr($category->term_id); ?>" <?php echo in_array($category->term_id, $selected_categories) ? 'selected="selected"' : ''; ?>>
                <?php echo esc_html($category->name); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <script>
        jQuery(document).ready(function($) {
            $('#smarty_ksf_select_categories').select2();

            // Toggle visibility of categories field based on display condition
            function toggleCategoriesField() {
                var conditions = $('#smarty_ksf_display_condition').val();
                if (conditions.includes('categories')) {
                    $('#smarty_ksf_select_categories').closest('tr').show();
                } else {
                    $('#smarty_ksf_select_categories').closest('tr').hide();
                }
            }

            toggleCategoriesField();

            $('#smarty_ksf_display_condition').on('change', toggleCategoriesField);
        });
    </script>
    <?php
}

if (!function_exists('smarty_ksf_settings_page_content')) {
    /**
     * Render the settings page for Klaviyo forms.
     */
    function smarty_ksf_settings_page_content() {
        if (isset($_POST['smarty_save_klaviyo_forms'])) {
            check_admin_referer('smarty_save_klaviyo_forms', 'smarty_ksf_events_nonce');

            // Iterate through the forms and add the current date and time if it's missing
            foreach ($_POST['smarty_klaviyo_forms'] as $index => $form_data) {
                if (empty($form_data['created'])) {
                    $_POST['smarty_klaviyo_forms'][$index]['created'] = current_time('mysql');
                }
            }
        }

        // Check if the form was submitted
        if (isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true') {
            // Add a success message
            add_settings_error(
                'smarty_ksf_messages',
                'smarty_ksf_message',
                __('Settings saved.', 'smarty-klaviyo-subscription-forms'),
                'updated'
            );
        }

        // Get the saved settings
        $smarty_klaviyo_forms = get_option('smarty_klaviyo_forms', []);
        ?>
        <div class="wrap">
            <h1><?php echo __('Klaviyo Subscription Forms | Settings', 'smarty-klaviyo-subscription-forms'); ?></h1>
            <div id="smarty-ksf-settings-container">
                <div>
                    <?php settings_errors('smarty_ksf_messages');?>
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('smarty_ksf_settings_group');
                        do_settings_sections('smarty_ksf_settings');
                        ?>

                        <h2><?php echo __('Forms', 'smarty-klaviyo-subscription-forms'); ?></h2>
                        <p><?php echo __('Manage Klaviyo subscription forms for specific WooCommerce products.', 'smarty-klaviyo-subscription-forms'); ?></p>

                        <table id="smarty-klaviyo-forms-table" class="wp-list-table widefat striped">
                            <thead>
                                <tr>
                                    <th><?php echo __('Products', 'smarty-klaviyo-subscription-forms'); ?></th>
                                    <th><?php echo __('Display Condition', 'smarty-klaviyo-subscription-forms'); ?></th>
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
                                            <td style="position:relative;">
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
                                            <td>
                                                <select name="smarty_klaviyo_forms[<?php echo $index; ?>][display_condition][]" multiple="multiple" class="form-display-condition-select select2" style="width: 100%;">
                                                    <option value="default"><?php _e('Use Global Setting', 'smarty-klaviyo-subscription-forms'); ?></option>
                                                    <option value="out_of_stock" <?php selected(in_array('out_of_stock', (array)$form_data['display_condition']), true); ?>><?php _e('Out of Stock', 'smarty-klaviyo-subscription-forms'); ?></option>
                                                    <option value="low_stock" <?php selected(in_array('low_stock', (array)$form_data['display_condition']), true); ?>><?php _e('Low Stock (<5)', 'smarty-klaviyo-subscription-forms'); ?></option>
                                                </select>
                                                <select name="smarty_klaviyo_forms[<?php echo $index; ?>][category]" class="form-category-select select2" style="width: 100%; display: <?php echo in_array('categories', (array)$form_data['display_condition']) ? 'block' : 'none'; ?>;">
                                                    <?php 
                                                    $categories = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => false));
                                                    foreach ($categories as $category): 
                                                    ?>
                                                        <option value="<?php echo esc_attr($category->term_id); ?>" <?php echo isset($form_data['category']) && $form_data['category'] == $category->term_id ? 'selected="selected"' : ''; ?>>
                                                            <?php echo esc_html($category->name); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td style="width:5%">
                                                <input type="text" name="smarty_klaviyo_forms[<?php echo $index; ?>][form_id]" value="<?php echo esc_attr($form_data['form_id']); ?>" />
                                            </td>
                                            <td style="width:5%">
                                                <select name="smarty_klaviyo_forms[<?php echo $index; ?>][hook]">
                                                    <?php
                                                    $hooks = array(
                                                        'woocommerce_before_main_content'          => 'Before Main Content',
                                                        'woocommerce_before_single_product'        => 'Before Single Product',
                                                        'woocommerce_single_product_summary'       => 'Before Single Product Summary',
                                                        'woocommerce_after_single_product_summary' => 'After Single Product Summary',
                                                        'woocommerce_after_single_product'         => 'After Single Product',
                                                    );

                                                    foreach ($hooks as $hook => $label) {
                                                        $selected = selected($form_data['hook'], $hook, false);
                                                        echo "<option value='{$hook}' {$selected}>{$label}</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </td>
                                            <td style="width:5%">
                                                <label class="smarty-toggle-switch">
                                                    <input type="checkbox" name="smarty_klaviyo_forms[<?php echo $index; ?>][enabled]" value="yes" <?php checked(isset($form_data['enabled']) ? $form_data['enabled'] : '', 'yes'); ?>>
                                                    <span class="slider round"></span>
                                                </label>
                                            </td>
                                            <td style="position: relative; width:10%">
                                                <?php echo !empty($form_data['created']) ? esc_html($form_data['created']) : 'N/A'; ?>
                                                <button type="button" class="remove-form-row">
                                                    <span class="dashicons dashicons-no"></span>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center;">
                                            <button type="button" id="smarty-add-form-row" class="add-form-row">
                                                <span class="dashicons dashicons-plus"></span>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <?php submit_button(); ?>
                    </form>
                    
                    <!-- The Tabs Container (Documentation & Changelog) -->
                    <div id="smarty-ksf-tabs-container">
                        <div>
                            <h2 class="smarty-ksf-nav-tab-wrapper">
                                <a href="#smarty-ksf-documentation" class="smarty-ksf-nav-tab smarty-ksf-nav-tab-active">
                                    <?php esc_html_e('Documentation', 'smarty-ksf-events-for-woocommerce'); ?>
                                </a>
                                <a href="#smarty-ksf-changelog" class="smarty-ksf-nav-tab">
                                    <?php esc_html_e('Changelog', 'smarty-ksf-events-for-woocommerce'); ?>
                                </a>
                            </h2>
                            <div id="smarty-ksf-documentation" class="smarty-ksf-tab-content active">
                                <div class="smarty-ksf-view-more-container">
                                    <p><?php esc_html_e('Click "View More" to load the plugin documentation.', 'smarty-ksf-events-for-woocommerce'); ?></p>
                                    <button id="smarty-ksf-load-readme-btn" class="button button-primary">
                                        <?php esc_html_e('View More', 'smarty-ksf-events-for-woocommerce'); ?>
                                    </button>
                                </div>
                                <div id="smarty-ksf-readme-content" style="margin-top: 20px;"></div>
                            </div>
                            <div id="smarty-ksf-changelog" class="smarty-ksf-tab-content">
                                <div class="smarty-ksf-view-more-container">
                                    <p><?php esc_html_e('Click "View More" to load the plugin changelog.', 'smarty-ksf-events-for-woocommerce'); ?></p>
                                    <button id="smarty-ksf-load-changelog-btn" class="button button-primary">
                                        <?php esc_html_e('View More', 'smarty-ksf-events-for-woocommerce'); ?>
                                    </button>
                                </div>
                                <div id="smarty-ksf-changelog-content" style="margin-top: 20px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div><?php
    }
}

if (!function_exists('smarty_add_klaviyo_form_for_out_of_stock_products')) {
    function smarty_add_klaviyo_form_for_out_of_stock_products() {
        $smarty_klaviyo_forms = get_option('smarty_klaviyo_forms', []);
        $global_settings = get_option('smarty_ksf_general_settings', []);

        if (!$smarty_klaviyo_forms) {
            error_log('No Klaviyo forms found');
            return;
        }

        foreach ($smarty_klaviyo_forms as $form_data) {
            if (isset($form_data['enabled']) && $form_data['enabled'] === 'yes') {
                add_action($form_data['hook'], function() use ($form_data, $global_settings) {
                    global $product;

                    if (!$product || !is_a($product, 'WC_Product')) {
                        error_log('No product found or product is not a WC_Product');
                        return;
                    }

                    $product_id = $product->get_id();
                    $show_form = false;

                    // Safely determine the display conditions, ensuring they are arrays
                    $display_conditions = [];

                    if (isset($form_data['display_condition']) && is_array($form_data['display_condition']) && !empty($form_data['display_condition']) && !in_array('default', $form_data['display_condition'])) {
                        $display_conditions = $form_data['display_condition'];
                    } elseif (isset($global_settings['display_condition']) && is_array($global_settings['display_condition'])) {
                        $display_conditions = $global_settings['display_condition'];
                    }

                    // Log an error if the display conditions are not an array
                    if (!is_array($display_conditions)) {
                        error_log('Display conditions should be an array, but it is of type: ' . gettype($display_conditions));
                    }

                    // Log the conditions being checked
                    error_log('Checking conditions for product ID: ' . $product_id);

                    foreach ($display_conditions as $condition) {
                        switch ($condition) {
                            case 'out_of_stock':
                                if (!$product->is_in_stock()) {
                                    $show_form = true;
                                    error_log('Condition met: out_of_stock for product ID: ' . $product_id);
                                }
                                break;
                            case 'low_stock':
                                if ($product->get_stock_quantity() < 5) {
                                    $show_form = true;
                                    error_log('Condition met: low_stock for product ID: ' . $product_id);
                                }
                                break;
                            default:
                                error_log('No matching condition for product ID: ' . $product_id);
                        }

                        if ($show_form) {
                            break;
                        }
                    }

                    if ($show_form && in_array($product_id, (array)$form_data['product_ids'])) {
                        echo '<div class="klaviyo-form-' . esc_attr($form_data['form_id']) . '"></div>';
                        error_log('Form displayed for product ID: ' . $product_id);
                    } else {
                        error_log('Form not displayed for product ID: ' . $product_id);
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

if (!function_exists('smarty_ksf_get_categories')) {
    function smarty_ksf_get_categories() {
        $categories = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => false));
        $formatted_categories = [];

        foreach ($categories as $category) {
            $formatted_categories[] = [
                'id' => $category->term_id,
                'name' => $category->name,
            ];
        }

        wp_send_json_success($formatted_categories);
    }
    add_action('wp_ajax_smarty_ksf_get_categories', 'smarty_ksf_get_categories');
}

if (!function_exists('smarty_ksf_load_readme')) {
    /**
     * AJAX handler to load and parse the README.md content.
     */
    function smarty_ksf_load_readme() {
        check_ajax_referer('smarty_ksf_events_nonce', 'nonce');
    
        if (!current_user_can('manage_options')) {
            wp_send_json_error('You do not have sufficient permissions.');
        }
    
        $readme_path = plugin_dir_path(__FILE__) . 'README.md';
        if (file_exists($readme_path)) {
            // Include Parsedown library
            if (!class_exists('Parsedown')) {
                require_once plugin_dir_path(__FILE__) . 'libs/Parsedown.php';
            }
    
            $parsedown = new Parsedown();
            $markdown_content = file_get_contents($readme_path);
            $html_content = $parsedown->text($markdown_content);
    
            // Remove <img> tags from the content
            $html_content = preg_replace('/<img[^>]*>/', '', $html_content);
    
            wp_send_json_success($html_content);
        } else {
            wp_send_json_error('README.md file not found.');
        }
    }    
    add_action('wp_ajax_smarty_ksf_load_readme', 'smarty_ksf_load_readme');
}

if (!function_exists('smarty_ksf_load_changelog')) {
    /**
     * AJAX handler to load and parse the CHANGELOG.md content.
     */
    function smarty_ksf_load_changelog() {
        check_ajax_referer('smarty_ksf_events_nonce', 'nonce');
    
        if (!current_user_can('manage_options')) {
            wp_send_json_error('You do not have sufficient permissions.');
        }
    
        $changelog_path = plugin_dir_path(__FILE__) . 'CHANGELOG.md';
        if (file_exists($changelog_path)) {
            if (!class_exists('Parsedown')) {
                require_once plugin_dir_path(__FILE__) . 'libs/Parsedown.php';
            }
    
            $parsedown = new Parsedown();
            $markdown_content = file_get_contents($changelog_path);
            $html_content = $parsedown->text($markdown_content);
    
            wp_send_json_success($html_content);
        } else {
            wp_send_json_error('CHANGELOG.md file not found.');
        }
    }
    add_action('wp_ajax_smarty_ksf_load_changelog', 'smarty_ksf_load_changelog');
}
