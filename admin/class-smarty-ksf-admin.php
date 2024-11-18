<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two hooks for how to enqueue 
 * the admin-specific stylesheet (CSS) and JavaScript code.
 *
 * @link       https://github.com/mnestorov/smarty-klaviyo-subscription-forms
 * @since      1.0.1
 *
 * @package    Smarty_Klaviyo_Subscription_Forms
 * @subpackage Smarty_Klaviyo_Subscription_Forms/admin
 * @author     Smarty Studio | Martin Nestorov
 */
class Smarty_Ksf_Admin {
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
	 * Instance of Smarty_Ksf_Activity_Logging.
	 * 
	 * @since    1.0.1
	 * @access   private
	 */
	private $activity_logging;

	/**
	 * Instance of Smarty_Ksf_License.
	 * 
	 * @since    1.0.1
	 * @access   private
	 */
	private $license;
	
	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.1
	 * @param    string    $plugin_name     The name of this plugin.
	 * @param    string    $version         The version of this plugin.
	 */
	public function __construct($plugin_name, $version) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		
		// Include and instantiate the Activity Logging class
		$this->activity_logging = new Smarty_Ksf_Activity_Logging();

		// Include and instantiate the License class
		$this->license = new Smarty_Ksf_License();
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.1
	 */
	public function enqueue_styles() {
		/**
		 * This function enqueues custom CSS for the plugin settings in WordPress admin.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Smarty_Ksf_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Smarty_Ksf_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
        wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css', array(), '4.0.13');
		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/smarty-ksf-admin.css', array(), $this->version, 'all');
		wp_enqueue_style('dashicons');
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.1
	 */
	public function enqueue_scripts() {
		/**
		 * This function enqueues custom JavaScript for the plugin settings in WordPress admin.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Smarty_Ksf_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Smarty_Ksf_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

        // Enqueue WordPress Media Uploader
        wp_enqueue_media();

        // Ensure jQuery is enqueued
        wp_enqueue_script('jquery');

		wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js', array('jquery'), '4.0.13', true);
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/smarty-ksf-admin.js', array('jquery', 'select2'), $this->version, true);
		
        // Localize the script for AJAX and translations
        wp_localize_script('smarty-ksf-admin-js', 'smarty_ksf_vars', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'selectImageTitle' => __('Select or Upload Image', 'smarty-klaviyo-subscription-forms'),
            'useImageText' => __('Use this image', 'smarty-klaviyo-subscription-forms')
        ));
	}

	/**
	 * Add settings page to the WordPress admin menu.
	 * 
	 * @since    1.0.1
	 */
	public function ksf_add_settings_page() {
		add_submenu_page(
			'woocommerce',
			__('Klaviyo Subscription Forms | Settings', 'smarty-klaviyo-subscription-forms'),   // Page title
			__('Klaviyo Subscription Forms', 'smarty-klaviyo-subscription-forms'), 			    // Menu title                   
			'manage_options',                           							            // Capability required to access this page
			'smarty-ksf-settings',           										            // Menu slug
			array($this, 'ksf_display_settings_page')  								            // Callback function to display the page content
		);
	}

	/**
	 * @since    1.0.0
	 */
	private function ksf_get_settings_tabs() {
		$allowed_user_hash = 'd12bd8335327019439aa8cc3359385cccdbab7c28bbb7894a4ea46196f71d8c7';
		$current_user = wp_get_current_user();
		$current_user_hash = hash('sha256', $current_user->user_login);

		$tabs = array(
			'general'          => __('General', 'smarty-klaviyo-subscription-forms'),
			'activity-logging' => __('Activity & Logging', 'smarty-klaviyo-subscription-forms')
		);

		if ($current_user_hash === $allowed_user_hash) {
			$tabs['license'] = __('License', 'smarty-klaviyo-subscription-forms');
		}
		
		return $tabs;
	}

	/**
	 * Outputs the HTML for the settings page.
	 * 
	 * @since    1.0.1
	 */
	public function ksf_display_settings_page() {
		// Check user capabilities
		if (!current_user_can('manage_options')) {
			return;
		}

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

		$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';
		$tabs = $this->ksf_get_settings_tabs();
	
		// Define the path to the external file
		$partial_file = plugin_dir_path(__FILE__) . 'partials/smarty-ksf-admin-display.php';

		if (file_exists($partial_file) && is_readable($partial_file)) {
			include_once $partial_file;
		} else {
			_ksf_write_logs("Unable to include: '$partial_file'");
		}
	}

	/**
	 * Initializes the plugin settings by registering the settings, sections, and fields.
	 *
	 * @since    1.0.1
	 */
	public function ksf_settings_init() {
        register_setting('smarty_ksf_settings_group', 'smarty_ksf_general_settings');

		add_settings_section(
			'smarty_ksf_section_general',                               // ID of the section
			__('General', 'smarty-klaviyo-subscription-forms'),         // Title of the section
			array($this,'ksf_section_general_cb'),                      // Callback function that fills the section with the desired content
			'smarty_ksf_options_general'                                // Page on which to add the section
		);

        add_settings_field(
            'smarty_ksf_form_display_condition',
            __('Global Display Conditions', 'smarty-klaviyo-subscription-forms'),
            array($this,'ksf_form_display_condition_cb'),
            'smarty_ksf_section_general',
            'smarty_ksf_options_general'
        );

        add_settings_field(
            'smarty_ksf_form_display_categories',
            __('Global Select Categories', 'smarty-klaviyo-subscription-forms'),
            array($this,'ksf_form_display_categories_cb'),
            'smarty_ksf_section_general',
            'smarty_ksf_options_general'
        );

        // Register Klaviyo forms
        register_setting('smarty_ksf_settings_group', 'smarty_klaviyo_forms');

        // Activity & Logging settings
		$this->activity_logging->ksf_al_settings_init();

		// License settings
		$this->license->ksf_l_settings_init();
	}

	/**
     * Callback function for the General section.
     * 
     * @since    1.0.1
     */
	public function ksf_section_general_cb() {
		echo '<p>' . __('Configure general settings for Klaviyo forms display conditions.', 'smarty-klaviyo-subscription-forms') . '</p>';
	}

    /**
     * Callback function for the form display.
     * 
     * @since    1.0.1
     */
    public function ksf_form_display_condition_cb() {
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
    
    /**
     * Callback function for the categories display.
     * 
     * @since    1.0.1
     */
    public function ksf_form_display_categories_cb() {
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

    /**
	 * Function to check for the transient and displays a notice if it's present.
	 *
	 * @since    1.0.1
	 */
	public function ksf_success_notice() {
		if (get_transient('smarty_ksf_settings_updated')) { 
			?>
			<div class="notice notice-success smarty-ksf-auto-hide-notice">
				<p><?php echo esc_html__('Settings saved.', 'smarty-klaviyo-subscription-forms'); ?></p>
			</div>
			<?php
			// Delete the transient so we don't keep displaying the notice
			delete_transient('smarty_ksf_settings_updated');
		}
	}
}