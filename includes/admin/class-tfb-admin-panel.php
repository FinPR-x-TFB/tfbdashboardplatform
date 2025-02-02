<?php
/**
 * Plugin functions and definitions for Admin.
 *
 * For additional information on potential customization options,
 * read the developers' documentation:
 *
 * @package tfbdashboard
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TFBDashboard_Admin_Panel {
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'tfbdashboard_add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'tfbdashboard_register_settings' ) );
    }

    /**
     * Add the TFB Dashboard menu to the WordPress admin sidebar.
     */
    public function tfbdashboard_add_admin_menu() {
        add_menu_page(
            __( 'TFB Dashboard', 'tfbdashboard' ),
            __( 'TFB Dashboard', 'tfbdashboard' ),
            'manage_options',
            'tfbdashboard',
            array( $this, 'tfbdashboard_settings_page' ),
            'dashicons-dashboard',
            58
        );
    }

    /**
     * Register all plugin settings.
     */
    public function tfbdashboard_register_settings() {
        register_setting( 'tfbdashboard_options_group', 'tfbdashboard_enabled' );
        register_setting( 'tfbdashboard_options_group', 'tfbdashboard_environment' );
        register_setting( 'tfbdashboard_options_group', 'tfbdashboard_sandbox_endpoint' );
        register_setting( 'tfbdashboard_options_group', 'tfbdashboard_sandbox_test_key' );
        register_setting( 'tfbdashboard_options_group', 'tfbdashboard_live_endpoint' );
        register_setting( 'tfbdashboard_options_group', 'tfbdashboard_live_api_key' );
        register_setting( 'tfbdashboard_options_group', 'tfbdashboard_save_log_response' );
    }

    /**
     * Render the settings page for TFB Dashboard.
     */
    public function tfbdashboard_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'TFB Dashboard Settings', 'tfbdashboard' ); ?></h1>
            <form method="post" action="options.php">
                <?php 
                settings_fields( 'tfbdashboard_options_group' ); 
                do_settings_sections( 'tfbdashboard_options_group' ); 
                ?>
                <table class="form-table">
                    <!-- Enable Plugin -->
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Enable TFB Dashboard', 'tfbdashboard' ); ?></th>
                        <td>
                            <input type="checkbox" name="tfbdashboard_enabled" value="1" <?php checked( 1, get_option( 'tfbdashboard_enabled', 0 ) ); ?> />
                            <p class="description"><?php esc_html_e( 'Check to enable TFB Dashboard functionalities.', 'tfbdashboard' ); ?></p>
                        </td>
                    </tr>
                    <!-- Environment Radio -->
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Environment', 'tfbdashboard' ); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="radio" name="tfbdashboard_environment" value="sandbox" <?php checked( 'sandbox', get_option( 'tfbdashboard_environment', 'sandbox' ) ); ?> />
                                    <?php esc_html_e( 'Sandbox Version', 'tfbdashboard' ); ?>
                                </label><br/>
                                <label>
                                    <input type="radio" name="tfbdashboard_environment" value="live" <?php checked( 'live', get_option( 'tfbdashboard_environment', 'sandbox' ) ); ?> />
                                    <?php esc_html_e( 'Live Version', 'tfbdashboard' ); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    <!-- Sandbox Endpoint URL -->
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Sandbox Endpoint URL', 'tfbdashboard' ); ?></th>
                        <td>
                            <input type="text" name="tfbdashboard_sandbox_endpoint" value="<?php echo esc_attr( get_option( 'tfbdashboard_sandbox_endpoint', 'https://gateway-dev.thefundedbettor.com' ) ); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <!-- Sandbox Test Key -->
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Sandbox Test Key', 'tfbdashboard' ); ?></th>
                        <td>
                            <input type="text" name="tfbdashboard_sandbox_test_key" value="<?php echo esc_attr( get_option( 'tfbdashboard_sandbox_test_key', '18c98a659a174bd68c6380751ff821ac686b0f6dcba14e2497a01702d7f0584d' ) ); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <!-- Live Endpoint URL -->
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Live Endpoint URL', 'tfbdashboard' ); ?></th>
                        <td>
                            <input type="text" name="tfbdashboard_live_endpoint" value="<?php echo esc_attr( get_option( 'tfbdashboard_live_endpoint', 'https://gateway-dev.thefundedbettor.com' ) ); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <!-- Live API Key -->
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Live API Key', 'tfbdashboard' ); ?></th>
                        <td>
                            <input type="text" name="tfbdashboard_live_api_key" value="<?php echo esc_attr( get_option( 'tfbdashboard_live_api_key', '18c98a659a174bd68c6380751ff821ac686b0f6dcba14e2497a01702d7f0584d' ) ); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <!-- Save Log Response -->
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e( 'Save Log Response', 'tfbdashboard' ); ?></th>
                        <td>
                            <input type="checkbox" name="tfbdashboard_save_log_response" value="1" <?php checked( 1, get_option( 'tfbdashboard_save_log_response', 1 ) ); ?> />
                            <p class="description"><?php esc_html_e( 'Check to save the log response from API calls.', 'tfbdashboard' ); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
