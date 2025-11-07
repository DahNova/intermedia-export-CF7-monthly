<?php
/**
 * Plugin Name: CF7 Monthly Export to Google Sheets
 * Plugin URI: https://github.com/DahNova/intermedia-export-CF7-monthly
 * Description: Export Contact Form 7 submissions to Google Sheets monthly or on-demand
 * Version: 1.0.0
 * Author: DahNova
 * Author URI: https://github.com/DahNova
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cf7-monthly-export
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('CF7_MONTHLY_EXPORT_VERSION', '1.0.0');
define('CF7_MONTHLY_EXPORT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CF7_MONTHLY_EXPORT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CF7_MONTHLY_EXPORT_PLUGIN_FILE', __FILE__);

// Autoloader for Composer dependencies
if (file_exists(CF7_MONTHLY_EXPORT_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once CF7_MONTHLY_EXPORT_PLUGIN_DIR . 'vendor/autoload.php';
}

// Include plugin classes
require_once CF7_MONTHLY_EXPORT_PLUGIN_DIR . 'includes/class-google-sheets-client.php';
require_once CF7_MONTHLY_EXPORT_PLUGIN_DIR . 'includes/class-cf7-exporter.php';
require_once CF7_MONTHLY_EXPORT_PLUGIN_DIR . 'includes/class-cron-handler.php';
require_once CF7_MONTHLY_EXPORT_PLUGIN_DIR . 'includes/class-admin-page.php';

/**
 * Main plugin class
 */
class CF7_Monthly_Export {

    /**
     * Single instance of the class
     */
    private static $instance = null;

    /**
     * Admin page handler
     */
    public $admin_page;

    /**
     * Cron handler
     */
    public $cron_handler;

    /**
     * Get single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Activation/Deactivation hooks
        register_activation_hook(CF7_MONTHLY_EXPORT_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(CF7_MONTHLY_EXPORT_PLUGIN_FILE, array($this, 'deactivate'));

        // Admin initialization
        if (is_admin()) {
            $this->admin_page = new CF7_Monthly_Export_Admin_Page();
        }

        // Cron handler
        $this->cron_handler = new CF7_Monthly_Export_Cron_Handler();

        // Load plugin textdomain
        add_action('plugins_loaded', array($this, 'load_textdomain'));

        // Check dependencies
        add_action('admin_init', array($this, 'check_dependencies'));
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Schedule monthly cron event
        if (!wp_next_scheduled('cf7_monthly_export_cron')) {
            // Schedule for first day of next month at 2 AM
            $next_month = strtotime('first day of next month 02:00:00');
            wp_schedule_event($next_month, 'monthly', 'cf7_monthly_export_cron');
        }

        // Create default options
        if (!get_option('cf7_monthly_export_settings')) {
            add_option('cf7_monthly_export_settings', array(
                'google_credentials' => '',
                'spreadsheet_id' => '',
                'sheet_name' => 'CF7 Exports',
                'forms_to_export' => array(),
                'auto_export_enabled' => false,
                'last_export_date' => '',
                'exported_entries' => array()
            ));
        }
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Remove scheduled cron event
        $timestamp = wp_next_scheduled('cf7_monthly_export_cron');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'cf7_monthly_export_cron');
        }
    }

    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'cf7-monthly-export',
            false,
            dirname(plugin_basename(CF7_MONTHLY_EXPORT_PLUGIN_FILE)) . '/languages'
        );
    }

    /**
     * Check for required dependencies
     */
    public function check_dependencies() {
        $missing_plugins = array();

        // Check for Contact Form 7
        if (!class_exists('WPCF7')) {
            $missing_plugins[] = 'Contact Form 7';
        }

        // Check for CFDB7 (Contact Form 7 Database Addon)
        if (!class_exists('CFDB7_DB_Query')) {
            $missing_plugins[] = 'Contact Form CFDB7';
        }

        // Check for Google API client library
        if (!file_exists(CF7_MONTHLY_EXPORT_PLUGIN_DIR . 'vendor/autoload.php')) {
            add_action('admin_notices', function() {
                ?>
                <div class="notice notice-error">
                    <p><?php _e('CF7 Monthly Export: Please run <code>composer install</code> in the plugin directory to install required dependencies.', 'cf7-monthly-export'); ?></p>
                </div>
                <?php
            });
        }

        if (!empty($missing_plugins)) {
            add_action('admin_notices', function() use ($missing_plugins) {
                ?>
                <div class="notice notice-error">
                    <p>
                        <?php
                        printf(
                            __('CF7 Monthly Export requires the following plugins: %s', 'cf7-monthly-export'),
                            '<strong>' . implode(', ', $missing_plugins) . '</strong>'
                        );
                        ?>
                    </p>
                </div>
                <?php
            });
        }
    }
}

/**
 * Add custom cron schedule for monthly execution
 */
function cf7_monthly_export_cron_schedules($schedules) {
    if (!isset($schedules['monthly'])) {
        $schedules['monthly'] = array(
            'interval' => MONTH_IN_SECONDS,
            'display' => __('Once Monthly', 'cf7-monthly-export')
        );
    }
    return $schedules;
}
add_filter('cron_schedules', 'cf7_monthly_export_cron_schedules');

/**
 * Initialize the plugin
 */
function cf7_monthly_export_init() {
    return CF7_Monthly_Export::get_instance();
}

// Start the plugin
add_action('plugins_loaded', 'cf7_monthly_export_init');
