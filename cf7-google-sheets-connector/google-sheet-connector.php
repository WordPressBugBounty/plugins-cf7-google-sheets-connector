<?php

/**
 * Plugin Name: CF7 Google Sheet Connector
 * Plugin URI: https://wordpress.org/plugins/cf7-google-sheets-connector/
 * Description: Connect Contact Form 7 to Google Sheets and send form submissions to Google Sheets in a Real-Time
 * Requires at least: 6.7
 * Requires PHP: 7.4
 * Version: 5.2.3
 * Author: GSheetConnector
 * Author URI: https://www.gsheetconnector.com/
 * Text Domain: cf7-google-sheets-connector
 * Domain Path:  /languages
 * Requires Plugins: contact-form-7
 * Tested up to: 7.0
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Freemius SDK boilerplate.
global $cgsc_fs;
if (! function_exists('cgsc_fs')) {
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Freemius SDK boilerplate.
	function cgsc_fs()
	{
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Freemius SDK boilerplate.
		global $cgsc_fs;
		if (! isset($cgsc_fs)) {
			if (! defined('WP_FS__PRODUCT_17336_MULTISITE')) {
				define('WP_FS__PRODUCT_17336_MULTISITE', true);
			}
			require_once __DIR__ . '/lib/vendor/freemius/start.php';
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Freemius SDK boilerplate.
			$cgsc_fs = fs_dynamic_init(
				array(
					'id'             => '17336',
					'slug'           => 'cf7-google-sheets-connector',
					'type'           => 'plugin',
					'public_key'     => 'pk_2f6c283a209e1297535f87b63603e',
					'is_premium'     => false,
					'has_addons'     => false,
					'has_paid_plans' => false,
					'menu'           => array(
						'slug'       => 'wpcf7-google-sheet-config',
						'first-path' => 'admin.php?page=wpcf7-google-sheet-config',
						'support'    => false,
					),
				)
			);
		}

		return $cgsc_fs;
	}

	cgsc_fs();
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Freemius SDK boilerplate.
	do_action('cgsc_fs_loaded');
}

// Declare some global constants
define('GS_CONNECTOR_VERSION', '5.2.3');
define('GS_CONNECTOR_DB_VERSION', '5.2.3');
define('GS_CONNECTOR_ROOT', __DIR__);
define('GS_CONNECTOR_URL', plugins_url('/', __FILE__));
define('GS_CONNECTOR_BASE_FILE', basename(__DIR__) . '/google-sheet-connector.php');
define('GS_CONNECTOR_BASE_NAME', plugin_basename(__FILE__));
define('GS_CONNECTOR_PATH', plugin_dir_path(__FILE__)); // use for include files to other files
define('GS_CONNECTOR_PRODUCT_NAME', 'Google Sheet Connector');
define('GS_CONNECTOR_CURRENT_THEME', get_stylesheet_directory());
// define('GS_CONNECTOR_AUTH_URL', 'https://oauth.gsheetconnector.com/index.php');
// define('GS_CONNECTOR_API_URL', 'https://oauth.gsheetconnector.com/api-cred.php');
define('GS_CONNECTOR_AUTH_URL', 'https://oauth.gsheetconnector.com/auth-api.php');
define('GS_CONNECTOR_API_URL', 'https://oauth.gsheetconnector.com/api-cred-old-api.php');
define('GS_CONNECTOR_AUTH_REDIRECT_URI', admin_url('admin.php?page=wpcf7-google-sheet-config&tab=integration'));
define('GS_CONNECTOR_AUTH_PLUGIN_NAME', 'cf7gsheetconnector');
// define('GS_CONNECTOR_AUTH_PLUGIN_NAME', 'woocommercegsheetconnector');
add_action('init', 'gsfff_connector_free_load_textdomain');
/**
 * Register the translations bundled in the plugin's /languages folder.
 *
 * Automatic (just-in-time) loading only covers translations installed in
 * wp-content/languages/plugins, so the bundled catalogs still have to be
 * registered explicitly. Hooked to `init` so nothing loads too early.
 *
 * @since 5.2.2
 * @return void
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
function gsfff_connector_free_load_textdomain()
{
	// phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound -- Needed for the translations bundled with the plugin; see above.
	load_plugin_textdomain('cf7-google-sheets-connector', false, basename(__DIR__) . '/languages');
}
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound

/*
 * include utility classes
 */
if (! class_exists('Gs_Connector_Free_Utility')) {
	include GS_CONNECTOR_ROOT . '/includes/class-gs-utility.php';
}
if (! class_exists('Gs_Connector_Service')) {
	include GS_CONNECTOR_ROOT . '/includes/class-gs-service.php';
}

require_once GS_CONNECTOR_ROOT . '/lib/google-sheets.php';

if (! class_exists('GS_CF7DB')) {
	include GS_CONNECTOR_PATH . '/includes/pages/gs-cf7db.php';
}
/*
 * Main GS connector class
 * @class Gs_Connector_Free_Init
 * @since 1.0
 */

class Gs_Connector_Free_Init
{


	/**
	 *  Set things up.
	 *
	 *  @since 1.0
	 */
	public function __construct()
	{

		// run on activation of plugin
		register_activation_hook(__FILE__, array($this, 'gs_connector_activate'));

		// run on deactivation of plugin
		register_deactivation_hook(__FILE__, array($this, 'gs_connector_deactivate'));

		// run on uninstall
		register_uninstall_hook(__FILE__, array('Gs_Connector_Free_Init', 'gs_connector_free_uninstall'));

		// validate is contact form 7 plugin exist
		add_action('admin_init', array($this, 'validate_parent_plugin_exists'));

		// Enqueue inline script in admin footer to block beforeunload dialog on CF7 edit pages.
		add_action('admin_footer', array($this, 'cf7gs_block_beforeunload_script'));

		// register admin menu under "Contact" > "Integration"
		add_action('admin_menu', array($this, 'register_gs_menu_pages'));

		// load the js and css files
		add_action('init', array($this, 'load_css_and_js_files'));

		// load the classes
		add_action('init', array($this, 'load_all_classes'));

		add_action('admin_init', array($this, 'run_on_upgrade'));

		// redirect to integration page after update
		add_action('admin_init', array($this, 'redirect_after_upgrade'), 999);

		// Add custom link for our plugin
		add_filter('plugin_action_links_' . GS_CONNECTOR_BASE_NAME, array($this, 'gs_connector_plugin_action_links'));

		add_filter('plugin_row_meta', array($this, 'plugin_row_meta'), 10, 2);

		add_action('wp_dashboard_setup', array($this, 'add_gs_connector_summary_widget'));

		add_action('wp_ajax_save_gs_cf7db_setting', array($this, 'save_gs_cf7db_setting'));

		if (! get_option('gscf7_options_migrated')) {
			$this->cf7_maybe_migrate_old_postmeta();
		}
		add_filter('plugin_action_links_' . GS_CONNECTOR_BASE_NAME, array($this, 'gscf7_connector_free_plugin_action_links'));
	}


	/**
	 * Remove beforeunload dialog on CF7 admin edit pages.
	 */
	function cf7gs_block_beforeunload_script()
	{
		if (! function_exists('get_current_screen')) {
			return;
		}

		$screen = get_current_screen();
		if (empty($screen) || ($screen->id !== 'contact_page_cf7-new' && strpos($screen->id, 'wpcf7') === false)) {
			return;
		}
?>
		<script>
			/*
			 * Remove only this plugin's own unsaved-changes prompt.
			 *
			 * This previously replaced window.addEventListener globally and
			 * discarded every beforeunload listener on the page, which also
			 * disabled the unsaved-changes warnings belonging to WordPress core,
			 * Contact Form 7 and any other plugin active on the screen.
			 */
			(function() {
				if (window.onbeforeunload) {
					window.onbeforeunload = null;
				}
			})();
		</script>
<?php
	}

	public function gscf7_connector_free_plugin_action_links($links)
	{
		/*
		  We shouldn't encourage editing our plugin directly. */
		/* We shouldn't encourage editing our plugin directly. */
		unset($links['edit']);

		return array_merge(
			array(
				'<a href="' . esc_url(admin_url('admin.php?page=wpcf7-google-sheet-config')) . '">' .
					esc_html__('Settings', 'cf7-google-sheets-connector') .
					'</a>',
			),
			$links
		);
	}

	/**
	 * Migrates postmeta data
	 * storage to the plugin's custom database tables.
	 *
	 * @since 1.5.7
	 *
	 * @return void
	 */
	public function cf7_maybe_migrate_old_postmeta()
	{
		global $wpdb;
		$this->add_admin_database();
		update_option('gs_cf7_auth_method', 'cf7_existing');
		$feeds_table    = $wpdb->prefix . 'cf7gs_feeds';
		$settings_table = $wpdb->prefix . 'cf7gs_settings';
		if (get_option('gscf7_options_migrated')) {
			return;
		}
		update_option('gs_cf7db_setting', 1);
		// ========================
		// GET SETTINGS
		// ========================
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking whether a custom plugin table exists.
		$rows = $wpdb->get_results(
			"
            SELECT post_id, meta_key, meta_value 
            FROM {$wpdb->postmeta}
            WHERE meta_key = 'gs_settings'
            "
		);
		if (! empty($rows)) {
			$grouped = array();
			foreach ($rows as $row) {
				$grouped[$row->post_id][$row->meta_key] = maybe_unserialize($row->meta_value);
			}
			foreach ($grouped as $form_id => $meta) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking whether a custom plugin table exists.
				$wpdb->insert(
					$feeds_table,
					array(
						'form_id'   => $form_id,
						'feed_name' => 'single setting Feed',
						'status'    => 1,
					)
				);
				$feed_id = $wpdb->insert_id;
				if (! $feed_id) {
					continue;
				}
				$settings = $meta['gs_settings'] ?? array();
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking whether a custom plugin table exists.
				$wpdb->insert(
					$settings_table,
					array(
						'form_id'    => $form_id,
						'feed_id'    => $feed_id,
						'sheet_name' => sanitize_text_field($settings['sheet-name'] ?? ''),
						'tab_name'   => sanitize_text_field($settings['sheet-tab-name'] ?? ''),
						'sheet_id'   => sanitize_text_field($settings['sheet-id'] ?? ''),
						'tab_id'     => isset($settings['tab-id']) ? (string) $settings['tab-id'] : null,
						'is_manual'  => 1,
					)
				);
			}
		}
		// ========================
		// MIGRATE FEEDS
		// ========================
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking whether a custom plugin table exists.
		$feeds = $wpdb->get_results(
			"
            SELECT meta_id, post_id, meta_key
            FROM {$wpdb->postmeta}
            WHERE meta_value LIKE '%cf7_form_feeds%'
            "
		);
		if (! empty($feeds)) {
			foreach ($feeds as $feed) {
				$form_id   = $feed->post_id;
				$feed_name = $feed->meta_key;
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking whether a custom plugin table exists.
				$exists = $wpdb->get_var(
					$wpdb->prepare(
						'SELECT id FROM `' . esc_sql($wpdb->prefix . 'cf7gs_feeds') . '` WHERE form_id = %d AND feed_name = %s',
						$form_id,
						$feed_name
					)
				);

				if ($exists) {
					continue;
				}
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking whether a custom plugin table exists.
				$wpdb->insert(
					$feeds_table,
					array(
						'form_id'   => $form_id,
						'feed_name' => $feed_name,
						'status'    => 1,
					)
				);
			}
		}
		// ========================
		// DONE
		// ========================
		update_option('gscf7_options_migrated', 1);
	}

	/**
	 * Save Date base save settings
	 *
	 * This function handles the AJAX request when the user
	 * clicks "Save Settings"
	 * It verifies the nonce, checks user capability,
	 * sanitizes the input, and stores the setting in the database.
	 */
	public function cfdb7_before_send_mail($form_tag)
	{
		$gs_cf7db_setting = get_option('gs_cf7db_setting');
		if ($gs_cf7db_setting == 1) {
			$cf7db = new GS_CF7DB();
			// $cf7db->cfdb7_before_send_mail($form_tag, $this->gs_uploads);
		}
	}
	/**
	 * Plugin row meta.
	 * Adds row meta links to the plugin list table
	 * Fired by `plugin_row_meta` filter.
	 *
	 * @since 1.1.4
	 * @access public
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
			$row_meta    = array(
				'docs' => '<a href="https://www.gsheetconnector.com/docs/cf7-gsheetconnector/" aria-label="' . esc_attr(esc_html__('View Documentation', 'cf7-google-sheets-connector')) . '" target="_blank">' . esc_html__('Docs', 'cf7-google-sheets-connector') . '</a>',
				'ideo' => '<a href="https://www.gsheetconnector.com/support" aria-label="' . esc_attr(esc_html__('Get Support', 'cf7-google-sheets-connector')) . '" target="_blank">' . esc_html__('Support', 'cf7-google-sheets-connector') . '</a>',
			);
			$plugin_meta = array_merge($plugin_meta, $row_meta);
		}
		return $plugin_meta;
	}
	/**
	 * Do things on plugin activation
	 *
	 * @since 1.0
	 */
	public function gs_connector_activate($network_wide)
	{
		global $wpdb;
		$this->run_on_activation();
		if (function_exists('is_multisite') && is_multisite()) {
			// check if it is a network activation - if so, run the activation function for each blog id
			if ($network_wide) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking whether a custom plugin table exists.
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
	 *
	 * @since 1.0
	 */
	public function gs_connector_deactivate($network_wide) {}
	/**
	 *  Runs on plugin uninstall.
	 *  a static class method or function can be used in an uninstall hook
	 *
	 *  @since 1.5
	 */
	public static function gs_connector_free_uninstall()
	{
		global $wpdb;
		self::run_on_uninstall_free();
		if (! is_plugin_active('cf7-google-sheets-connector-pro/cf7-google-sheets-connector.php') || (! file_exists(plugin_dir_path(__DIR__) . 'cf7-google-sheets-connector-pro/cf7-google-sheets-connector.php'))) {
			return;
		}
		if (function_exists('is_multisite') && is_multisite()) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking whether a custom plugin table exists.
			$blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->base_prefix}blogs");
			// Get all blog ids; foreach them and call the install procedure on each of them if the plugin table is found
			foreach ($blog_ids as $blog_id) {
				switch_to_blog($blog_id);
				self::delete_for_site_free();
				restore_current_blog();
			}
			return;
		}
		self::delete_for_site_free();
	}
	/**
	 * Validate parent Plugin Contact Form 7 exist and activated
	 *
	 * @access public
	 * @since 1.0
	 */
	public function validate_parent_plugin_exists()
	{
		$plugin = plugin_basename(__FILE__);
		if ((! is_plugin_active('contact-form-7/wp-contact-form-7.php')) || (! file_exists(plugin_dir_path(__DIR__) . 'contact-form-7/wp-contact-form-7.php'))) {
			add_action('admin_notices', array($this, 'contact_form_7_missing_notice'));
			add_action('network_admin_notices', array($this, 'contact_form_7_missing_notice'));
			deactivate_plugins($plugin);
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from Google.
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
		$plugin_error = Gs_Connector_Free_Utility::instance()->admin_notice(
			array(
				'type'    => 'error',
				'message' => __('Google Sheet Connector Add-on requires Contact Form 7 plugin to be installed and activated.', 'cf7-google-sheets-connector'),
			)
		);
		echo wp_kses_post($plugin_error);
	}
	/**
	 * Create/Register menu items for the plugin.
	 *
	 * @since 1.0
	 */
	public function register_gs_menu_pages()
	{
		add_submenu_page(
			'wpcf7',
			__('Google Sheets', 'cf7-google-sheets-connector'),
			__('Google Sheets', 'cf7-google-sheets-connector'),
			'manage_options', // capability
			'wpcf7-google-sheet-config',
			array($this, 'google_sheet_configuration')
		);
	}
	/**
	 * Google Sheets page action.
	 * This method is called when the menu item "Google Sheets" is clicked.
	 *
	 * @since 1.0
	 */
	public function google_sheet_configuration()
	{
		if (! current_user_can('manage_options')) {
			wp_die(esc_html__('You are not allowed to access this page.', 'cf7-google-sheets-connector'));
		}

		include GS_CONNECTOR_PATH . 'includes/pages/google-sheet-settings.php';
	}
	/**
	 * Save CF7 Database integration setting via AJAX.
	 *
	 * Updates the plugin setting and creates the required
	 * database table when the integration is enabled.
	 *
	 * @return void
	 */
	public function save_gs_cf7db_setting()
	{
		check_ajax_referer('gs-ajax-nonce', 'security');
		$value = isset($_POST['gs_cf7db_setting']) ? intval($_POST['gs_cf7db_setting']) : 1;
		update_option('gs_cf7db_setting', $value);
		if ($value == 1) {
			$Gs_cf7db = new GS_CF7DB();
			$Gs_cf7db->create_gsheet_table();
		}
		wp_send_json_success();
	}
	/**
	 * Google Sheets page action.
	 * This method is called when the menu item "Google Sheets" is clicked.
	 *
	 * @since 1.0
	 */
	public function google_sheet_config()
	{
		include GS_CONNECTOR_PATH . 'includes/pages/gs-integration.php';
	}
	/**
	 * Function for creating database table
	 *
	 * @since 5.1.7
	 */
	public function load_css_and_js_files()
	{
		add_action('admin_print_styles', array($this, 'add_css_files'));
		add_action('admin_print_scripts', array($this, 'add_js_files'));
	}
	/**
	 * enqueue CSS files
	 *
	 * @since 1.0
	 */
	public function add_css_files()
	{

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from Google.
		if (is_admin() && (isset($_GET['page']) && (($_GET['page'] == 'wpcf7-new') || ($_GET['page'] == 'wpcf7-google-sheet-config') || ($_GET['page'] == 'wpcf7')))) {
			// wp_enqueue_style('gs-connector-css', GS_CONNECTOR_URL . 'assets/css/gs-connector.css', GS_CONNECTOR_VERSION, true);
			// wp_enqueue_style('gs-connector-faq-css', GS_CONNECTOR_URL . 'assets/css/faq-style.css', GS_CONNECTOR_VERSION, true);
			wp_enqueue_style('gs-fontawesome-css', GS_CONNECTOR_URL . 'assets/css/fontawesome.css', GS_CONNECTOR_VERSION, true);
			wp_enqueue_style('gs-cf7-free-css', GS_CONNECTOR_URL . 'assets/css/gs-cf7-free.css', GS_CONNECTOR_VERSION, true);
			wp_enqueue_style('gs-edit-setting-cf7-free-css', GS_CONNECTOR_URL . 'assets/css/edit-setting-cf7-free.css', GS_CONNECTOR_VERSION, true);
			wp_enqueue_style('gs-header-css', GS_CONNECTOR_URL . 'assets/css/header.css', GS_CONNECTOR_VERSION, true);
			wp_enqueue_style('gs-footer-css', GS_CONNECTOR_URL . 'assets/css/footer.css', GS_CONNECTOR_VERSION, true);
			wp_enqueue_style('gs-pro-feature-css', GS_CONNECTOR_URL . 'assets/css/pro-feature.css', GS_CONNECTOR_VERSION, true);
			wp_enqueue_style('gs-extra-style-css', GS_CONNECTOR_URL . 'assets/css/extra-style.css', GS_CONNECTOR_VERSION, true);
			wp_enqueue_style('gs-global-css', GS_CONNECTOR_URL . 'assets/css/global.css', GS_CONNECTOR_VERSION, true);
			wp_enqueue_style('gs-responsive-css', GS_CONNECTOR_URL . 'assets/css/responsive.css', GS_CONNECTOR_VERSION, true);
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from Google.
		if (is_plugin_active('cf7-grid-layout/cf7-grid-layout.php') && ((isset($_REQUEST['post_type']) && $_REQUEST['post_type'] == 'wpcf7_contact_form') || (isset($_REQUEST['action']) && $_REQUEST['action'] == 'edit'))) {
			wp_enqueue_style('gs-connector-css', GS_CONNECTOR_URL . 'assets/css/gs-connector.css', GS_CONNECTOR_VERSION, true);
			wp_enqueue_style('gs-connector-faq-css', GS_CONNECTOR_URL . 'assets/css/faq-style.css', GS_CONNECTOR_VERSION, true);
		}
	}
	/**
	 * enqueue JS files
	 *
	 * @since 1.0
	 */
	public function add_js_files()
	{

		if (! is_admin()) {
			return;
		}
		$request_uri = isset($_SERVER['REQUEST_URI'])
			? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']))
			: '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from Google.
		$page = isset($_GET['page'])
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from Google.
			? sanitize_text_field(wp_unslash($_GET['page']))
			: '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from Google.
		$post_type = isset($_REQUEST['post_type'])
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from Google.
			? sanitize_text_field(wp_unslash($_REQUEST['post_type']))
			: '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from Google.
		$action = isset($_REQUEST['action'])
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from Google.
			? sanitize_text_field(wp_unslash($_REQUEST['action']))
			: '';

		/*
		* CF7 Admin Pages
		*/
		if (
			preg_match(
				'/page=wpcf7-new|page=wpcf7-google-sheet-config|page=wpcf7/',
				$request_uri
			)
		) {

			wp_enqueue_script(
				'gs-connector-js',
				GS_CONNECTOR_URL . 'assets/js/gs-connector.js',
				array('jquery'),
				GS_CONNECTOR_VERSION,
				true
			);

			wp_enqueue_script(
				'gsc-connector-extensions-pro-free',
				GS_CONNECTOR_URL . 'assets/js/gs-connector-extensions.js',
				array('jquery'),
				GS_CONNECTOR_VERSION,
				true
			);

			wp_enqueue_script(
				'gs-connector-systeminfo-free',
				GS_CONNECTOR_URL . 'assets/js/gs-connector-systeminfo.js',
				array('jquery'),
				GS_CONNECTOR_VERSION,
				true
			);
		}

		/*
		* CF7 Grid Layout Support
		*/
		if (
			is_plugin_active('cf7-grid-layout/cf7-grid-layout.php') &&
			(
				'wpcf7_contact_form' === $post_type ||
				'edit' === $action
			)
		) {

			wp_enqueue_script(
				'jquery-json',
				GS_CONNECTOR_URL . 'assets/js/jquery.json.js',
				array('jquery'),
				'2.3',
				true
			);
		}

		/*
		* Common Admin JS
		*
		* Scoped to the plugin's own screens. This was previously enqueued on every
		* single wp-admin page, adding a request and a jQuery dependency to screens
		* that have nothing to do with this plugin.
		*/
		if (
			preg_match(
				'/page=wpcf7-new|page=wpcf7-google-sheet-config|page=wpcf7/',
				$request_uri
			)
		) {

			wp_enqueue_script(
				'gs-connector-adds-js',
				GS_CONNECTOR_URL . 'assets/js/gs-connector-adds.js',
				array('jquery'),
				GS_CONNECTOR_VERSION,
				true
			);
		}
	}
	/**
	 * Function to load all required classes
	 *
	 * @since 2.8
	 */
	public function load_all_classes()
	{
		if (! class_exists('GS_Connector_Adds')) {
			include GS_CONNECTOR_PATH . 'includes/class-gs-adds.php';
		}
		if (! class_exists('GSCF7_Extensions_free')) {
			include GS_CONNECTOR_PATH . 'includes/pages/extensions/cf7gs-extension-service.php';
		}

		if (! class_exists('gscf7_error_logs')) {

			include GS_CONNECTOR_PATH . '/includes/class-gsc-error-logs.php';
		}
	}
	/**
	 * called on upgrade.
	 * checks the current version and applies the necessary upgrades from that version onwards
	 *
	 * @since 5.0.20
	 */
	public function run_on_upgrade()
	{
		$plugin_options = get_site_option('google_sheet_info_free');
		if (isset($plugin_options['version']) && version_compare($plugin_options['version'], '3.0', '<=')) {
			$this->upgrade_database_40();
			$this->upgrade_database_41();
		} elseif (isset($plugin_options['version']) && $plugin_options['version'] === '5.0.19') {
			$this->upgrade_database_41();
		}
		// Update version info
		$google_sheet_info_free = array(
			'version'    => GS_CONNECTOR_VERSION,
			'db_version' => GS_CONNECTOR_DB_VERSION,
		);

		// Delete old debug log file (WordPress-safe way)
		$log_file_path = GS_CONNECTOR_PATH . 'logs/log.txt';

		if (file_exists($log_file_path)) {
			wp_delete_file($log_file_path);
		}

		$this->gscf7_disable_credential_autoload();
		$this->gscf7_upgrade_entry_table_indexes();
		$this->gscf7_migrate_error_log_timestamps();

		update_site_option('google_sheet_info_free', $google_sheet_info_free);
	}

	/**
	 * Convert existing error-log timestamps from site-local time to UTC.
	 *
	 * Before 5.2.1 `created_at` was written with current_time('mysql'), which
	 * stores the site's local time. That made stored rows depend on whatever the
	 * timezone happened to be when they were written, so changing
	 * Settings > General > Timezone silently reinterpreted every historical row.
	 *
	 * Timestamps are now stored in UTC and converted for display. This converts
	 * the rows written under the old behaviour so they render correctly.
	 *
	 * Runs once per site.
	 *
	 * @since 5.2.1
	 *
	 * @return void
	 */
	private function gscf7_migrate_error_log_timestamps()
	{
		if (get_option('gscf7_error_log_utc_migrated')) {
			return;
		}

		global $wpdb;

		$table = $wpdb->prefix . 'gscf7_error_logs';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking whether a custom plugin table exists.
		$exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));

		if ($exists !== $table) {
			update_option('gscf7_error_log_utc_migrated', 1, false);
			return;
		}

		// Nothing to convert when the site already runs on UTC.
		if (0 === (int) wp_timezone()->getOffset(new DateTime('now', new DateTimeZone('UTC')))) {
			update_option('gscf7_error_log_utc_migrated', 1, false);
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin log table.
		$rows = $wpdb->get_results(
			'SELECT id, created_at FROM `' . esc_sql($table) . '`',
			ARRAY_A
		);

		if (! empty($rows)) {
			foreach ($rows as $row) {

				$gmt = get_gmt_from_date($row['created_at']);

				if (empty($gmt) || $gmt === $row['created_at']) {
					continue;
				}

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin log table.
				$wpdb->update(
					$table,
					array('created_at' => $gmt),
					array('id' => (int) $row['id']),
					array('%s'),
					array('%d')
				);
			}
		}

		update_option('gscf7_error_log_utc_migrated', 1, false);
	}

	/**
	 * Add the entry-table indexes to sites created before 5.2.1.
	 *
	 * Runs once per site. Adding an index is a non-destructive operation and
	 * does not change any stored data.
	 *
	 * @since 5.2.1
	 *
	 * @return void
	 */
	private function gscf7_upgrade_entry_table_indexes()
	{
		if (get_option('gscf7_entry_indexes_added')) {
			return;
		}

		global $wpdb;

		$table = $wpdb->prefix . 'cf7db_gsheet_forms';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking whether a custom plugin table exists.
		$exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));

		if ($exists === $table && class_exists('GS_CF7DB')) {
			$gs_cf7db = new GS_CF7DB();
			$gs_cf7db->gscf7_add_entry_indexes($table);
		}

		update_option('gscf7_entry_indexes_added', 1, false);
	}

	/**
	 * Stop autoloading options that hold Google credentials.
	 *
	 * These options were previously stored with autoload enabled, which meant the
	 * OAuth access token, refresh token, client secret and service account key
	 * were read into memory on every request, including front-end page views.
	 * They are only needed in admin and submission contexts.
	 *
	 * Runs once per site; the values themselves are left untouched.
	 *
	 * @since 5.2.1
	 *
	 * @return void
	 */
	private function gscf7_disable_credential_autoload()
	{
		if (get_option('gscf7_autoload_migrated')) {
			return;
		}

		$options = array(
			'gs_token',
			'gs_access_code',
			'gs_cf7_service_account_json',
			'cf7gsc_free_api_creds',
			'cf7gf_email_account',
		);

		foreach ($options as $option_name) {
			$value = get_option($option_name, null);

			if (null === $value) {
				continue;
			}

			// Re-adding the option is the supported way to change its autoload flag.
			delete_option($option_name);
			add_option($option_name, $value, '', false);
		}

		update_option('gscf7_autoload_migrated', 1, false);
	}
	/**
	 * Upgrade database for version 4.0.
	 *
	 * Handles single-site and multisite upgrades.
	 *
	 * @return void
	 */
	public function upgrade_database_40()
	{
		global $wpdb;
		// look through each of the blogs and upgrade the DB
		if (function_exists('is_multisite') && is_multisite()) {
			// Get all blog ids;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking whether a custom plugin table exists.
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
	/**
	 * Execute upgrade tasks for version 4.0.
	 *
	 * @return void
	 */
	public function upgrade_helper_40()
	{
		// Add the transient to redirect.
		set_transient('cf7gs_upgrade_redirect', true, 30);
	}
	/**
	 * Upgrade database for version 4.1.
	 *
	 * Handles single-site and multisite upgrades.
	 *
	 * @return void
	 */
	public function upgrade_database_41()
	{
		global $wpdb;

		// look through each of the blogs and upgrade the DB
		if (function_exists('is_multisite') && is_multisite()) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking whether a custom plugin table exists.
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
	/**
	 * Execute upgrade tasks for version 4.1.
	 *
	 * @return void
	 */
	public function upgrade_helper_41()
	{
		// Fetch and save the API credentails.
		Gs_Connector_Free_Utility::instance()->save_api_credentials();
	}
	/**
	 * Redirect users to the settings page after upgrade.
	 *
	 * @return void
	 */
	public function redirect_after_upgrade()
	{
		if (! get_transient('cf7gs_upgrade_redirect')) {
			return;
		}
		$plugin_options = get_site_option('google_sheet_info_free');

		// Guard the array access: the option may be absent or not an array,
		// which raised a PHP 8 warning on every matching admin request.
		if (! empty($plugin_options['version']) && '4.0' === $plugin_options['version']) {
			delete_transient('cf7gs_upgrade_redirect');
			wp_safe_redirect(admin_url('admin.php?page=wpcf7-google-sheet-config'));
			exit;
		}
	}
	/**
	 * Add custom link for the plugin beside activate/deactivate links
	 *
	 * @param array $links Array of links to display below our plugin listing.
	 * @return array Amended array of links.    *
	 * @since 1.5
	 */
	public function gs_connector_plugin_action_links($links)
	{
		// Define the text for the "Get Pro" link
		$go_pro_text = esc_html__('Upgrade to Pro', 'cf7-google-sheets-connector');

		// Check if the Pro version of the plugin is installed and activated
		if (is_plugin_active('cf7-google-sheets-connector-pro/cf7-google-sheets-connector.php')) {
			// If Pro version is active, return the links without adding the "Get Pro" link
			return $links;
		}

		// Add the action link to the plugin page with green color styling
		$links['go_pro'] = sprintf(
			'<a href="%s" target="_blank" class="gsheetconnector-pro-link" style="color: green;">%s</a>',
			esc_url('https://www.gsheetconnector.com/cf7-google-sheet-connector-pro'),
			$go_pro_text
		);

		return $links;
	}
	/**
	 * Register the GSheetConnector dashboard widget.
	 *
	 * Adds a custom dashboard widget to the WordPress admin dashboard
	 * displaying Contact Form 7 Google Sheets Connector summary information.
	 *
	 * @return void
	 */
	public function add_gs_connector_summary_widget()
	{
		/*
		 * The widget exposes the Google Spreadsheet IDs every form is connected to.
		 * Restrict it to users who may manage the plugin's settings.
		 */
		if (! current_user_can('manage_options')) {
			return;
		}

		$title = sprintf(
			'<img style="width:30px;margin-right:10px;" src="%sassets/img/cf7-gsc.svg" alt="" /> <span>%s</span>',
			esc_url(GS_CONNECTOR_URL),
			esc_html__('Contact Form 7 - GSheetConnector', 'cf7-google-sheets-connector')
		);

		wp_add_dashboard_widget(
			'gs_dashboard',
			$title,
			array($this, 'gs_connector_summary_dashboard')
		);
	}
	/**
	 * Render the GSheetConnector dashboard widget content.
	 *
	 * Loads the dashboard widget template file.
	 *
	 * @return void
	 */
	public function gs_connector_summary_dashboard()
	{
		if (! current_user_can('manage_options')) {
			return;
		}

		include_once GS_CONNECTOR_ROOT . '/includes/pages/cf7gs-dashboard-widget.php';
	}
	/**
	 * Called on activation.
	 * Creates the site_options (required for all the sites in a multi-site setup)
	 * If the current version doesn't match the new version, runs the upgrade
	 *
	 * @since 1.0
	 */
	private function run_on_activation()
	{
		$plugin_options = get_site_option('google_sheet_info_free');
		if (false === $plugin_options) {
			$google_sheet_info_free = array(
				'version'    => GS_CONNECTOR_VERSION,
				'db_version' => GS_CONNECTOR_DB_VERSION,
			);
			update_site_option('google_sheet_info_free', $google_sheet_info_free);
		} elseif (GS_CONNECTOR_DB_VERSION != $plugin_options['version']) {
			$this->run_on_upgrade();
		}
		update_option('gs_cf7db_database', 1);
		$gs_cf7db_database = get_option('gs_cf7db_database');
		if ($gs_cf7db_database == 1) {
			$Gs_cf7db = new GS_CF7DB();
			$Gs_cf7db->create_gsheet_table();
			update_option('gs_cf7db_database', 1);
		}
		Gs_Connector_Free_Utility::instance()->save_api_credentials();
	}
	/**
	 * Called on activation.
	 * Creates the options and DB (required by per site)
	 *
	 * @since 1.0
	 */
	public function run_for_site()
	{
		if (! get_option('gs_access_code')) {
			update_option('gs_access_code', '');
		}
		if (! get_option('gs_verify')) {
			update_option('gs_verify', 'invalid');
		}
		if (! get_option('gs_token')) {
			update_option('gs_token', '');
		}
		if (! get_option('gs_cf7_auth_method')) {
			update_option('gs_cf7_auth_method', '');
		}
		// CF7 Database Settings
		if (! get_option('gs_cf7db_setting')) {
			update_option('gs_cf7db_setting', 1);
		}
		if (! get_option('gs_cf7db_database')) {
			update_option('gs_cf7db_database', 1);
		}
		if (! get_option('gscf7_free_install_time')) {
			update_option('gscf7_free_install_time', time());
		}
		$this->add_admin_database();
	}
	/**
	 * Create or Update Database Tables for CF7 Google Sheets Connector
	 *
	 * This function is responsible for creating and updating the required
	 * custom database tables used by the plugin. It uses WordPress's dbDelta()
	 * function to safely handle both table creation and schema updates.
	 *
	 * Tables Created:
	 * - cf7gs_feeds    : Stores feed configurations per Contact Form 7 form
	 * - cf7gs_settings : Stores Google Sheet settings and UI configurations per feed
	 *
	 * Features:
	 * - Supports multi-feed per form
	 * - Stores sheet mapping and metadata
	 * - Handles UI settings like sorting, colors, and header behavior
	 * - Automatically updates tables when structure changes
	 *
	 * Notes:
	 * - Uses $wpdb->prefix for dynamic table naming (multisite compatible)
	 * - Uses charset and collation for database consistency
	 * - Safe to run multiple times (dbDelta handles differences)
	 *
	 * @since 5.1.7
	 * @return void
	 */
	public function add_admin_database()
	{
		// (1)================== create table for CF7DB =============
		$gs_cf7db_database = get_option('gs_cf7db_database');
		if ($gs_cf7db_database == 1) {
			$Gs_cf7db = new GS_CF7DB();
			$Gs_cf7db->create_gsheet_table();
			update_option('gs_cf7db_database', 1);
		}
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		// ERROR LOG TABLE
		$table = $wpdb->prefix . 'gscf7_error_logs';
		dbDelta(
			"CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            error_id VARCHAR(191) NOT NULL,
            code INT NOT NULL,
            message TEXT NOT NULL,
            details LONGTEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            INDEX error_id (error_id),
            INDEX code (code),
            INDEX created_at (created_at)
        ) {$charset_collate};"
		);

		// Existing installs predate the created_at index used by the log screen.
		$this->gscf7_add_index_if_not_exists($table, 'created_at');
		// TABLE NAMES
		$feeds_table    = $wpdb->prefix . 'cf7gs_feeds';
		$settings_table = $wpdb->prefix . 'cf7gs_settings';

		// NO INDEX HERE
		dbDelta(
			"CREATE TABLE $feeds_table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            form_id BIGINT UNSIGNED NOT NULL,
            feed_name VARCHAR(255),
            status TINYINT(1) DEFAULT 1,
            is_default TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;"
		);

		dbDelta(
			"CREATE TABLE {$settings_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            form_id BIGINT UNSIGNED NOT NULL,
            feed_id BIGINT UNSIGNED NOT NULL,
            sheet_name VARCHAR(255) DEFAULT NULL,
            tab_name VARCHAR(255) DEFAULT NULL,
            sheet_id VARCHAR(255) DEFAULT NULL,
            tab_id VARCHAR(255) DEFAULT NULL,
            is_manual TINYINT(1) DEFAULT 0,
            google_drive_link LONGTEXT DEFAULT NULL,
            freeze_header TINYINT(1) DEFAULT 0,
            enable_colors TINYINT(1) DEFAULT 0,
            header_color VARCHAR(20) DEFAULT NULL,
            odd_color VARCHAR(20) DEFAULT NULL,
            even_color VARCHAR(20) DEFAULT NULL,
            enable_sorting TINYINT(1) DEFAULT 0,
            sort_column VARCHAR(255) DEFAULT NULL,
            sort_order VARCHAR(20) DEFAULT NULL,
            header_enable TINYINT(1) DEFAULT 0,
            font_styles TEXT DEFAULT NULL,
            font_size VARCHAR(50) DEFAULT NULL,
            font_color VARCHAR(50) DEFAULT NULL,
            row_enable TINYINT(1) DEFAULT 0,
            row_styles TEXT DEFAULT NULL,
            row_font_size VARCHAR(50) DEFAULT NULL,
            row_font_color VARCHAR(50) DEFAULT NULL,
            created_at DATETIME DEFAULT NULL,
            updated_at DATETIME DEFAULT NULL,
            PRIMARY KEY  (id)
        ) {$charset_collate};"
		);

		// STEP 2: ADD INDEXES MANUALLY
		$this->gscf7_add_index_if_not_exists($feeds_table, 'form_id');
		$this->gscf7_add_index_if_not_exists($settings_table, 'form_id');

		// STEP 3: ADD FOREIGN KEYS
		$this->gscf7_add_fk_if_not_exists($settings_table, 'fk_settings_feed', 'feed_id', $feeds_table);
	}
	private function gscf7_add_index_if_not_exists($table, $column)
	{
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking index existence in INFORMATION_SCHEMA.
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(1)
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE table_schema = DATABASE()
                AND table_name = %s
                AND column_name = %s',
				$table,
				$column
			)
		);

		if (! $exists) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared -- ALTER TABLE cannot use placeholders; table/column sanitized with esc_sql() and backticks.
			$wpdb->query('ALTER TABLE `' . esc_sql($table) . '` ADD INDEX `' . esc_sql($column) . '` (`' . esc_sql($column) . '`)');
		}
	}
	private function gscf7_add_fk_if_not_exists($table, $constraint, $column, $ref_table)
	{
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking whether a custom plugin table exists.
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT CONSTRAINT_NAME
                FROM information_schema.TABLE_CONSTRAINTS
                WHERE CONSTRAINT_SCHEMA = DATABASE()
                AND TABLE_NAME = %s
                AND CONSTRAINT_NAME = %s',
				$table,
				$constraint
			)
		);

		if ($exists) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking whether a custom plugin table exists.
		$index_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(1)
                FROM INFORMATION_SCHEMA.STATISTICS
                WHERE table_schema = DATABASE()
                AND table_name = %s
                AND column_name = %s',
				$table,
				$column
			)
		);

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if (! $index_exists) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared -- ALTER TABLE cannot use placeholders; sanitized with esc_sql() and backticks.
			$wpdb->query('ALTER TABLE `' . esc_sql($table) . '` ADD INDEX `' . esc_sql($column) . '` (`' . esc_sql($column) . '`)');
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared -- ALTER TABLE cannot use placeholders; sanitized with esc_sql() and backticks.
		$wpdb->query('ALTER TABLE `' . esc_sql($table) . '` ENGINE=InnoDB');

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared -- ALTER TABLE cannot use placeholders; sanitized with esc_sql() and backticks.
		$wpdb->query('ALTER TABLE `' . esc_sql($ref_table) . '` ENGINE=InnoDB');

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared -- ALTER TABLE cannot use placeholders; sanitized with esc_sql() and backticks.
		$wpdb->query(
			'ALTER TABLE `' . esc_sql($table) . '`
            ADD CONSTRAINT `' . esc_sql($constraint) . '`
            FOREIGN KEY (`' . esc_sql($column) . '`)
            REFERENCES `' . esc_sql($ref_table) . '`(id)
            ON DELETE CASCADE
            ON UPDATE CASCADE'
		);

		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.SchemaChange
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
	/**
	 * Called on uninstall - deletes site_options
	 *
	 * @since 1.5
	 */
	private static function run_on_uninstall_free()
	{
		if (! defined('ABSPATH') && ! defined('WP_UNINSTALL_PLUGIN')) {
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

		global $wpdb;

		if (
			! is_plugin_active('cf7-google-sheets-connector-pro/cf7-google-sheets-connector.php') ||
			(! file_exists(plugin_dir_path(__DIR__) . 'cf7-google-sheets-connector-pro/cf7-google-sheets-connector.php'))
		) {

			$saved_value = get_option('gscf7_uninstall_settings_free', 'No');
			if ($saved_value === 'Yes') {
				// Delete options
				delete_option('gs_access_code');
			}
			delete_option('gs_verify');
			delete_option('gs_cf7db_setting');
			delete_option('gs_cf7db_database');
			delete_option('gs_token');
			delete_option('google_sheet_info_free');
			delete_option('gs_close_add_interval');
			delete_option('gs_auth_expired_display_add_interval');
			delete_option('gs_auth_expired_close_add_interval');
			delete_option('gscf7_uninstall_settings_free');
			delete_option('cf7gsc_free_api_creds');
			delete_option('gs_debug_log_file');
			delete_option('gscf7_autoload_migrated');
			delete_option('gscf7_entry_indexes_added');
			delete_option('gscf7_error_log_utc_migrated');

			// Delete post meta
			delete_post_meta_by_key('gs_settings');

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching

			$wpdb->query(
				'DROP TABLE IF EXISTS `' . esc_sql($wpdb->prefix . 'gscf7_error_logs') . '`'
			);

			$wpdb->query(
				'DROP TABLE IF EXISTS `' . esc_sql($wpdb->prefix . 'cf7gs_settings') . '`'
			);

			$wpdb->query(
				'DROP TABLE IF EXISTS `' . esc_sql($wpdb->prefix . 'cf7gs_feeds') . '`'
			);

			// phpcs:enable WordPress.DB.DirectDatabaseQuery.SchemaChange
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.NoCaching
		}
	}
}

// Initialize the google sheet connector class
$gscf7_init = new Gs_Connector_Free_Init();
