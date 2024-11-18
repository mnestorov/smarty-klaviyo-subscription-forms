<?php
/**
 * Plugin Name: SM - Klaviyo Subscription Forms for WooCommerce
 * Plugin URI:  https://github.com/mnestorov/smarty-klaviyo-subscription-forms
 * Description: A plugin to manage Klaviyo subscription forms for specific products in WooCommerce, with support for multisite environments.
 * Version:     1.2.0
 * Author:      Smarty Studio | Martin Nestorov
 * Author URI:  https://github.com/mnestorov
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

        // Enqueue WordPress Media Uploader
        wp_enqueue_media();

        // Ensure jQuery is enqueued
        wp_enqueue_script('jquery');

        // Enqueue Select2
        wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array('jquery'), '4.0.13.' . time(), true);
        wp_enqueue_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css');
        
        // Enqueue your custom script
        wp_enqueue_script('smarty-ksf-admin-js', plugin_dir_url(__FILE__) . 'js/smarty-ksf-admin.js', array('jquery', 'select2'), null, true);

        // Localize the script for AJAX and translations
        wp_localize_script('smarty-ksf-admin-js', 'smarty_ksf_vars', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'selectImageTitle' => __('Select or Upload Image', 'smarty-klaviyo-subscription-forms'),
            'useImageText' => __('Use this image', 'smarty-klaviyo-subscription-forms')
        ));
        
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

function smarty_ksf_register_popup_settings() {
    // Register the settings group for popups
    register_setting('smarty_klaviyo_popup_settings_group', 'smarty_klaviyo_popup_settings');

    // Add settings section for popup settings
    add_settings_section(
        'smarty_ksf_popup_section',
        __('Popups', 'smarty-klaviyo-subscription-forms'),
        'smarty_ksf_popup_section_callback',
        'smarty_klaviyo_popup_settings'
    );

    // Optionally, you can add fields for individual settings here
    // Example for adding a field:
    // add_settings_field(
    //     'popup_image_id',
    //     __('Popup Image', 'smarty-klaviyo-subscription-forms'),
    //     'your_callback_function_name',
    //     'smarty_klaviyo_popup_settings',
    //     'smarty_ksf_popup_section'
    // );
}
add_action('admin_init', 'smarty_ksf_register_popup_settings');

// Callback function for the settings section
function smarty_ksf_popup_section_callback() {
    echo '<p>' . __('Configure popup settings for your Klaviyo subscription.', 'smarty-klaviyo-subscription-forms') . '</p>';
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
            check_admin_referer('smarty_save_klaviyo_forms', 'smarty_klaviyo_nonce');

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

        // Get the saved popup settings
        $popup_settings = get_option('smarty_klaviyo_popup_settings', [
            'image_id' => '',
            'delay' => 5,
            'position' => 'center',
            'content' => '',
            'font_size' => '16px',
            'pages' => [], // Store selected pages for the popup
            'form_id' => '', // Store Klaviyo form ID
        ]);

        // Get the saved settings
        $smarty_klaviyo_forms = get_option('smarty_klaviyo_forms', []);
        ?>
        <div class="wrap">
            <h1><?php echo __('Klaviyo Subscription Forms | Settings', 'smarty-klaviyo-subscription-forms'); ?></h1>

            <?php
            // Display any messages registered by the settings API
            settings_errors('smarty_ksf_messages');
            ?>

            <!-- Tabs Navigation -->
            <nav class="nav-tab-wrapper">
                <a href="#forms-tab" class="nav-tab nav-tab-active"><?php _e('Forms', 'smarty-klaviyo-subscription-forms'); ?></a>
                <a href="#popups-tab" class="nav-tab"><?php _e('Popups', 'smarty-klaviyo-subscription-forms'); ?></a>
            </nav>

            <!-- Tabs Content -->
            <div id="forms-tab" class="tab-content active-tab">
            
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
            </div>

            <div id="popups-tab" class="tab-content">
                <form method="post" action="options.php">
                    <?php
                    settings_fields('smarty_klaviyo_popup_settings_group');
                    do_settings_sections('smarty_klaviyo_popup_settings');
                    ?>
                    
                    <table class="form-table">
                        <!-- Image Selection -->
                        <tr>
                            <th scope="row"><?php _e('Select Popup Image', 'smarty-klaviyo-subscription-forms'); ?></th>
                            <td>
                                <button id="select-popup-image" class="button"><?php _e('Select Image', 'smarty-klaviyo-subscription-forms'); ?></button>
                                <button id="remove-popup-image" class="button" style="<?php echo $popup_settings['image_id'] ? 'display:inline-block;' : 'display:none;'; ?>">
                                    <?php _e('Remove Image', 'smarty-klaviyo-subscription-forms'); ?>
                                </button>
                                <input type="hidden" id="popup-image-id" name="smarty_klaviyo_popup_settings[image_id]" value="<?php echo esc_attr($popup_settings['image_id']); ?>">
                                <div id="popup-image-preview">
                                    <?php if ($popup_settings['image_id']) : ?>
                                        <?php echo wp_get_attachment_image($popup_settings['image_id'], 'thumbnail'); ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>

                        <!-- Page Selection Using Select2 -->
                        <tr>
                            <th scope="row"><?php _e('Select Pages to Show Popup', 'smarty-klaviyo-subscription-forms'); ?></th>
                            <td>
                                <select id="popup-pages" name="smarty_klaviyo_popup_settings[pages][]" multiple="multiple" class="select2" style="width: 100%;">
                                    <?php
                                    $pages = get_pages();
                                    foreach ($pages as $page) {
                                        echo '<option value="' . esc_attr($page->ID) . '"' . (in_array($page->ID, is_array($popup_settings['pages']) ? $popup_settings['pages'] : []) ? ' selected' : '') . '>' . esc_html($page->post_title) . '</option>';
                                    }
                                    ?>
                                </select>
                                <p><?php _e('Choose the pages where the popup will be displayed.', 'smarty-klaviyo-subscription-forms'); ?></p>
                            </td>
                        </tr>

                        <!-- Klaviyo Form ID -->
                        <tr>
                            <th scope="row"><?php _e('Klaviyo Form ID', 'smarty-klaviyo-subscription-forms'); ?></th>
                            <td>
                                <input type="text" name="smarty_klaviyo_popup_settings[form_id]" value="<?php echo esc_attr($popup_settings['form_id']); ?>">
                                <p><?php _e('Enter the Klaviyo form ID to be displayed within the popup.', 'smarty-klaviyo-subscription-forms'); ?></p>
                            </td>
                        </tr>

                        <!-- Delay Option -->
                        <tr>
                            <th scope="row"><?php _e('Popup Delay (seconds)', 'smarty-klaviyo-subscription-forms'); ?></th>
                            <td>
                                <input type="number" name="smarty_klaviyo_popup_settings[delay]" value="<?php echo esc_attr($popup_settings['delay']); ?>" min="1">
                            </td>
                        </tr>

                        <!-- Position Option -->
                        <tr>
                            <th scope="row"><?php _e('Popup Position', 'smarty-klaviyo-subscription-forms'); ?></th>
                            <td>
                                <select name="smarty_klaviyo_popup_settings[position]">
                                    <option value="center" <?php selected($popup_settings['position'], 'center'); ?>><?php _e('Center', 'smarty-klaviyo-subscription-forms'); ?></option>
                                    <option value="top" <?php selected($popup_settings['position'], 'top'); ?>><?php _e('Top', 'smarty-klaviyo-subscription-forms'); ?></option>
                                    <option value="bottom" <?php selected($popup_settings['position'], 'bottom'); ?>><?php _e('Bottom', 'smarty-klaviyo-subscription-forms'); ?></option>
                                </select>
                            </td>
                        </tr>

                        <!-- Custom Text Option -->
                        <tr>
                            <th scope="row"><?php _e('Popup Content', 'smarty-klaviyo-subscription-forms'); ?></th>
                            <td>
                                <textarea name="smarty_klaviyo_popup_settings[content]" rows="5" cols="50"><?php echo esc_textarea($popup_settings['content']); ?></textarea>
                                <p><?php _e('You can use HTML tags to customize the content.', 'smarty-klaviyo-subscription-forms'); ?></p>
                            </td>
                        </tr>

                        <!-- Font Size Option -->
                        <tr>
                            <th scope="row"><?php _e('Font Size', 'smarty-klaviyo-subscription-forms'); ?></th>
                            <td>
                                <input type="text" name="smarty_klaviyo_popup_settings[font_size]" value="<?php echo esc_attr($popup_settings['font_size']); ?>">
                                <p><?php _e('Specify the font size (e.g., 16px, 1em).', 'smarty-klaviyo-subscription-forms'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button(); ?>
                </form>
            </div>  
        </div>
        <?php
    }
}

if (!function_exists('smarty_ksf_display_popup')) {
    function smarty_ksf_display_popup() {
        // Get the saved popup settings
        $popup_settings = get_option('smarty_klaviyo_popup_settings', [
            'image_id' => '',
            'delay' => 5,
            'position' => 'center',
            'content' => '',
            'font_size' => '16px',
            'pages' => [],
            'form_id' => ''
        ]);

        // Check if the current page is in the selected pages
        if (!is_page(is_array($popup_settings['pages']) ? $popup_settings['pages'] : [])) {
            return; // Do not display the popup on pages not selected
        }

        // Prepare the popup content
        $image_url = $popup_settings['image_id'] ? wp_get_attachment_image_url($popup_settings['image_id'], 'full') : '';
        $position = $popup_settings['position'];
        $delay = (int) $popup_settings['delay'] * 1000; // Convert delay to milliseconds
        $content = $popup_settings['content'];
        $font_size = $popup_settings['font_size'];
        $form_id = $popup_settings['form_id'];
        ?>
        
        <!-- Popup Structure -->
        <div id="smarty-popup-overlay" style="display: none;"></div>
        <div id="smarty-popup" class="<?php echo esc_attr($position); ?>" style="display: none; font-size: <?php echo esc_attr($font_size); ?>;">
            <?php if ($image_url): ?>
                <img src="<?php echo esc_url($image_url); ?>" alt="Popup Image">
            <?php endif; ?>
            <div class="smarty-popup-content">
                <?php echo wp_kses_post($content); ?>
                <?php if ($form_id): ?>
                    <!-- Klaviyo Form Embed -->
                    <div class="klaviyo-form" data-form-id="<?php echo esc_attr($form_id); ?>"></div>
                <?php endif; ?>
            </div>
            <button id="smarty-popup-close">X</button>
        </div>

        <!-- Inline CSS and JS for Popup -->
        <style>
            #smarty-popup-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 300px;
                height: 300px;
                background: rgba(0, 0, 0, 0.5);
                z-index: 9998;
            }

            #smarty-popup {
                position: fixed;
                <?php if ($position === 'center'): ?>
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                <?php elseif ($position === 'top'): ?>
                    top: 10%;
                    left: 50%;
                    transform: translateX(-50%);
                <?php elseif ($position === 'bottom'): ?>
                    bottom: 10%;
                    left: 50%;
                    transform: translateX(-50%);
                <?php endif; ?>
                background: #fff;
                padding: 20px;
                border-radius: 8px;
                z-index: 9999;
            }

            #smarty-popup-close {
                position: absolute;
                top: 10px;
                right: 10px;
                background: none;
                border: none;
                font-size: 16px;
                cursor: pointer;
            }

            .smarty-popup-content img {
                max-width: 100%;
                height: auto;
            }
        </style>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Show popup after delay
                setTimeout(function() {
                    document.getElementById('smarty-popup-overlay').style.display = 'block';
                    document.getElementById('smarty-popup').style.display = 'block';

                    // Add blur effect to body
                    //document.body.style.filter = 'blur(5px)';
                }, <?php echo $delay; ?>);

                // Close popup and remove blur effect
                document.getElementById('smarty-popup-close').addEventListener('click', function() {
                    document.getElementById('smarty-popup-overlay').style.display = 'none';
                    document.getElementById('smarty-popup').style.display = 'none';
                    document.body.style.filter = '';
                });
            });
        </script>
        <?php
    }
    //add_action('wp_footer', 'smarty_ksf_display_popup');
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
