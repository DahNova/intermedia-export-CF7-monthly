<?php
/**
 * Admin Settings Page Template
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap cf7-export-settings">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php settings_errors('cf7_monthly_export_settings'); ?>

    <div class="cf7-export-container">
        <!-- Statistics Card -->
        <div class="cf7-export-stats-card">
            <h2><?php _e('Export Statistics', 'cf7-monthly-export'); ?></h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-label"><?php _e('Total Forms', 'cf7-monthly-export'); ?></span>
                    <span class="stat-value"><?php echo esc_html($stats['total_forms']); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label"><?php _e('Forms Selected', 'cf7-monthly-export'); ?></span>
                    <span class="stat-value"><?php echo esc_html($stats['forms_to_export']); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label"><?php _e('Total Submissions', 'cf7-monthly-export'); ?></span>
                    <span class="stat-value"><?php echo esc_html($stats['total_submissions']); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label"><?php _e('Already Exported', 'cf7-monthly-export'); ?></span>
                    <span class="stat-value"><?php echo esc_html($stats['exported_count']); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label"><?php _e('Pending Export', 'cf7-monthly-export'); ?></span>
                    <span class="stat-value highlight"><?php echo esc_html($stats['pending_count']); ?></span>
                </div>
                <div class="stat-item full-width">
                    <span class="stat-label"><?php _e('Last Export', 'cf7-monthly-export'); ?></span>
                    <span class="stat-value">
                        <?php
                        if ($stats['last_export_date'] !== 'Never') {
                            echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($stats['last_export_date'])));
                        } else {
                            echo esc_html($stats['last_export_date']);
                        }
                        ?>
                    </span>
                </div>
                <?php if ($next_schedule): ?>
                <div class="stat-item full-width">
                    <span class="stat-label"><?php _e('Next Scheduled Export', 'cf7-monthly-export'); ?></span>
                    <span class="stat-value"><?php echo esc_html($next_schedule); ?></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <button type="button" class="button button-primary" id="cf7-manual-export">
                    <?php _e('Export Now (New Entries Only)', 'cf7-monthly-export'); ?>
                </button>
                <button type="button" class="button button-secondary" id="cf7-test-connection">
                    <?php _e('Test Connection', 'cf7-monthly-export'); ?>
                </button>
            </div>

            <div id="cf7-export-result" class="export-result" style="display:none;"></div>
        </div>

        <!-- Settings Form -->
        <form method="post" action="options.php" class="cf7-export-form">
            <?php settings_fields('cf7_monthly_export_settings_group'); ?>

            <!-- Google Sheets Configuration -->
            <div class="cf7-export-section">
                <h2><?php _e('Google Sheets Configuration', 'cf7-monthly-export'); ?></h2>
                <p class="description">
                    <?php _e('Configure your Google Sheets API credentials and target spreadsheet.', 'cf7-monthly-export'); ?>
                    <a href="https://console.cloud.google.com/" target="_blank"><?php _e('Get credentials from Google Cloud Console', 'cf7-monthly-export'); ?></a>
                </p>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="google_credentials"><?php _e('Google Credentials JSON', 'cf7-monthly-export'); ?></label>
                        </th>
                        <td>
                            <textarea
                                name="cf7_monthly_export_settings[google_credentials]"
                                id="google_credentials"
                                rows="8"
                                class="large-text code"
                                placeholder='{"type": "service_account", "project_id": "...", ...}'
                            ><?php echo esc_textarea(isset($settings['google_credentials']) ? $settings['google_credentials'] : ''); ?></textarea>
                            <p class="description">
                                <?php _e('Paste the entire JSON content from your Google Service Account credentials file.', 'cf7-monthly-export'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="spreadsheet_id"><?php _e('Spreadsheet ID', 'cf7-monthly-export'); ?></label>
                        </th>
                        <td>
                            <input
                                type="text"
                                name="cf7_monthly_export_settings[spreadsheet_id]"
                                id="spreadsheet_id"
                                value="<?php echo esc_attr(isset($settings['spreadsheet_id']) ? $settings['spreadsheet_id'] : ''); ?>"
                                class="regular-text"
                                placeholder="1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms"
                            />
                            <p class="description">
                                <?php _e('The ID from your Google Sheets URL: https://docs.google.com/spreadsheets/d/<strong>SPREADSHEET_ID</strong>/edit', 'cf7-monthly-export'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="sheet_name"><?php _e('Sheet Name', 'cf7-monthly-export'); ?></label>
                        </th>
                        <td>
                            <input
                                type="text"
                                name="cf7_monthly_export_settings[sheet_name]"
                                id="sheet_name"
                                value="<?php echo esc_attr(isset($settings['sheet_name']) ? $settings['sheet_name'] : 'CF7 Exports'); ?>"
                                class="regular-text"
                                placeholder="CF7 Exports"
                            />
                            <p class="description">
                                <?php _e('Name of the sheet tab where data will be exported. Will be created if it doesn\'t exist.', 'cf7-monthly-export'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Forms Selection -->
            <div class="cf7-export-section">
                <h2><?php _e('Forms to Export', 'cf7-monthly-export'); ?></h2>
                <p class="description">
                    <?php _e('Select which Contact Form 7 forms should be included in the export.', 'cf7-monthly-export'); ?>
                </p>

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Select Forms', 'cf7-monthly-export'); ?></th>
                        <td>
                            <?php if (!empty($all_forms)): ?>
                                <fieldset>
                                    <?php foreach ($all_forms as $form): ?>
                                        <label>
                                            <input
                                                type="checkbox"
                                                name="cf7_monthly_export_settings[forms_to_export][]"
                                                value="<?php echo esc_attr($form['id']); ?>"
                                                <?php checked(in_array($form['id'], $selected_forms)); ?>
                                            />
                                            <?php echo esc_html($form['title']); ?> (ID: <?php echo esc_html($form['id']); ?>)
                                        </label><br/>
                                    <?php endforeach; ?>
                                </fieldset>
                            <?php else: ?>
                                <p><?php _e('No Contact Form 7 forms found. Please create at least one form.', 'cf7-monthly-export'); ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Storage Backend -->
            <div class="cf7-export-section">
                <h2><?php _e('Storage Backend', 'cf7-monthly-export'); ?></h2>
                <p class="description">
                    <?php _e('Choose which plugin is used to store Contact Form 7 submissions.', 'cf7-monthly-export'); ?>
                </p>

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Submission Storage', 'cf7-monthly-export'); ?></th>
                        <td>
                            <?php
                            $current_backend = isset($settings['storage_backend']) ? $settings['storage_backend'] : 'auto';
                            $detected_backend = $exporter->detect_storage_backend();
                            ?>

                            <label style="display: block; margin-bottom: 10px;">
                                <input
                                    type="radio"
                                    name="cf7_monthly_export_settings[storage_backend]"
                                    value="auto"
                                    <?php checked($current_backend, 'auto'); ?>
                                />
                                <?php _e('Auto-detect (recommended)', 'cf7-monthly-export'); ?>
                            </label>

                            <label style="display: block; margin-bottom: 10px;">
                                <input
                                    type="radio"
                                    name="cf7_monthly_export_settings[storage_backend]"
                                    value="flamingo"
                                    <?php checked($current_backend, 'flamingo'); ?>
                                />
                                <?php _e('Flamingo', 'cf7-monthly-export'); ?>
                            </label>

                            <label style="display: block; margin-bottom: 10px;">
                                <input
                                    type="radio"
                                    name="cf7_monthly_export_settings[storage_backend]"
                                    value="cfdb7"
                                    <?php checked($current_backend, 'cfdb7'); ?>
                                />
                                <?php _e('Contact Form CFDB7', 'cf7-monthly-export'); ?>
                            </label>

                            <p class="description" style="margin-top: 15px;">
                                <strong><?php _e('Currently detected:', 'cf7-monthly-export'); ?></strong>
                                <?php
                                if ($detected_backend === 'flamingo') {
                                    echo '<span style="color: #00a32a;">✓ ' . __('Flamingo is active', 'cf7-monthly-export') . '</span>';
                                } elseif ($detected_backend === 'cfdb7') {
                                    echo '<span style="color: #00a32a;">✓ ' . __('Contact Form CFDB7 is active', 'cf7-monthly-export') . '</span>';
                                } else {
                                    echo '<span style="color: #d63638;">✗ ' . __('No storage backend detected', 'cf7-monthly-export') . '</span>';
                                }
                                ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Export Settings -->
            <div class="cf7-export-section">
                <h2><?php _e('Export Settings', 'cf7-monthly-export'); ?></h2>

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Automatic Export', 'cf7-monthly-export'); ?></th>
                        <td>
                            <label>
                                <input
                                    type="checkbox"
                                    name="cf7_monthly_export_settings[auto_export_enabled]"
                                    value="1"
                                    <?php checked(isset($settings['auto_export_enabled']) ? $settings['auto_export_enabled'] : false); ?>
                                />
                                <?php _e('Enable automatic scheduled export', 'cf7-monthly-export'); ?>
                            </label>
                            <p class="description">
                                <?php _e('When enabled, exports will run automatically based on the schedule below.', 'cf7-monthly-export'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Schedule Frequency', 'cf7-monthly-export'); ?></th>
                        <td>
                            <select name="cf7_monthly_export_settings[schedule_frequency]">
                                <option value="daily" <?php selected(isset($settings['schedule_frequency']) ? $settings['schedule_frequency'] : 'monthly', 'daily'); ?>>
                                    <?php _e('Daily (every day at 2:00 AM)', 'cf7-monthly-export'); ?>
                                </option>
                                <option value="weekly" <?php selected(isset($settings['schedule_frequency']) ? $settings['schedule_frequency'] : 'monthly', 'weekly'); ?>>
                                    <?php _e('Weekly (every Monday at 2:00 AM)', 'cf7-monthly-export'); ?>
                                </option>
                                <option value="monthly" <?php selected(isset($settings['schedule_frequency']) ? $settings['schedule_frequency'] : 'monthly', 'monthly'); ?>>
                                    <?php _e('Monthly (first day of month at 2:00 AM)', 'cf7-monthly-export'); ?>
                                </option>
                            </select>
                            <p class="description">
                                <?php
                                if ($next_schedule) {
                                    printf(__('Next scheduled export: <strong>%s</strong>', 'cf7-monthly-export'), esc_html($next_schedule));
                                } else {
                                    _e('No export scheduled. Enable automatic export above and save settings.', 'cf7-monthly-export');
                                }
                                ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Test Cron', 'cf7-monthly-export'); ?></th>
                        <td>
                            <button type="button" class="button button-secondary" id="cf7-test-cron">
                                <?php _e('Run Cron Now (Test)', 'cf7-monthly-export'); ?>
                            </button>
                            <p class="description">
                                <?php _e('Manually trigger the scheduled export to test if everything works correctly.', 'cf7-monthly-export'); ?>
                            </p>
                            <div id="cf7-cron-result" style="margin-top: 10px;"></div>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Email Notifications', 'cf7-monthly-export'); ?></th>
                        <td>
                            <label>
                                <input
                                    type="checkbox"
                                    name="cf7_monthly_export_settings[send_notifications]"
                                    value="1"
                                    <?php checked(isset($settings['send_notifications']) ? $settings['send_notifications'] : false); ?>
                                />
                                <?php _e('Send email notification after automatic export', 'cf7-monthly-export'); ?>
                            </label>
                            <br/><br/>
                            <input
                                type="email"
                                name="cf7_monthly_export_settings[notification_email]"
                                value="<?php echo esc_attr(isset($settings['notification_email']) ? $settings['notification_email'] : get_option('admin_email')); ?>"
                                class="regular-text"
                                placeholder="<?php echo esc_attr(get_option('admin_email')); ?>"
                            />
                            <p class="description">
                                <?php _e('Email address to receive export notifications. Defaults to admin email.', 'cf7-monthly-export'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <?php submit_button(__('Save Settings', 'cf7-monthly-export')); ?>
        </form>

        <!-- Documentation -->
        <div class="cf7-export-section">
            <h2><?php _e('Setup Instructions', 'cf7-monthly-export'); ?></h2>
            <ol class="cf7-instructions">
                <li>
                    <strong><?php _e('Create a Google Cloud Project', 'cf7-monthly-export'); ?></strong>
                    <ul>
                        <li><?php _e('Go to', 'cf7-monthly-export'); ?> <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a></li>
                        <li><?php _e('Create a new project or select an existing one', 'cf7-monthly-export'); ?></li>
                    </ul>
                </li>
                <li>
                    <strong><?php _e('Enable Google Sheets API', 'cf7-monthly-export'); ?></strong>
                    <ul>
                        <li><?php _e('In your project, go to "APIs & Services" > "Library"', 'cf7-monthly-export'); ?></li>
                        <li><?php _e('Search for "Google Sheets API" and enable it', 'cf7-monthly-export'); ?></li>
                    </ul>
                </li>
                <li>
                    <strong><?php _e('Create Service Account', 'cf7-monthly-export'); ?></strong>
                    <ul>
                        <li><?php _e('Go to "APIs & Services" > "Credentials"', 'cf7-monthly-export'); ?></li>
                        <li><?php _e('Click "Create Credentials" > "Service Account"', 'cf7-monthly-export'); ?></li>
                        <li><?php _e('Fill in the details and click "Create and Continue"', 'cf7-monthly-export'); ?></li>
                        <li><?php _e('Skip optional steps and click "Done"', 'cf7-monthly-export'); ?></li>
                    </ul>
                </li>
                <li>
                    <strong><?php _e('Download Credentials', 'cf7-monthly-export'); ?></strong>
                    <ul>
                        <li><?php _e('Click on the created service account', 'cf7-monthly-export'); ?></li>
                        <li><?php _e('Go to "Keys" tab', 'cf7-monthly-export'); ?></li>
                        <li><?php _e('Click "Add Key" > "Create new key"', 'cf7-monthly-export'); ?></li>
                        <li><?php _e('Choose JSON format and download', 'cf7-monthly-export'); ?></li>
                        <li><?php _e('Copy the entire content and paste it in the "Google Credentials JSON" field above', 'cf7-monthly-export'); ?></li>
                    </ul>
                </li>
                <li>
                    <strong><?php _e('Share Your Google Spreadsheet', 'cf7-monthly-export'); ?></strong>
                    <ul>
                        <li><?php _e('Open your Google Spreadsheet', 'cf7-monthly-export'); ?></li>
                        <li><?php _e('Click "Share" button', 'cf7-monthly-export'); ?></li>
                        <li><?php _e('Add the service account email (found in the JSON credentials as "client_email")', 'cf7-monthly-export'); ?></li>
                        <li><?php _e('Give it "Editor" permissions', 'cf7-monthly-export'); ?></li>
                    </ul>
                </li>
                <li>
                    <strong><?php _e('Configure Plugin', 'cf7-monthly-export'); ?></strong>
                    <ul>
                        <li><?php _e('Fill in the Spreadsheet ID from your Google Sheets URL', 'cf7-monthly-export'); ?></li>
                        <li><?php _e('Select the forms you want to export', 'cf7-monthly-export'); ?></li>
                        <li><?php _e('Save settings and test the connection', 'cf7-monthly-export'); ?></li>
                    </ul>
                </li>
            </ol>
        </div>
    </div>
</div>
