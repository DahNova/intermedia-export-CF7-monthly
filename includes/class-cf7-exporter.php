<?php
/**
 * CF7 Data Exporter
 *
 * Handles extraction and export of Contact Form 7 data
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class CF7_Monthly_Export_Exporter {

    /**
     * Google Sheets client
     */
    private $sheets_client;

    /**
     * Settings
     */
    private $settings;

    /**
     * Constructor
     */
    public function __construct() {
        $this->sheets_client = new CF7_Monthly_Export_Google_Sheets_Client();
        $this->settings = get_option('cf7_monthly_export_settings', array());
    }

    /**
     * Get all CF7 forms
     *
     * @return array Array of form objects with id and title
     */
    public function get_all_cf7_forms() {
        $forms = array();

        $cf7_forms = get_posts(array(
            'post_type' => 'wpcf7_contact_form',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));

        foreach ($cf7_forms as $form) {
            $forms[] = array(
                'id' => $form->ID,
                'title' => $form->post_title
            );
        }

        return $forms;
    }

    /**
     * Get submissions from CFDB7 for specific forms
     *
     * @param array $form_ids Array of form IDs to export
     * @param string $since_date Get submissions since this date (Y-m-d format)
     * @return array Array of submissions
     */
    public function get_submissions($form_ids = null, $since_date = null) {
        global $wpdb;

        if ($form_ids === null) {
            $form_ids = isset($this->settings['forms_to_export']) ? $this->settings['forms_to_export'] : array();
        }

        if (empty($form_ids)) {
            return array();
        }

        // CFDB7 table name
        $table_name = $wpdb->prefix . 'db7_forms';

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return array();
        }

        // Build query
        $placeholders = implode(',', array_fill(0, count($form_ids), '%d'));
        $query = "SELECT * FROM $table_name WHERE form_post_id IN ($placeholders)";
        $params = $form_ids;

        // Add date filter if provided
        if ($since_date) {
            $query .= " AND form_date >= %s";
            $params[] = $since_date;
        }

        $query .= " ORDER BY form_date DESC";

        $results = $wpdb->get_results($wpdb->prepare($query, $params), ARRAY_A);

        return $results ? $results : array();
    }

    /**
     * Parse and normalize CFDB7 submission data
     *
     * @param array $submissions Raw submissions from CFDB7
     * @return array Normalized data ready for export
     */
    public function normalize_submissions($submissions) {
        $normalized = array();
        $all_fields = array();

        // First pass: collect all unique fields from all forms
        foreach ($submissions as $submission) {
            $form_data = maybe_unserialize($submission['form_value']);

            if (is_array($form_data)) {
                foreach ($form_data as $key => $value) {
                    // Skip internal CF7 fields and empty keys
                    if (!in_array($key, array('_wpcf7', '_wpcf7_version', '_wpcf7_locale', '_wpcf7_unit_tag', '_wpcf7_container_post')) && !empty($key)) {
                        $all_fields[$key] = true;
                    }
                }
            }
        }

        // Define standard columns
        $standard_columns = array(
            'submission_id' => 'ID Submissione',
            'form_id' => 'ID Form',
            'form_title' => 'Titolo Form',
            'submission_date' => 'Data Submissione',
            'submission_time' => 'Ora Submissione'
        );

        // Get all field names
        $field_names = array_keys($all_fields);
        sort($field_names); // Sort alphabetically for consistency

        // Second pass: normalize all submissions with all fields
        foreach ($submissions as $submission) {
            $form_data = maybe_unserialize($submission['form_value']);
            $form_post = get_post($submission['form_post_id']);

            $normalized_row = array();

            // Add standard columns
            $normalized_row['submission_id'] = $submission['form_id'];
            $normalized_row['form_id'] = $submission['form_post_id'];
            $normalized_row['form_title'] = $form_post ? $form_post->post_title : 'Unknown';

            // Parse date and time
            $date_time = new DateTime($submission['form_date']);
            $normalized_row['submission_date'] = $date_time->format('Y-m-d');
            $normalized_row['submission_time'] = $date_time->format('H:i:s');

            // Add all fields (with empty value if not present in this submission)
            if (is_array($form_data)) {
                foreach ($field_names as $field_name) {
                    $value = isset($form_data[$field_name]) ? $form_data[$field_name] : '';

                    // Handle array values (like checkboxes)
                    if (is_array($value)) {
                        $value = implode(', ', $value);
                    }

                    $normalized_row[$field_name] = $value;
                }
            }

            $normalized[] = $normalized_row;
        }

        return $normalized;
    }

    /**
     * Filter out already exported entries
     * Checks Google Sheets directly to see which submission IDs are already present
     *
     * @param array $submissions Normalized submissions
     * @return array Filtered submissions (only new ones)
     */
    public function filter_new_submissions($submissions) {
        // Try to get existing IDs from Google Sheets (source of truth)
        $exported_ids_from_sheets = array();

        try {
            // Initialize client if not already done
            if ($this->sheets_client->init_client()) {
                $exported_ids_from_sheets = $this->sheets_client->get_existing_submission_ids();
            }
        } catch (Exception $e) {
            error_log('CF7 Monthly Export - Error reading from Google Sheets, using local DB as fallback: ' . $e->getMessage());
        }

        // Fallback to local database if Google Sheets is not accessible
        if (empty($exported_ids_from_sheets)) {
            $exported_ids_from_sheets = isset($this->settings['exported_entries']) ? $this->settings['exported_entries'] : array();
        }

        // If no exported IDs at all, return all submissions
        if (empty($exported_ids_from_sheets)) {
            return $submissions;
        }

        // Filter out submissions that are already in Google Sheets
        $new_submissions = array();
        foreach ($submissions as $submission) {
            if (!in_array($submission['submission_id'], $exported_ids_from_sheets)) {
                $new_submissions[] = $submission;
            }
        }

        return $new_submissions;
    }

    /**
     * Mark submissions as exported
     *
     * @param array $submissions Submissions to mark as exported
     */
    public function mark_as_exported($submissions) {
        $exported_ids = isset($this->settings['exported_entries']) ? $this->settings['exported_entries'] : array();

        foreach ($submissions as $submission) {
            if (!in_array($submission['submission_id'], $exported_ids)) {
                $exported_ids[] = $submission['submission_id'];
            }
        }

        // Update settings
        $this->settings['exported_entries'] = $exported_ids;
        update_option('cf7_monthly_export_settings', $this->settings);
    }

    /**
     * Perform full export to Google Sheets
     *
     * @param bool $force_all Export all entries, not just new ones
     * @return array Result with success status and message
     */
    public function export_to_sheets($force_all = false) {
        try {
            // Initialize Google Sheets client
            if (!$this->sheets_client->init_client()) {
                throw new Exception('Failed to initialize Google Sheets client. Please check your credentials.');
            }

            // Get submissions
            $submissions = $this->get_submissions();

            if (empty($submissions)) {
                return array(
                    'success' => false,
                    'message' => 'No submissions found to export.'
                );
            }

            // Normalize submissions
            $normalized = $this->normalize_submissions($submissions);

            // Filter for new entries only (unless force_all is true)
            if (!$force_all) {
                $normalized = $this->filter_new_submissions($normalized);
            }

            if (empty($normalized)) {
                return array(
                    'success' => true,
                    'message' => 'No new submissions to export.'
                );
            }

            // Check if this is the first export (sheet is empty)
            $existing_data = $this->sheets_client->get_existing_data();
            $is_first_export = empty($existing_data);

            // Append data to Google Sheets
            $success = $this->sheets_client->append_data($normalized, $is_first_export);

            if (!$success) {
                throw new Exception('Failed to append data to Google Sheets.');
            }

            // Format header if first export
            if ($is_first_export) {
                $this->sheets_client->format_header();
            }

            // Mark submissions as exported
            $this->mark_as_exported($normalized);

            // Update last export date
            $this->settings['last_export_date'] = current_time('mysql');
            update_option('cf7_monthly_export_settings', $this->settings);

            return array(
                'success' => true,
                'message' => sprintf('Successfully exported %d submissions to Google Sheets.', count($normalized)),
                'count' => count($normalized)
            );

        } catch (Exception $e) {
            error_log('CF7 Monthly Export - Export Error: ' . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Export failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * Get export statistics
     *
     * @return array Statistics about exports
     */
    public function get_export_stats() {
        // Try to get real count from Google Sheets
        $exported_count = 0;
        try {
            if ($this->sheets_client->init_client()) {
                $exported_ids = $this->sheets_client->get_existing_submission_ids();
                $exported_count = count($exported_ids);
            }
        } catch (Exception $e) {
            // Fallback to local database count on error
            $exported_count = count(isset($this->settings['exported_entries']) ? $this->settings['exported_entries'] : array());
        }

        $stats = array(
            'total_forms' => count($this->get_all_cf7_forms()),
            'forms_to_export' => count(isset($this->settings['forms_to_export']) ? $this->settings['forms_to_export'] : array()),
            'total_submissions' => 0,
            'exported_count' => $exported_count,
            'pending_count' => 0,
            'last_export_date' => isset($this->settings['last_export_date']) ? $this->settings['last_export_date'] : 'Never'
        );

        // Get total submissions for selected forms
        $submissions = $this->get_submissions();
        $stats['total_submissions'] = count($submissions);

        // Calculate pending (filter_new_submissions now checks Google Sheets)
        $normalized = $this->normalize_submissions($submissions);
        $new_submissions = $this->filter_new_submissions($normalized);
        $stats['pending_count'] = count($new_submissions);

        return $stats;
    }

    /**
     * Test connection to Google Sheets
     *
     * @return array Result with success status and message
     */
    public function test_connection() {
        try {
            if (!$this->sheets_client->init_client()) {
                throw new Exception('Failed to initialize Google Sheets client.');
            }

            // Try to read from spreadsheet
            $this->sheets_client->get_existing_data();

            return array(
                'success' => true,
                'message' => 'Successfully connected to Google Sheets!'
            );

        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage()
            );
        }
    }
}
