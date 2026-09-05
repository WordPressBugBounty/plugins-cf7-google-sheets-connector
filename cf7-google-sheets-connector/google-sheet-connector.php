<?php

/**
 * Plugin Name: CF7 Google Sheet Connector
 * Plugin URI: https://wordpress.org/plugins/cf7-google-sheets-connector/
 * Description: Connect Contact Form 7 to Google Sheets and send form submissions to Google Sheets in a Real-Time
 * Requires at least: 6.7
 * Requires PHP: 7.4
 * Version: 5.2.5
 * Author: GSheetConnector
 * Author URI: https://www.gsheetconnector.com/
 * Text Domain: cf7-google-sheets-connector
 * Domain Path:  /languages
 * Requires Plugins: contact-form-7
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

/*
 * This plugin owns and maintains its own custom database tables
 * (cf7gs_feeds, cf7gs_settings, cf7db_gsheet_forms). Everything the DB sniffs
 * flag below is inherent to that and unavoidable:
 *  - direct $wpdb calls on those tables (no WP API covers them);
 *  - schema changes (CREATE / ALTER / RENAME / DROP TABLE) during activation
 *    and the one-time dedupe migration;
 *  - table / index / column identifiers interpolated into the SQL, which
 *    $wpdb->prepare() placeholders cannot handle -- every one is passed
 *    through esc_sql() first.
 * These are suppressed file-wide rather than with dozens of per-line ignores.
 */
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared

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
define('GS_CONNECTOR_VERSION', '5.2.5');
define('GS_CONNECTOR_DB_VERSION', '5.2.5');
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
Gs_Connector_Service::instance();

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

		// Version-independent credential bootstrap -- see gscf7_maybe_bootstrap_credentials().
		add_action('admin_init', array($this, 'gscf7_maybe_bootstrap_credentials'), 5);

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

		// Clears the duplicate feed / settings rows left behind by the migration
		// before it was made idempotent -- see gscf7_repair_duplicate_rows().
		add_action('admin_init', array($this, 'gscf7_repair_duplicate_rows'), 1);
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
			 * Suppress the browser's "Leave site? Changes you made may not be
			 * saved" prompt on the Contact Form 7 form editor.
			 *
			 * CF7 (admin/includes/js/index.js) registers a window "beforeunload"
			 * listener that fires that prompt whenever any control inside
			 * #wpcf7-admin-form-element differs from its default. This plugin's
			 * "Google Sheets" editor panel injects its own inputs into that
			 * form, so CF7 reads the page as "unsaved" even when nothing was
			 * edited and the prompt appears on every navigation away.
			 *
			 * This inline script runs during page parse -- before CF7's
			 * DOMContentLoaded handler registers its listener -- so our
			 * beforeunload listener is first in line. It stops the event, so
			 * CF7's listener never runs and never calls preventDefault().
			 */
			(function () {
				var gscf7SwallowBeforeUnload = function (event) {
					event.stopImmediatePropagation();
				};

				window.addEventListener('beforeunload', gscf7SwallowBeforeUnload, true);

				// Legacy: some setups assign window.onbeforeunload directly.
				window.onbeforeunload = null;
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

		/*
		 * Claim the migration BEFORE writing a single row.
		 *
		 * This runs from the constructor, i.e. on every request, until the flag
		 * is set -- and the flag used to be set only on the very last line, so a
		 * fatal, a timeout or two overlapping requests left it unset and the
		 * whole migration ran again on the next page load. Nothing below was
		 * deduplicated, so each re-run appended another feed row plus another
		 * settings row per form, which is how sites ended up with hundreds of
		 * identical "single setting Feed" rows for one form.
		 *
		 * add_option() is atomic (the option name is a unique key), so exactly
		 * one process can claim the run, and every exit path below leaves it
		 * claimed. gscf7_repair_duplicate_rows() cleans up what already grew.
		 */
		if (! add_option('gscf7_options_migrated', 'running')) {
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
				// Re-use the feed this migration created previously instead of
				// adding a second copy of it.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table lookup, no caching needed.
				$feed_id = (int) $wpdb->get_var(
					$wpdb->prepare(
						'SELECT id FROM `' . esc_sql($wpdb->prefix . 'cf7gs_feeds') . '` WHERE form_id = %d AND feed_name = %s ORDER BY id ASC LIMIT 1',
						$form_id,
						'single setting Feed'
					)
				);

				if (! $feed_id) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking whether a custom plugin table exists.
					$wpdb->insert(
						$feeds_table,
						array(
							'form_id'   => $form_id,
							'feed_name' => 'single setting Feed',
							'status'    => 1,
						)
					);
					$feed_id = (int) $wpdb->insert_id;
				}

				if (! $feed_id) {
					continue;
				}
				$settings      = $meta['gs_settings'] ?? array();
				$settings_data = array(
					'form_id'    => $form_id,
					'feed_id'    => $feed_id,
					'sheet_name' => sanitize_text_field($settings['sheet-name'] ?? ''),
					'tab_name'   => sanitize_text_field($settings['sheet-tab-name'] ?? ''),
					'sheet_id'   => sanitize_text_field($settings['sheet-id'] ?? ''),
					'tab_id'     => isset($settings['tab-id']) ? (string) $settings['tab-id'] : null,
					'is_manual'  => 1,
				);

				// One settings row per feed: refresh it rather than append to it.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table lookup, no caching needed.
				$existing_settings = (int) $wpdb->get_var(
					$wpdb->prepare(
						'SELECT id FROM `' . esc_sql($wpdb->prefix . 'cf7gs_settings') . '` WHERE feed_id = %d ORDER BY id ASC LIMIT 1',
						$feed_id
					)
				);

				if ($existing_settings) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking whether a custom plugin table exists.
					$wpdb->update($settings_table, $settings_data, array('id' => $existing_settings));
				} else {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking whether a custom plugin table exists.
					$wpdb->insert($settings_table, $settings_data);
				}
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
	 * One-time repair for databases bloated by the repeating legacy migration.
	 *
	 * Before this release `cf7_maybe_migrate_old_postmeta()` could return (or
	 * die) without marking itself complete, and its feed / settings inserts were
	 * not deduplicated, so every re-run added another "single setting Feed" row
	 * and another settings row for the same form. Affected sites ended up with
	 * hundreds of identical feeds, which is what made the dashboard widget list
	 * the same form and sheet over and over, and what turned the widget's join
	 * into a several-hundred-thousand-row result set.
	 *
	 * Fixing the migration stops the growth; this removes what already
	 * accumulated. Every step is idempotent, so re-running it is harmless.
	 *
	 * @since 2.2.0
	 *
	 * @return void
	 */
	public function gscf7_repair_duplicate_rows()
	{
		global $wpdb;

		/*
		 * Deliberately a free-plugin option of its own, not the PRO plugin's
		 * `gscf7_dupes_repaired`: the two repairs cover different table sets and
		 * both have to get their pass on a site running both plugins.
		 *
		 * Version marker, not a boolean, so earlier passes can be corrected:
		 *
		 * Pass 2 - pass 1 collapsed feeds on (form_id, feed_name) alone, which
		 *   destroyed real data: a feed re-pointed at another tab leaves a
		 *   second row under the same name that is a different feed. Keying on
		 *   the sheet and tab too keeps both, and renames rather than deletes.
		 * Pass 3 - passes 1 and 2 renamed the live table away without releasing
		 *   the foreign key, so `fk_settings_feed` followed it onto the
		 *   discarded copy. Settings were then validated against frozen data and
		 *   no newly created feed could save its settings at all.
		 */
		$repair_version = 3;

		if ((int) get_option('gscf7_free_dupes_repaired') >= $repair_version) {
			return;
		}

		/*
		 * Concurrency lock, NOT a completion marker. An affected table can hold
		 * a very large number of rows, so the cleanup below can plausibly hit
		 * max_execution_time or the memory limit and die part-way through. A
		 * permanent claim here would leave the site uncleaned forever with no
		 * retry, so the lock is a transient that expires and only a full pass
		 * sets `gscf7_free_dupes_repaired`.
		 */
		if (get_transient('gscf7_free_dupes_repair_lock')) {
			return;
		}
		set_transient('gscf7_free_dupes_repair_lock', 1, 10 * MINUTE_IN_SECONDS);

		// The cleanup is a bulk operation (it can touch hundreds of thousands
		// of rows on a site inflated by the old repeating migration); the
		// default request limits are not enough for it.
		wp_raise_memory_limit('admin');
		if (function_exists('set_time_limit')) {
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, Squiz.PHP.DiscouragedFunctions.Discouraged -- Needed for the one-time bulk dedupe; guarded with function_exists() and silenced because some hosts disable it.
			@set_time_limit(0);
		}

		$feeds_table    = $wpdb->prefix . 'cf7gs_feeds';
		$settings_table = $wpdb->prefix . 'cf7gs_settings';

		$feeds_sql    = esc_sql($feeds_table);
		$settings_sql = esc_sql($settings_table);

		/*
		 * Before anything else, undo the damage an earlier pass's swap did: a
		 * stranded `_gscf7_old` copy still holding the foreign key. Until that
		 * is cleared the constraint points at frozen data and new feeds cannot
		 * save their settings at all.
		 */
		$this->gscf7_cleanup_swap_leftovers($feeds_table);
		$this->gscf7_cleanup_swap_leftovers($settings_table);

		/*
		 * Settings first, so that every feed carries at most one settings row.
		 * The feed pass below joins the two tables, and a feed with two settings
		 * rows would otherwise appear in two groups and confuse the survivor
		 * count. The newest row holds the current configuration.
		 */
		$this->gscf7_dedupe_table($settings_table, array('form_id', 'feed_id'), 'MAX');

		/*
		 * Feeds. Rows are the same logical feed when they share a name AND point
		 * at the same sheet and tab -- see the version note above for why the
		 * name alone is not enough. The newest of each group survives.
		 */
		$this->gscf7_dedupe_feeds_by_sheet($feeds_table, $settings_table);

		// Settings rows belonging to the feeds just removed.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Custom plugin tables, names escaped above.
		$wpdb->query(
			"DELETE s FROM `{$settings_sql}` s
			LEFT JOIN `{$feeds_sql}` f ON s.feed_id = f.id
			WHERE f.id IS NULL"
		);

		/*
		 * Survivors that still share a name are different feeds pointing at
		 * different sheets, so both are kept and the later ones are renamed the
		 * way the import disambiguates them. This has to happen before the
		 * unique index goes on, which is what the index is waiting for.
		 */
		$this->gscf7_suffix_duplicate_feed_names($feeds_table);

		// Now that the data is unique, let the schema enforce it.
		$this->gscf7_add_unique_index_if_not_exists(
			$settings_table,
			'gscf7_settings_unique',
			'feed_id'
		);
		$this->gscf7_add_unique_index_if_not_exists(
			$feeds_table,
			'gscf7_feeds_unique',
			'form_id, feed_name(150)'
		);

		// Only now, after a complete pass, is the repair recorded as done.
		update_option('gscf7_free_dupes_repaired', $repair_version);
		delete_transient('gscf7_free_dupes_repair_lock');
	}

	/**
	 * Drops every foreign key that touches a table, returning them for restore.
	 *
	 * A foreign key follows the table it points at, so the dedupe passes' RENAME
	 * swap drags `fk_settings_feed` onto the discarded copy: it can no longer be
	 * dropped, and settings are validated against frozen data, so any feed
	 * created afterwards fails to save its settings. Releasing before the swap
	 * and restoring after leaves the keys on the live table.
	 *
	 * @since 2.2.0
	 *
	 * @param string $table Full table name, already prefixed.
	 *
	 * @return array Constraint rows for gscf7_restore_foreign_keys().
	 */
	private function gscf7_release_foreign_keys($table)
	{
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Schema lookup for custom plugin tables.
		$constraints = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE CONSTRAINT_SCHEMA = DATABASE()
                AND REFERENCED_TABLE_NAME IS NOT NULL
                AND (REFERENCED_TABLE_NAME = %s OR TABLE_NAME = %s)',
				$table,
				$table
			)
		);

		if (empty($constraints)) {
			return array();
		}

		foreach ($constraints as $constraint) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared -- ALTER TABLE cannot use placeholders; names escaped.
			$wpdb->query(
				'ALTER TABLE `' . esc_sql($constraint->TABLE_NAME) . '` DROP FOREIGN KEY `' . esc_sql($constraint->CONSTRAINT_NAME) . '`'
			);
		}

		return $constraints;
	}

	/**
	 * Re-creates constraints released by gscf7_release_foreign_keys().
	 *
	 * Orphans are cleared first: the key was released so rows could be deleted
	 * underneath it, and MySQL refuses a constraint the data already violates.
	 * Skipping this drops the key silently and leaves the tables unprotected.
	 *
	 * @since 2.2.0
	 *
	 * @param array $constraints Rows returned by gscf7_release_foreign_keys().
	 *
	 * @return void
	 */
	private function gscf7_restore_foreign_keys($constraints)
	{
		global $wpdb;

		if (empty($constraints)) {
			return;
		}

		foreach ($constraints as $constraint) {
			$child  = esc_sql($constraint->TABLE_NAME);
			$parent = esc_sql($constraint->REFERENCED_TABLE_NAME);
			$column = esc_sql($constraint->COLUMN_NAME);

			// Every one of these constraints references the parent's `id`, the
			// same assumption gscf7_add_fk_if_not_exists() makes when adding one.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared -- Custom plugin tables, names escaped above.
			$wpdb->query(
				"DELETE c FROM `{$child}` c
				LEFT JOIN `{$parent}` p ON c.`{$column}` = p.`id`
				WHERE p.`id` IS NULL AND c.`{$column}` IS NOT NULL"
			);

			$this->gscf7_add_fk_if_not_exists(
				$constraint->TABLE_NAME,
				$constraint->CONSTRAINT_NAME,
				$constraint->COLUMN_NAME,
				$constraint->REFERENCED_TABLE_NAME
			);
		}
	}

	/**
	 * Clears a `_gscf7_old` table stranded by an earlier swap.
	 *
	 * Passes that ran before gscf7_release_foreign_keys() existed left the
	 * discarded copy in place holding the foreign key, which is what made it
	 * undroppable. Releasing the constraint frees the table and lets the
	 * restore below re-point it at the live one.
	 *
	 * @since 2.2.0
	 *
	 * @param string $table Full table name, already prefixed.
	 *
	 * @return void
	 */
	private function gscf7_cleanup_swap_leftovers($table)
	{
		global $wpdb;

		$stale = $table . '_gscf7_old';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking whether a custom plugin table exists.
		if (! $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', esc_sql($stale)))) {
			return;
		}

		$constraints = $this->gscf7_release_foreign_keys($stale);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared -- Table name escaped above.
		$wpdb->query('DROP TABLE IF EXISTS `' . esc_sql($stale) . '`');

		// Point whatever was hanging off the stale copy back at the live table.
		foreach ($constraints as $constraint) {
			if ($constraint->REFERENCED_TABLE_NAME === $stale) {
				$constraint->REFERENCED_TABLE_NAME = $table;
			}
		}

		$this->gscf7_restore_foreign_keys($constraints);

		// A leftover also means the swap's clean copy may still be around.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared -- Table name escaped above.
		$wpdb->query('DROP TABLE IF EXISTS `' . esc_sql($table . '_gscf7_clean') . '`');
	}

	/**
	 * Collapses feed rows that describe the same sheet and tab under one name.
	 *
	 * Rebuilt rather than cleaned with a self-joined DELETE, for the same reason
	 * gscf7_dedupe_table() is: an affected site can hold a very large number of
	 * duplicates, and building the clean copy then swapping it in with a single
	 * atomic RENAME is O(n) and leaves the original in place until the
	 * replacement is complete.
	 *
	 * @since 2.2.0
	 *
	 * @param string $feeds_table    Full table name, already prefixed.
	 * @param string $settings_table Full table name, already prefixed.
	 *
	 * @return void
	 */
	private function gscf7_dedupe_feeds_by_sheet($feeds_table, $settings_table)
	{
		global $wpdb;

		$feeds    = esc_sql($feeds_table);
		$settings = esc_sql($settings_table);

		if (! $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $feeds))) {
			return;
		}

		if (! $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $settings))) {
			return;
		}

		/*
		 * NULL and '' are the same absence of a sheet here, so COALESCE keeps
		 * rows that differ only in which of the two they stored from being
		 * treated as separate feeds. The GROUP BY expression is a fixed literal
		 * inlined into each query below (no interpolated variable).
		 */
		$total  = (int) $wpdb->get_var("SELECT COUNT(*) FROM `{$feeds}`");
		$unique = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM (
				SELECT 1 FROM `{$feeds}` f
				LEFT JOIN `{$settings}` s ON s.feed_id = f.id
				GROUP BY f.form_id, f.feed_name, COALESCE(s.sheet_id, ''), COALESCE(s.tab_id, '')
			) AS gscf7_groups"
		);

		if ($total <= $unique) {
			return;
		}

		// esc_sql() the whole derived name so the sanitisation is visible at
		// the point of use (the raw table name is a fixed, prefixed plugin
		// table -- never user input).
		$clean = esc_sql($feeds_table . '_gscf7_clean');
		$old   = esc_sql($feeds_table . '_gscf7_old');

		$wpdb->query("DROP TABLE IF EXISTS `{$clean}`");
		$wpdb->query("DROP TABLE IF EXISTS `{$old}`");

		if (false === $wpdb->query("CREATE TABLE `{$clean}` LIKE `{$feeds}`")) {
			return;
		}

		/*
		 * One surviving row per group: the HIGHEST id, which carries the current
		 * configuration. The extra GROUP BY around the keep ids is not
		 * redundant -- if a feed somehow still holds two settings rows it lands
		 * in two groups whose MAX is the same id, and inserting that id twice
		 * would fail against the primary key.
		 */
		$inserted = $wpdb->query(
			"INSERT INTO `{$clean}`
			SELECT f.* FROM `{$feeds}` f
			INNER JOIN (
				SELECT keep_id FROM (
					SELECT MAX(f2.id) AS keep_id
					FROM `{$feeds}` f2
					LEFT JOIN `{$settings}` s ON s.feed_id = f2.id
					GROUP BY f2.form_id, f2.feed_name, COALESCE(s.sheet_id, ''), COALESCE(s.tab_id, '')
				) AS gscf7_keep
				GROUP BY keep_id
			) k ON f.id = k.keep_id"
		);

		if (false === $inserted) {
			$wpdb->query("DROP TABLE IF EXISTS `{$clean}`");
			return;
		}

		// Constraints must let go of the table before it is renamed away, or
		// they follow it onto the discarded copy.
		$constraints = $this->gscf7_release_foreign_keys($feeds_table);

		// Atomic swap -- the live table is never absent.
		if (false === $wpdb->query("RENAME TABLE `{$feeds}` TO `{$old}`, `{$clean}` TO `{$feeds}`")) {
			$wpdb->query("DROP TABLE IF EXISTS `{$clean}`");
			$this->gscf7_restore_foreign_keys($constraints);
			return;
		}

		$wpdb->query("DROP TABLE IF EXISTS `{$old}`");

		$this->gscf7_restore_foreign_keys($constraints);
	}

	/**
	 * Renames feeds that still share a name within a form, oldest keeping it.
	 *
	 * After the collapse above, two rows sharing a name point at different
	 * sheets, so both are real configuration. Renaming the later ones " (n)"
	 * keeps them all -- and lets the unique index go on.
	 *
	 * @since 2.2.0
	 *
	 * @param string $feeds_table Full table name, already prefixed.
	 *
	 * @return void
	 */
	private function gscf7_suffix_duplicate_feed_names($feeds_table)
	{
		global $wpdb;

		$feeds = esc_sql($feeds_table);

		if (! $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $feeds))) {
			return;
		}

		$clashes = $wpdb->get_results(
			"SELECT form_id, feed_name
			FROM `{$feeds}`
			GROUP BY form_id, feed_name
			HAVING COUNT(*) > 1"
		);

		if (empty($clashes)) {
			return;
		}

		foreach ($clashes as $clash) {
			// Oldest keeps the original name; every later row is renamed.
			$ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT id FROM `{$feeds}` WHERE form_id = %d AND feed_name = %s ORDER BY id ASC",
					$clash->form_id,
					$clash->feed_name
				)
			);

			array_shift($ids);

			// Matches the import: stay inside the unique index's 150-char prefix.
			$base   = substr((string) $clash->feed_name, 0, 140);
			$suffix = 0;

			foreach ($ids as $id) {
				do {
					$suffix++;
					$candidate = $base . ' (' . $suffix . ')';

					$taken = (int) $wpdb->get_var(
						$wpdb->prepare(
							"SELECT id FROM `{$feeds}` WHERE form_id = %d AND feed_name = %s LIMIT 1",
							$clash->form_id,
							$candidate
						)
					);
				} while ($taken);

				$wpdb->update($feeds_table, array('feed_name' => $candidate), array('id' => (int) $id));
			}
		}
	}

	/**
	 * Removes duplicate rows from a plugin table, keeping the lowest id per group.
	 *
	 * The table is rebuilt rather than cleaned with a self-joined DELETE: an
	 * affected site can hold a huge number of duplicates, where a self-join
	 * would run long enough to time the request out and never finish. Building
	 * the clean copy and swapping it in with a single atomic RENAME is O(n) and
	 * leaves the original in place until the replacement is complete.
	 *
	 * @since 2.2.0
	 *
	 * @param string $table       Full table name, already prefixed.
	 * @param array  $key_columns Columns that together identify one logical row.
	 * @param string $survivor    'MIN' to keep the oldest row of each group,
	 *                            'MAX' to keep the newest. The newest holds the
	 *                            current configuration wherever the duplicates
	 *                            were produced by the repeating migration.
	 *
	 * @return void
	 */
	private function gscf7_dedupe_table($table, array $key_columns, $survivor = 'MIN')
	{
		global $wpdb;

		$table = esc_sql($table);
		// Resolves to the literal 'MAX' or 'MIN'; esc_sql() keeps the
		// sanitisation visible where the value is interpolated into SQL below.
		$survivor = esc_sql('MAX' === strtoupper((string) $survivor) ? 'MAX' : 'MIN');

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking whether a custom plugin table exists.
		if (! $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table))) {
			return;
		}

		$group_by = array();
		foreach ($key_columns as $column) {
			$group_by[] = '`' . esc_sql($column) . '`';
		}
		$group_by = implode(', ', $group_by);

		$total  = (int) $wpdb->get_var("SELECT COUNT(*) FROM `{$table}`");
		$unique = (int) $wpdb->get_var("SELECT COUNT(*) FROM ( SELECT 1 FROM `{$table}` GROUP BY {$group_by} ) AS gscf7_groups");

		if ($total <= $unique) {
			return;
		}

		// esc_sql() the whole derived name so the sanitisation is visible at
		// the point of use ($table is already esc_sql()'d above and holds a
		// fixed, prefixed plugin table name -- esc_sql() is idempotent for it).
		$clean = esc_sql($table . '_gscf7_clean');
		$old   = esc_sql($table . '_gscf7_old');

		$wpdb->query("DROP TABLE IF EXISTS `{$clean}`");
		$wpdb->query("DROP TABLE IF EXISTS `{$old}`");

		if (false === $wpdb->query("CREATE TABLE `{$clean}` LIKE `{$table}`")) {
			return;
		}

		// One surviving row per group.
		$inserted = $wpdb->query(
			"INSERT INTO `{$clean}`
			SELECT t.* FROM `{$table}` t
			INNER JOIN ( SELECT {$survivor}(id) AS keep_id FROM `{$table}` GROUP BY {$group_by} ) k
			ON t.id = k.keep_id"
		);

		if (false === $inserted) {
			$wpdb->query("DROP TABLE IF EXISTS `{$clean}`");
			return;
		}

		// Constraints must let go of the table before it is renamed away, or
		// they follow it onto the discarded copy -- see
		// gscf7_release_foreign_keys().
		$constraints = $this->gscf7_release_foreign_keys($table);

		// Atomic swap -- the live table is never absent.
		if (false === $wpdb->query("RENAME TABLE `{$table}` TO `{$old}`, `{$clean}` TO `{$table}`")) {
			$wpdb->query("DROP TABLE IF EXISTS `{$clean}`");
			$this->gscf7_restore_foreign_keys($constraints);
			return;
		}

		$wpdb->query("DROP TABLE IF EXISTS `{$old}`");

		$this->gscf7_restore_foreign_keys($constraints);
	}

	/**
	 * Adds a unique index to a table if an index of that name is not present.
	 *
	 * @since 2.2.0
	 *
	 * @param string $table      Full table name, already prefixed.
	 * @param string $index_name Index name.
	 * @param string $columns    Column list for the index definition.
	 *
	 * @return void
	 */
	private function gscf7_add_unique_index_if_not_exists($table, $index_name, $columns)
	{
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Schema lookup for a custom plugin table.
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(1)
				FROM INFORMATION_SCHEMA.STATISTICS
				WHERE table_schema = DATABASE()
				AND table_name = %s
				AND index_name = %s",
				$table,
				$index_name
			)
		);

		if ($exists) {
			return;
		}

		$t = esc_sql($table);
		$i = esc_sql($index_name);

		// $columns is a fixed spec from this class ("feed_id",
		// "form_id, feed_name(150)"). Rebuild it from a strict pattern so only
		// an identifier and an optional prefix length can survive: each name
		// through esc_sql(), each length cast to int.
		$column_sql = array();
		foreach (explode(',', (string) $columns) as $gscf7_col) {
			if (preg_match('/^\s*([A-Za-z0-9_]+)\s*(?:\(\s*(\d+)\s*\))?\s*$/', $gscf7_col, $gscf7_m)) {
				$column_sql[] = '`' . esc_sql($gscf7_m[1]) . '`'
					. (isset($gscf7_m[2]) && '' !== $gscf7_m[2] ? '(' . (int) $gscf7_m[2] . ')' : '');
			}
		}

		if (empty($column_sql)) {
			return;
		}

		$column_sql = implode(', ', $column_sql);

		$wpdb->query("ALTER TABLE `{$t}` ADD UNIQUE INDEX `{$i}` ({$column_sql})");
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
		$value = isset($_POST['gs_cf7db_setting']) ? intval($_POST['gs_cf7db_setting']) : 0;
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
		add_action('wp_enqueue_scripts', array($this, 'add_frontend_css_files'));
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

		/*
		* Privacy & GDPR tab renders a live Contact Form 7 shortcode as a preview.
		* CF7 only registers/enqueues its own stylesheet on the front-end
		* 'wp_enqueue_scripts' hook, which never fires in wp-admin, so without this
		* the preview form renders completely unstyled.
		*/
		if (
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only screen check, not a form submission.
			is_admin() && isset($_GET['page']) && 'wpcf7-google-sheet-config' === $_GET['page'] &&
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only screen check, not a form submission.
			isset($_GET['tab']) && 'gdpr-privacy-policy' === $_GET['tab'] &&
			function_exists('wpcf7_plugin_url')
		) {
			wp_enqueue_style(
				'contact-form-7',
				wpcf7_plugin_url('includes/css/styles.css'),
				array(),
				defined('WPCF7_VERSION') ? WPCF7_VERSION : GS_CONNECTOR_VERSION
			);

			wp_enqueue_style(
				'gs-connector-frontend-css',
				GS_CONNECTOR_URL . 'assets/css/gs-connector-frontend.css',
				array(),
				GS_CONNECTOR_VERSION
			);

			// Needed so the notice editors' "Insert Image" button can open
			// the WordPress media library.
			wp_enqueue_media();
		}
	}
	/**
	 * enqueue front-end CSS — only on pages that actually contain a CF7 form.
	 *
	 * @since 5.2.4
	 */
	public function add_frontend_css_files()
	{
		global $post;

		$has_form = is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'contact-form-7');

		if (! $has_form) {
			return;
		}

		wp_enqueue_style('gs-connector-frontend-css', GS_CONNECTOR_URL . 'assets/css/gs-connector-frontend.css', array(), GS_CONNECTOR_VERSION);
	}
	/**
	 * enqueue JS files
	 *
	 * @since 5.2.4
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
		* Analytics Charts (Dashboard tab + CF7 Database screen)
		*
		* Chart.js is only needed on:
		* - the Dashboard tab (tab=dashboard, or no tab param -- it's the
		*   default), which shows an aggregate overview across every form
		* - the CF7 Database screen (tab=cf7_db), both its "All Forms"
		*   default view and a specific form -- both render the chart, the
		*   Form/status filters, and the AJAX-driven entries table
		* so it's scoped to those screens instead of loading on every
		* plugin page.
		*/
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only screen check, not a form submission.
		$gscf7_tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'dashboard';

		$gscf7_is_dashboard_tab = ('dashboard' === $gscf7_tab);
		// The CF7 Database screen always shows the chart + AJAX-driven entries
		// table now (both the "All Forms" default and a specific formId), so
		// this no longer gates on formId being present.
		$gscf7_is_entries_tab   = ('cf7_db' === $gscf7_tab);

		if (
			preg_match('/page=wpcf7-google-sheet-config/', $request_uri) &&
			($gscf7_is_dashboard_tab || $gscf7_is_entries_tab)
		) {

			wp_enqueue_style(
				'gs-cf7db-analytics-css',
				GS_CONNECTOR_URL . 'assets/css/gs-cf7db-analytics.css',
				array(),
				GS_CONNECTOR_VERSION
			);

			wp_enqueue_script(
				'gs-chartjs',
				GS_CONNECTOR_URL . 'assets/js/chart.umd.min.js',
				array(),
				'4.5.1',
				true
			);

			wp_enqueue_script(
				'gs-cf7db-charts-js',
				GS_CONNECTOR_URL . 'assets/js/gs-cf7db-charts.js',
				array('gs-chartjs'),
				GS_CONNECTOR_VERSION,
				true
			);

			// Column labels for the "All Forms" generic entries table, which
			// (unlike the per-form table) has no server-rendered header for
			// gs-cf7db-charts.js to reuse -- it builds its own from these.
			wp_localize_script(
				'gs-cf7db-charts-js',
				'gscf7CF7DBi18n',
				array(
					'id'      => esc_html__('ID', 'cf7-google-sheets-connector'),
					'date'    => esc_html__('Date', 'cf7-google-sheets-connector'),
					'form'    => esc_html__('Form Name', 'cf7-google-sheets-connector'),
					'status'  => esc_html__('Status', 'cf7-google-sheets-connector'),
					'actions' => esc_html__('Actions', 'cf7-google-sheets-connector'),
					'loading'          => esc_html__('Loading…', 'cf7-google-sheets-connector'),
					'error'            => esc_html__('Unable to load entries.', 'cf7-google-sheets-connector'),
					'confirmDelete'     => esc_html__('Delete the selected entries? This cannot be undone.', 'cf7-google-sheets-connector'),
					'noEntriesSelected' => esc_html__('Select at least one entry first.', 'cf7-google-sheets-connector'),
					'selectAll'         => esc_html__('Select All', 'cf7-google-sheets-connector'),
				)
			);
		}
		/*
		

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

		/*
		 * Bring the custom tables up to the running version's schema.
		 *
		 * add_admin_database() is the only place `cf7gs_feeds` and
		 * `cf7gs_settings` are created and altered, and it used to be reachable
		 * from just two places: the activation hook, and
		 * cf7_maybe_migrate_old_postmeta(). Neither one runs on a real update --
		 * WordPress does not fire activation hooks when a plugin is updated in
		 * place (auto-update, "Update now" and WP-CLI all skip it), and the
		 * migration is skipped for good once `gscf7_options_migrated` is set.
		 *
		 * A site that migrated once and has updated in place ever since
		 * therefore kept whatever schema it had at migration time, and every
		 * column added later was simply missing. save_gs_settings()'s insert
		 * and update then failed silently, so the Google Sheet configuration
		 * entered in the form editor was never stored and came back empty.
		 *
		 * Running it here repairs those installs and removes the need for a
		 * reactivation after any future column addition. The marker is a plain
		 * option, not a site option: the tables are per-blog ($wpdb->prefix),
		 * so on multisite every blog has to get its own pass. dbDelta() is a
		 * no-op once the schema matches, and the option is autoload-off and
		 * read from cache, so the steady-state cost is one string comparison.
		 */
		if (GS_CONNECTOR_VERSION !== get_option('gscf7_db_schema_version')) {

			$this->add_admin_database();

			update_option('gscf7_db_schema_version', GS_CONNECTOR_VERSION, false);
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
	 * Keep the managed OAuth client credentials fresh independently of the
	 * version-string upgrade waterfall above.
	 *
	 * `register_activation_hook()` does not run on a real plugin update --
	 * auto-update, "Update now" and WP-CLI all skip it -- and the waterfall
	 * in run_on_upgrade() only ever matched exact previous version strings,
	 * so any version not explicitly listed silently skipped the one chance
	 * it had to refresh `cf7gsc_free_api_creds`. This hook instead re-checks
	 * on every admin page load (cheaply -- see save_api_credentials()'s
	 * lock/back-off/fingerprint-skip) until a fetch has actually succeeded
	 * for the running plugin version.
	 *
	 * @since 5.2.4
	 *
	 * @return void
	 */
	public function gscf7_maybe_bootstrap_credentials()
	{
		// Never let an AJAX request, cron run, or non-admin request reach a
		// relay call -- and a form submission (a non-GET admin request) must
		// not trigger one either.
		if (wp_doing_ajax() || (function_exists('wp_doing_cron') && wp_doing_cron()) || ! is_admin()) {
			return;
		}

		if (! isset($_SERVER['REQUEST_METHOD']) || 'GET' !== $_SERVER['REQUEST_METHOD']) {
			return;
		}

		if (! current_user_can('manage_options')) {
			return;
		}

		$this->gscf7_repair_invalid_token();

		if (get_option('gs_cf7_auth_method', 'cf7_existing') !== 'cf7_existing') {
			return;
		}

		$marker       = 'gscf7_creds_bootstrap_version';
		$done_version = is_multisite() ? get_site_option($marker) : get_option($marker);

		if (GS_CONNECTOR_VERSION === $done_version) {
			return;
		}

		$fetched = Gs_Connector_Free_Utility::instance()->save_api_credentials('bootstrap');

		if (! $fetched) {
			// Leave the marker unset so the next admin page load retries
			// instead of burning the one chance on a transient failure.
			return;
		}

		if (is_multisite()) {
			update_site_option($marker, GS_CONNECTOR_VERSION);
		} else {
			update_option($marker, GS_CONNECTOR_VERSION, false);
		}
	}

	/**
	 * One-time repair for a `gs_token` option left holding an OAuth error
	 * payload or a bare, unexchanged auth code from before the credential
	 * exchange handling was fixed (see updateToken() in lib/google-sheets.php).
	 *
	 * Only removes the option when it carries neither an access nor a
	 * refresh token -- a working token is never touched.
	 *
	 * @since 5.2.4
	 *
	 * @return void
	 */
	private function gscf7_repair_invalid_token()
	{
		if (get_option('gscf7_token_repaired')) {
			return;
		}

		$token_json = get_option('gs_token');

		if (! empty($token_json)) {
			$token     = json_decode($token_json, true);
			$has_token = is_array($token) && (! empty($token['access_token']) || ! empty($token['refresh_token']));

			if (! $has_token) {
				delete_option('gs_token');
			}
		}

		update_option('gscf7_token_repaired', 1, false);
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
		// cf7gsc_free_api_creds is a single network-wide option on multisite
		// (see Gs_Connector_Free_Utility::save_api_credentials()), so fetching
		// it once here -- instead of once per blog via upgrade_helper_41() --
		// avoids a needless N-request stampede against the relay on large
		// networks.
		if (function_exists('is_multisite') && is_multisite()) {
			Gs_Connector_Free_Utility::instance()->save_api_credentials('legacy_upgrade_41');
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
		// The GDPR notice needs no activation seed: until the admin saves the
		// GDPR settings once, the built-in default text is shown automatically
		// (see Gs_Connector_Service::gscf7_get_notice_settings()).
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

		/*
		 * A form may not hold two feeds of the same name -- create_new_feed_cf7gsc()
		 * refuses it, and the import suffixes " (n)" rather than repeat one -- so
		 * let the schema enforce what the code already promises. Every path that
		 * could produce a duplicate is then closed, not just the ones known today.
		 *
		 * Guarded on the data actually being unique: a site still carrying the
		 * duplicates left by the old migration would fail the ALTER, and the
		 * repair pass adds the index itself once it has cleaned them out.
		 */
		$this->gscf7_add_unique_index_if_data_allows($feeds_table, 'gscf7_feeds_unique', 'form_id, feed_name(150)', array('form_id', 'feed_name'));
		$this->gscf7_add_unique_index_if_data_allows($settings_table, 'gscf7_settings_unique', 'feed_id', array('feed_id'));

		// STEP 3: ADD FOREIGN KEYS
		$this->gscf7_add_fk_if_not_exists($settings_table, 'fk_settings_feed', 'feed_id', $feeds_table);
	}

	/**
	 * Adds a unique index, but only once the table's data satisfies it.
	 *
	 * Attempting the ALTER against duplicate rows just errors, and this runs on
	 * activation where that would surface as a broken install rather than the
	 * no-op it should be. Checking first keeps it safe to call unconditionally.
	 *
	 * @since 2.2.0
	 *
	 * @param string $table       Full table name, already prefixed.
	 * @param string $index_name  Index name.
	 * @param string $columns     Column list for the index definition.
	 * @param array  $key_columns The same columns, for the uniqueness pre-check.
	 *
	 * @return void
	 */
	private function gscf7_add_unique_index_if_data_allows($table, $index_name, $columns, array $key_columns)
	{
		global $wpdb;

		$table_sql = esc_sql($table);

		if (! $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_sql))) {
			return;
		}

		$group_by = array();
		foreach ($key_columns as $column) {
			$group_by[] = '`' . esc_sql($column) . '`';
		}
		$group_by = implode(', ', $group_by);

		$total  = (int) $wpdb->get_var("SELECT COUNT(*) FROM `{$table_sql}`");
		$unique = (int) $wpdb->get_var("SELECT COUNT(*) FROM ( SELECT 1 FROM `{$table_sql}` GROUP BY {$group_by} ) AS gscf7_groups");

		if ($total > $unique) {
			return;
		}

		$this->gscf7_add_unique_index_if_not_exists($table, $index_name, $columns);
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

		if (! $index_exists) {
			$wpdb->query('ALTER TABLE `' . esc_sql($table) . '` ADD INDEX `' . esc_sql($column) . '` (`' . esc_sql($column) . '`)');
		}

		$wpdb->query('ALTER TABLE `' . esc_sql($table) . '` ENGINE=InnoDB');
		$wpdb->query('ALTER TABLE `' . esc_sql($ref_table) . '` ENGINE=InnoDB');

		$wpdb->query(
			'ALTER TABLE `' . esc_sql($table) . '`
            ADD CONSTRAINT `' . esc_sql($constraint) . '`
            FOREIGN KEY (`' . esc_sql($column) . '`)
            REFERENCES `' . esc_sql($ref_table) . '`(id)
            ON DELETE CASCADE
            ON UPDATE CASCADE'
		);
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

		// Managed credential bookkeeping is network-wide on multisite (see
		// Gs_Connector_Free_Utility::save_api_credentials()); delete_site_option()
		// falls back to a regular option on a non-multisite install, so this
		// is safe to call unconditionally.
		delete_site_option('cf7gsc_free_api_creds');
		delete_site_option('cf7gsc_free_api_creds_pending');
		delete_site_option('cf7gsc_free_api_creds_meta');
		delete_site_option('gscf7_creds_bootstrap_version');
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
			delete_option('cf7gsc_free_api_creds_pending');
			delete_option('cf7gsc_free_api_creds_meta');
			delete_option('gscf7_creds_bootstrap_version');
			delete_option('gscf7_token_client_fp');
			delete_option('gscf7_token_repaired');
			delete_transient('cf7gsc_free_creds_lock');
			delete_transient('cf7gsc_free_creds_backoff');
			delete_option('gs_debug_log_file');
			delete_option('gscf7_autoload_migrated');
			delete_option('gscf7_entry_indexes_added');
			delete_option('gscf7_error_log_utc_migrated');
			delete_option('gscf7_db_schema_version');

			// Delete post meta
			delete_post_meta_by_key('gs_settings');

			$wpdb->query(
				'DROP TABLE IF EXISTS `' . esc_sql($wpdb->prefix . 'gscf7_error_logs') . '`'
			);

			$wpdb->query(
				'DROP TABLE IF EXISTS `' . esc_sql($wpdb->prefix . 'cf7gs_settings') . '`'
			);

			$wpdb->query(
				'DROP TABLE IF EXISTS `' . esc_sql($wpdb->prefix . 'cf7gs_feeds') . '`'
			);
		}
	}
}

// Initialize the google sheet connector class
$gscf7_init = new Gs_Connector_Free_Init();
