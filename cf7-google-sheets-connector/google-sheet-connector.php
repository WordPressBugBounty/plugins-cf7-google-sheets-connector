<?php
/**
 * Plugin Name: CF7 Google Sheet Connector
 * Plugin URI: https://wordpress.org/plugins/cf7-google-sheets-connector/
 * Description: Send your Contact Form 7 data to your Google Sheets spreadsheet.
 * Version: 5.0.21
 * Author: GSheetConnector
 * Author URI: https://www.gsheetconnector.com/
 * Text Domain: gsconnector
 * Domain Path:  /languages
 * Requires Plugins: contact-form-7
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!function_exists('cgsc_fs')) {
    // Create a helper function for easy SDK access.
    function cgsc_fs()
    {
        global $cgsc_fs;

        if (!isset($cgsc_fs)) {
            // Activate multisite network integration.
            if (!defined('WP_FS__PRODUCT_17336_MULTISITE')) {
                define('WP_FS__PRODUCT_17336_MULTISITE', true);
            }

            // Manually include the Freemius SDK (not needed if using Composer).
            require_once dirname(__FILE__) . '/freemius/start.php';

            $cgsc_fs = fs_dynamic_init(array(
                'id' => '17336',
                'slug' => 'cf7-google-sheets-connector',
                'type' => 'plugin',
                'public_key' => 'pk_2f6c283a209e1297535f87b63603e',
                'is_premium' => false,
                'has_addons' => false,
                'has_paid_plans' => false,
                'menu' => array(
                    'slug' => 'cf7-google-sheets-connector',
                    'first-path' => 'admin.php?page=wpcf7-google-sheet-config',
                    'account' => false,
                    'support' => false,
                ),
            ));
        }

        return $cgsc_fs;
    }

    // Init Freemius.
    cgsc_fs();
    // Signal that SDK was initiated.
    do_action('cgsc_fs_loaded');
}


// Declare some global constants
define('GS_CONNECTOR_VERSION', '5.0.21');
define('GS_CONNECTOR_DB_VERSION', '5.0.21');
define('GS_CONNECTOR_ROOT', dirname(__FILE__));
define('GS_CONNECTOR_URL', plugins_url('/', __FILE__));
define('GS_CONNECTOR_BASE_FILE', basename(dirname(__FILE__)) . '/google-sheet-connector.php');
define('GS_CONNECTOR_BASE_NAME', plugin_basename(__FILE__));
define('GS_CONNECTOR_PATH', plugin_dir_path(__FILE__)); //use for include files to other files
define('GS_CONNECTOR_PRODUCT_NAME', 'Google Sheet Connector');
define('GS_CONNECTOR_CURRENT_THEME', get_stylesheet_directory());
define('GS_CONNECTOR_AUTH_URL', 'https://oauth.gsheetconnector.com/index.php');
define('GS_CONNECTOR_API_URL', 'https://oauth.gsheetconnector.com/api-cred.php');
define('GS_CONNECTOR_AUTH_REDIRECT_URI', admin_url('admin.php?page=wpcf7-google-sheet-config'));
//define('GS_CONNECTOR_AUTH_PLUGIN_NAME', 'cf7gsheetconnector');
define('GS_CONNECTOR_AUTH_PLUGIN_NAME', 'woocommercegsheetconnector');

load_plugin_textdomain('gsconnector', false, basename(dirname(__FILE__)) . '/languages');

/*
 * include utility classes
 */
if (!class_exists('Gs_Connector_Free_Utility')) {
    include(GS_CONNECTOR_ROOT . '/includes/class-gs-utility.php');
}
if (!class_exists('Gs_Connector_Service')) {
    include(GS_CONNECTOR_ROOT . '/includes/class-gs-service.php');
}
//Include Library Files
require_once GS_CONNECTOR_ROOT . '/lib/vendor/autoload.php';

include_once(GS_CONNECTOR_ROOT . '/lib/google-sheets.php');

/*
 * Main GS connector class
 * @class Gs_Connector_Free_Init
 * @since 1.0
 */

class Gs_Connector_Free_Init
{

    /**
     *  Set things up.
     *  @since 1.0
     */
    public function __construct()
    {
        //run on activation of plugin
        register_activation_hook(__FILE__, array($this, 'gs_connector_activate'));

        //run on deactivation of plugin
        register_deactivation_hook(__FILE__, array($this, 'gs_connector_deactivate'));

        //run on uninstall
        register_uninstall_hook(__FILE__, array('Gs_Connector_Free_Init', 'gs_connector_free_uninstall'));

        // validate is contact form 7 plugin exist
        add_action('admin_init', array($this, 'validate_parent_plugin_exists'));

        // register admin menu under "Contact" > "Integration"
        add_action('admin_menu', array($this, 'register_gs_menu_pages'));

        // load the js and css files
        add_action('init', array($this, 'load_css_and_js_files'));

        // load the classes
        add_action('init', array($this, 'load_all_classes'));

        // Add custom link for our plugin
        add_filter('plugin_action_links_' . GS_CONNECTOR_BASE_NAME, array($this, 'gs_connector_plugin_action_links'));

        add_filter('plugin_row_meta', [$this, 'plugin_row_meta'], 10, 2);

        add_action('wp_dashboard_setup', array($this, 'add_gs_connector_summary_widget'));

        add_action('admin_init', array($this, 'run_on_upgrade'));

        // redirect to integration page after update
        add_action('admin_init', array($this, 'redirect_after_upgrade'), 999);
    }


    /**
     * Plugin row meta.
     *
     * Adds row meta links to the plugin list table
     *
     * Fired by `plugin_row_meta` filter.
     *
     * @since 1.1.4
     * @access public
     *
     * @param array  $plugin_meta An array of the plugin's metadata, including
     *                            the version, author, author URI, and plugin URI.
     * @param string $plugin_file Path to the plugin file, relative to the plugins
     *                            directory.
     *
     * @return array An array of plugin row meta links.
     */
    public function plugin_row_meta($plugin_meta, $plugin_file)
    {
        if (GS_CONNECTOR_BASE_NAME === $plugin_file) {
            $row_meta = [
                'docs' => '<a href="https://support.gsheetconnector.com/kb-category/cf7-gsheetconnector" aria-label="' . esc_attr(esc_html__('View Documentation', 'gsconnector')) . '" target="_blank">' . esc_html__('Docs', 'gsconnector') . '</a>',
                'ideo' => '<a href="https://www.gsheetconnector.com/support" aria-label="' . esc_attr(esc_html__('Get Support', 'gsconnector')) . '" target="_blank">' . esc_html__('Support', 'gsconnector') . '</a>',
            ];

            $plugin_meta = array_merge($plugin_meta, $row_meta);
        }

        return $plugin_meta;
    }
    /**
     * Do things on plugin activation
     * @since 1.0
     */
    public function gs_connector_activate($network_wide)
    {
        global $wpdb;
        $this->run_on_activation();
        if (function_exists('is_multisite') && is_multisite()) {
            // check if it is a network activation - if so, run the activation function for each blog id
            if ($network_wide) {
                // Get all blog ids
                $blogids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->base_prefix}blogs");
                foreach ($blogids as $blog_id) {
                    switch_to_blog($blog_id);
                    $this->run_for_site();
                    restore_current_blog();
                }
                return;
            }
        }

        // for non-network sites only
        $this->run_for_site();
    }

    /**
     * deactivate the plugin
     * @since 1.0
     */
    public function gs_connector_deactivate($network_wide)
    {

    }

    /**
     *  Runs on plugin uninstall.
     *  a static class method or function can be used in an uninstall hook
     *
     *  @since 1.5
     */
    public static function gs_connector_free_uninstall()
    {
        global $wpdb;
        Gs_Connector_Free_Init::run_on_uninstall_free();

        if (!is_plugin_active('cf7-google-sheets-connector-pro/google-sheet-connector-pro.php') || (!file_exists(plugin_dir_path(__DIR__) . 'cf7-google-sheets-connector-pro/google-sheet-connector-pro.php'))) {
            return;
        }

        if (function_exists('is_multisite') && is_multisite()) {
            //Get all blog ids; foreach of them call the uninstall procedure
            $blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->base_prefix}blogs");

            //Get all blog ids; foreach them and call the install procedure on each of them if the plugin table is found
            foreach ($blog_ids as $blog_id) {
                switch_to_blog($blog_id);
                Gs_Connector_Free_Init::delete_for_site_free();
                restore_current_blog();
            }
            return;
        }
        Gs_Connector_Free_Init::delete_for_site_free();
    }

    /**
     * Validate parent Plugin Contact Form 7 exist and activated
     * @access public
     * @since 1.0
     */
    public function validate_parent_plugin_exists()
    {
        $plugin = plugin_basename(__FILE__);
        if ((!is_plugin_active('contact-form-7/wp-contact-form-7.php')) || (!file_exists(plugin_dir_path(__DIR__) . 'contact-form-7/wp-contact-form-7.php'))) {
            add_action('admin_notices', array($this, 'contact_form_7_missing_notice'));
            add_action('network_admin_notices', array($this, 'contact_form_7_missing_notice'));
            deactivate_plugins($plugin);
            if (isset($_GET['activate'])) {
                // Do not sanitize it because we are destroying the variables from URL
                unset($_GET['activate']);
            }
        }
    }

    /**
     * If Contact Form 7 plugin is not installed or activated then throw the error
     *
     * @access public
     * @return mixed error_message, an array containing the error message
     *
     * @since 1.0 initial version
     */
    public function contact_form_7_missing_notice()
    {
        $plugin_error = Gs_Connector_Free_Utility::instance()->admin_notice(array(
            'type' => 'error',
            'message' => __('Google Sheet Connector Add-on requires Contact Form 7 plugin to be installed and activated.', 'gsconnector')
        ));
        echo $plugin_error;
    }

    /**
     * Create/Register menu items for the plugin.
     * @since 1.0
     */
    public function register_gs_menu_pages()
    {
        if (current_user_can('wpcf7_edit_contact_forms')) {
            $current_role = Gs_Connector_Free_Utility::instance()->get_current_user_role();
            add_submenu_page('wpcf7', __('Google Sheets', 'gsconnector'), __('Google Sheets', 'gsconnector'), $current_role, 'wpcf7-google-sheet-config', array($this, 'google_sheet_configuration'));
        }
    }

    /**
     * Google Sheets page action.
     * This method is called when the menu item "Google Sheets" is clicked.
     * @since 1.0
     */
    public function google_sheet_configuration()
    {
        include(GS_CONNECTOR_PATH . "includes/pages/google-sheet-settings.php");
    }

    /**
     * Google Sheets page action.
     * This method is called when the menu item "Google Sheets" is clicked.
     *
     * @since 1.0
     */
    public function google_sheet_config()
    {
        // if (isset($_GET['code'])) {
        //     $cf7gsc_code = sanitize_text_field($_GET['code']);
        //     update_option('is_new_client_secret_cf7gscfree', 1);
        //     $header = admin_url('admin.php?page=wpcf7-google-sheet-config');
        // } else {
        //     $cf7gsc_code = "";
        //     $header = "";
        // }

        $cf7gsc_code = "";
        $header = "";
        if (isset($_GET['code'])) {
            if (is_string($_GET['code'])) {
                $cf7gsc_code = sanitize_text_field($_GET['code']);
            }
            update_option('is_new_client_secret_cf7gscfree', 1);
            $header = esc_url_raw(admin_url('admin.php?page=wpcf7-google-sheet-config'));
        }

        ?>


        <div class="card-cf7gs dropdownoption-cf7gs">
            <div class="lbl-drop-down-select">
                <label for="cf7gs_dro_option"><?php echo esc_html__('Choose Google API Setting :', 'gsconnector'); ?></label>
            </div>
            <div class="drop-down-select-btn">
                <select id="cf7gs_dro_option" name="cf7gs_dro_option">
                    <option value="cf7gs_existing" selected>
                        <?php echo esc_html__('Use Existing Client/Secret Key (Auto Google API Configuration)', 'gsconnector'); ?>
                    </option>
                    <option value="cf7gs_manual" disabled="">
                        <?php echo esc_html__('Use Manual Client/Secret Key (Use Your Google API Configuration) (Upgrade To PRO)', 'gsconnector'); ?>
                    </option>
                </select>
                <p class="int-meth-btn-cf7gs"><a href="https://www.gsheetconnector.com/cf7-google-sheet-connector-pro"
                        target="_blank"><input type="button" name="save-method-api-cf7gs" id="save-method-api-cf7gs"
                            value="<?php _e('Upgrade To PRO', 'gsconnector'); ?>" class="button button-primary" />
                    </a>
                    <span class="tooltip"> <img src="<?php echo GS_CONNECTOR_URL; ?>assets/img/help.png" class="help-icon">
                        <span
                            class="tooltiptext tooltip-right"><?php _e('Manual Client/Secret Key (Use Your Google API Configuration) method is available in the PRO version of the plugin.', 'gsconnector'); ?></span></span>
                </p>
            </div>
        </div>

        <div class="gs-form">
            <div class="gs-parts">
                <div class="gs-card" id="googlesheet">
                    <h2 class="title"><?php echo esc_html(__('CF7 - Google Sheet Integration', 'gsconnector')); ?></h2>
                    <hr />

                    <div class="inside">
                        <?php if (empty(get_option('gs_token'))) { ?>
                            <!--  <p class="gs-alert">
                    <?php echo esc_html(__('Click on "Sign in with Google" button to retrieve your code from Google Drive to allow us to access your spreadsheets. ', 'gsconnector')); ?>
                </p> -->

                            <div class="cf7-gs-alert-kk" id="google-drive-msg">
                                <p class="cf7-gs-alert-heading">
                                    <?php echo esc_html__('Authenticate with your Google account, follow these steps:', 'gsconnector'); ?>
                                </p>
                                <ol class="cf7-gs-alert-steps">
                                    <li><?php echo esc_html__('Click on the "Sign In With Google" button.', 'gsconnector'); ?></li>
                                    <li><?php echo esc_html__('Grant permissions for the following:', 'gsconnector'); ?>
                                        <ul class="cf7-gs-alert-permissions">
                                            <li><?php echo esc_html__('Google Drive', 'gsconnector'); ?></li>
                                            <li><?php echo esc_html__('Google Sheets', 'gsconnector'); ?>
                                                <span class="cf7-gs-alert-note">
                                                    <?php echo esc_html__('* Ensure that you enable the checkbox for each of these services.', 'gsconnector'); ?>
                                                </span>
                                            </li>
                                        </ul>

                                    </li>
                                    <li><?php echo esc_html__('This will allow the integration to access your Google Drive and Google Sheets.', 'gsconnector'); ?>
                                    </li>
                                </ol>
                            </div>
                        <?php } ?>
                        <p>
                            <label><?php echo esc_html(__('Google Access Code', 'gsconnector')); ?></label>
                            <?php if (!empty(get_option('gs_token')) && get_option('gs_token') !== "") { ?>
                                <input type="text" name="gs-code" id="gs-code" value="" disabled
                                    placeholder="<?php echo esc_html(__('Currently Active', 'gsconnector')); ?>" />
                                <input type="button" name="deactivate-log" id="deactivate-log"
                                    value="<?php _e('Deactivate', 'gsconnector'); ?>" class="button button-primary" />
                                <span class="tooltip"> <img src="<?php echo GS_CONNECTOR_URL; ?>assets/img/help.png"
                                        class="help-icon"> <span class="tooltiptext tooltip-right"><?php echo __('On deactivation, all your data
                            saved with authentication will be removed and you need to reauthenticate with your google
                            account.', 'gsconnector'); ?></span></span>
                                <span class="loading-sign-deactive">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
                                <?php
                            } else {

                                $cf7gsc_auth_url = GS_CONNECTOR_AUTH_URL . "?client_admin_url=" . GS_CONNECTOR_AUTH_REDIRECT_URI . "&plugin=" . GS_CONNECTOR_AUTH_PLUGIN_NAME;
                                ?>
                                <input type="text" name="gs-code" id="gs-code" value="<?php echo esc_attr($cf7gsc_code); ?>"
                                    placeholder="<?php echo esc_html(__('Click on Sign in with Google button', 'gsconnector')); ?>"
                                    disabled />
                                <?php if ($cf7gsc_code == "") { ?>
                                    <a href="<?php echo $cf7gsc_auth_url; ?>" target="_blank" class="button-cf7pro"><img
                                            src="<?php echo GS_CONNECTOR_URL ?>/assets/img/btn_google_signin_dark_pressed_web.gif"
                                            class="button_cf7formgsc"></a>
                                <?php } ?>
                            <?php } ?>


                            <?php if ($cf7gsc_code != "") { ?>

                                <input type="button" name="save-gs-code" id="save-gs-code"
                                    value="<?php _e('Click here to Save Authentication Code', 'gsconnector'); ?>"
                                    class="button button-primary blinking-button-wc" />
                            <?php } ?>
                            <span class="loading-sign">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>


                        </p>

                        <?php
                        //resolved - google sheet permission issues - START
                        if (!empty(get_option('gs_verify')) && (get_option('gs_verify') == "invalid-auth")) {
                            ?>
                            <p style="color:#c80d0d; font-size: 14px; border: 1px solid;padding: 8px;">
                                <?php echo esc_html(__('Something went wrong! It looks you have not given the permission of Google Drive and Google Sheets from your google account.Please Deactivate Auth and Re-Authenticate again with the permissions.', 'gsconnector')); ?>
                            </p>
                            <p style="color:#c80d0d;border: 1px solid;padding: 8px;"><img width="350px"
                                    src="<?php echo GS_CONNECTOR_URL; ?>assets/img/permission_screen.png"></p>
                            <p style="color:#c80d0d; font-size: 14px; border: 1px solid;padding: 8px;">
                                <?php echo esc_html(__('Also,', 'gsconnector')); ?><a
                                    href="https://myaccount.google.com/permissions" target="_blank">
                                    <?php echo esc_html(__('Click Here ', 'gsconnector')); ?></a>
                                <?php echo esc_html(__('and if it displays "GSheetConnector for WP Contact Forms" under Third-party apps with account access then remove it.', 'gsconnector')); ?>
                            </p>
                            <?php
                        }
                        //resolved - google sheet permission issues - END
                        else {
                            // connected-email-account
                            $token = get_option('gs_token');
                            if (!empty($token) && $token !== "") {
                                $google_sheet = new CF7GSC_googlesheet();
                                $email_account = $google_sheet->gsheet_print_google_account_email();

                                if ($email_account) {
                                    update_option('cf7gs_auth_expired_free', 'false');
                                    ?>
                                    <p class="connected-account">
                                        <?php printf(__('Connected email account: %s', 'gsconnector'), $email_account); ?>
                                    <p>
                                    <?php } else {
                                    update_option('cf7gs_auth_expired_free', 'true');
                                    ?>
                                    <p style="color:red">
                                        <?php echo esc_html(__('Something wrong ! Your Auth Code may be wrong or expired. Please deactivate and do Re-Authentication again. ', 'gsconnector')); ?>
                                    </p>
                                    <?php
                                }
                            }
                        }
                        ?>

                        <p>
                            <label><?php echo esc_html(__('Debug Log', 'gsconnector')); ?></label>
                            <label>
                                <!-- display error logs -->
                                <button class="gsc-cf7free-logs"><?php echo esc_html(__('View', 'gsconnector')); ?></button>
                                <!-- <a href="<?php echo plugins_url('/logs/log.txt', __FILE__); ?>" target="_blank"
                            class="debug-view">View</a> -->
                            </label>
                            <!-- clear logs -->
                            <label><a class="debug-clear"><?php echo esc_html(__('Clear', 'gsconnector')); ?></a></label>
                            <span class="clear-loading-sign">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
                        </p>
                        <p id="gs-validation-message"></p>
                        <span id="deactivate-message"></span>
                        <div id="cf7-gsc-cta" class="cf7-gsc-privacy-box">
                            <div class="cf7-gsc-table">
                                <div class="cf7-gsc-less-free">
                                    <i class="dashicons dashicons-lock"></i>
                                    <p> <?php echo esc_html(__('We do not store any of the data from your Google account on our servers, everything is processed & stored on your server. We take your privacy extremely seriously and ensure it is never misused.', 'gsconnector')); ?><br />
                                        <a href="https://gsheetconnector.com/usage-tracking/" target="_blank"
                                            rel="noopener noreferrer"><?php echo esc_html(__('Learn more.', 'gsconnector')); ?></a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <!-- set nonce -->
                        <input type="hidden" name="gs-ajax-nonce" id="gs-ajax-nonce"
                            value="<?php echo wp_create_nonce('gs-ajax-nonce'); ?>" />
                        <input type="hidden" name="redirect_auth" id="redirect_auth"
                            value="<?php echo (isset($header)) ? $header : ''; ?>">

                    </div>
                </div>








            </div>
            <!-- display content error logs -->
            <?php /*?><div class="system-error-cf7free-logs" style="display:none;">
                                                                                            
                                                                                              <button id="copy-logs-btn" onclick="copyLogs()">Copy Logs</button>
                                                                                            
                                                                                           <div class="display-cf7free-logs">
                                                                                                <?php
                                                                                                $existDebugFile = get_option('gs_debug_log_file');
                                                                                                // check if debug unique log file exist or not
                                                                                                if (!empty($existDebugFile) && file_exists($existDebugFile)) {
                                                                                                  $displaycf7freeLogs =  nl2br(file_get_contents($existDebugFile));
                                                                                                if(!empty($displaycf7freeLogs)){
                                                                                                 echo $displaycf7freeLogs;
                                                                                                }
                                                                                                else{
                                                                                                    echo esc_html(__('No errors found.', 'gsconnector'));
                                                                                                 }
                                                                                            }
                                                                                           else{
                                                                                                // check if debug unique log file not exist
                                                                                                echo esc_html(__('No log file exists as no errors are generated.', 'gsconnector'));
                                                                                                
                                                                                            }
                                                                                                
                                                                                                 ?>
                                                                                            </div>
                                                                                          </div><?php */ ?>


            <!-- display content error logs -->
            <div class="system-error-cf7free-logs card" style="display:none;">
                <button id="copy-logs-btn" onclick="copyLogs()">Copy Logs</button>
                <div class="display-cf7free-logs">
                    <?php
                    echo '';
                    // Fetch the debug log file without checking the condition
                    $cf7existDebugFile = get_option('gs_debug_log_file');
                    $displaycf7proLogs = !empty($cf7existDebugFile) && file_exists($cf7existDebugFile)
                        ? nl2br(file_get_contents($cf7existDebugFile))
                        : __('No log file exists as no errors are generated', 'gsconnector');

                    // Display the logs and copy button
                    echo '<pre id="cf7pro-log-content">' . __($displaycf7proLogs, 'gsconnector') . '</pre>';

                    ?>
                </div>

            </div><br>
            <br>
            <script>
                function copyLogs() {
                    const logContent = document.getElementById('cf7free-log-content').innerText;
                    const tempTextArea = document.createElement('textarea');
                    tempTextArea.value = logContent;
                    document.body.appendChild(tempTextArea);
                    tempTextArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(tempTextArea);

                    // Display success message
                    alert('Logs copied to clipboard!');
                }
            </script>





            <div>

            </div>
        </div>


        <script>
            function copyLogs() {
                const logContent = document.getElementById('cf7pro-log-content').innerText;
                const tempTextArea = document.createElement('textarea');
                tempTextArea.value = logContent;
                document.body.appendChild(tempTextArea);
                tempTextArea.select();
                document.execCommand('copy');
                document.body.removeChild(tempTextArea);

                // Display success message
                alert('Logs copied to clipboard!');
            }
        </script>


        <div class="plugin-features">
            <h2 class="inner-title">
                <?php echo esc_html(__('CF7 Google Sheet Connector Pro - Amazing Key Features', 'gsconnector')); ?>
                <span
                    class="sub-line"><?php echo esc_html(__('Common Features of GSheetConnector Pro Plugins.', 'gsconnector')); ?></span>
            </h2>

            <div class="features-list">
                <div class="features">
                    <span class="icons-oneclick icon"></span>
                    <h3><?php echo esc_html(__('One-Click Authentication', 'gsconnector')); ?></h3>
                    <p><?php echo esc_html(__('Get spreadsheet and Worksheet list directly to your contact form’s google sheet settings with one-click authentication.', 'gsconnector')); ?>
                    </p>
                </div> <!-- features #end -->

                <div class="features">
                    <span class="icons-fetch icon"></span>
                    <h3><?php echo esc_html(__('Auto Fetch Sheets and Integration', 'gsconnector')); ?> </h3>
                    <p><?php echo esc_html(__('You can add multiple Contact Forms of your site to multiple Google Sheets. And can add as many Google Sheets as forms.', 'gsconnector')); ?>
                    </p>
                </div> <!-- features #end -->

                <div class="features">
                    <span class="icons-api icon"></span>
                    <h3><?php echo esc_html(__('Google Sheets API Up to date', 'gsconnector')); ?></h3>
                    <p><?php echo esc_html(__('One of the features you get with the latest API-V4 is the ability to format content in Google Sheets. when using our addon plugins.', 'gsconnector')); ?>
                    </p>
                </div> <!-- features #end -->



                <div class="features">
                    <span class="icon-map icon"></span>
                    <h3><?php echo esc_html(__('Map Contact Form Mail Tags to GSheet Columns', 'gsconnector')); ?></h3>
                    <p><?php echo esc_html(__('In the Google sheets tab, provide column names in row 1. The first column should be “date” for each.', 'gsconnector')); ?>
                    </p>
                </div> <!-- features #end -->

                <div class="features">
                    <span class="icon-quick icon"></span>
                    <h3><?php echo esc_html(__('Quick Configuration', 'gsconnector')); ?> </h3>
                    <p><?php echo esc_html(__('The Configuration of the form to the GSheet is very easy. Just follow the steps provided by the plugin and you will get data on the GSheet.', 'gsconnector')); ?>
                    </p>
                </div> <!-- features #end -->

                <div class="features">
                    <span class="icon-multisite icon"></span>
                    <h3><?php echo esc_html(__('Support WordPress Multi-site', 'gsconnector')); ?></h3>
                    <p><?php echo esc_html(__('The Configuration of the form to the GSheet is very easy. Just follow the steps provided by the plugin and you will get data on the GSheet.', 'gsconnector')); ?>
                    </p>
                </div> <!-- features #end -->

            </div> <!-- features-list 3end -->

            <div class="button-bar">

                <a href="https://cf7demo.gsheetconnector.com/" class="demo-btn"
                    target="_blank"><?php echo esc_html(__('See Demo', 'gsconnector')); ?></a>
                <a href="https://www.gsheetconnector.com/cf7-google-sheet-connector-pro" class="action-btn" target="_blank">See
                    All Futures &amp; Buy Now </a>
            </div>


        </div> <!-- plugin-features #end -->


        <!-- two column start -->
        <div class="two-col wc-free-box-help12">
            <div class="col wc-free-box12">

                <h3><?php echo esc_html(__('Next steps…', 'gsconnector')); ?></h3>

                <div class="wc-free-box-content12">
                    <ul class="wc-free-list-icon12">
                        <li> <a href="https://www.gsheetconnector.com/cf7-google-sheet-connector-pro" target="_blank">
                                <div>
                                    <button class="icon-button"> <span class="dashicons dashicons-star-filled"></span> </button>
                                    <strong><?php echo esc_html(__('Upgrade to PRO', 'gsconnector')); ?></strong>
                                    <p><?php echo esc_html(__('Sync Orders, Order wise data and much more...', 'gsconnector')); ?>
                                    </p>
                                </div>
                            </a> </li>
                        <li> <a href="https://www.gsheetconnector.com/docs/cf7-gsheet-connector-free/requirements"
                                target="_blank">
                                <div>
                                    <button class="icon-button"> <span class="dashicons dashicons-download"></span> </button>
                                    <strong><?php echo esc_html(__('Compatibility', 'gsconnector')); ?></strong>
                                    <p><?php echo esc_html(__('Compatibility with Contact Form 7 Third-Party Plugins', 'gsconnector')); ?>
                                    </p>
                                </div>
                            </a> </li>
                        <li> <a href="https://www.gsheetconnector.com/cf7-google-sheet-connector-pro" target="_blank">
                                <div>
                                    <button class="icon-button"> <span class="dashicons dashicons-chart-bar"></span> </button>
                                    <strong><?php echo esc_html(__('Multi Languages', 'gsconnector')); ?></strong>
                                    <p><?php echo esc_html(__('This plugin supports multi-languages as well!', 'gsconnector')); ?>
                                    </p>
                                </div>
                            </a> </li>
                        <li> <a href="https://www.gsheetconnector.com/docs/cf7-gsheet-connector-free/" target="_blank">
                                <div>
                                    <button class="icon-button"> <span class="dashicons dashicons-download"></span> </button>
                                    <strong><?php echo esc_html(__('Support Wordpress multisites', 'gsconnector')); ?></strong>
                                    <p><?php echo esc_html(__('With the use of a Multisite, you’ll also have a new level of user-available: the Super
              Admin.', 'gsconnector')); ?></p>
                                </div>
                            </a> </li>
                    </ul>
                </div>
            </div>

            <!-- 2nd div -->
            <div class="col wc-free-box13">

                <h3><?php echo esc_html(__('Product Support', 'gsconnector')); ?></h3>

                <div class="wc-free-box-content13">
                    <ul class="wc-free-list-icon13">
                        <li> <a href="https://www.gsheetconnector.com/docs/cf7-gsheet-connector-free/" target="_blank"> <span
                                    class="dashicons dashicons-book"></span>
                                <div> <strong><?php echo esc_html(__('Online Documentation', 'gsconnector')); ?></strong>
                                    <p><?php echo esc_html(__('Understand all the capabilities of Contact Form 7 - CF7 GSheetConnector', 'gsconnector')); ?>
                                    </p>
                                </div>
                            </a> </li>
                        <li> <a href="https://www.gsheetconnector.com/docs/cf7-gsheet-connector-free/" target="_blank"> <span
                                    class="dashicons dashicons-sos"></span>
                                <div> <strong><?php echo esc_html(__('Ticket Support', 'gsconnector')); ?></strong>
                                    <p><?php echo esc_html(__('Direct help from our qualified support team', 'gsconnector')); ?>
                                    </p>
                                </div>
                            </a> </li>
                        <li> <a href="https://www.gsheetconnector.com/affiliates" target="_blank"> <span
                                    class="dashicons dashicons-admin-links"></span>
                                <div> <strong><?php echo esc_html(__('Affiliate Program', 'gsconnector')); ?></strong>
                                    <p>Earn flat 30% on every sale!</p>
                                </div>
                            </a> </li>
                    </ul>
                </div>
            </div>
        </div>
        <!-- two column #end  -->


        <?php
    }

    public function load_css_and_js_files()
    {
        add_action('admin_print_styles', array($this, 'add_css_files'));
        add_action('admin_print_scripts', array($this, 'add_js_files'));
    }

    /**
     * enqueue CSS files
     * @since 1.0
     */
    public function add_css_files()
    {
        if (is_admin() && (isset($_GET['page']) && (($_GET['page'] == 'wpcf7-new') || ($_GET['page'] == 'wpcf7-google-sheet-config') || ($_GET['page'] == 'wpcf7')))) {
            wp_enqueue_style('gs-connector-css', GS_CONNECTOR_URL . 'assets/css/gs-connector.css', GS_CONNECTOR_VERSION, true);
            wp_enqueue_style('gs-connector-faq-css', GS_CONNECTOR_URL . 'assets/css/faq-style.css', GS_CONNECTOR_VERSION, true);
        }

        if (is_plugin_active('cf7-grid-layout/cf7-grid-layout.php') && ((isset($_REQUEST['post_type']) && $_REQUEST['post_type'] == "wpcf7_contact_form") || (isset($_REQUEST['action']) && $_REQUEST['action'] == "edit"))) {
            wp_enqueue_style('gs-connector-css', GS_CONNECTOR_URL . 'assets/css/gs-connector.css', GS_CONNECTOR_VERSION, true);
            wp_enqueue_style('gs-connector-faq-css', GS_CONNECTOR_URL . 'assets/css/faq-style.css', GS_CONNECTOR_VERSION, true);
        }
    }

    /**
     * enqueue JS files
     * @since 1.0
     */
    public function add_js_files()
    {
        if (is_admin() && (isset($_GET['page']) && (($_GET['page'] == 'wpcf7-new') || ($_GET['page'] == 'wpcf7-google-sheet-config')))) {
            wp_enqueue_script('gs-connector-js', GS_CONNECTOR_URL . 'assets/js/gs-connector.js', GS_CONNECTOR_VERSION, true);
            wp_enqueue_script('jquery-json', GS_CONNECTOR_URL . 'assets/js/jquery.json.js', '', '2.3', true);
        }
        if (is_admin() && (isset($_REQUEST['page']) && preg_match_all('/page=wpcf7-new(.*)|page=wpcf7-google-sheet-config(.*)|page=wpcf7(.*)/', $_SERVER['REQUEST_URI'], $matches))) {
            wp_enqueue_script('gs-connector-js', GS_CONNECTOR_URL . 'assets/js/gs-connector.js', GS_CONNECTOR_VERSION, true);
            wp_enqueue_script('jquery-json', GS_CONNECTOR_URL . 'assets/js/jquery.json.js', '', '2.3', true);
        }

        if (is_plugin_active('cf7-grid-layout/cf7-grid-layout.php') && ((isset($_REQUEST['post_type']) && $_REQUEST['post_type'] == "wpcf7_contact_form") || (isset($_REQUEST['action']) && $_REQUEST['action'] == "edit"))) {
            wp_enqueue_script('gs-connector-js', GS_CONNECTOR_URL . 'assets/js/gs-connector.js', GS_CONNECTOR_VERSION, true);
            wp_enqueue_script('jquery-json', GS_CONNECTOR_URL . 'assets/js/jquery.json.js', '', '2.3', true);
        }

        if (is_admin()) {
            wp_enqueue_script('gs-connector-adds-js', GS_CONNECTOR_URL . 'assets/js/gs-connector-adds.js', GS_CONNECTOR_VERSION, true);
        }
    }

    /**
     * Function to load all required classes
     * @since 2.8
     */
    public function load_all_classes()
    {
        if (!class_exists('GS_Connector_Adds')) {
            include(GS_CONNECTOR_PATH . 'includes/class-gs-adds.php');
        }
    }

    /**
     * called on upgrade. 
     * checks the current version and applies the necessary upgrades from that version onwards
     * @since 5.0.20
     */
    public function run_on_upgrade()
    {
        $plugin_options = get_site_option('google_sheet_info_free');

        if ($plugin_options['version'] <= "3.0") {
            $this->upgrade_database_40();
            $this->upgrade_database_41();

        } elseif ($plugin_options['version'] == '5.0.19') {
            $this->upgrade_database_41();

        }

        // update the version value
        $google_sheet_info_free = array(
            'version' => GS_CONNECTOR_VERSION,
            'db_version' => GS_CONNECTOR_DB_VERSION
        );
        // check if debug log file exists or not
        $logFilePathToDelete = GS_CONNECTOR_PATH . "logs/log.txt";
        // Check if the log file exists before attempting to delete
        if (file_exists($logFilePathToDelete)) {
            unlink($logFilePathToDelete);
        }

        update_site_option('google_sheet_info_free', $google_sheet_info_free);
    }

    public function upgrade_database_40()
    {
        global $wpdb;

        // look through each of the blogs and upgrade the DB
        if (function_exists('is_multisite') && is_multisite()) {
            //Get all blog ids;
            $blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->base_prefix}blogs");
            foreach ($blog_ids as $blog_id) {
                switch_to_blog($blog_id);
                $this->upgrade_helper_40();
                restore_current_blog();
            }
            return;
        }
        $this->upgrade_helper_40();
    }

    public function upgrade_helper_40()
    {

        // Add the transient to redirect.
        set_transient('cf7gs_upgrade_redirect', true, 30);

    }
    public function upgrade_database_41()
    {
        global $wpdb;

        // look through each of the blogs and upgrade the DB
        if (function_exists('is_multisite') && is_multisite()) {
            //Get all blog ids;
            $blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->base_prefix}blogs");
            foreach ($blog_ids as $blog_id) {
                switch_to_blog($blog_id);
                $this->upgrade_helper_41();
                restore_current_blog();
            }
            return;
        }
        $this->upgrade_helper_41();
    }

    public function upgrade_helper_41()
    {

        // Fetch and save the API credentails.
        Gs_Connector_Free_Utility::instance()->save_api_credentials();

    }


    public function redirect_after_upgrade()
    {
        if (!get_transient('cf7gs_upgrade_redirect')) {
            return;
        }
        $plugin_options = get_site_option('google_sheet_info_free');
        if ($plugin_options['version'] == "4.0") {
            delete_transient('cf7gs_upgrade_redirect');
            wp_safe_redirect('admin.php?page=wpcf7-google-sheet-config');
        }
    }

    /**
     * Add custom link for the plugin beside activate/deactivate links
     * @param array $links Array of links to display below our plugin listing.
     * @return array Amended array of links.    * 
     * @since 1.5
     */
    public function gs_connector_plugin_action_links($links)
    {
        // Define the text for the "Get Pro" link
        $go_pro_text = esc_html__('Get CF7 Google Sheet Pro', 'elementor');

        // Check if the Pro version of the plugin is installed and activated
        if (is_plugin_active('cf7-google-sheets-connector-pro/google-sheet-connector-pro.php')) {
            // If Pro version is active, return the links without adding the "Get Pro" link
            return $links;
        }

        // Add the action link to the plugin page with green color styling
        $links['go_pro'] = sprintf(
            '<a href="%s" target="_blank" class="gsheetconnector-pro-link" style="color: green; font-weight: bold;">%s</a>',
            esc_url('https://www.gsheetconnector.com/cf7-google-sheet-connector-pro'),
            $go_pro_text
        );

        return $links;
    }

    public function add_gs_connector_summary_widget()
    {
        wp_add_dashboard_widget('gs_dashboard', __("<img style='width:30px;margin-right: 10px;' src='" . GS_CONNECTOR_URL . "assets/img/cf7-gsc.png'><span>Contact Form 7 - GSheetConnector</span>", 'gsconnector'), array($this, 'gs_connector_summary_dashboard'));
    }

    public function gs_connector_summary_dashboard()
    {
        include_once(GS_CONNECTOR_ROOT . '/includes/pages/cf7gs-dashboard-widget.php');
    }

    /**
     * Called on activation.
     * Creates the site_options (required for all the sites in a multi-site setup)
     * If the current version doesn't match the new version, runs the upgrade
     * @since 1.0
     */
    private function run_on_activation()
    {
        $plugin_options = get_site_option('google_sheet_info_free');

        if (false === $plugin_options) {
            $google_sheet_info_free = array(
                'version' => GS_CONNECTOR_VERSION,
                'db_version' => GS_CONNECTOR_DB_VERSION
            );
            update_site_option('google_sheet_info_free', $google_sheet_info_free);
        } else if (GS_CONNECTOR_DB_VERSION != $plugin_options['version']) {
            $this->run_on_upgrade();
        }
        Gs_Connector_Free_Utility::instance()->save_api_credentials();

    }

    /**
     * Called on activation.
     * Creates the options and DB (required by per site)
     * @since 1.0
     */
    private function run_for_site()
    {
        if (!get_option('gs_access_code')) {
            update_option('gs_access_code', '');
        }
        if (!get_option('gs_verify')) {
            update_option('gs_verify', 'invalid');
        }
        if (!get_option('gs_token')) {
            update_option('gs_token', '');
        }
    }

    /**
     * Called on uninstall - deletes site_options
     *
     * @since 1.5
     */
    private static function run_on_uninstall_free()
    {
        if (!defined('ABSPATH') && !defined('WP_UNINSTALL_PLUGIN')) {
            exit();
        }

        delete_site_option('google_sheet_info_free');
    }

    /**
     * Called on uninstall - deletes site specific options
     *
     * @since 1.5
     */
    private static function delete_for_site_free()
    {
        if (!is_plugin_active('cf7-google-sheets-connector-pro/google-sheet-connector-pro.php') || (!file_exists(plugin_dir_path(__DIR__) . 'cf7-google-sheets-connector-pro/google-sheet-connector-pro.php'))) {
            delete_option('gs_access_code');
            delete_option('gs_verify');
            delete_option('gs_token');
            delete_post_meta_by_key('gs_settings');
        }
    }

    function my_custom_admin_menu()
    {
        add_menu_page(
            'Custom Page Title',   // Page Title
            'Custom Menu',         // Menu Title
            'manage_options',      // Capability (who can access it)
            'custom-menu-slug',    // Menu Slug
            'custom_admin_page',   // Callback function to display content
            'dashicons-admin-generic', // Icon (from Dashicons)
            25                     // Position in the menu
        );
    }

    // Callback function to display page content
    function custom_admin_page()
    {
        echo "<div class='wrap'><h1>Welcome to Custom Admin Page</h1></div>";
    }

    // Hook to add the menu in the admin dashboard



}

// Initialize the google sheet connector class
$init = new Gs_Connector_Free_Init();
