<?php
/**
 * Google Sheets Client
 *
 * Handles connection and data operations with Google Sheets API
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class CF7_Monthly_Export_Google_Sheets_Client {

    /**
     * Google Client instance
     */
    private $client;

    /**
     * Google Sheets Service
     */
    private $service;

    /**
     * Spreadsheet ID
     */
    private $spreadsheet_id;

    /**
     * Sheet name
     */
    private $sheet_name;

    /**
     * Constructor
     */
    public function __construct() {
        $settings = get_option('cf7_monthly_export_settings', array());
        $this->spreadsheet_id = isset($settings['spreadsheet_id']) ? $settings['spreadsheet_id'] : '';
        $this->sheet_name = isset($settings['sheet_name']) ? $settings['sheet_name'] : 'CF7 Exports';
    }

    /**
     * Initialize Google Client with credentials
     *
     * @param string $credentials_json JSON credentials from Google Cloud Console
     * @return bool True on success, false on failure
     */
    public function init_client($credentials_json = null) {
        try {
            if (!class_exists('Google_Client')) {
                throw new Exception('Google API Client library not found. Please run composer install.');
            }

            $this->client = new Google_Client();
            $this->client->setApplicationName('CF7 Monthly Export');
            $this->client->setScopes([Google_Service_Sheets::SPREADSHEETS]);
            $this->client->setAccessType('offline');

            // Get credentials from settings if not provided
            if ($credentials_json === null) {
                $settings = get_option('cf7_monthly_export_settings', array());
                $credentials_json = isset($settings['google_credentials']) ? $settings['google_credentials'] : '';
            }

            if (empty($credentials_json)) {
                throw new Exception('Google credentials not configured.');
            }

            // Decode credentials JSON
            $credentials = json_decode($credentials_json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON credentials format.');
            }

            $this->client->setAuthConfig($credentials);

            // Initialize Sheets service
            $this->service = new Google_Service_Sheets($this->client);

            return true;

        } catch (Exception $e) {
            error_log('CF7 Monthly Export - Google Sheets Client Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if sheet exists in spreadsheet
     *
     * @param string $sheet_name Sheet name to check
     * @return bool|int False if not exists, sheet ID if exists
     */
    public function sheet_exists($sheet_name = null) {
        if ($sheet_name === null) {
            $sheet_name = $this->sheet_name;
        }

        try {
            $spreadsheet = $this->service->spreadsheets->get($this->spreadsheet_id);
            $sheets = $spreadsheet->getSheets();

            foreach ($sheets as $sheet) {
                if ($sheet->getProperties()->getTitle() === $sheet_name) {
                    return $sheet->getProperties()->getSheetId();
                }
            }

            return false;

        } catch (Exception $e) {
            error_log('CF7 Monthly Export - Error checking sheet existence: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create new sheet
     *
     * @param string $sheet_name Sheet name to create
     * @return bool True on success, false on failure
     */
    public function create_sheet($sheet_name = null) {
        if ($sheet_name === null) {
            $sheet_name = $this->sheet_name;
        }

        try {
            $requests = [
                new Google_Service_Sheets_Request([
                    'addSheet' => [
                        'properties' => [
                            'title' => $sheet_name
                        ]
                    ]
                ])
            ];

            $batchUpdateRequest = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
                'requests' => $requests
            ]);

            $this->service->spreadsheets->batchUpdate($this->spreadsheet_id, $batchUpdateRequest);
            return true;

        } catch (Exception $e) {
            error_log('CF7 Monthly Export - Error creating sheet: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all existing data from sheet
     *
     * @param string $range Range to read (e.g., 'A:ZZ')
     * @return array Existing data
     */
    public function get_existing_data($range = null) {
        if ($range === null) {
            $range = $this->sheet_name . '!A:ZZ';
        } else {
            $range = $this->sheet_name . '!' . $range;
        }

        try {
            $response = $this->service->spreadsheets_values->get($this->spreadsheet_id, $range);
            $values = $response->getValues();

            return $values ? $values : array();

        } catch (Exception $e) {
            error_log('CF7 Monthly Export - Error reading existing data: ' . $e->getMessage());
            return array();
        }
    }

    /**
     * Get existing submission IDs from column A
     *
     * @return array Array of submission IDs already in the sheet
     */
    public function get_existing_submission_ids() {
        try {
            // Read only column A (submission IDs)
            $range = $this->sheet_name . '!A:A';
            $response = $this->service->spreadsheets_values->get($this->spreadsheet_id, $range);
            $values = $response->getValues();

            if (empty($values)) {
                return array();
            }

            $submission_ids = array();
            // Skip header row (index 0) and collect all submission IDs
            for ($i = 1; $i < count($values); $i++) {
                if (isset($values[$i][0]) && !empty($values[$i][0])) {
                    $submission_ids[] = $values[$i][0];
                }
            }

            return $submission_ids;

        } catch (Exception $e) {
            error_log('CF7 Monthly Export - Error reading submission IDs: ' . $e->getMessage());
            return array();
        }
    }

    /**
     * Append data to sheet
     *
     * @param array $data Array of rows to append
     * @param bool $include_header Include header row
     * @return bool True on success, false on failure
     */
    public function append_data($data, $include_header = false) {
        try {
            // Check if sheet exists, create if not
            if (!$this->sheet_exists()) {
                $this->create_sheet();
            }

            // Get existing data to check if we need to add headers
            $existing_data = $this->get_existing_data();
            $is_empty_sheet = empty($existing_data);

            $values_to_append = array();

            // Add header if needed
            if ($include_header && $is_empty_sheet && !empty($data)) {
                $values_to_append[] = array_keys($data[0]);
            }

            // Add data rows
            foreach ($data as $row) {
                $values_to_append[] = array_values($row);
            }

            if (empty($values_to_append)) {
                return true; // Nothing to append
            }

            // Use A1 as starting point to ensure data starts from column A
            $range = $this->sheet_name . '!A1';
            $body = new Google_Service_Sheets_ValueRange([
                'values' => $values_to_append
            ]);

            $params = [
                'valueInputOption' => 'RAW',
                'insertDataOption' => 'INSERT_ROWS'
            ];

            $this->service->spreadsheets_values->append(
                $this->spreadsheet_id,
                $range,
                $body,
                $params
            );

            return true;

        } catch (Exception $e) {
            error_log('CF7 Monthly Export - Error appending data: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Clear all data from sheet
     *
     * @return bool True on success, false on failure
     */
    public function clear_sheet() {
        try {
            $range = $this->sheet_name . '!A:ZZ';
            $clear = new Google_Service_Sheets_ClearValuesRequest();

            $this->service->spreadsheets_values->clear(
                $this->spreadsheet_id,
                $range,
                $clear
            );

            return true;

        } catch (Exception $e) {
            error_log('CF7 Monthly Export - Error clearing sheet: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update specific range with data
     *
     * @param string $range Range to update (e.g., 'A1:C10')
     * @param array $data Data to update
     * @return bool True on success, false on failure
     */
    public function update_range($range, $data) {
        try {
            $range = $this->sheet_name . '!' . $range;
            $body = new Google_Service_Sheets_ValueRange([
                'values' => $data
            ]);

            $params = [
                'valueInputOption' => 'RAW'
            ];

            $this->service->spreadsheets_values->update(
                $this->spreadsheet_id,
                $range,
                $body,
                $params
            );

            return true;

        } catch (Exception $e) {
            error_log('CF7 Monthly Export - Error updating range: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Format header row (bold, freeze)
     *
     * @return bool True on success, false on failure
     */
    public function format_header() {
        try {
            $sheet_id = $this->sheet_exists();
            if ($sheet_id === false) {
                return false;
            }

            $requests = [
                // Bold header row
                new Google_Service_Sheets_Request([
                    'repeatCell' => [
                        'range' => [
                            'sheetId' => $sheet_id,
                            'startRowIndex' => 0,
                            'endRowIndex' => 1
                        ],
                        'cell' => [
                            'userEnteredFormat' => [
                                'textFormat' => [
                                    'bold' => true
                                ]
                            ]
                        ],
                        'fields' => 'userEnteredFormat.textFormat.bold'
                    ]
                ]),
                // Freeze header row
                new Google_Service_Sheets_Request([
                    'updateSheetProperties' => [
                        'properties' => [
                            'sheetId' => $sheet_id,
                            'gridProperties' => [
                                'frozenRowCount' => 1
                            ]
                        ],
                        'fields' => 'gridProperties.frozenRowCount'
                    ]
                ])
            ];

            $batchUpdateRequest = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
                'requests' => $requests
            ]);

            $this->service->spreadsheets->batchUpdate($this->spreadsheet_id, $batchUpdateRequest);
            return true;

        } catch (Exception $e) {
            error_log('CF7 Monthly Export - Error formatting header: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Set spreadsheet ID
     */
    public function set_spreadsheet_id($spreadsheet_id) {
        $this->spreadsheet_id = $spreadsheet_id;
    }

    /**
     * Set sheet name
     */
    public function set_sheet_name($sheet_name) {
        $this->sheet_name = $sheet_name;
    }

    /**
     * Get spreadsheet ID
     */
    public function get_spreadsheet_id() {
        return $this->spreadsheet_id;
    }

    /**
     * Get sheet name
     */
    public function get_sheet_name() {
        return $this->sheet_name;
    }
}
