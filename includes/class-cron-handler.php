<?php
/**
 * Cron Handler
 *
 * Handles scheduled monthly exports
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class CF7_Monthly_Export_Cron_Handler {

    /**
     * Constructor
     */
    public function __construct() {
        // Hook into the cron event
        add_action('cf7_monthly_export_cron', array($this, 'run_scheduled_export'));
    }

    /**
     * Run scheduled export
     */
    public function run_scheduled_export() {
        // Check if auto export is enabled
        $settings = get_option('cf7_monthly_export_settings', array());
        $auto_export_enabled = isset($settings['auto_export_enabled']) ? $settings['auto_export_enabled'] : false;

        if (!$auto_export_enabled) {
            error_log('CF7 Monthly Export - Scheduled export skipped: Auto export is disabled.');
            return;
        }

        // Check if required settings are configured
        if (empty($settings['google_credentials']) || empty($settings['spreadsheet_id'])) {
            error_log('CF7 Monthly Export - Scheduled export skipped: Google Sheets not configured.');
            return;
        }

        if (empty($settings['forms_to_export'])) {
            error_log('CF7 Monthly Export - Scheduled export skipped: No forms selected for export.');
            return;
        }

        // Run export
        $exporter = new CF7_Monthly_Export_Exporter();
        $result = $exporter->export_to_sheets();

        // Log result
        if ($result['success']) {
            error_log('CF7 Monthly Export - Scheduled export completed: ' . $result['message']);

            // Send email notification if configured
            $this->send_notification_email($result);
        } else {
            error_log('CF7 Monthly Export - Scheduled export failed: ' . $result['message']);

            // Send error email notification
            $this->send_error_email($result);
        }
    }

    /**
     * Send success notification email
     *
     * @param array $result Export result
     */
    private function send_notification_email($result) {
        $settings = get_option('cf7_monthly_export_settings', array());
        $notification_email = isset($settings['notification_email']) ? $settings['notification_email'] : get_option('admin_email');

        if (empty($notification_email) || !isset($settings['send_notifications']) || !$settings['send_notifications']) {
            return;
        }

        $to = $notification_email;
        $subject = sprintf('[%s] CF7 Monthly Export - Success', get_bloginfo('name'));

        $message = sprintf(
            "The monthly Contact Form 7 export has been completed successfully.\n\n" .
            "Details:\n" .
            "- Entries exported: %d\n" .
            "- Export date: %s\n" .
            "- Spreadsheet: %s\n\n" .
            "Message: %s\n\n" .
            "You can view the spreadsheet at:\n" .
            "https://docs.google.com/spreadsheets/d/%s\n",
            isset($result['count']) ? $result['count'] : 0,
            current_time('mysql'),
            isset($settings['sheet_name']) ? $settings['sheet_name'] : 'CF7 Exports',
            $result['message'],
            isset($settings['spreadsheet_id']) ? $settings['spreadsheet_id'] : ''
        );

        wp_mail($to, $subject, $message);
    }

    /**
     * Send error notification email
     *
     * @param array $result Export result
     */
    private function send_error_email($result) {
        $settings = get_option('cf7_monthly_export_settings', array());
        $notification_email = isset($settings['notification_email']) ? $settings['notification_email'] : get_option('admin_email');

        if (empty($notification_email)) {
            return;
        }

        $to = $notification_email;
        $subject = sprintf('[%s] CF7 Monthly Export - Error', get_bloginfo('name'));

        $message = sprintf(
            "The monthly Contact Form 7 export has failed.\n\n" .
            "Error message:\n" .
            "%s\n\n" .
            "Please check the plugin settings and try again.\n" .
            "You can manually run the export from the WordPress admin panel.\n",
            $result['message']
        );

        wp_mail($to, $subject, $message);
    }

    /**
     * Get next scheduled export date
     *
     * @return string|bool Next schedule date or false if not scheduled
     */
    public function get_next_schedule() {
        $timestamp = wp_next_scheduled('cf7_monthly_export_cron');

        if ($timestamp) {
            return date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $timestamp);
        }

        return false;
    }

    /**
     * Manually reschedule export
     *
     * @param string $schedule Schedule type (monthly, weekly, daily)
     */
    public function reschedule_export($schedule = 'monthly') {
        // Clear existing schedule
        $timestamp = wp_next_scheduled('cf7_monthly_export_cron');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'cf7_monthly_export_cron');
        }

        // Schedule new event
        $next_run = strtotime('first day of next month 02:00:00');

        if ($schedule === 'weekly') {
            $next_run = strtotime('next monday 02:00:00');
        } elseif ($schedule === 'daily') {
            $next_run = strtotime('tomorrow 02:00:00');
        }

        wp_schedule_event($next_run, $schedule, 'cf7_monthly_export_cron');
    }

    /**
     * Clear all schedules
     */
    public function clear_schedule() {
        $timestamp = wp_next_scheduled('cf7_monthly_export_cron');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'cf7_monthly_export_cron');
        }
    }
}
