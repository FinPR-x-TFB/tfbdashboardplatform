<?php
/**
 * Plugin functions and definitions for Admin.
 *
 * For additional information on potential customization options,
 * read the developers' documentation:
 *
 * @package tfbdashboard
 */
if (!defined('ABSPATH')) {
    exit;
}

class TFBDashboard_Admin_Panel {
    public function __construct() {
        add_action('admin_menu', array($this, 'tfbdashboard_add_admin_menu'));
        add_action('admin_init', array($this, 'tfbdashboard_register_settings'));
    }

    public function tfbdashboard_add_admin_menu() {
        add_menu_page(
            __('TFB Dashboard', 'tfbdashboard'),
            __('TFB Dashboard', 'tfbdashboard'),
            'manage_options',
            'tfbdashboard',
            array($this, 'tfbdashboard_admin_page'),
            'dashicons-dashboard',
            6
        );
    }

    public function tfbdashboard_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('TFB Dashboard Settings', 'tfbdashboard'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('tfbdashboard_settings_group');
                do_settings_sections('tfbdashboard');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function tfbdashboard_register_settings() {
        register_setting('tfbdashboard_settings_group', 'tfbdashboard_enabled');
        add_settings_section('tfbdashboard_main_section', __('Main Settings', 'tfbdashboard'), null, 'tfbdashboard');
        add_settings_field('tfbdashboard_enabled', __('Enable Plugin', 'tfbdashboard'), array($this, 'tfbdashboard_enabled_callback'), 'tfbdashboard', 'tfbdashboard_main_section');
    }

    public function tfbdashboard_enabled_callback() {
        $enabled = get_option('tfbdashboard_enabled', false);
        echo '<input type="checkbox" name="tfbdashboard_enabled" value="1" ' . checked(1, $enabled, false) . ' />';
    }
}