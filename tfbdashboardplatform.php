<?php
/**
 * @link              https://finpr.com
 * @since             1.2.1
 * @package           tfbdashboard
 * GitHub Plugin URI: https://github.com/FinPR-x-TFB/tfbdashboardplatform
 * GitHub Branch: develop
 * @wordpress-plugin
 * Plugin Name:       TFB Dashboard
 * Plugin URI:        https://finpr.com
 * Description:       This Plugin to Create User and Account to Dashboard TFB Dashboard
 * Version:           1.0.1.2
 * Author:            FinPR X TFB Team
 * Author URI:        https://finpr.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       tfbdashboard
 * Domain Path:       /languages
 */


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('TFBDASHBOARD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TFBDASHBOARD_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load plugin files
require_once TFBDASHBOARD_PLUGIN_DIR . 'includes/admin/class-tfb-admin-panel.php';
require_once TFBDASHBOARD_PLUGIN_DIR . 'includes/helper/class-tfb-helper.php';
require_once TFBDASHBOARD_PLUGIN_DIR . 'includes/public/class-tfb-rest-api-order.php';
require_once TFBDASHBOARD_PLUGIN_DIR . 'includes/public/class-tfb-api-account-creation.php';

// Initialize plugin classes
function tfbdashboard_init() {
    new TFBDashboard_Admin_Panel();
    if ( get_option('tfbdashboard_enabled', false) ) {
        // Call the static method for initializing order meta.
        TFBDashboard_Helper::tfbdashboard_init_order_meta();
        
        // Instantiate the helper if you need to use non-static methods.
        $tfb_helper = new TFBDashboard_Helper();
        
        new TFBDashboard_REST_API_Order();
        new TFBDashboard_API_Account_Creation();
        
        // Later, when needed, you can use instance methods:
        $tfb_helper->show_all_custom_order_meta_in_custom_fields($order);
    }
}
add_action('plugins_loaded', 'tfbdashboard_init');

// Load text domain for translations
function tfbdashboard_load_textdomain() {
    load_plugin_textdomain('tfbdashboard', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('init', 'tfbdashboard_load_textdomain');