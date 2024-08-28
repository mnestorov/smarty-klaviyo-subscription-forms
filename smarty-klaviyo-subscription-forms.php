<?php
/**
 * Plugin Name: SM - Klaviyo Subscription Forms for WooCommerce
 * Plugin URI:  https://github.com/smartystudio/smarty-klaviyo-subscription-forms
 * Description: A plugin to manage Klaviyo subscription forms for specific products in WooCommerce, with support for multisite environments.
 * Version:     1.0.0
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

/**
 * Add settings page for Klaviyo forms management.
 */
function smarty_klaviyo_add_admin_menu() {
    add_submenu_page(
        'woocommerce',                                  // Parent slug
        'Klaviyo Subscription Forms',                   // Page title
        'Klaviyo Subscription Forms',                   // Menu title
        'manage_options',                               // Capability
        'smarty-ksf-settings',                          // Menu slug
        'smarty_klaviyo_settings_page_content'          // Callback function
    );
}
add_action( 'admin_menu', 'smarty_klaviyo_add_admin_menu' );

/**
 * Render the settings page for Klaviyo forms.
 */
function smarty_klaviyo_settings_page_content() {
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

            <table class="form-table" id="klaviyo-forms-table">
                <thead>
                    <tr>
                        <th><?php echo __('Product Name', 'smarty-klaviyo-subscription-forms'); ?></th>
                        <th><?php echo __('Klaviyo Form ID', 'smarty-klaviyo-subscription-forms'); ?></th>
                        <th><?php echo __('Preview', 'smarty-klaviyo-subscription-forms'); ?></th>
                        <th><?php echo __('Action', 'smarty-klaviyo-subscription-forms'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($smarty_klaviyo_forms)): ?>
                        <?php foreach ($smarty_klaviyo_forms as $index => $form_data): ?>
                            <tr>
                                <td><input type="text" name="smarty_klaviyo_forms[<?php echo $index; ?>][product_name]" value="<?php echo esc_attr($form_data['product_name']); ?>" /></td>
                                <td><input type="text" name="smarty_klaviyo_forms[<?php echo $index; ?>][form_id]" value="<?php echo esc_attr($form_data['form_id']); ?>" /></td>
                                <td><div class="klaviyo-form-preview klaviyo-form-<?php echo esc_attr($form_data['form_id']); ?>"></div></td>
                                <td><button type="button" class="button button-secondary remove-form-row">Remove</button></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <p><button type="button" class="button button-secondary" id="add-form-row">Add New Form</button></p>

            <p class="submit">
                <input type="submit" name="smarty_save_klaviyo_forms" class="button-primary" value="Save Changes">
            </p>
        </form>
    </div>

    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#add-form-row').on('click', function() {
                var index = $('#klaviyo-forms-table tbody tr').length;
                var newRow = `
                    <tr>
                        <td><input type="text" name="smarty_klaviyo_forms[` + index + `][product_name]" /></td>
                        <td><input type="text" name="smarty_klaviyo_forms[` + index + `][form_id]" /></td>
                        <td><div class="klaviyo-form-preview klaviyo-form-"></div></td>
                        <td><button type="button" class="button button-secondary remove-form-row">Remove</button></td>
                    </tr>
                `;
                $('#klaviyo-forms-table tbody').append(newRow);
            });

            $(document).on('click', '.remove-form-row', function() {
                $(this).closest('tr').remove();
            });
        });
    </script>
    <?php
}

/**
 * Display the Klaviyo form for out of stock products.
 */
function smarty_add_klaviyo_form_for_out_of_stock_products() {
    // Check if we are on the correct blog
    if (get_current_blog_id() == 1) {
        global $product;

        // Check if the product is out of stock
        if (!$product->is_in_stock()) {
            // Get the product title
            $product_name = get_the_title($product->get_id());

            // Get the Klaviyo forms configuration
            $smarty_klaviyo_forms = get_option('smarty_klaviyo_forms', []);

            // Determine which Klaviyo form to display based on the product name
            foreach ($smarty_klaviyo_forms as $form_data) {
                if (isset($form_data['product_name']) && strpos($product_name, $form_data['product_name']) !== false) {
                    // Display the form for the configured product
                    echo '<div class="klaviyo-form-' . esc_attr($form_data['form_id']) . '"></div>';
                    break; // Stop after the first matching product name
                }
            }
        }
    }
}
add_action('woocommerce_single_product_summary', 'smarty_add_klaviyo_form_for_out_of_stock_products', 6);

