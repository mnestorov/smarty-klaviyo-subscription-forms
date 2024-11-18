<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://github.com/mnestorov/smarty-klaviyo-subscription-forms
 * @since      1.0.1
 *
 * @package    Smarty_Klaviyo_Subscription_Forms
 * @subpackage Smarty_Klaviyo_Subscription_Forms/admin/partials
 * @author     Smarty Studio | Martin Nestorov
 */
?>

<?php $license_options = get_option('smarty_ksf_settings_license'); ?>
<?php $api_key = $license_options['api_key'] ?? ''; ?>
<?php $smarty_klaviyo_forms = get_option('smarty_klaviyo_forms', []); ?>

<div class="wrap">
	<h1><?php echo esc_html('Klaviyo Subscription Forms | Settings', 'smarty-klaviyo-subscription-forms'); ?></h1>

    <?php settings_errors('smarty_ksf_messages'); // Display any messages registered by the settings API ?>

	<h2 class="nav-tab-wrapper">
		<?php foreach ($tabs as $tab_key => $tab_caption) : ?>
			<?php $active = $current_tab == $tab_key ? 'nav-tab-active' : ''; ?>
			<a class="nav-tab <?php echo $active; ?>" href="?page=smarty-ksf-settings&tab=<?php echo $tab_key; ?>">
				<?php echo $tab_caption; ?>
			</a>
		<?php endforeach; ?>
	</h2>

	<?php if ($this->license->ksf_is_valid_api_key($api_key)) : ?>
		<form action="options.php" method="post">
			<?php if ($current_tab == 'general') : ?>
				<?php settings_fields('smarty_ksf_options_general'); ?>
				<?php do_settings_sections('smarty_ksf_options_general'); ?>
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
			<?php elseif ($current_tab == 'activity-logging') : ?>
				<?php settings_fields('smarty_ksf_options_activity_logging'); ?>
				<?php do_settings_sections('smarty_ksf_options_activity_logging'); ?>
			<?php elseif ($current_tab == 'license') : ?>
				<?php settings_fields('smarty_ksf_options_license'); ?>
				<?php do_settings_sections('smarty_ksf_options_license'); ?>
			<?php endif; ?>
			<?php submit_button(__('Save Settings', 'smarty-klaviyo-subscription-forms')); ?>
		</form>
	<?php else: ?>
		<form action="options.php" method="post">
			<?php if ($current_tab == 'license') : ?>
				<?php settings_fields('smarty_ksf_options_license'); ?>
				<?php do_settings_sections('smarty_ksf_options_license'); ?>
				<?php submit_button(__('Save Settings', 'smarty-klaviyo-subscription-forms')); ?>
			<?php else: ?>
				<p class="description smarty-error" style="margin: 30px 0;"><?php echo esc_html__('Please enter a valid license key in the License tab to access this setting.', 'smarty-google-feed-generator'); ?></p>
			<?php endif; ?>
		</form>
	<?php endif; ?>
</div>