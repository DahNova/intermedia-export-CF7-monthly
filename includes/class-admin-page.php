<?php
/**
 * Admin Page Handler
 *
 * Manages the plugin settings page in WordPress admin
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class CF7_Monthly_Export_Admin_Page {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // AJAX handlers
        add_action('wp_ajax_cf7_test_connection', array($this, 'ajax_test_connection'));
        add_action('wp_ajax_cf7_manual_export', array($this, 'ajax_manual_export'));
        add_action('wp_ajax_cf7_get_stats', array($this, 'ajax_get_stats'));
        add_action('wp_ajax_cf7_test_cron', array($this, 'ajax_test_cron'));
    }

    /**
     * Add admin menu item
     */
    public function add_admin_menu() {
        add_options_page(
            __('CF7 Monthly Export Settings', 'cf7-monthly-export'),
            __('CF7 Export', 'cf7-monthly-export'),
            'manage_options',
            'cf7-monthly-export',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('cf7_monthly_export_settings_group', 'cf7_monthly_export_settings', array(
            'sanitize_callback' => array($this, 'sanitize_settings')
        ));
    }

    /**
     * Sanitize settings before saving
     */
    public function sanitize_settings($input) {
        $sanitized = array();

        // Google credentials (validate JSON)
        if (isset($input['google_credentials'])) {
            $credentials = trim($input['google_credentials']);
            if (!empty($credentials)) {
                $decoded = json_decode($credentials, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $sanitized['google_credentials'] = $credentials;
                } else {
                    add_settings_error(
                        'cf7_monthly_export_settings',
                        'invalid_credentials',
                        __('Invalid Google credentials JSON format.', 'cf7-monthly-export')
                    );
                }
            }
        }

        // Spreadsheet ID
        if (isset($input['spreadsheet_id'])) {
            $sanitized['spreadsheet_id'] = sanitize_text_field($input['spreadsheet_id']);
        }

        // Sheet name
        if (isset($input['sheet_name'])) {
            $sanitized['sheet_name'] = sanitize_text_field($input['sheet_name']);
        }

        // Forms to export
        if (isset($input['forms_to_export']) && is_array($input['forms_to_export'])) {
            $sanitized['forms_to_export'] = array_map('intval', $input['forms_to_export']);
        } else {
            $sanitized['forms_to_export'] = array();
        }

        // Auto export enabled
        $sanitized['auto_export_enabled'] = isset($input['auto_export_enabled']) ? true : false;

        // Schedule frequency
        if (isset($input['schedule_frequency']) && in_array($input['schedule_frequency'], array('daily', 'weekly', 'monthly'))) {
            $sanitized['schedule_frequency'] = $input['schedule_frequency'];
        } else {
            $sanitized['schedule_frequency'] = 'monthly';
        }

        // Send notifications
        $sanitized['send_notifications'] = isset($input['send_notifications']) ? true : false;

        // Notification email
        if (isset($input['notification_email'])) {
            $email = sanitize_email($input['notification_email']);
            if (is_email($email)) {
                $sanitized['notification_email'] = $email;
            }
        }

        // Preserve exported entries list and last export date
        $current_settings = get_option('cf7_monthly_export_settings', array());
        $sanitized['exported_entries'] = isset($current_settings['exported_entries']) ? $current_settings['exported_entries'] : array();
        $sanitized['last_export_date'] = isset($current_settings['last_export_date']) ? $current_settings['last_export_date'] : '';

        // Reschedule cron if frequency changed or auto export enabled changed
        if ($sanitized['auto_export_enabled']) {
            $cron_handler = new CF7_Monthly_Export_Cron_Handler();
            $cron_handler->reschedule_export($sanitized['schedule_frequency']);
        }

        return $sanitized;
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if ($hook !== 'settings_page_cf7-monthly-export') {
            return;
        }

        wp_enqueue_style('cf7-monthly-export-admin', CF7_MONTHLY_EXPORT_PLUGIN_URL . 'admin/css/admin-style.css', array(), CF7_MONTHLY_EXPORT_VERSION);

        wp_enqueue_script('cf7-monthly-export-admin', CF7_MONTHLY_EXPORT_PLUGIN_URL . 'admin/js/admin-script.js', array('jquery'), CF7_MONTHLY_EXPORT_VERSION, true);

        wp_localize_script('cf7-monthly-export-admin', 'cf7ExportAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cf7_export_nonce'),
            'strings' => array(
                'testing' => __('Testing connection...', 'cf7-monthly-export'),
                'exporting' => __('Exporting data...', 'cf7-monthly-export'),
                'success' => __('Success!', 'cf7-monthly-export'),
                'error' => __('Error', 'cf7-monthly-export'),
                'confirm_export' => __('Are you sure you want to export all data now?', 'cf7-monthly-export')
            )
        ));
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = get_option('cf7_monthly_export_settings', array());
        $exporter = new CF7_Monthly_Export_Exporter();
        $cron_handler = new CF7_Monthly_Export_Cron_Handler();

        // Get available forms
        $all_forms = $exporter->get_all_cf7_forms();
        $selected_forms = isset($settings['forms_to_export']) ? $settings['forms_to_export'] : array();

        // Get stats
        $stats = $exporter->get_export_stats();

        // Get next schedule
        $next_schedule = $cron_handler->get_next_schedule();

        include CF7_MONTHLY_EXPORT_PLUGIN_DIR . 'admin/views/settings-page.php';
    }

    /**
     * AJAX: Test Google Sheets connection
     */
    public function ajax_test_connection() {
        check_ajax_referer('cf7_export_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied.'));
        }

        $exporter = new CF7_Monthly_Export_Exporter();
        $result = $exporter->test_connection();

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * AJAX: Manual export
     */
    public function ajax_manual_export() {
        check_ajax_referer('cf7_export_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied.'));
        }

        $force_all = isset($_POST['force_all']) && $_POST['force_all'] === 'true';

        $exporter = new CF7_Monthly_Export_Exporter();
        $result = $exporter->export_to_sheets($force_all);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * AJAX: Get export statistics
     */
    public function ajax_get_stats() {
        check_ajax_referer('cf7_export_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied.'));
        }

        $exporter = new CF7_Monthly_Export_Exporter();
        $stats = $exporter->get_export_stats();

        wp_send_json_success($stats);
    }

    /**
     * AJAX: Test cron execution
     */
    public function ajax_test_cron() {
        check_ajax_referer('cf7_export_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied.'));
        }

        // Run the cron job manually
        $cron_handler = new CF7_Monthly_Export_Cron_Handler();
        $cron_handler->run_scheduled_export();

        wp_send_json_success(array(
            'message' => 'Cron job executed successfully. Check the results above or your email.'
        ));
    }
}
