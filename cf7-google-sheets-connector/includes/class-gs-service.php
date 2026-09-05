<?php

/**
 * Service class for Google Sheet Connector
 *
 * @since 1.0
 */
if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly
}
/**
 * Gs_Connector_Service Class
 *
 * @since 1.0
 */

/**
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
 */
class Gs_Connector_Service
{


	/**
	 * Shared instance.
	 *
	 * The constructor registers 13 hooks. Because add_action() derives its
	 * callback ID from the object hash, every `new Gs_Connector_Service()`
	 * registered a fresh duplicate set rather than being deduplicated.
	 *
	 * @var Gs_Connector_Service|null
	 */
	private static $instance = null;

	private static $gscf7_rendered_notices = array();

	/**
	 * Get the shared instance, creating it on first use.
	 *
	 * Use this instead of `new Gs_Connector_Service()` when the service is only
	 * needed for its helper methods.
	 *
	 * @since 5.2.1
	 *
	 * @return Gs_Connector_Service
	 */
	public static function instance()
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private $allowed_tags      = array('text', 'email', 'url', 'tel', 'number', 'range', 'date', 'textarea', 'select', 'checkbox', 'radio', 'acceptance', 'quiz', 'file', 'hidden');
	private $special_mail_tags = array('date', 'time', 'serial_number', 'remote_ip', 'user_agent', 'url', 'post_id', 'post_name', 'post_title', 'post_url', 'post_author', 'post_author_email', 'site_title', 'site_description', 'site_url', 'site_admin_email', 'user_login', 'user_email', 'user_display_name');
	protected $gs_uploads      = array();

	/**
	 *  Set things up.
	 *
	 *  @since 1.0
	 */
	public function __construct()
	{

		// AJAX action to verify Google Sheets integration status.
		add_action(
			'wp_ajax_verify_gs_integation',
			array($this, 'verify_gs_integation')
		);

		// AJAX action to deactivate Google Sheets integration.
		add_action(
			'wp_ajax_deactivate_gs_integation',
			array($this, 'deactivate_gs_integation')
		);

		// clear debug logs method using ajax for system status tab
		add_action('wp_ajax_cf7_clear_debug_log', array($this, 'cf7_clear_debug_logs'));

		// Add new tab to contact form 7 editors panel
		add_filter('wpcf7_editor_panels', array($this, 'cf7_gs_editor_panels'));

		// Save Google Sheets settings when a Contact Form 7 form is updated.
		add_action('wpcf7_after_save', array($this, 'save_gs_settings'));

		// Store uploaded files locally before form submission is processed.
		add_action('wpcf7_before_send_mail', array($this, 'save_uploaded_files_local'));

		// Send Contact Form 7 submission data to Google Sheets after successful submission.
		add_action('wpcf7_mail_sent', array($this, 'cf7_save_to_google_sheets'));

		// AJAX action to save plugin uninstall settings.
		add_action('wp_ajax_gscf7_save_uninstall_settings', array($this, 'gscf7_save_uninstall_settings'));

		// AJAX action to save the Privacy Policy and GDPR notice settings.
		add_action('wp_ajax_gscf7_save_privacy_setting', array($this, 'gscf7_save_privacy_setting_callback'));

		// AJAX actions for admin notice management.
		add_action('wp_ajax_gscf7_dismiss_notice', array($this, 'gscf7_dismiss_notice_callback'));
		add_action('wp_ajax_gscf7_snooze_notice', array($this, 'gscf7_snooze_notice_callback'));

		// AJAX action to save the selected Google authentication method.
		add_action('wp_ajax_save_method_api_cf7', array($this, 'save_method_api_cf7'));

		// AJAX actions for Service Account authentication management.
		add_action('wp_ajax_save_service_account_json_cf7', array($this, 'save_service_account_json_cf7'));
		add_action('wp_ajax_deactivate_service_account_cf7', array($this, 'deactivate_service_account_cf7'));

		// Clear the plugin's stored logs via AJAX.
		add_action('wp_ajax_gscf7_clear_logs', array($this, 'ajax_clear_logs'));

		// Log the user's privacy consent just before CF7 sends the mail.
		add_action('wpcf7_before_send_mail', array($this, 'gscf7_log_privacy_consent'));

		// Output the GDPR notice below the form on the front end.
		add_filter('wpcf7_form_elements', array($this, 'gscf7_append_frontend_privacy_notice'));

		// AJAX handler for rendering a live preview of a CF7 form.
		add_action('wp_ajax_gscf7_preview_form', array($this, 'gscf7_preview_form_callback'));

		// Print the JS map of export links used in the admin footer.
		add_action('admin_footer', array($this, 'gscf7_output_export_links_map'));

		// Handle the "Export form" admin-post request as JSON.
		add_action('admin_post_gscf7_export_form', array($this, 'gscf7_export_form_json'));

		// AJAX handler for importing a form from JSON.
		add_action('wp_ajax_gscf7_import_form', array($this, 'gscf7_import_form_callback'));

		// AJAX action for the Entries Dashboard's filtered, paginated, sortable table.
		add_action('wp_ajax_gscf7_dashboard_entries_query', array($this, 'gscf7_dashboard_entries_query'));

		// AJAX action for the Entries Dashboard's "Form" filter -- refreshes the
		add_action('wp_ajax_gscf7_dashboard_stats_query', array($this, 'gscf7_dashboard_stats_query'));

		// AJAX action for the CF7 Database screen's per-form entries table
		add_action('wp_ajax_gscf7_cf7db_table_query', array($this, 'gscf7_cf7db_entries_table_query'));

		// AJAX action for Delete/Read/Unread bulk actions on the CF7 Database
		add_action('wp_ajax_gscf7_cf7db_bulk_action', array($this, 'gscf7_cf7db_bulk_action_query'));
	}

	/**
	 * AJAX callback: render a live preview of a Contact Form 7 form.
	 *
	 * Verifies the request nonce and current user's capability, loads the
	 * requested CF7 form by ID, strips out any "Basic Fields" heading from
	 * its rendered output, and returns the resulting HTML for preview
	 * (e.g. inside the plugin's admin settings screen).
	 *
	 * Expects $_POST['form_id'] and the 'gscf7-privacy-setting-ajax-nonce' nonce.
	 *
	 * @return void Sends a JSON response via wp_send_json_success()/wp_send_json_error() and exits.
	 */

	public function gscf7_preview_form_callback()
	{

		check_ajax_referer(
			'gscf7-privacy-setting-ajax-nonce',
			'nonce'
		);


		if (! current_user_can('manage_options')) {
			wp_send_json_error(
				array(
					'message' => __('Permission denied.', 'cf7-google-sheets-connector'),
				)
			);
		}

		$form_id = isset($_POST['form_id'])
			? absint($_POST['form_id'])
			: 0;

		if (! $form_id) {
			wp_send_json_error(
				array(
					'message' => __('Invalid form ID.', 'cf7-google-sheets-connector'),
				)
			);
		}

		$contact_form = wpcf7_contact_form($form_id);

		if (! $contact_form) {
			wp_send_json_error(
				array(
					'message' => __('Contact Form 7 form not found.', 'cf7-google-sheets-connector'),
				)
			);
		}

		$html = preg_replace(
			'/<h[1-6][^>]*>\s*Basic Fields\s*<\/h[1-6]>/i',
			'',
			do_shortcode(
				'[contact-form-7 id="' . $form_id . '"]'
			)
		);

		// This is a preview only -- disable the submit button so it can't
		// actually be triggered (submitting here isn't a real form request).
		$html = preg_replace(
			'/(<(?:input|button)\b[^>]*type=["\']submit["\'][^>]*)(\/?>)/i',
			'$1 disabled="disabled"$2',
			$html
		);

		wp_send_json_success(
			array(
				'html' => $html,
			)
		);
	}

	/**
	 * Output a JS map of form_id => connected Google Sheet URL, used by the
	 * "Export" row action injected into the CF7 forms list table (Edit | Duplicate | Export).
	 *
	 * @since 1.0
	 */
	public function gscf7_output_export_links_map()
	{
		$screen = function_exists('get_current_screen') ? get_current_screen() : null;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only screen check, not a form submission.
		if (! $screen || (isset($_GET['page']) && 'wpcf7' !== $_GET['page'])) {
			return;
		}

		printf(
			'<script>window.gscf7ExportNonce = %s; window.gscf7ImportNonce = %s;</script>',
			wp_json_encode(wp_create_nonce('gscf7_export_form')),
			wp_json_encode(wp_create_nonce('gscf7_import_form'))
		);
	}

	/**
	 * Output a JS map of form_id => connected Google Sheet URL, used by the
	 * "Import" row action injected into the CF7 forms list table.
	 *
	 * @since 1.0
	 */

	public function gscf7_export_form_json()
	{
		if (! current_user_can('wpcf7_edit_contact_forms')) {
			wp_die(esc_html__('Permission denied.', 'cf7-google-sheets-connector'));
		}

		check_admin_referer('gscf7_export_form');

		$form_id = isset($_GET['form_id']) ? absint($_GET['form_id']) : 0;

		if (! $form_id || ! class_exists('WPCF7_ContactForm')) {
			wp_die(esc_html__('Invalid form.', 'cf7-google-sheets-connector'));
		}

		$contact_form = WPCF7_ContactForm::get_instance($form_id);

		if (! $contact_form) {
			wp_die(esc_html__('Form not found.', 'cf7-google-sheets-connector'));
		}

		$export = array(
			'title'               => $contact_form->title(),
			'locale'              => $contact_form->locale(),
			'form'                => $contact_form->prop('form'),
			'mail'                => $contact_form->prop('mail'),
			'mail_2'              => $contact_form->prop('mail_2'),
			'messages'            => $contact_form->prop('messages'),
			'additional_settings' => $contact_form->prop('additional_settings'),
			'gsheet_connector'    => $this->gscf7_get_gsheet_export_data($form_id),
		);

		$filename = sanitize_title($contact_form->title()) . '-export.json';

		nocache_headers();
		header('Content-Type: application/json; charset=utf-8');
		header('Content-Disposition: attachment; filename="' . $filename . '"');

		echo wp_json_encode($export, JSON_PRETTY_PRINT);
		exit;
	}

	/**
	 * The cf7gs_settings columns (besides id/form_id/feed_id/created_at/updated_at)
	 * that are copied verbatim on export/import.
	 *
	 * @since 1.6.0
	 * @return array
	 */
	private function gscf7_settings_export_columns()
	{
		return array(
			'sheet_name',
			'tab_name',
			'sheet_id',
			'tab_id',
			'is_manual',
			'google_drive_link',
			'freeze_header',
			'enable_colors',
			'header_color',
			'odd_color',
			'even_color',
			'enable_sorting',
			'sort_column',
			'sort_order',
			'header_enable',
			'font_styles',
			'font_size',
			'font_color',
			'row_enable',
			'row_styles',
			'row_font_size',
			'row_font_color',
		);
	}

	/**
	 * Prepares an export file's feed list for insertion.
	 *
	 * Collapses entries that describe the same feed, then gives each survivor a
	 * name that is unique within the form. Entries are the same feed when they
	 * share a name AND a sheet AND a tab; the name alone is not enough, because
	 * a feed that was re-pointed at another tab leaves a second entry under the
	 * same name that is real configuration, not a duplicate.
	 *
	 * tab_id is a Google Sheets gid and gid 0 is the first tab of any
	 * spreadsheet -- an ordinary value, not "no tab chosen". Only '' and NULL
	 * mean that, so the key is built with isset() and a cast; empty() would
	 * treat '0' as absent and merge a real feed away.
	 *
	 * The import always creates a new form, so no feed can already exist for it
	 * and only names claimed within this run need checking.
	 *
	 * @since 1.6.0
	 * @param array $feeds The decoded 'gsheet_connector' array.
	 * @return array
	 */
	private function gscf7_prepare_import_feeds($feeds)
	{
		$collapsed = array();

		foreach ((array) $feeds as $feed) {
			if (! is_array($feed)) {
				continue;
			}

			$settings = (isset($feed['settings']) && is_array($feed['settings'])) ? $feed['settings'] : array();

			$key = (isset($feed['feed_name']) ? (string) $feed['feed_name'] : '')
				. '|' . (isset($settings['sheet_id']) ? (string) $settings['sheet_id'] : '')
				. '|' . (isset($settings['tab_id']) ? (string) $settings['tab_id'] : '');

			// Re-assigning an existing key keeps its original position, so the
			// newest copy wins without reordering the list.
			$collapsed[$key] = $feed;
		}

		$prepared = array();
		$taken    = array();

		foreach ($collapsed as $feed) {
			$name = isset($feed['feed_name']) ? sanitize_text_field($feed['feed_name']) : '';

			if ('' === trim($name)) {
				$name = __('Imported Feed', 'cf7-google-sheets-connector');
			}

			// The unique index covers feed_name(150); stay inside that prefix so
			// the " (n)" suffix is part of what gets compared.
			$name      = substr($name, 0, 140);
			$candidate = $name;
			$suffix    = 0;

			while (isset($taken[strtolower($candidate)])) {
				$suffix++;
				$candidate = $name . ' (' . $suffix . ')';
			}

			$taken[strtolower($candidate)] = true;

			$feed['feed_name'] = $candidate;
			$prepared[]        = $feed;
		}

		return $prepared;
	}

	/**
	 * Collects this form's Google Sheets Connector feeds + settings so they can
	 * travel inside the form's "Export" JSON alongside the CF7 form/mail/messages.
	 *
	 * @since 1.6.0
	 * @param int $form_id
	 * @return array List of feeds, each carrying its settings sub-array.
	 */
	private function gscf7_get_gsheet_export_data($form_id)
	{
		global $wpdb;

		$feeds_table    = $wpdb->prefix . 'cf7gs_feeds';
		$settings_table = $wpdb->prefix . 'cf7gs_settings';

		/*
		 * One entry per feed, keyed on name + sheet + tab so a form left holding
		 * duplicate rows by the old migration does not bake them into the file.
		 * The highest id of each group wins -- it holds the current settings.
		 */
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table, read at export time only.
		$feeds = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT f.id, f.feed_name, f.status, f.is_default
				FROM `' . esc_sql($feeds_table) . '` f
				INNER JOIN (
					SELECT MAX(f2.id) AS id
					FROM `' . esc_sql($feeds_table) . '` f2
					LEFT JOIN `' . esc_sql($settings_table) . '` s2 ON s2.feed_id = f2.id
					WHERE f2.form_id = %d
					GROUP BY f2.feed_name, COALESCE(s2.sheet_id, \'\'), COALESCE(s2.tab_id, \'\')
				) k ON k.id = f.id
				ORDER BY f.id ASC',
				$form_id
			),
			ARRAY_A
		);

		if (empty($feeds)) {
			return array();
		}

		$settings_columns = $this->gscf7_settings_export_columns();
		$export           = array();

		foreach ($feeds as $feed) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table, read at export time only.
			$settings = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT * FROM `' . esc_sql($settings_table) . '` WHERE feed_id = %d LIMIT 1',
					$feed['id']
				),
				ARRAY_A
			);

			$settings_export = array();

			foreach ($settings_columns as $column) {
				$settings_export[$column] = isset($settings[$column]) ? $settings[$column] : '';
			}

			$export[] = array(
				'feed_name'  => $feed['feed_name'],
				'status'     => $feed['status'],
				'is_default' => $feed['is_default'],
				'settings'   => $settings_export,
			);
		}

		return $export;
	}

	/**
	 * Re-creates cf7gs_feeds / cf7gs_settings rows for a freshly imported form from
	 * the 'gsheet_connector' block of an export JSON produced by
	 * gscf7_get_gsheet_export_data().
	 *
	 * Each feed is written under a freshly allocated id with its settings row
	 * attached to it, so ids in the file -- which mean nothing here -- never
	 * collide with ids already in this database.
	 *
	 * @since 1.6.0
	 * @param int   $form_id The newly imported form's post ID.
	 * @param array $feeds   The decoded 'gsheet_connector' array from the export file.
	 * @return void
	 */
	private function gscf7_import_gsheet_data($form_id, $feeds)
	{
		if (empty($feeds) || ! is_array($feeds)) {
			return;
		}

		global $wpdb;

		$feeds_table    = $wpdb->prefix . 'cf7gs_feeds';
		$settings_table = $wpdb->prefix . 'cf7gs_settings';

		$settings_columns = $this->gscf7_settings_export_columns();

		$now = current_time('mysql');

		foreach ($this->gscf7_prepare_import_feeds($feeds) as $feed) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table, written once per imported feed.
			$wpdb->insert(
				$feeds_table,
				array(
					'form_id'    => $form_id,
					'feed_name'  => $feed['feed_name'],
					'status'     => isset($feed['status']) ? absint($feed['status']) : 1,
					'is_default' => isset($feed['is_default']) ? absint($feed['is_default']) : 0,
				)
			);

			$feed_id = $wpdb->insert_id;

			if (! $feed_id) {
				continue;
			}

			$settings_in = (isset($feed['settings']) && is_array($feed['settings'])) ? $feed['settings'] : array();

			$row = array(
				'form_id'    => $form_id,
				'feed_id'    => $feed_id,
				// The column defaults to NULL, so an imported settings row used
				// to carry no timestamps at all.
				'created_at' => $now,
				'updated_at' => $now,
			);

			foreach ($settings_columns as $column) {
				$value        = isset($settings_in[$column]) ? $settings_in[$column] : '';
				$row[$column] = is_string($value) ? sanitize_text_field($value) : $value;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table, written once per imported feed.
			$wpdb->insert($settings_table, $row);
			if (! empty($row['sheet_id']) && class_exists('CF7GSC_googlesheet')) {
				CF7GSC_googlesheet::gsc_flush_meta_cache($row['sheet_id']);
			}
		}
	}


	/**
	 * gscf7_import_form_callback().
	 *
	 * @since 1.6.0
	 * @param int   $form_id The newly imported form's post ID.
	 * @param array $feeds   The decoded 'gsheet_connector' array from the export file.
	 * @return void
	 */
	public function gscf7_import_form_callback()
	{
		check_ajax_referer('gscf7_import_form', 'nonce');

		if (! current_user_can('wpcf7_edit_contact_forms')) {
			wp_send_json_error(array('message' => __('Permission denied.', 'cf7-google-sheets-connector')));
		}

		if (! class_exists('WPCF7_ContactForm')) {
			wp_send_json_error(array('message' => __('Contact Form 7 is not active.', 'cf7-google-sheets-connector')));
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Decoded as JSON below; every field is individually sanitized before use.
		$raw = isset($_POST['json_data']) ? wp_unslash($_POST['json_data']) : '';
		$data = json_decode($raw, true);

		if (JSON_ERROR_NONE !== json_last_error() || empty($data['form'])) {
			wp_send_json_error(array('message' => __('That file is not a valid form export.', 'cf7-google-sheets-connector')));
		}

		$contact_form = WPCF7_ContactForm::get_template(array(
			'locale' => ! empty($data['locale']) ? sanitize_text_field($data['locale']) : null,
		));

		$contact_form->set_title(
			! empty($data['title']) ? sanitize_text_field($data['title']) . ' (Imported)' : __('Imported Form', 'cf7-google-sheets-connector')
		);

		$contact_form->set_properties(array(
			'form'                => isset($data['form']) ? wp_kses_post($data['form']) : '',
			'mail'                => isset($data['mail']) ? (array) $data['mail'] : array(),
			'mail_2'              => isset($data['mail_2']) ? (array) $data['mail_2'] : array(),
			'messages'            => isset($data['messages']) ? (array) $data['messages'] : array(),
			'additional_settings' => isset($data['additional_settings']) ? (string) $data['additional_settings'] : '',
		));

		$id = $contact_form->save();

		if (! $id) {
			wp_send_json_error(array('message' => __('Could not save the imported form.', 'cf7-google-sheets-connector')));
		}

		$gsheet_imported = false;

		if (! empty($data['gsheet_connector'])) {
			$this->gscf7_import_gsheet_data($id, $data['gsheet_connector']);
			$gsheet_imported = true;
		}

		$message = $gsheet_imported
			? __('Form and its Google Sheets Connector settings were imported successfully.', 'cf7-google-sheets-connector')
			: __('Form imported successfully.', 'cf7-google-sheets-connector');

		/*
		 * A file exported from the PRO plugin carries the form's stored entries
		 * as well. This edition does not restore them, and dropping them without
		 * a word left no way to tell that part of the file had been ignored.
		 */
		$skipped_entries = (isset($data['entries']) && is_array($data['entries'])) ? count($data['entries']) : 0;

		if ($skipped_entries) {
			$message .= ' ' . sprintf(
				/* translators: %d: number of form entries present in the export file. */
				_n(
					'%d saved entry in the file was not imported.',
					'%d saved entries in the file were not imported.',
					$skipped_entries,
					'cf7-google-sheets-connector'
				),
				$skipped_entries
			);
		}

		wp_send_json_success(array(
			'message'         => $message,
			'skipped_entries' => $skipped_entries,
			'edit_url'        => admin_url('admin.php?page=wpcf7&post=' . absint($id) . '&action=edit'),
		));
	}

	/**
	 * Return the default GDPR notice text.
	 *
	 * @since 5.2.4
	 * @return string
	 */
	public function gscf7_default_gdpr_text()
	{
		return /* translators: default GDPR notice */
			'<span>Your information will be securely sent to and stored in Google Sheets for the purpose of processing your form submission.</span>';
	}

	/**
	 * Build the markup for the GDPR notice block.
	 *
	 * @since 5.2.4
	 * @param array  $settings     The saved settings array ('enabled','text','page_id').
	 * @param string $default_text Unused. Kept for signature compatibility; an empty notice now renders nothing.
	 * @param string $css_class    CSS class used for this notice on the front end.
	 * @param string $type       Notice type key ('gdpr').
	 * @return string Empty string if the notice is disabled or has been saved with an empty editor.
	 */
	private function gscf7_build_notice_markup($settings, $default_text, $css_class, $type)
	{
		if (empty($settings['enabled']) || (int) $settings['enabled'] !== 1) {
			return '';
		}

		// No fallback to the default text: once the admin clears the editor and
		// saves, the notice is stored empty and nothing is rendered.
		$text = (string) ($settings['text'] ?? '');
		if (trim(wp_strip_all_tags($text)) === '') {
			return '';
		}

		self::$gscf7_rendered_notices[$type] = true;

		return sprintf(
			'<div class="%s">%s</div>',
			esc_attr($css_class),
			wp_kses_post($text)
		);
	}

	/**
	 * Read the gdpr settings out of the gscf7_privacy_settings option.
	 *
	 * @since 5.2.4
	 * @return array array('gdpr' => array(...))
	 */
	private function gscf7_get_notice_settings()
	{
		$settings = get_option('gscf7_privacy_settings', array());
		$stored   = (is_array($settings) && isset($settings['gdpr']) && is_array($settings['gdpr']))
			? $settings['gdpr']
			: array();

		$defaults = array(
			'enabled'    => 1,
			'text'       => '',
			'page_id'    => 0,
		);

		$gdpr = wp_parse_args($stored, $defaults);

		// The 'configured' flag is written the first time the admin saves the
		// GDPR settings. Until then the built-in default text is shown. Once
		// configured, the stored text is used verbatim -- so an editor the
		// admin cleared and saved stays empty and the notice is hidden.
		if (empty($stored['configured'])) {
			$gdpr['text'] = $this->gscf7_default_gdpr_text();
		}

		// The GDPR notice is always "enabled"; it is hidden only by saving an
		// empty editor. Ignore any stale enabled=0 from older data.
		$gdpr['enabled'] = 1;
		$gdpr['page_id'] = 0;

		return array(
			'gdpr' => $gdpr,
		);
	}

	/**
	 * Whether a Contact Form 7 form is connected to a Google Sheet.
	 *
	 * Mirrors the check cf7_save_to_google_sheets() makes before pushing a
	 * submission: a row in {prefix}cf7gs_settings for this form with both a
	 * sheet name and a tab name filled in.
	 *
	 * @since 5.2.4
	 * @param int $form_id
	 * @return bool
	 */
	private function gscf7_form_is_connected($form_id)
	{
		$form_id = absint($form_id);

		if (! $form_id) {
			return false;
		}

		global $wpdb;

		$table_name = $wpdb->prefix . 'cf7gs_settings';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom plugin table; name sanitized with esc_sql() and backticks.
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT sheet_name, tab_name FROM `' . esc_sql($table_name) . '` WHERE form_id = %d',
				$form_id
			),
			ARRAY_A
		);

		return ! empty($row['sheet_name']) && ! empty($row['tab_name']);
	}

	/**
	 * Append the saved GDPR notice below the Contact Form 7 form.
	 *
	 * The GDPR notice is only appended when the form being rendered is
	 * connected to a Google Sheet -- forms with no GSheetConnector mapping
	 * never send data to Google, so the notice would not apply to them.
	 *
	 * @since 5.2.4
	 * @param string $form_html
	 * @return string
	 */
	public function gscf7_append_frontend_privacy_notice($form_html)
	{

		$notice_settings = $this->gscf7_get_notice_settings();

		$gdpr_notice = '';

		$current_form = function_exists('wpcf7_get_current_contact_form') ? wpcf7_get_current_contact_form() : null;

		// In wp-admin the only place a CF7 form is rendered is this plugin's
		// "Form Preview" on the GDPR tab, where the notice is always shown so
		// its text can be previewed and edited regardless of which form is
		// selected. On the front end it is gated to forms that are actually
		// connected to a Google Sheet.
		$show_gdpr = is_admin()
			|| ($current_form && $this->gscf7_form_is_connected($current_form->id()));

		if ($show_gdpr) {
			$gdpr_notice = $this->gscf7_build_notice_markup(
				$notice_settings['gdpr'],
				$this->gscf7_default_gdpr_text(),
				'gscf7-frontend-gdpr-notice',
				'gdpr'
			);
		}

		return $form_html . $gdpr_notice;
	}

	/**
	 * Log consent evidence for the GDPR notice shown to the visitor at the
	 * time of submission.
	 *
	 * @since 5.2.4
	 * @param WPCF7_ContactForm $contact_form
	 * @return void
	 */
	public function gscf7_log_privacy_consent($contact_form)
	{
		$notice_settings = $this->gscf7_get_notice_settings();
		$gdpr            = $notice_settings['gdpr'];

		// The GDPR notice counts as shown only when it is enabled and has
		// non-empty text (an empty editor means the admin hid the notice).
		$gdpr_enabled = ! empty($gdpr['enabled'])
			&& (int) $gdpr['enabled'] === 1
			&& trim(wp_strip_all_tags($gdpr['text'] ?? '')) !== '';

		if (! $gdpr_enabled) {
			return;
		}
		$submission = WPCF7_Submission::get_instance();
		if (! $submission) {
			return;
		}
		$consent_log = get_option('gscf7_privacy_consent_log', array());
		$entry = array(
			'form_id'    => $contact_form->id(),
			'timestamp'  => current_time('mysql'),
			'ip'         => isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '',
			'gdpr_shown' => 1,
		);

		$entry['gdpr_notice'] = wp_strip_all_tags($gdpr['text']);

		$consent_log[] = $entry;
		if (count($consent_log) > 500) {
			$consent_log = array_slice($consent_log, -500);
		}

		update_option('gscf7_privacy_consent_log', $consent_log, false);
	}

	/**
	 * AJAX handler: save the GDPR notice settings from the admin screen.
	 *
	 * An empty editor is stored as an empty string (not replaced with the
	 * default text), which hides the notice on the front end.
	 *
	 * @since 5.2.4
	 * @return void
	 */
	public function gscf7_save_privacy_setting_callback()
	{
		check_ajax_referer('gscf7-privacy-setting-ajax-nonce', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('Permission denied.', 'cf7-google-sheets-connector')));
		}

		$gscf7_normalize_notice_text = function ($html) {
			// Treat whitespace/empty-tag-only content (e.g. after the user
			// deletes all visible text but stray markup remains) as blank.
			return trim(wp_strip_all_tags($html)) === '' ? '' : $html;
		};

		// Preserve any legacy 'privacy' block already stored so a downgrade
		// doesn't lose it; only the 'gdpr' block is editable now.
		$existing = get_option('gscf7_privacy_settings', array());
		$settings = is_array($existing) ? $existing : array();

		$settings['gdpr'] = array(
			'enabled'    => 1,
			// Marks that the admin has saved at least once: from now on the
			// stored text is used as-is (an empty editor hides the notice
			// instead of falling back to the default).
			'configured' => 1,
			'text'       => isset($_POST['gscf7_gdpr_text']) ? $gscf7_normalize_notice_text(wp_kses_post(wp_unslash($_POST['gscf7_gdpr_text']))) : '',
			'page_id'    => 0,
		);

		update_option('gscf7_privacy_settings', $settings);
		wp_send_json_success(array('message' => __('GDPR notice settings saved successfully.', 'cf7-google-sheets-connector')));
	}

	/**
	 * Remove error log
	 *
	 * @since 1.0
	 * @return void
	 */
	public function ajax_clear_logs()
	{
		check_ajax_referer('gs-ajax-nonce', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(
				array(
					'message' => __('Permission denied.', 'cf7-google-sheets-connector'),
				),
				403
			);
		}

		global $wpdb;

		$table = $wpdb->prefix . 'gscf7_error_logs';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name only (from $wpdb->prefix), not user input; cannot use $wpdb->prepare() placeholders for identifiers.
		$result = $wpdb->query('TRUNCATE TABLE `' . esc_sql($table) . '`');

		if (false === $result) {
			wp_send_json_error(
				array(
					'message' => __('Failed to clear logs.', 'cf7-google-sheets-connector'),
				)
			);
		}

		wp_send_json_success(
			array(
				'message' => __('Logs cleared successfully.', 'cf7-google-sheets-connector'),
			)
		);
	}

	/**
	 * Validate a decoded Google service account key.
	 *
	 * Mirrors the client-side check so the same rules apply whether the request
	 * comes from the plugin's own screen or is posted directly.
	 *
	 * @since 5.2.1
	 *
	 * @param array $json Decoded service account JSON.
	 * @return string Empty string when valid, otherwise a translated error message.
	 */
	public static function gscf7_validate_service_account_json($json)
	{
		$required = array(
			'client_email',
			'private_key',
			'type',
			'project_id',
			'token_uri',
		);

		foreach ($required as $key) {
			if (! isset($json[$key]) || ! is_scalar($json[$key]) || '' === trim((string) $json[$key])) {
				return __('Your uploaded JSON key is invalid.', 'cf7-google-sheets-connector');
			}
		}

		$client_email = trim((string) $json['client_email']);
		$private_key  = (string) $json['private_key'];

		if (
			! preg_match('/\A[^\s@]+@[^\s@]+\.iam\.gserviceaccount\.com\z/', $client_email)
			|| false === strpos($private_key, 'BEGIN PRIVATE KEY')
		) {
			return __('Your uploaded JSON key is invalid.', 'cf7-google-sheets-connector');
		}

		if ('service_account' !== trim((string) $json['type'])) {
			return __('Invalid JSON: file is not a service_account type.', 'cf7-google-sheets-connector');
		}

		return '';
	}

	/**
	 * Save Google Service Account JSON credentials via AJAX.
	 * Validates the JSON content and stores it in the WordPress options table.
	 *
	 * @return void
	 */
	public function save_service_account_json_cf7()
	{

		// Verify AJAX nonce.
		check_ajax_referer('gs-ajax-nonce', 'security');

		// Ensure the current user has permission to manage plugin settings.
		if (! current_user_can('manage_options')) {
			wp_send_json_error(
				array(
					'error' => __('Permission denied', 'cf7-google-sheets-connector'),
				)
			);
		}

		// Get and sanitize the submitted JSON credentials.
		$json = isset($_POST['json'])
			? sanitize_textarea_field(wp_unslash($_POST['json']))
			: '';

		// Decode JSON to validate its structure.
		$decoded = json_decode($json, true);

		// Return an error if the JSON is invalid.
		if (JSON_ERROR_NONE !== json_last_error() || ! is_array($decoded)) {
			wp_send_json_error(
				array(
					'error' => __('Invalid JSON format', 'cf7-google-sheets-connector'),
				)
			);
		}

		/*
		* Confirm this is actually a Google service account key.
		*
		* Parsing successfully only proves the upload was valid JSON. Without a
		* structural check any JSON file was accepted and the integration was
		* marked "valid", so the failure only surfaced later as a silent sync
		* error on every submission.
		*/
		$validation_error = self::gscf7_validate_service_account_json($decoded);

		if ('' !== $validation_error) {
			wp_send_json_error(
				array(
					'error' => $validation_error,
				)
			);
		}

		// Save the validated service account credentials.
		// Not autoloaded: this holds a private key and is only read in admin and
		// submission contexts, never on ordinary front-end page views.
		update_option(
			'gs_cf7_service_account_json',
			wp_json_encode($decoded),
			false
		);
		update_option('gs_verify_service', 'valid');
		CF7GSC_googlesheet::gsc_flush_meta_cache();
		// Return a success response.
		wp_send_json_success(
			array(
				'saved' => true,
			)
		);
	}

	/**
	 * Deactivate Service Account authentication.
	 *
	 * Removes the stored Google Service Account credentials
	 * and returns an AJAX response.
	 *
	 * @return void
	 */
	public function deactivate_service_account_cf7()
	{

		// Verify AJAX nonce for security.
		check_ajax_referer('gs-ajax-nonce', 'security');

		// Ensure the current user has permission to manage plugin settings.
		if (! current_user_can('manage_options')) {
			wp_send_json_error(
				array(
					'error' => __('Permission denied', 'cf7-google-sheets-connector'),
				)
			);
		}

		// Remove the stored Service Account JSON credentials.
		delete_option('gs_cf7_service_account_json');
		delete_option('gs_verify_service');
		CF7GSC_googlesheet::gsc_flush_meta_cache();
		// Return a success response.
		wp_send_json_success(
			array(
				'removed' => true,
			)
		);
	}

	/**
	 * Save the selected Google authentication method.
	 *
	 * Stores the authentication method selected by the user
	 * (e.g. OAuth or Service Account).
	 *
	 * @return void
	 */
	public function save_method_api_cf7()
	{
		try {
			$msg = array();
			check_ajax_referer('gs-ajax-nonce', 'security');
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from Google.
			$method = isset($_POST['method_api_cf7']) ? sanitize_text_field(wp_unslash($_POST['method_api_cf7'])) : '';
			// Store the current selected method
			update_option('gs_cf7_auth_method', $method);
			wp_send_json_success();
		} catch (Exception $e) {
			$msg['ERROR_MSG'] = $e->getMessage();
			$msg['TRACE_STK'] = $e->getTraceAsString();
			Gs_Connector_Free_Utility::gs_debug_log($msg);
			wp_send_json_error();
		}
	}

	/**
	 * Dismiss an admin notice permanently.
	 *
	 * Stores the dismissed status for the specified notice.
	 *
	 * @return void
	 */
	public function gscf7_dismiss_notice_callback()
	{
		check_ajax_referer('gs-ajax-nonce', 'security');
		if (! isset($_POST['key'])) {
			wp_send_json_error('Missing key');
			return;
		}
		$key = sanitize_text_field(wp_unslash($_POST['key']));
		update_option('gscf7_free_notice_' . $key, 'dismissed', false);
		wp_send_json_success();
	}

	/**
	 * Build the "Contact Forms connected with Google Sheets" table.
	 *
	 * One row per Contact Form 7 feed: Form Name (links to the form editor),
	 * Feed Name (links to the form's Google Sheets panel, where the feed is
	 * edited) and Sheet URL ("Sheet -> Tab", links to that tab in Google
	 * Sheets). Every feed is listed; feeds with no sheet + tab mapped show
	 * "Not connected" in the Sheet URL column instead of a link. No pagination
	 * -- the caller wraps this in a fixed-height scrollable box.
	 *
	 * Used by both the plugin Dashboard tab and the WordPress dashboard widget
	 * so the two stay identical.
	 *
	 * @since 5.2.4
	 * @return string Table HTML.
	 */
	public function gscf7_render_connected_feeds_table()
	{
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					p.ID,
					p.post_title,
					f.id AS feed_id,
					f.feed_name,
					s.sheet_name,
					s.sheet_id,
					s.tab_id,
					s.tab_name
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->prefix}cf7gs_feeds f
					ON p.ID = f.form_id
				LEFT JOIN {$wpdb->prefix}cf7gs_settings s
					ON s.feed_id = f.id
				WHERE p.post_type = %s
				  AND p.post_status = %s
				ORDER BY p.ID DESC, f.id ASC",
				'wpcf7_contact_form',
				'publish'
			)
		);

		ob_start();
?>
		<table class="widefat striped gscf7-connected-feeds-table">
			<thead>
				<tr>
					<th><?php esc_html_e('Form Name', 'cf7-google-sheets-connector'); ?></th>
					<th><?php esc_html_e('Feed Name', 'cf7-google-sheets-connector'); ?></th>
					<th><?php esc_html_e('Sheet URL', 'cf7-google-sheets-connector'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if (empty($rows)) : ?>
					<tr>
						<td colspan="3" class="gscf7-connected-feeds-empty">
							<?php esc_html_e('No Contact Form 7 feeds found.', 'cf7-google-sheets-connector'); ?>
						</td>
					</tr>
				<?php else : ?>
					<?php foreach ($rows as $gscf7_row) : ?>
						<?php
						$gscf7_form_edit_url = admin_url('admin.php?page=wpcf7&post=' . (int) $gscf7_row->ID . '&action=edit');

						// "Connected" matches cf7_save_to_google_sheets() / gscf7_form_is_connected():
						// a sheet name and a tab name are enough. sheet_id / tab_id are only
						// needed to build the deep link.
						$gscf7_is_connected = '' !== trim((string) $gscf7_row->sheet_name)
							&& '' !== trim((string) $gscf7_row->tab_name);

						// tab_id is a Google Sheets gid; gid "0" is the first tab -- a real
						// value, so test for null/'' rather than using empty().
						$gscf7_has_link = $gscf7_is_connected
							&& '' !== trim((string) $gscf7_row->sheet_id)
							&& null !== $gscf7_row->tab_id
							&& '' !== (string) $gscf7_row->tab_id;
						?>
						<tr>
							<td>
								<a href="<?php echo esc_url($gscf7_form_edit_url); ?>">
									<?php echo esc_html($gscf7_row->post_title); ?>
								</a>
							</td>
							<td>
								<a href="<?php echo esc_url($gscf7_form_edit_url . '&google_sheets'); ?>">
									<?php echo esc_html($gscf7_row->feed_name); ?>
								</a>
							</td>
							<td>
								<?php if ($gscf7_has_link) : ?>
									<a target="_blank" rel="noopener noreferrer"
										href="<?php echo esc_url('https://docs.google.com/spreadsheets/d/' . $gscf7_row->sheet_id . '/edit#gid=' . $gscf7_row->tab_id); ?>">
										<?php echo esc_html($gscf7_row->sheet_name . ' → ' . $gscf7_row->tab_name); ?>
									</a>
								<?php elseif ($gscf7_is_connected) : ?>
									<?php echo esc_html($gscf7_row->sheet_name . ' → ' . $gscf7_row->tab_name); ?>
								<?php else : ?>
									<span class="gscf7-feed-not-connected">
										<?php esc_html_e('Not connected', 'cf7-google-sheets-connector'); ?>
									</span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	<?php
		return ob_get_clean();
	}
	/**
	 * Snooze an admin notice temporarily.
	 *
	 * Stores the current timestamp to delay redisplaying
	 * the specified notice.
	 *
	 * @return void
	 */
	public function gscf7_snooze_notice_callback()
	{
		check_ajax_referer('gs-ajax-nonce', 'security');
		if (! isset($_POST['key'])) {
			wp_send_json_error('Missing key');
			return;
		}
		$key = sanitize_text_field(wp_unslash($_POST['key']));
		update_option('gscf7_free_notice_' . $key . '_time', time(), false);
		wp_send_json_success();
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
			$cf7db->cfdb7_before_send_mail($form_tag, $this->gs_uploads);
		}
	}

	/**
	 * Save uninstall settings
	 *
	 * This function handles the AJAX request when the user
	 * clicks "Save Settings" on the uninstall preferences page.
	 * It verifies the nonce, checks user capability,
	 * sanitizes the input, and stores the setting in the database.
	 */
	public function gscf7_save_uninstall_settings()
	{
		/* Verify security nonce to prevent CSRF */
		check_ajax_referer('gs-ajax-nonce', 'security');
		/* Check user capability */
		if (! current_user_can('manage_options')) {
			wp_send_json_error('Permission denied');
		}
		/*
		* Get uninstall setting value from AJAX request
		* Default value is 'No' if not provided
		*/
		$setting = isset($_POST['uninstall_setting'])

			? sanitize_text_field(wp_unslash($_POST['uninstall_setting']))

			: 'No';
		/* Save uninstall setting to WordPress options table */
		update_option('gscf7_uninstall_settings_free', $setting);
		/* Return success response */
		wp_send_json_success();
	}

	/**
	 * Build System Information String
	 *
	 * @global object $wpdb
	 * @return string
	 * @since 5.3
	 */
	public function get_cf7gs_system_info()
	{
		global $wpdb;
		// Get WordPress version
		$wp_version         = get_bloginfo('version');
		$theme_data         = wp_get_theme();
		$theme_name_version = $theme_data->get('Name') . ' ' . $theme_data->get('Version');
		$parent_theme       = $theme_data->get('Template');
		if (! empty($parent_theme)) {
			$parent_theme_data         = wp_get_theme($parent_theme);
			$parent_theme_name_version = $parent_theme_data->get('Name') . ' ' . $parent_theme_data->get('Version');
		} else {
			$parent_theme_name_version = 'N/A';
		}
		// Check plugin version and subscription plan
		$plugin_version    = defined('GS_CONNECTOR_VERSION') ? GS_CONNECTOR_VERSION : 'N/A';
		$subscription_plan = 'FREE';
		// Check Google Account Authentication
		$api_token_auto     = get_option('gs_token');
		$gs_cf7_auth_method = get_option('gs_cf7_auth_method');
		$cf7_service_email  = '';
		$api_token_service  = get_option('gs_cf7_service_account_json');
		$selected_method    = '';
		if ($gs_cf7_auth_method == 'cf7_existing') {
			$selected_method = esc_html__('Authenticated Using Existing Method', 'cf7-google-sheets-connector');
		} elseif ($gs_cf7_auth_method == 'cf7_service') {

			$selected_method = esc_html__('Authenticated Using Service Account', 'cf7-google-sheets-connector');
		}
		if ($gs_cf7_auth_method == 'cf7_existing') {
			// The user is authenticated through the auto method
			$google_sheet_auto  = new CF7GSC_googlesheet();
			$email_account_auto = $google_sheet_auto->gsheet_print_google_account_email();
			$connected_email    = ! empty($email_account_auto) ? esc_html($email_account_auto) : 'Not Connected';
		} elseif ($gs_cf7_auth_method == 'cf7_service') {
			$decoded_json = json_decode($api_token_service, true);
			if (json_last_error() === JSON_ERROR_NONE && isset($decoded_json['client_email'])) {
				$cf7_service_email = $decoded_json['client_email'];
				$connected_email   = $cf7_service_email;
				// $cf7_service_valid = true;
			}
		} else {
			// Neither auto nor manual authentication is available
			$connected_email = 'Not Connected';
		}
		$gs_verify_status  = get_option('gs_verify');
		$search_permission = ($gs_verify_status === 'valid') ? 'Granted' : 'Denied';
		$system_info       = '<div class="system-statuswc">';
		$system_info      .= '<div class="mb-20 mt-20"><button id="cf7-free-show-info-button" class="info-button">GSheetConnector<span class="dashicons dashicons-arrow-down"></span></div>';
		$system_info      .= '<div id="info-container" class="info-content shadow-box pt-20 pb-20 pl-30 pr-30" style="display:none;">';
		$system_info      .= '<table>';
		$system_info      .= '<tr><td>Plugin Name</td><td class="fw-600 common-badge-table info-name-blue">CF7 Google Sheet Connector</td></tr>';
		$system_info      .= '<tr><td>Plugin Version</td><td class="fw-600 common-badge-table info-name-blue">' . esc_html($plugin_version) . '</td></tr>';
		$system_info      .= '<tr><td>Plugin Subscription Plan</td><td class="fw-600 common-badge-table pro-badge">' . esc_html($subscription_plan) . '</td></tr>';
		$system_info      .= '<tr><td>Connected Email Account</td><td class="fw-600">' . $connected_email . '</td></tr>';
		$system_info      .= '<tr><td>Authentication method for connecting to Google Sheets</td><td class="fw-600">' . esc_html($selected_method) . '</td></tr>';
		$gscpclass         = ' permission-not-given';
		if ($search_permission == 'Granted') {
			$gscpclass = ' permission-given';
		}
		if ($gs_cf7_auth_method == 'cf7_existing') {
			$system_info .= '<tr><td>Google Drive Permission</td><td class="fw-700 permission-badge' . $gscpclass . '">' . esc_html($search_permission) . '</td></tr>';

			$system_info .= '<tr><td>Google Sheet Permission</td><td class="fw-700 permission-badge' . $gscpclass . '">' . esc_html($search_permission) . '</td></tr>';
		}
		// $system_info .= '<tr><td>Google Drive Permission</td><td>' . esc_html($search_permission) . '</td></tr>';

		// $system_info .= '<tr><td>Google Sheet Permission</td><td>' . esc_html($search_permission) . '</td></tr>';

		$system_info .= '</table>';

		$system_info .= '</div>';

		// Add WordPress info

		// Create a button for WordPress info

		$system_info .= '<div class="mb-20 mt-20"><button id="cf7-free-show-wordpress-info-button" class="info-button">WordPress Info<span class="dashicons dashicons-arrow-down"></span></div>';

		$system_info .= '<div id="cf7-free-wordpress-info-container" class="info-content shadow-box pt-20 pb-20 pl-30 pr-30" style="display:none;">';

		$system_info .= '<table>';

		$system_info .= '<tr><td>Version</td><td class="fw-600 common-badge-table info-name-blue">' . get_bloginfo('version') . '</td></tr>';

		$system_info .= '<tr><td>Site Language</td><td class="fw-600">' . get_bloginfo('language') . '</td></tr>';

		$system_info .= '<tr><td>Debug Mode</td><td class="fw-600 common-badge-table info-name-yellow">' . (WP_DEBUG ? 'Enabled' : 'Disabled') . '</td></tr>';

		$system_info .= '<tr><td>Home URL</td><td class="fw-600 common-badge-table info-name-blue">' . get_home_url() . '</td></tr>';

		$system_info .= '<tr><td>Site URL</td><td class="fw-600 common-badge-table info-name-blue">' . get_site_url() . '</td></tr>';

		$system_info .= '<tr><td>Permalink structure</td><td class="fw-600">' . get_option('permalink_structure') . '</td></tr>';

		$system_info .= '<tr><td>Is this site using HTTPS?</td><td class="fw-600">' . (is_ssl() ? 'Yes' : 'No') . '</td></tr>';

		$system_info .= '<tr><td>Is this a multisite?</td><td class="fw-600">' . (is_multisite() ? 'Yes' : 'No') . '</td></tr>';

		$system_info .= '<tr><td>Can anyone register on this site?</td><td class="fw-600">' . (get_option('users_can_register') ? 'Yes' : 'No') . '</td></tr>';

		$system_info .= '<tr><td>Is this site discouraging search engines?</td><td class="fw-600">' . (get_option('blog_public') ? 'No' : 'Yes') . '</td></tr>';

		$system_info .= '<tr><td>Default comment status</td><td class="fw-600">' . get_option('default_comment_status') . '</td></tr>';
		$server_ip    = '';

		if (isset($_SERVER['REMOTE_ADDR'])) {

			$server_ip = sanitize_text_field(
				wp_unslash($_SERVER['REMOTE_ADDR'])
			);
		}

		if ($server_ip == '127.0.0.1' || $server_ip == '::1') {

			$environment_type = 'localhost';
		} else {

			$environment_type = 'production';
		}

		$system_info .= '<tr><td>Environment type</td><td class="fw-600 common-badge-table info-name-yellow">' . esc_html($environment_type) . '</td></tr>';

		$user_count = count_users();

		$total_users = $user_count['total_users'];

		$system_info .= '<tr><td>User Count</td><td class="fw-600">' . esc_html($total_users) . '</td></tr>';

		$system_info .= '<tr><td>Communication with WordPress.org</td><td class="fw-600">' . (get_option('blog_publicize') ? 'Yes' : 'No') . '</td></tr>';

		$system_info .= '</table>';

		$system_info .= '</div>';

		// info about active theme

		$active_theme = wp_get_theme();

		$system_info .= '<div class="mb-20 mt-20"><button id="show-active-info-button" class="info-button">Active Theme<span class="dashicons dashicons-arrow-down"></span></div>';

		$system_info .= '<div id="active-info-container" class="info-content shadow-box pt-20 pb-20 pl-30 pr-30" style="display:none;">';

		$system_info .= '<table>';

		$system_info .= '<tr><td>Name</td><td class="fw-600 common-badge-table info-name-blue">' . $active_theme->get('Name') . '</td></tr>';

		$system_info .= '<tr><td>Version</td><td class="fw-600 common-badge-table info-name-blue">' . $active_theme->get('Version') . '</td></tr>';

		$system_info .= '<tr><td>Author</td><td class="fw-600">' . $active_theme->get('Author') . '</td></tr>';

		$system_info .= '<tr><td>Author website</td><td class="fw-600">' . $active_theme->get('AuthorURI') . '</td></tr>';

		$system_info .= '<tr><td>Theme directory location</td><td class="fw-600">' . $active_theme->get_template_directory() . '</td></tr>';

		$system_info .= '</table>';

		$system_info .= '</div>';
		// Get a list of other plugins you want to check compatibility with

		$other_plugins = array(

			'plugin-folder/plugin-file.php', // Replace with the actual plugin slug

			// Add more plugins as needed

		);
		// Network Active Plugins

		if (is_multisite()) {

			$network_active_plugins = get_site_option('active_sitewide_plugins', array());

			if (! empty($network_active_plugins)) {

				$system_info .= '<div class="mb-20 mt-20"><button id="show-netplug-info-button" class="info-button">Network Active plugins<span class="dashicons dashicons-arrow-down"></span></div>';

				$system_info .= '<div id="netplug-info-container" class="info-content shadow-box pt-20 pb-20 pl-30 pr-30" style="display:none;">';

				$system_info .= '<table>';

				foreach ($network_active_plugins as $plugin => $plugin_data) {

					$plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);

					$system_info .= '<tr><td>' . $plugin_data['Name'] . '</td><td>' . $plugin_data['Version'] . '</td></tr>';
				}

				// Add more network active plugin statuses here...

				$system_info .= '</table>';

				$system_info .= '</div>';
			}
		}

		// Active plugins.
		$active_plugins = get_option('active_plugins', array());

		// Total active plugins count
		$total_active_plugins = count($active_plugins);
		$system_info         .= '<div class="mb-20 mt-20">

      <button id="show-acplug-info-button" class="info-button">'

			. esc_html__('Active plugins', 'cf7-google-sheets-connector') .

			' (' . esc_html($total_active_plugins) . ') 

      <span class="dashicons dashicons-arrow-down"></span>

      </button>

      </div>';

		$system_info .= '<div id="acplug-info-container" class="info-content shadow-box pt-20 pb-20 pl-30 pr-30" style="display:none;">';

		$system_info .= '<table>';
		// Retrieve all active plugins data

		$active_plugins_data = array();

		$active_plugins = get_option('active_plugins', array());

		foreach ($active_plugins as $plugin) {

			$plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);

			$active_plugins_data[$plugin] = array(

				'name'    => $plugin_data['Name'],

				'version' => $plugin_data['Version'],

				'count'   => 0, // Initialize the count to zero

			);
		}
		// Count the number of active installations for each plugin

		$all_plugins = get_plugins();

		foreach ($all_plugins as $plugin_file => $plugin_data) {

			if (array_key_exists($plugin_file, $active_plugins_data)) {

				++$active_plugins_data[$plugin_file]['count'];
			}
		}

		// Sort plugins based on the number of active installations (descending order)

		uasort(
			$active_plugins_data,
			function ($a, $b) {

				return $b['count'] - $a['count'];
			}
		);

		// Display the top 5 most used plugins

		$counter = 0;

		foreach ($active_plugins_data as $plugin_data) {

			$system_info .= '<tr><td>' . $plugin_data['name'] . '</td><td class="fw-600 common-badge-table info-name-blue">' . $plugin_data['version'] . '</td></tr>';
		}

		$system_info .= '</table>';

		$system_info .= '</div>';

		// Webserver Configuration

		$system_info .= '<div class="mb-20 mt-20"><button id="show-server-info-button" class="info-button">Server<span class="dashicons dashicons-arrow-down"></span></div>';

		$system_info .= '<div id="server-info-container" class="info-content shadow-box pt-20 pb-20 pl-30 pr-30" style="display:none;">';

		$system_info .= '<table>';

		$system_info .= '<p class="text-dark"><b>The options shown below relate to your server setup. If changes are required, you may need your web host’s assistance.</b></p>';

		// Add Server information

		$system_info .= '<tr><td>Server Architecture</td><td class="fw-600">' . esc_html(php_uname('s')) . '</td></tr>';

		$server_software = '';

		if (isset($_SERVER['SERVER_SOFTWARE'])) {

			$server_software = sanitize_text_field(
				wp_unslash($_SERVER['SERVER_SOFTWARE'])
			);
		}

		$system_info .= '<tr><td>Web Server</td><td class="fw-600">' . esc_html($server_software) . '</td></tr>';

		$system_info .= '<tr><td>PHP Version</td><td class="fw-600 common-badge-table info-name-blue">' . esc_html(phpversion()) . '</td></tr>';

		$system_info .= '<tr><td>PHP SAPI</td><td class="fw-600">' . esc_html(php_sapi_name()) . '</td></tr>';

		$system_info .= '<tr><td>PHP Max Input Variables</td><td class="fw-600">' . esc_html(ini_get('max_input_vars')) . '</td></tr>';

		$system_info .= '<tr><td>PHP Time Limit</td><td class="fw-600">' . esc_html(ini_get('max_execution_time')) . ' seconds</td></tr>';

		$system_info .= '<tr><td>PHP Memory Limit</td><td class="fw-600">' . esc_html(ini_get('memory_limit')) . '</td></tr>';

		$system_info .= '<tr><td>Max Input Time</td><td class="fw-600">' . esc_html(ini_get('max_input_time')) . ' seconds</td></tr>';

		$system_info .= '<tr><td>Upload Max Filesize</td><td class="fw-600">' . esc_html(ini_get('upload_max_filesize')) . '</td></tr>';

		$system_info .= '<tr><td>PHP Post Max Size</td><td class="fw-600">' . esc_html(ini_get('post_max_size')) . '</td></tr>';

		$system_info .= '<tr><td>cURL Version</td><td class="fw-600">' . esc_html(curl_version()['version']) . '</td></tr>';

		$system_info .= '<tr><td>Is SUHOSIN Installed?</td><td class="fw-600">' . (extension_loaded('suhosin') ? 'Yes' : 'No') . '</td></tr>';

		$system_info .= '<tr><td>Is the Imagick Library Available?</td><td class="fw-600">' . (extension_loaded('imagick') ? 'Yes' : 'No') . '</td></tr>';

		$system_info .= '<tr><td>Are Pretty Permalinks Supported?</td><td class="fw-600">' . (get_option('permalink_structure') ? 'Yes' : 'No') . '</td></tr>';

		$htaccess_path = ABSPATH . '.htaccess';

		$system_info .= sprintf(
			'<tr><td>%s</td><td>%s</td></tr>',
			esc_html__('.htaccess Rules', 'cf7-google-sheets-connector'),
			esc_html(wp_is_writable($htaccess_path) ? 'Writable' : 'Non Writable')
		);

		$system_info .= '<tr><td>Current Time</td><td class="fw-600">' . esc_html(current_time('mysql')) . '</td></tr>';

		$system_info .= '<tr><td>Current UTC Time</td><td class="fw-600">' . esc_html(current_time('mysql', true)) . '</td></tr>';

		$system_info .= '<tr><td>Current Server Time</td><td class="fw-600">' . esc_html(gmdate('Y-m-d H:i:s')) . '</td></tr>';

		$system_info .= '</table>';

		$system_info .= '</div>';

		// Database Configuration
		$system_info .= '<div class="mb-20 mt-20"><button id="show-database-info-button" class="info-button">Database<span class="dashicons dashicons-arrow-down"></span></div>';

		$system_info .= '<div id="database-info-container" class="info-content shadow-box pt-20 pb-20 pl-30 pr-30" style="display:none;">';

		$system_info .= '<table>';

		$database_extension = 'mysqli';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking whether a custom plugin table exists.
		$database_server_version = $wpdb->get_var('SELECT VERSION() as version');

		$database_client_version = $wpdb->db_version();

		$database_username = DB_USER;

		$database_host = DB_HOST;

		$database_name = DB_NAME;

		$table_prefix = $wpdb->prefix;

		$database_charset = $wpdb->charset;

		$database_collation = $wpdb->collate;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking whether a custom plugin table exists.
		$max_allowed_packet_size = $wpdb->get_var("SHOW VARIABLES LIKE 'max_allowed_packet'");
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking whether a custom plugin table exists.
		$max_connections_number = $wpdb->get_var("SHOW VARIABLES LIKE 'max_connections'");

		$system_info .= '<tr><td>Extension</td><td class="fw-600">' . esc_html($database_extension) . '</td></tr>';

		$system_info .= '<tr><td>Server Version</td><td class="fw-600 common-badge-table info-name-blue">' . esc_html($database_server_version) . '</td></tr>';

		$system_info .= '<tr><td>Client Version</td><td class="fw-600 common-badge-table info-name-blue">' . esc_html($database_client_version) . '</td></tr>';

		$system_info .= '<tr><td>Database Username</td><td class="fw-600">' . esc_html($database_username) . '</td></tr>';

		$system_info .= '<tr><td>Database Host</td><td class="fw-600 common-badge-table info-name-yellow">' . esc_html($database_host) . '</td></tr>';

		$system_info .= '<tr><td>Database Name</td><td class="fw-600 common-badge-table info-name-blue">' . esc_html($database_name) . '</td></tr>';

		$system_info .= '<tr><td>Table Prefix</td><td class="fw-600">' . esc_html($table_prefix) . '</td></tr>';

		$system_info .= '<tr><td>Database Charset</td><td class="fw-600">' . esc_html($database_charset) . '</td></tr>';

		$system_info .= '<tr><td>Database Collation</td><td class="fw-600">' . esc_html($database_collation) . '</td></tr>';

		$system_info .= '<tr><td>Max Allowed Packet Size</td><td class="fw-600">' . esc_html($max_allowed_packet_size) . '</td></tr>';

		$system_info .= '<tr><td>Max Connections Number</td><td class="fw-600">' . esc_html($max_connections_number) . '</td></tr>';

		$system_info .= '</table>';

		$system_info .= '</div>';

		// WordPress constants

		$system_info .= '<div class="mb-20 mt-20"><button id="show-wrcons-info-button" class="info-button">WordPress Constants<span class="dashicons dashicons-arrow-down"></span></div>';

		$system_info .= '<div id="wrcons-info-container" class="info-content shadow-box pt-20 pb-20 pl-30 pr-30" style="display:none;">';

		$system_info .= '<table>';

		// Add WordPress Constants information

		$system_info .= '<tr><td>ABSPATH</td><td class="fw-600">' . esc_html(ABSPATH) . '</td></tr>';

		$system_info .= '<tr><td>WP_HOME</td><td class="fw-600 common-badge-table info-name-blue">' . esc_html(home_url()) . '</td></tr>';

		$system_info .= '<tr><td>WP_SITEURL</td><td class="fw-600">' . esc_html(site_url()) . '</td></tr>';

		$system_info .= '<tr><td>WP_CONTENT_DIR</td><td class="fw-600">' . esc_html(WP_CONTENT_DIR) . '</td></tr>';

		$system_info .= '<tr><td>WP_PLUGIN_DIR</td><td class="fw-600">' . esc_html(WP_PLUGIN_DIR) . '</td></tr>';

		$system_info .= '<tr><td>WP_MEMORY_LIMIT</td><td class="fw-600">' . esc_html(WP_MEMORY_LIMIT) . '</td></tr>';

		$system_info .= '<tr><td>WP_MAX_MEMORY_LIMIT</td><td class="fw-600">' . esc_html(WP_MAX_MEMORY_LIMIT) . '</td></tr>';

		$system_info .= '<tr><td>WP_DEBUG</td><td class="fw-600">' . (defined('WP_DEBUG') && WP_DEBUG ? 'Yes' : 'No') . '</td></tr>';

		$system_info .= '<tr><td>WP_DEBUG_DISPLAY</td><td class="fw-600">' . (defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY ? 'Yes' : 'No') . '</td></tr>';

		$system_info .= '<tr><td>SCRIPT_DEBUG</td><td class="fw-600">' . (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? 'Yes' : 'No') . '</td></tr>';

		$system_info .= '<tr><td>WP_CACHE</td><td class="fw-600">' . (defined('WP_CACHE') && WP_CACHE ? 'Yes' : 'No') . '</td></tr>';

		$system_info .= '<tr><td>CONCATENATE_SCRIPTS</td><td class="fw-600">' . (defined('CONCATENATE_SCRIPTS') && CONCATENATE_SCRIPTS ? 'Yes' : 'No') . '</td></tr>';

		$system_info .= '<tr><td>COMPRESS_SCRIPTS</td><td class="fw-600">' . (defined('COMPRESS_SCRIPTS') && COMPRESS_SCRIPTS ? 'Yes' : 'No') . '</td></tr>';

		$system_info .= '<tr><td>COMPRESS_CSS</td><td class="fw-600">' . (defined('COMPRESS_CSS') && COMPRESS_CSS ? 'Yes' : 'No') . '</td></tr>';

		// Manually define the environment type (example values: 'development', 'staging', 'production')

		$environment_type = 'development';

		// Display the environment type

		$system_info .= '<tr><td>WP_ENVIRONMENT_TYPE</td><td class="fw-600">' . esc_html($environment_type) . '</td></tr>';

		$system_info .= '<tr><td>WP_DEVELOPMENT_MODE</td><td class="fw-600">' . (defined('WP_DEVELOPMENT_MODE') && WP_DEVELOPMENT_MODE ? 'Yes' : 'No') . '</td></tr>';

		$system_info .= '<tr><td>DB_CHARSET</td><td class="fw-600">' . esc_html(DB_CHARSET) . '</td></tr>';

		$system_info .= '<tr><td>DB_COLLATE</td><td class="fw-600">' . esc_html(DB_COLLATE) . '</td></tr>';

		$system_info .= '</table>';

		$system_info .= '</div>';

		// Filesystem Permission

		$system_info .= '<div class="mb-20 mt-20"><button id="show-ftps-info-button" class="info-button">Filesystem Permission <span class="dashicons dashicons-arrow-down"></span></button></div>';

		$system_info .= '<div id="ftps-info-container" class="info-content shadow-box pt-20 pb-20 pl-30 pr-30" style="display:none;">';

		$system_info .= '<p class="text-dark"><b>Shows whether WordPress is able to write to the directories it needs access to.</b></p>';

		$system_info .= '<table>';

		// Filesystem Permission information

		$upload_dir = wp_upload_dir();

		$system_info .= sprintf(
			'<tr><td>%s</td><td>%s</td><td>%s</td></tr>',
			esc_html__('The main WordPress directory', 'cf7-google-sheets-connector'),
			esc_html(ABSPATH),
			esc_html(wp_is_writable(ABSPATH) ? 'Writable' : 'Not Writable')
		);

		$system_info .= sprintf(
			'<tr><td>%s</td><td>%s</td><td>%s</td></tr>',
			esc_html__('The wp-content directory', 'cf7-google-sheets-connector'),
			esc_html(WP_CONTENT_DIR),
			esc_html(wp_is_writable(WP_CONTENT_DIR) ? 'Writable' : 'Not Writable')
		);

		$system_info .= sprintf(
			'<tr><td>%s</td><td>%s</td><td>%s</td></tr>',
			esc_html__('The uploads directory', 'cf7-google-sheets-connector'),
			esc_html($upload_dir['basedir']),
			esc_html(wp_is_writable($upload_dir['basedir']) ? 'Writable' : 'Not Writable')
		);

		$system_info .= sprintf(
			'<tr><td>%s</td><td>%s</td><td>%s</td></tr>',
			esc_html__('The plugins directory', 'cf7-google-sheets-connector'),
			esc_html(WP_PLUGIN_DIR),
			esc_html(wp_is_writable(WP_PLUGIN_DIR) ? 'Writable' : 'Not Writable')
		);

		$system_info .= sprintf(
			'<tr><td>%s</td><td>%s</td><td>%s</td></tr>',
			esc_html__('The themes directory', 'cf7-google-sheets-connector'),
			esc_html(get_theme_root()),
			esc_html(wp_is_writable(get_theme_root()) ? 'Writable' : 'Not Writable')
		);

		$system_info .= '</table>';
		$system_info .= '</div>';
		return $system_info;
	}
	/**
	 * AJAX function - dispaly log file
	 *
	 * @since 2.1
	 */
	public function display_error_cf7_log()
	{
		ob_start();
		$debug_log_file = WP_CONTENT_DIR . '/debug.log';
		$rows           = array();
		// If file exists → process logs
		if (file_exists($debug_log_file)) {
			$debug_log_contents = file_get_contents($debug_log_file);
			$log_lines          = explode("\n", $debug_log_contents);
			$last_lines         = array_slice(array_reverse($log_lines), 0, 100);
			foreach ($last_lines as $line) {
				if (empty($line)) {
					continue;
				}
				preg_match(
					'/\[(.*?)\]\sPHP\s(.*?):\s(.*?)\sin\s(.*?)\son\sline\s(\d+)/',
					$line,
					$matches
				);
				if (! empty($matches)) {

					$raw_date = $matches[1];

					/*
					 * debug.log timestamps carry their own timezone suffix, e.g.
					 * "28-Jul-2026 19:38:00 UTC". wp_date() converts the parsed
					 * moment into the site's configured timezone (Settings > General)
					 * and appends its abbreviation, matching the Error Log screen.
					 */
					$timestamp = strtotime($raw_date);

					$formatted_date = $timestamp
						? wp_date(
							get_option('date_format') . ' ' . get_option('time_format') . ' (T)',
							$timestamp
						)
						: $raw_date;

					$rows[] = array(

						'date'    => $formatted_date,

						'type'    => $matches[2],

						'message' => wp_strip_all_tags($matches[3]),

						'file'    => $matches[4] . ' (Line ' . $matches[5] . ')',

					);
				}
			}
		}

		// ONE TABLE ONLY

		echo '<table class="gscf7-free-error-log-table widefat striped mt-30">';

		echo '<thead>

      <tr>

      <th>Date</th>

      <th>Type</th>

      <th>Message</th>

      <th>File</th>

      </tr>

      </thead>';

		echo '<tbody>';

		// If no file OR no logs

		if (empty($rows)) {

			echo '<tr>

         <td colspan="4" style="text-align:center;">

         No debug log data found.

         </td>

         </tr>';
		} else {

			foreach ($rows as $row) {

				echo '<tr>

            <td>' . esc_html($row['date']) . '</td>

            <td>' . esc_html($row['type']) . '</td>

            <td>' . esc_html($row['message']) . '</td>

            <td>' . esc_html($row['file']) . '</td>

            </tr>';
			}
		}

		echo '</tbody></table>';

		return ob_get_clean();
	}

	/**
	 * AJAX function - verifies the token
	 *
	 * @since 1.0
	 */
	public function verify_gs_integation()
	{

		// nonce checksave_gs_settings
		check_ajax_referer('gs-ajax-nonce', 'security');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(
				array(
					'message' => __('Permission denied', 'cf7-google-sheets-connector'),
				)
			);
		}

		$Code = '';
		if (isset($_POST['code'])) {
			$Code = sanitize_text_field(
				wp_unslash($_POST['code'])
			);
		}
		update_option('gs_access_code', $Code, false);

		if ('' === get_option('gs_access_code')) {
			update_option('gs_verify', 'invalid');
			wp_send_json_error(
				array(
					'code'    => 'missing_code',
					'message' => __('Access code can\'t be blank.', 'cf7-google-sheets-connector'),
				)
			);
		}

		include_once GS_CONNECTOR_ROOT . '/lib/google-sheets.php';

		/*
		 * A successful exchange is about to hand back a brand-new refresh
		 * token, so a credential set staged earlier (because rotating it
		 * live would have broken a still-working connection) is safe to
		 * promote right here -- and safe to refresh again, in case the
		 * relay has moved on since it was staged.
		 *
		 * Placement matters: this only runs after the nonce and capability
		 * check above, so it can never be reached by an unauthenticated
		 * request.
		 */
		Gs_Connector_Free_Utility::instance()->promote_pending_api_credentials();
		Gs_Connector_Free_Utility::instance()->maybe_refresh_api_credentials('pre_exchange', 5 * MINUTE_IN_SECONDS);

		// A relay failure above must not be fatal -- fall through to whatever
		// credentials are already stored.
		$exchanged = cf7gsc_googlesheet::preauth(get_option('gs_access_code'));

		update_option('gs_cf7_manual_setting', '0');

		CF7GSC_googlesheet::gsc_flush_meta_cache();

		if (true === $exchanged) {
			wp_send_json_success();
		}

		wp_send_json_error(
			array(
				'code'    => 'oauth_exchange_failed',
				'message' => __('Google did not accept this authorization. Please click "Sign in with Google" and try connecting again. If the problem continues, check the Error Logs below for details.', 'cf7-google-sheets-connector'),
			)
		);
	}

	/**
	 * AJAX function - deactivate activation
	 *
	 * @since 4.2
	 */
	public function deactivate_gs_integation()
	{

		check_ajax_referer('gs-ajax-nonce', 'security');

		if (! current_user_can('manage_options')) {
			wp_send_json_error();
		}

		if (get_option('gs_token') !== '') {
			delete_option('gs_feeds');
			delete_option('gs_sheetId');
			delete_option('gs_token');
			delete_option('cf7gf_email_account');
			delete_option('gs_access_code');
			delete_option('gs_verify');
			delete_option('gscf7_token_client_fp');

			// Disconnecting must work even while the relay is unreachable --
			// this only promotes whatever is already staged locally.
			Gs_Connector_Free_Utility::instance()->promote_pending_api_credentials();

			CF7GSC_googlesheet::gsc_flush_meta_cache();
			wp_send_json_success();
		} else {

			wp_send_json_error();
		}
	}



	/**
	 * AJAX function - clear log file for system status tab
	 *
	 * @since 2.1
	 */
	public function cf7_clear_debug_logs()
	{

		// Nonce check
		check_ajax_referer('gs-ajax-nonce', 'security');

		global $wp_filesystem;

		if (empty($wp_filesystem)) {
			require_once ABSPATH . 'wp-admin/includes/file.php';

			/*
			* WP_Filesystem() returns false when credentials are unavailable and
			* leaves $wp_filesystem null. Calling a method on it then raised a
			* fatal error.
			*/
			if (! WP_Filesystem()) {
				wp_send_json_error(
					array(
						'error' => __('Filesystem access is unavailable on this site.', 'cf7-google-sheets-connector'),
					)
				);
			}
		}

		if (! is_object($wp_filesystem)) {
			wp_send_json_error(
				array(
					'error' => __('Filesystem access is unavailable on this site.', 'cf7-google-sheets-connector'),
				)
			);
		}

		/*
		* Honour a custom WP_DEBUG_LOG path. Hardcoding wp-content/debug.log
		* truncated a file the site may not even be writing to.
		*/
		$log_path = (defined('WP_DEBUG_LOG') && is_string(WP_DEBUG_LOG) && '' !== WP_DEBUG_LOG)
			? WP_DEBUG_LOG
			: WP_CONTENT_DIR . '/debug.log';

		$wp_filesystem->put_contents($log_path, '', FS_CHMOD_FILE);
		wp_send_json_success();
	}

	/**
	 * Add new tab to contact form 7 editors panel
	 *
	 * @since 1.0
	 */
	public function cf7_gs_editor_panels($panels)
	{

		$selected_method = '';

		$authenticated = get_option('gs_token');

		$cf7_manual = get_option('cf7_manual');

		$auth_method = get_option('gs_cf7_auth_method');

		$authenticatedService = get_option('gs_cf7_service_account_json');

		$email_account = '';

		$gsc_is_valid = get_option('gs_verify');

		if (Gs_Connector_Free_Utility::instance()->has_live_google_token() && $gsc_is_valid == 'valid' && $auth_method === 'cf7_existing') {

			$google_sheet = new CF7GSC_googlesheet();

			$email_account = $google_sheet->gsheet_print_google_account_email();

			if ($email_account) {

				$selected_method = ' (' . __('Existing', 'cf7-google-sheets-connector') . ')';
			}
		} elseif (! empty($authenticatedService) && $auth_method === 'cf7_service') {

			$selected_method = ' (' . __('Service', 'cf7-google-sheets-connector') . ')';
		}

		if (current_user_can('wpcf7_edit_contact_forms')) {

			$panels['google_sheets'] = array(

				'title'    => __('Google Sheets', 'cf7-google-sheets-connector') . $selected_method,

				'callback' => array($this, 'cf7_editor_panel_google_sheet'),

			);
		}

		return $panels;
	}

	/**
	 * Set Google sheet settings with contact form
	 *
	 * @since 1.0
	 */
	public function save_gs_settings($post)
	{

		global $wpdb;

		$default = array(

			'sheet-name'     => '',

			'sheet-id'       => '',

			'sheet-tab-name' => '',

			'tab-id'         => '',

		);

		$sheet_data = $default;

		/*
		* Bail when the Google Sheets panel was not part of this save.
		*
		* wpcf7_after_save fires for every form save, including programmatic
		* saves, form duplication and CF7's import. Those requests carry no
		* 'cf7-gs' field, and the method previously fell through to the empty
		* defaults and wrote them over the existing mapping, silently
		* disconnecting the form from its spreadsheet.
		*/
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce and capability verified by Contact Form 7 before this hook fires.
		if (! isset($_POST['cf7-gs'])) {

			return;
		}

		$sheet_data = array_map(
			'sanitize_text_field',
			wp_unslash((array) $_POST['cf7-gs']) // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce and capability verified by Contact Form 7 before this hook fires.
		);

		// update_post_meta($post->id(), 'gs_settings', $sheet_data);

		$form_id = $post->id();

		$feeds_table = $wpdb->prefix . 'cf7gs_feeds';

		$settings_table = $wpdb->prefix . 'cf7gs_settings';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking whether a custom plugin table exists.
		$feed_id = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT id FROM `' . esc_sql($wpdb->prefix . 'cf7gs_feeds') . '` WHERE form_id = %d',
				$form_id
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

			$feed_id = $wpdb->insert_id;
		}

		$sheet_name = $sheet_data['sheet-name'] ?? '';

		$sheet_id = $sheet_data['sheet-id'] ?? '';

		$tab_name = $sheet_data['sheet-tab-name'] ?? '';

		$tab_id = $sheet_data['tab-id'] ?? '';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking whether a custom plugin table exists.
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT id FROM `' . esc_sql($wpdb->prefix . 'cf7gs_settings') . '` WHERE feed_id = %d',
				$feed_id
			)
		);

		if ($exists) {

			// UPDATE
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking whether a custom plugin table exists.
			$wpdb->update(
				$settings_table,
				array(

					'sheet_name' => $sheet_name,

					'sheet_id'   => $sheet_id,

					'tab_name'   => $tab_name,

					'tab_id'     => $tab_id,

					'is_manual'  => 1,

				),
				array('feed_id' => $feed_id)
			);
		} else {

			// INSERT
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking whether a custom plugin table exists.
			$wpdb->insert(
				$settings_table,
				array(

					'form_id'    => $form_id,

					'feed_id'    => $feed_id,

					'sheet_name' => $sheet_name,

					'sheet_id'   => $sheet_id,

					'tab_name'   => $tab_name,

					'tab_id'     => $tab_id,

					'is_manual'  => 1,

				)
			);
		}

		// The mapping changed, so any cached spreadsheet structure is stale.
		if (! empty($sheet_id) && class_exists('CF7GSC_googlesheet')) {
			CF7GSC_googlesheet::gsc_flush_meta_cache($sheet_id);
		}
	}

	/**
	 * Create array of file name for the uploaded files
	 *
	 * @since 4.5
	 */

	/**
	 * Create array of file name for the uploaded files
	 *
	 * @since 4.5
	 */
	public function save_uploaded_files_local($form_data)
	{
		$upload = wp_upload_dir();

		if (get_option('uploads_use_yearmonth_folders')) {
			$time             = current_time('mysql');
			$y                = substr($time, 0, 4);
			$m                = substr($time, 5, 2);
			$upload['subdir'] = "/$y/$m";
		}

		$upload['subdir'] = '/cf7gs' . $upload['subdir'];
		$upload['path']   = $upload['basedir'] . $upload['subdir'];
		$upload['url']    = $upload['baseurl'] . $upload['subdir'];

		if (! is_dir($upload['path'])) {
			wp_mkdir_p($upload['path']);
		}

		$htaccess_file = sprintf('%s/.htaccess', $upload['path']);

		if (! file_exists($htaccess_file)) :
			file_put_contents($htaccess_file, 'Options -Indexes');
		endif;

		$time_now = time();

		$form = WPCF7_Submission::get_instance();

		if ($form) {
			$files = $form->uploaded_files();

			$uploads_stored = array();

			if (! empty($files)) {
				foreach ($files as $name => $paths) {
					// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Read-only presence check on a Contact Form 7 submission already validated by CF7's own nonce handling; no data is taken from the superglobal.
					if (! isset($_FILES[$name]) || empty($paths)) {
						continue;
					}

					$paths = is_array($paths) ? $paths : array($paths);

					foreach ($paths as $path) {
						if (! file_exists($path)) {
							continue;
						}

						$file_name       = sanitize_file_name(basename($path));
						$destination     = $upload['path'] . '/' . $time_now . '-' . $file_name;
						$destination_url = sprintf('%s/%s', $upload['url'], $time_now . '-' . $file_name);

						$uploads_stored[$name][] = $destination_url;

						copy($path, $destination);
					}
				}

				$this->gs_uploads = $uploads_stored;
			}
		}
	}
	public function get_special_mail_tags()
	{

		return $this->special_mail_tags;
	}

	/**
	 * Function - To send contact form data to google spreadsheet
	 *
	 * @param object $form
	 * @since 1.0
	 */
	public function cf7_save_to_google_sheets($form)
	{

		$form_id = $form->id();

		$enable_entry_id = get_option('gs_cf7db_setting') == '1';

		$next_entry_id = 0;

		if ($enable_entry_id) {

			global $wpdb;

			$cfdb = apply_filters('cfdb7_database', $wpdb);

			$table_name = $cfdb->prefix . 'cf7db_gsheet_forms';

			$latest_entry_id = $cfdb->get_var(
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking whether a custom plugin table exists.
				$cfdb->prepare(
					"SELECT MAX(id) FROM $table_name WHERE form_id = %d",
					$form_id
				)
			);

			$latest_entry_id = intval($latest_entry_id);

			if ($latest_entry_id <= 0) {

				$next_entry_id = 1;
			} else {

				$next_entry_id = $latest_entry_id + 1;
			}
		}

		$this->cfdb7_before_send_mail($form);

		$submission = WPCF7_Submission::get_instance();

		global $wpdb;

		$table_name = $wpdb->prefix . 'cf7gs_settings';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table, caching not appropriate here.
		$form_data = $wpdb->get_row(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name sanitized with esc_sql() and backticks.
				'SELECT * FROM `' . esc_sql($table_name) . '` WHERE form_id = %d',
				$form_id
			),
			ARRAY_A
		);

		if (empty($form_data)) {

			return;
		}

		$sheet_name = $form_data['sheet_name'] ?? '';

		$tab_name = $form_data['tab_name'] ?? '';

		$sheet_id = $form_data['sheet_id'] ?? '';

		$tab_id = $form_data['tab_id'] ?? '';

		$data = array();

		if ($submission && ! empty($sheet_name) && ! empty($tab_name)) {

			$posted_data = $submission->get_posted_data();

			try {

				include_once GS_CONNECTOR_ROOT . '/lib/google-sheets.php';

				$doc = new cf7gsc_googlesheet();

				$doc->auth();

				$doc->setSpreadsheetId($sheet_id);

				$doc->setWorkTabId($tab_id);

				// ========================

				// SPECIAL MAIL TAGS

				// ========================

				$meta = array();

				$special_mail_tags = array(

					'serial_number',

					'remote_ip',

					'user_agent',

					'url',

					'date',

					'time',

					'post_id',

					'post_name',

					'post_title',

					'post_url',

					'post_author',

					'post_author_email',

					'site_title',

					'site_description',

					'site_url',

					'site_admin_email',

					'user_login',

					'user_email',

					'user_url',

					'user_first_name',

					'user_last_name',

					'user_nickname',

					'user_display_name',

				);

				foreach ($special_mail_tags as $smt) {

					$tagname = sprintf('_%s', $smt);

					$mail_tag = new WPCF7_MailTag(sprintf('[%s]', $tagname), $tagname, '');
					// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- CF7 core hook, not defined by this plugin.
					$meta[$smt] = apply_filters('wpcf7_special_mail_tags', '', $tagname, false, $mail_tag);
				}

				// Submission date/time columns are taken from CF7's own [_date]
				// and [_time] special mail tags, exactly as before: CF7 already
				// formats them with the site's "date_format" / "time_format"
				// options, so the cell reads precisely what Settings -> General
				// is set to show.
				//
				// $text_field_names collects the columns whose value must stay
				// a literal string, mapped to whether add_row() also has to
				// re-write the cell (see step 7 there) to stop Sheets marking
				// it as forced text and showing the stray leading apostrophe in
				// the formula bar. True for these two: "August 22, 2026" and
				// "9:45 AM" are both forms Sheets would otherwise read as a
				// date/time.
				$text_field_names = array();

				if (! empty($meta)) {

					$data['date'] = $meta['date'];

					$text_field_names['date'] = true;

					$data['time'] = $meta['time'];

					$text_field_names['time'] = true;

					$data['serial-number'] = $meta['serial_number'];

					$data['remote-ip'] = $meta['remote_ip'];

					$data['user-agent'] = $meta['user_agent'];

					$data['url'] = $meta['url'];

					$data['post-id'] = $meta['post_id'];

					$data['post-name'] = $meta['post_name'];

					$data['post-title'] = $meta['post_title'];

					$data['post-url'] = $meta['post_url'];

					$data['post-author'] = $meta['post_author'];

					$data['post-author-email'] = $meta['post_author_email'];

					$data['site-title'] = $meta['site_title'];

					$data['site-description'] = $meta['site_description'];

					$data['site-url'] = $meta['site_url'];

					$data['site-admin-email'] = $meta['site_admin_email'];

					$data['user-login'] = $meta['user_login'];

					$data['user-email'] = $meta['user_email'];

					$data['user-url'] = $meta['user_url'];

					$data['user-first-name'] = $meta['user_first_name'];

					$data['user-last-name'] = $meta['user_last_name'];

					$data['user-nickname'] = $meta['user_nickname'];

					$data['user-display-name'] = $meta['user_display_name'];

					$data['Date'] = $meta['date'];

					$text_field_names['Date'] = true;

					$data['Time'] = $meta['time'];

					$text_field_names['Time'] = true;

					$data['DATE'] = $meta['date'];

					$text_field_names['DATE'] = true;

					$data['TIME'] = $meta['time'];

					$text_field_names['TIME'] = true;
				}

				// ========================

				// POSTED FORM DATA

				// ========================

				// CF7's [date] field submits the browser's native input value,
				// which is always ISO "yyyy-mm-dd". Track which posted field
				// names come from a [date] tag so their value can be rewritten
				// to the dd-mm-yyyy the field itself presents (below, once the
				// posted data has been copied into $data) and kept as literal
				// text, so the cell and the formula bar both read that exact
				// string.
				$cf7_date_field_names = array();

				foreach ($form->scan_form_tags(array('basetype' => 'date')) as $date_tag) {

					if (! empty($date_tag->name)) {

						$cf7_date_field_names[$date_tag->name] = true;

						// Listed as text, but false: dd-mm-yyyy is not a form
						// Sheets reads as a date, so these cells never grew the
						// apostrophe and must not be re-written by add_row().
						$text_field_names[$date_tag->name] = false;
					}
				}

				foreach ($posted_data as $key => $value) {

					// skip CF7 internal fields

					if (strpos($key, '_wpcf7') !== false || strpos($key, '_wpnonce') !== false) {

						continue;
					}

					// handle file uploads

					$uploaded_file = $this->gs_uploads;

					if (array_key_exists($key, $uploaded_file)) {

						$file_value = $uploaded_file[$key];

						if (is_array($file_value)) {
							// Multiple files on this field: sheet shows filenames only, comma-separated.
							$data[$key] = implode(', ', array_map('basename', $file_value));
						} else {
							$data[$key] = basename($file_value);
						}

						continue;
					}

					// handle arrays (checkbox, multi-select)

					if (is_array($value)) {

						$data[$key] = sanitize_text_field(implode(', ', $value));
					} else {

						$data[$key] = sanitize_textarea_field(stripcslashes($value));
					}
				}

				// Rewrite the [date] field values copied above from the ISO
				// "yyyy-mm-dd" the browser posts to the dd-mm-yyyy the field
				// presents. A value that isn't ISO (a filter supplied its own,
				// say) is left exactly as it is rather than guessed at.
				foreach ($cf7_date_field_names as $cf7_date_field_name => $unused) {

					if (empty($data[$cf7_date_field_name])) {

						continue;
					}

					$cf7_date_object = DateTime::createFromFormat('Y-m-d', $data[$cf7_date_field_name]);

					// createFromFormat() is lenient -- it happily reads
					// "23-08-2026" as year 23 with an overflowing day count --
					// so the parse only counts if it round-trips exactly.
					if (
						$cf7_date_object instanceof DateTime
						&& $cf7_date_object->format('Y-m-d') === $data[$cf7_date_field_name]
					) {

						$data[$cf7_date_field_name] = $cf7_date_object->format('d-m-Y');
					}
				}

				// ========================

				// STRIP FORMULA INJECTION

				// ========================

				foreach ($data as $key => $value) {

					// Date/time values are generated here, never formulas, and
					// are written as literal text below (add_row()); prefixing
					// them with an apostrophe would only put back the stray
					// character in the formula bar this avoids.
					if (isset($text_field_names[$key])) {

						continue;
					}

					$value = (string) $value;

					/*
					* Neutralise the formula rather than stripping it.
					*
					* ltrim() with a character list removed every leading occurrence,
					* which corrupted legitimate values: "+1-555-0100" became
					* "1-555-0100", "-5" became "5" and "@handle" became "handle".
					* A leading apostrophe tells Google Sheets to treat the cell as
					* text while preserving the value exactly as submitted.
					*/
					if ('' !== $value && false !== strpos("=+-@\t\r", $value[0])) {

						$data[$key] = "'" . $value;
					}
				}

				// ========================

				// FIX 3: entry_id injected FIRST using array_merge

				// ========================

				if ($enable_entry_id) {

					$data = array_merge(
						array(

							'entry_id' => $next_entry_id,

							'Entry ID' => $next_entry_id,

						),
						$data
					);
				}

				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- CF7 core hook, not defined by this plugin.
				$data = apply_filters('gsc_filter_form_data', $data, $form);

				$gsc_result = $doc->add_row($data, $text_field_names);

				/*
				* Surface a failed write. add_row() previously returned silently on
				* error, so a submission could be lost with nothing recorded.
				*/
				if (is_wp_error($gsc_result)) {

					Gs_Connector_Free_Utility::gs_debug_log(
						array(
							'context' => 'cf7_save_to_google_sheets',
							'form_id' => $form_id,
							'fields'  => array_keys($data),
							'error'   => $gsc_result->get_error_message(),
						)
					);
				}
			} catch (Exception $e) {

				/*
				* Log diagnostic context only. $data holds the visitor's submission,
				* so field names are recorded but never their values.
				*/
				Gs_Connector_Free_Utility::gs_debug_log(
					array(
						'context' => 'cf7_save_to_google_sheets',
						'form_id' => $form_id,
						'fields'  => array_keys($data),
						'message' => $e->getMessage(),
						'file'    => $e->getFile(),
						'line'    => $e->getLine(),
					)
				);
			}
		}
	}

	/*
	 * Google sheet settings page

	 * @since 1.0

	*/

	public function cf7_editor_panel_google_sheet($post)
	{

		// Fall back to the form object when the screen has no `post` query arg
		// (e.g. the "Add New Form" screen), so $form_id is always defined below.
		$form_id = is_object($post) && method_exists($post, 'id') ? $post->id() : 0;
	?>

		<div id="gscf7-free-metabox" class="postbox d-none gscf7-upgrade-pro-informationdiv gscf7-free">

			<div class="inside">

				<div>

					<div class="gsheet-header-logo d-flex justify-center mt-20 mb-20">

						<img src="<?php echo esc_url(GS_CONNECTOR_URL); ?>/assets/img/gsc-logo.webp" width="150px">

					</div>

				</div>

				<ul class="mt-10 mb-20">

					<li><?php echo esc_html(__('Automatic sheet configuration', 'cf7-google-sheets-connector')); ?></li>

					<li><?php echo esc_html(__('Smart field mapping', 'cf7-google-sheets-connector')); ?></li>

					<li><?php echo esc_html(__('Conditional logic support', 'cf7-google-sheets-connector')); ?></li>

					<li><?php echo esc_html(__('Header and row customization', 'cf7-google-sheets-connector')); ?></li>

					<li><?php echo esc_html(__('Advanced mail tags', 'cf7-google-sheets-connector')); ?></li>

					<li><?php echo esc_html(__('Real-time submission sync', 'cf7-google-sheets-connector')); ?></li>

					<li><?php echo esc_html(__('Spreadsheet download option', 'cf7-google-sheets-connector')); ?></li>

				</ul>

				<div class="text-center">

					<a class="btn btn-primary link-hover-white text-center" href="https://www.gsheetconnector.com/cf7-google-sheet-connector-pro" target="_blank">

						<?php echo esc_html__('Upgrade to Pro', 'cf7-google-sheets-connector'); ?>

					</a>

				</div>

			</div>

		</div>

		<?php

		// Check if the user is authenticated

		$authenticated = get_option('gs_token');

		$per = get_option('gs_verify');

		// check user is authenticated when save existing api method

		$show_setting = 0;

		$selected_method = '';

		$selected_method_display = '';

		$authenticated = get_option('gs_token');

		$cf7_manual = get_option('cf7_manual');

		$auth_method = get_option('gs_cf7_auth_method');

		$authenticatedService = get_option('gs_cf7_service_account_json');

		if ($auth_method == 'cf7_existing') {

			// The user is authenticated through the auto method

			$google_sheet_auto = new CF7GSC_googlesheet();

			$selected_method_display = esc_html__('Authenticated Using Existing Method', 'cf7-google-sheets-connector');

			$email_account_auto = $google_sheet_auto->gsheet_print_google_account_email();

			$connected_email = ! empty($email_account_auto) ? esc_html($email_account_auto) : 'Not Connected';
		} elseif ($auth_method == 'cf7_service') {

			$decoded_json = json_decode($authenticatedService, true);

			if (json_last_error() === JSON_ERROR_NONE && isset($decoded_json['client_email'])) {

				$cf7_service_email = $decoded_json['client_email'];

				$connected_email = $cf7_service_email;

				// $cf7_service_valid = true;

				$selected_method_display = esc_html__('Service account', 'cf7-google-sheets-connector');
			}
		} else {

			// Neither auto nor manual authentication is available

			$connected_email = 'Not Connected';
		}

		// Check if the user is authenticated when saving existing API method

		if ((Gs_Connector_Free_Utility::instance()->has_live_google_token() && $per == 'valid' && $auth_method === 'cf7_existing') || ($auth_method === 'cf7_service' && ! empty($authenticatedService) && json_decode($authenticatedService, true))) {

			$show_setting = 1;
		} else {

		?>

			<?php

			if ($auth_method == 'cf7_existing') {

				$selected_method = __('Existing Client / Secret Key (Auto Setup).', 'cf7-google-sheets-connector');
			?>

				<!----Display msg without auth-->

				<div class="wrap w-100 m-0 gscf7-free">

					<div class="inner-wrap w-100 bg-white p-40">

						<div class="gsc-setup-alert">

							<div class="gsc-alert-icon">

								<svg width="30px" height="30px" viewBox="-0.5 0 25 25" fill="none" xmlns="http://www.w3.org/2000/svg">

									<path d="M18.2202 21.25H5.78015C5.14217 21.2775 4.50834 21.1347 3.94373 20.8364C3.37911 20.5381 2.90402 20.095 2.56714 19.5526C2.23026 19.0101 2.04372 18.3877 2.02667 17.7494C2.00963 17.111 2.1627 16.4797 2.47015 15.92L8.69013 5.10999C9.03495 4.54078 9.52077 4.07013 10.1006 3.74347C10.6804 3.41681 11.3346 3.24518 12.0001 3.24518C12.6656 3.24518 13.3199 3.41681 13.8997 3.74347C14.4795 4.07013 14.9654 4.54078 15.3102 5.10999L21.5302 15.92C21.8376 16.4797 21.9907 17.111 21.9736 17.7494C21.9566 18.3877 21.7701 19.0101 21.4332 19.5526C21.0963 20.095 20.6211 20.5381 20.0565 20.8364C19.4919 21.1347 18.8581 21.2775 18.2202 21.25V21.25Z" stroke="#9a3412" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />

									<path d="M10.8809 17.15C10.8809 17.0021 10.9102 16.8556 10.9671 16.7191C11.024 16.5825 11.1074 16.4586 11.2125 16.3545C11.3175 16.2504 11.4422 16.1681 11.5792 16.1124C11.7163 16.0567 11.8629 16.0287 12.0109 16.03C12.2291 16.034 12.4413 16.1021 12.621 16.226C12.8006 16.3499 12.9398 16.5241 13.0211 16.7266C13.1023 16.9292 13.122 17.1512 13.0778 17.3649C13.0335 17.5786 12.9272 17.7745 12.7722 17.9282C12.6172 18.0818 12.4203 18.1863 12.2062 18.2287C11.9921 18.2711 11.7703 18.2494 11.5685 18.1663C11.3666 18.0833 11.1938 17.9426 11.0715 17.7618C10.9492 17.5811 10.8829 17.3683 10.8809 17.15ZM11.2409 14.42L11.1009 9.20001C11.0876 9.07453 11.1008 8.94766 11.1398 8.82764C11.1787 8.70761 11.2424 8.5971 11.3268 8.5033C11.4112 8.40949 11.5144 8.33449 11.6296 8.28314C11.7449 8.2318 11.8697 8.20526 11.9959 8.20526C12.1221 8.20526 12.2469 8.2318 12.3621 8.28314C12.4774 8.33449 12.5805 8.40949 12.6649 8.5033C12.7493 8.5971 12.8131 8.70761 12.852 8.82764C12.8909 8.94766 12.9042 9.07453 12.8909 9.20001L12.7609 14.42C12.7609 14.6215 12.6808 14.8149 12.5383 14.9574C12.3957 15.0999 12.2024 15.18 12.0009 15.18C11.7993 15.18 11.606 15.0999 11.4635 14.9574C11.321 14.8149 11.2409 14.6215 11.2409 14.42Z" fill="#9a3412" />

								</svg>

							</div>







							<div class="gsc-alert-content">

								<div class="feed-alert-header"><?php esc_html_e('Google Sheets Setup Required', 'cf7-google-sheets-connector'); ?></div>

								<p><?php esc_html_e('your selected Method is : ', 'cf7-google-sheets-connector'); ?><?php echo esc_html($selected_method); ?></p>

								<p><?php esc_html_e('To start sending form entries to Google Sheets, please connect your Google account first.', 'cf7-google-sheets-connector'); ?></p>

								<ul>

									<li><?php esc_html_e('✔ Click on the Sign in with Google button', 'cf7-google-sheets-connector'); ?></li>

									<li><?php esc_html_e('✔ Log in using your Google account', 'cf7-google-sheets-connector'); ?></li>

									<li><?php esc_html_e('✔ Select the Google account where your Sheets are stored', 'cf7-google-sheets-connector'); ?></li>

									<li><?php esc_html_e('✔ Grant access to: Google Drive & Google Sheets', 'cf7-google-sheets-connector'); ?></li>

									<li><?php esc_html_e('✔ Save the authentication code if prompted', 'cf7-google-sheets-connector'); ?></li>

								</ul>

								<a href="admin.php?page=wpcf7-google-sheet-config&tab=integration" class="gsc-alert-btn link-hover-white">

									<?php esc_html_e('Go to Integration Setup', 'cf7-google-sheets-connector'); ?>

								</a>

							</div>



						</div>

					</div>

				</div>

			<?php

			} elseif ($auth_method == 'cf7_service') {

				$selected_method = __('Service Account (Recommended).', 'cf7-google-sheets-connector');
			?>

				<!----Display msg without auth-->

				<div class="wrap w-100 m-0 gscf7-free">

					<div class="inner-wrap w-100 bg-white p-40">

						<div class="gsc-setup-alert">

							<div class="gsc-alert-icon">

								<svg width="30px" height="30px" viewBox="-0.5 0 25 25" fill="none" xmlns="http://www.w3.org/2000/svg">

									<path d="M18.2202 21.25H5.78015C5.14217 21.2775 4.50834 21.1347 3.94373 20.8364C3.37911 20.5381 2.90402 20.095 2.56714 19.5526C2.23026 19.0101 2.04372 18.3877 2.02667 17.7494C2.00963 17.111 2.1627 16.4797 2.47015 15.92L8.69013 5.10999C9.03495 4.54078 9.52077 4.07013 10.1006 3.74347C10.6804 3.41681 11.3346 3.24518 12.0001 3.24518C12.6656 3.24518 13.3199 3.41681 13.8997 3.74347C14.4795 4.07013 14.9654 4.54078 15.3102 5.10999L21.5302 15.92C21.8376 16.4797 21.9907 17.111 21.9736 17.7494C21.9566 18.3877 21.7701 19.0101 21.4332 19.5526C21.0963 20.095 20.6211 20.5381 20.0565 20.8364C19.4919 21.1347 18.8581 21.2775 18.2202 21.25V21.25Z" stroke="#9a3412" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />

									<path d="M10.8809 17.15C10.8809 17.0021 10.9102 16.8556 10.9671 16.7191C11.024 16.5825 11.1074 16.4586 11.2125 16.3545C11.3175 16.2504 11.4422 16.1681 11.5792 16.1124C11.7163 16.0567 11.8629 16.0287 12.0109 16.03C12.2291 16.034 12.4413 16.1021 12.621 16.226C12.8006 16.3499 12.9398 16.5241 13.0211 16.7266C13.1023 16.9292 13.122 17.1512 13.0778 17.3649C13.0335 17.5786 12.9272 17.7745 12.7722 17.9282C12.6172 18.0818 12.4203 18.1863 12.2062 18.2287C11.9921 18.2711 11.7703 18.2494 11.5685 18.1663C11.3666 18.0833 11.1938 17.9426 11.0715 17.7618C10.9492 17.5811 10.8829 17.3683 10.8809 17.15ZM11.2409 14.42L11.1009 9.20001C11.0876 9.07453 11.1008 8.94766 11.1398 8.82764C11.1787 8.70761 11.2424 8.5971 11.3268 8.5033C11.4112 8.40949 11.5144 8.33449 11.6296 8.28314C11.7449 8.2318 11.8697 8.20526 11.9959 8.20526C12.1221 8.20526 12.2469 8.2318 12.3621 8.28314C12.4774 8.33449 12.5805 8.40949 12.6649 8.5033C12.7493 8.5971 12.8131 8.70761 12.852 8.82764C12.8909 8.94766 12.9042 9.07453 12.8909 9.20001L12.7609 14.42C12.7609 14.6215 12.6808 14.8149 12.5383 14.9574C12.3957 15.0999 12.2024 15.18 12.0009 15.18C11.7993 15.18 11.606 15.0999 11.4635 14.9574C11.321 14.8149 11.2409 14.6215 11.2409 14.42Z" fill="#9a3412" />

								</svg>

							</div>





							<div class="gsc-alert-content">

								<div class="feed-alert-header"><?php esc_html_e('Google Sheets Setup Required', 'cf7-google-sheets-connector'); ?></div>

								<p><?php esc_html_e('your selected Method is : ', 'cf7-google-sheets-connector'); ?><?php echo esc_html($selected_method); ?></p>

								<p><?php esc_html_e('To connect Google Sheets using Service Account:', 'cf7-google-sheets-connector'); ?></p>

								<ul>

									<li><?php esc_html_e('✔ Go to Google Cloud Console', 'cf7-google-sheets-connector'); ?></li>

									<li><?php esc_html_e('✔ Create or select a project', 'cf7-google-sheets-connector'); ?></li>

									<li><?php esc_html_e('✔ Enable Google Sheets & Drive APIs', 'cf7-google-sheets-connector'); ?></li>

									<li><?php esc_html_e('✔ Create a Service Account', 'cf7-google-sheets-connector'); ?></li>

									<li><?php esc_html_e('✔ Generate and download the JSON key', 'cf7-google-sheets-connector'); ?></li>

									<li><?php esc_html_e('✔ Upload the JSON file here', 'cf7-google-sheets-connector'); ?></li>

									<li><?php esc_html_e('✔ Share your Google Sheet with the Service Account email', 'cf7-google-sheets-connector'); ?></li>

								</ul>

								<a href="<?php echo esc_html(admin_url('admin.php?page=wpcf7-google-sheet-config&tab=integration')); ?>" class="gsc-alert-btn link-hover-white">

									<?php esc_html_e('Go to Integration Setup', 'cf7-google-sheets-connector'); ?>

								</a>

							</div>

						</div>



					</div>

				<?php

			}

				?>

				</p>

			<?php

		}

		if ($show_setting == 1) {

			$form_data = '';
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from Google.
			if (isset($_GET['post'])) {

				$form_id = '';

				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from Google.
				if (isset($_GET['post'])) {

					$form_id = sanitize_text_field(
						// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from Google.
						wp_unslash($_GET['post'])
					);
				}

				global $wpdb;
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from Google.
				$form_id = isset($_GET['post'])

					? sanitize_text_field(wp_unslash($_GET['post'])) // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from Google.

					: '';
				$feeds_table    = $wpdb->prefix . 'cf7gs_feeds';
				$settings_table = $wpdb->prefix . 'cf7gs_settings';
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking whether a custom plugin table exists.
				$feed_id = $wpdb->get_var(

					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
					$wpdb->prepare(
						'SELECT id FROM `' . esc_sql($wpdb->prefix . 'cf7gs_feeds') . '` WHERE form_id = %d LIMIT 1',
						$form_id
					)
				);

				// Get settings data

				$form_data = '';

				if ($feed_id) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking whether a custom plugin table exists.
					$row = $wpdb->get_row(

						// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
						$wpdb->prepare(
							'SELECT * FROM `' . esc_sql($wpdb->prefix . 'cf7gs_settings') . '` WHERE feed_id = %d LIMIT 1',
							$feed_id
						),
						ARRAY_A
					);

					if (! empty($row)) {

						// convert to OLD format (important)

						$form_data = array(

							'sheet-name'     => $row['sheet_name'],

							'sheet-id'       => $row['sheet_id'],

							'sheet-tab-name' => $row['tab_name'],

							'tab-id'         => $row['tab_id'],

						);
					}
				}
			}

			?>

				<div class="gscf7-free">

					<ul id="contact-form-editor-tabs" class="ui-tabs-nav">

						<li class="ui-tab cf7-sub-tab-single-li cf7-sub-tab-active">

							<a href="#" class="cf7gs-tab-toggle" data-tab="cf7-sub-tab-single">

								<?php esc_html_e('Single Sheet Connection', 'cf7-google-sheets-connector'); ?>

							</a>

						</li>

						<li class="ui-tab cf7-sub-tab-multi-li">

							<a href="#" class="cf7gs-tab-toggle" data-tab="cf7-sub-tab-multi">

								<?php esc_html_e('Multi Sheet Connection', 'cf7-google-sheets-connector'); ?>

								<span class="edit-form-pro"><?php esc_html_e('Pro', 'cf7-google-sheets-connector'); ?></span>

							</a>

						</li>

					</ul>

					<div class="cf7-sub-tab-single cf7-sub-tab" style="display:block">


						<div class="gscf7-integration-box">

							<div class="gsc-google-auth-card mt-30 mb-30">
								<div>
									<div class="heading mt-0 mb-30"> <?php echo esc_html(__('Google Account Connection', 'cf7-google-sheets-connector')); ?>
										<span class="badge"><?php echo esc_attr($selected_method_display); ?></span>
									</div>
								</div>

								<div class="d-flex flex-wrap gap-20 justify-between align-center">

									<div class="gsc-google-auth-left d-flex flex-wrap align-center gap-15">

										<div class="gsc-google-icon">G</div>

										<div class="connected-account">

											<div class="gsc-connected-left d-flex">

												<span class="gsc-connected-label">
													<?php
													printf(
														/* translators: %s: email address of the connected Google account. */
														esc_html__('Connected Email Account: %s', 'cf7-google-sheets-connector'),
														'<span class="connected-account-manual gsc-connected-email">' . esc_html($connected_email) . '</span>'
													);
													?>
												</span>

											</div>

										</div>

									</div>

									<div class="gsc-google-auth-right">

										<div class="gsc-connected-pill">

											<span class="dot"></span>

											<?php echo esc_html(__(' Connected', 'cf7-google-sheets-connector')); ?>
										</div>

									</div>
								</div>

							</div>

						</div>
						<!-- Single sheet connection START -->

						<form method="post">



							<div class="gs-fields shadow-box mt-30 p-30">

								<div class="heading mt-0">

									<?php echo esc_html(__('Manual Google Sheets Configuration', 'cf7-google-sheets-connector')); ?>

								</div>

								<p><?php echo esc_html(__('Connect your Google Sheet by entering the required sheet information.', 'cf7-google-sheets-connector')); ?></p>

								<div class="row">

									<div class="col-6 res-top-20">

										<div class="form-group field-row mr-10">

											<label><?php echo esc_html(__('Sheet Name', 'cf7-google-sheets-connector')); ?>

												<span class="tooltip"

													data-tooltip="<?php echo esc_html(__('Enter the exact name of your Google Spreadsheet (as shown in Google Sheets).', 'cf7-google-sheets-connector')); ?>"

													data-tooltip-pos="right" data-tooltip-length="medium">

													<i class="fa-solid fa-circle-question help-icon"></i>

												</span>

											</label>

											<input type="text" class="form-control" name="cf7-gs[sheet-name]" id="gs-sheet-name"

												value="<?php echo (isset($form_data['sheet-name'])) ? esc_attr($form_data['sheet-name']) : ''; ?>" />

											<div class="input-msg d-none">

												<?php echo esc_html__('Please fill out this field', 'cf7-google-sheets-connector'); ?>

											</div>

										</div>

									</div>

									<div class="col-6 res-top-20">

										<div class="form-group field-row mr-10">

											<label><?php echo esc_html(__('Sheet ID', 'cf7-google-sheets-connector')); ?>

												<span class="tooltip"

													data-tooltip="<?php echo esc_html(__('Paste the Spreadsheet ID from your Google Sheet URL. (Example: ', 'cf7-google-sheets-connector')); ?> https://docs.google.com/spreadsheets/d/**SPREADSHEET_ID**/edit)"

													data-tooltip-pos="right" data-tooltip-length="medium">

													<i class="fa-solid fa-circle-question help-icon"></i>

												</span>

											</label>

											<input type="text" class="form-control" name="cf7-gs[sheet-id]" id="gs-sheet-id"

												value="<?php echo (isset($form_data['sheet-id'])) ? esc_attr($form_data['sheet-id']) : ''; ?>" />

											<div class="input-msg d-none">

												<?php echo esc_html__('Please fill out this field', 'cf7-google-sheets-connector'); ?>

											</div>

										</div>

									</div>

									<div class="col-6 mt-20">

										<div class="form-group field-row mr-10">

											<label

												for="edit-tab-name"><?php echo esc_html(__('Tab Name', 'cf7-google-sheets-connector')); ?>

												<span class="tooltip"

													data-tooltip="<?php echo esc_html(__('Enter the exact sheet tab name (e.g., Sheet1) from the bottom of your Google Spreadsheet.', 'cf7-google-sheets-connector')); ?>"

													data-tooltip-pos="right" data-tooltip-length="medium">

													<i class="fa-solid fa-circle-question help-icon"></i>

												</span>

											</label>

											<input type="text" class="form-control" id="cf7-gs[edit-tab-name]" name="cf7-gs[sheet-tab-name]"

												value="<?php echo (isset($form_data['sheet-tab-name'])) ? esc_attr($form_data['sheet-tab-name']) : ''; ?>">

											<div class="input-msg d-none">

												<?php echo esc_html__('Please fill out this field', 'cf7-google-sheets-connector'); ?>

											</div>

										</div>

									</div>

									<div class="col-6 mt-20">

										<div class="form-group field-row mr-10">

											<label><?php echo esc_html(__('Tab ID', 'cf7-google-sheets-connector')); ?>

												<span class="tooltip"

													data-tooltip="<?php echo esc_html(__('Get the Tab ID from your sheet URL after gid=.', 'cf7-google-sheets-connector')); ?>"

													data-tooltip-pos="right" data-tooltip-length="medium">

													<i class="fa-solid fa-circle-question help-icon"></i>

												</span>

											</label>

											<input type="text" class="form-control" name="cf7-gs[tab-id]" id="gs-tab-id"



												value="<?php echo (isset($form_data['tab-id'])) ? esc_attr($form_data['tab-id']) : ''; ?>" />

											<div class="input-msg d-none">

												<?php echo esc_html__('Please fill out this field', 'cf7-google-sheets-connector'); ?>

											</div>

										</div>

									</div>

								</div>



								<div class="sheet-url field-row">

									<?php
									if ((isset($form_data['sheet-name'])) && ! empty($form_data['sheet-name']) && (isset($form_data['sheet-id'])) && (! empty($form_data['sheet-id'])) && (isset($form_data['sheet-tab-name'])) && (! empty($form_data['sheet-tab-name'])) && (isset($form_data['tab-id']))) {

										$link = 'https://docs.google.com/spreadsheets/d/' . $form_data['sheet-id'] . '/edit#gid=' . $form_data['tab-id'];

									?>

										<a class=" sheet-url-cf7 common-sheet-url btn text-dark text-decoration-none mt-30" href="<?php echo esc_url($link); ?>" target="_blank" class="cf7_gs_link" title="<?php echo esc_html__('View Spreadsheet', 'cf7-google-sheets-connector'); ?>">
											<i class="fa-regular fa-eye"></i></a>

									<?php } ?>

									<?php
									if ((isset($form_data['sheet-name'])) && ! empty($form_data['sheet-name']) && (isset($form_data['sheet-id'])) && (! empty($form_data['sheet-id'])) && (isset($form_data['sheet-tab-name'])) && (! empty($form_data['sheet-tab-name'])) && (isset($form_data['tab-id']))) {

									?>


									<?php } ?>

								</div>

							</div>

						</form>

						<?php

						$cf7_service_json = get_option('gs_cf7_service_account_json');

						$gs_cf7_auth_method = get_option('gs_cf7_auth_method');

						$cf7_service_email = '';

						// Decode JSON safely

						if (! empty($cf7_service_json)) {

							$decoded = json_decode($cf7_service_json, true);

							if (! empty($decoded) && isset($decoded['client_email'])) {

								$cf7_service_email = trim($decoded['client_email']);
							}
						}

						// Correct auth method check

						if (! empty($cf7_service_email) && $gs_cf7_auth_method === 'cf7_service') {
						?>



							<div class="sheet-email cf7gsc-notes cf7gsc-notes2" id="sheet-email">

								<?php

								$doc = new CF7GSC_googlesheet();

								$doc->auth();

								$sheet_id = ! empty($form_data) && isset($form_data['sheet-id'])

									? $form_data['sheet-id']

									: '';

								// Fallback to saved sheet

								if (empty($sheet_id)) {

									$getsheets_id = get_option('gs_sheetId');

									if (! empty($getsheets_id) && ! empty($saved_sheet_name) && isset($getsheets_id[$saved_sheet_name])) {

										$sheet_details = $getsheets_id[$saved_sheet_name];

										$sheet_id = isset($sheet_details['id']) ? $sheet_details['id'] : '';
									}
								}

								$sheet_id = trim($sheet_id);

								$result = $doc->check_sheet_access($sheet_id);

								// Safe result check

								if (! empty($result) && isset($result['status']) && $result['status']) {
								?>



									<div class="gsc-sheet-info mt-30">

										<!-- Header -->

										<div class="gsc-sheet-status-header">

											<div class="gsc-status-headings fw-600">

												<?php echo esc_html__('Google Sheets Connection', 'cf7-google-sheets-connector'); ?>

											</div>

										</div>



										<!-- Description -->

										<p class="gsc-sheet-status-desc">

											<?php echo esc_html__('Your Google Spreadsheet is securely connected and ready to receive form submissions in real time.', 'cf7-google-sheets-connector'); ?>

										</p>



										<!-- Email Box -->

										<div class="gsc-sheet-email-box">

											<span class="email-text email-success-service">

												<?php echo esc_html($cf7_service_email); ?>

											</span>

											<span class="gsc-sheet-badge connected">

												<?php echo esc_html__('Connected Successfully', 'cf7-google-sheets-connector'); ?>

											</span>

										</div>



										<!-- Help Info -->

										<ul class="gsc-sheet-status-info">

											<li class="success">

												<?php echo esc_html__('Green status means the spreadsheet access is configured correctly.', 'cf7-google-sheets-connector'); ?>

											</li>

											<li class="error">

												<?php echo esc_html__('Red status means permission is missing. Please grant editor access.', 'cf7-google-sheets-connector'); ?>

											</li>

										</ul>

									</div>



								<?php } else { ?>



									<div class="gsc-sheet-info mt-30">

										<div class="gsc-status-headings fw-600">

											<?php echo esc_html__('Sharing Required', 'cf7-google-sheets-connector'); ?>

										</div>



										<p>

											<?php echo esc_html__('Please share your Google Spreadsheet with the following service account to enable automatic syncing.', 'cf7-google-sheets-connector'); ?>

										</p>



										<p>

											<?php echo esc_html__('After sharing the spreadsheet, refresh the page to view the updated sharing status.', 'cf7-google-sheets-connector'); ?>

										</p>



										<div class="gsc-email-box d-flex align-center justify-between email-unsuccess-service">

											<div class="gsc-service-email">

												<?php echo esc_html($cf7_service_email); ?>

											</div>



											<a

												data-email="<?php echo esc_attr($cf7_service_email); ?>"

												class="gsc-copy-btn-feed-setting text-decoration-none link-hover-white"

												id="copy-service-email">

												<?php echo esc_html__('Copy', 'cf7-google-sheets-connector'); ?>

											</a>



											<div class="gsc-copy-msg d-none">

												<?php echo esc_html__('Copied successfully!', 'cf7-google-sheets-connector'); ?>

											</div>

										</div>



										<ul class="gsc-status-list">

											<li class="success">

												<?php echo esc_html__('Green email indicates the spreadsheet is shared successfully.', 'cf7-google-sheets-connector'); ?>

											</li>

											<li class="error">

												<?php echo esc_html__('Red email indicates access is missing. Please grant permission.', 'cf7-google-sheets-connector'); ?>

											</li>

										</ul>

									</div>



								<?php } ?>

							</div>



						<?php } ?>

						<!--Start pro Features-->

						<div class="edit-gs-pro-card shadow-box mt-40 mb-30">

							<div class="edit-gs-pro-header p-20">

								<div class="edit-gs-pro-icon">

									<span class="pro-badge">

										<i class="fas fa-lock gsc-pro-icon"></i>

									</span>

								</div>

								<div class="edit-gs-pro-title">

									<div class="heading mt-0">

										<?php echo esc_html(__('Unlock Advanced Features with Form Feeds', 'cf7-google-sheets-connector')); ?>

									</div>

									<div class="d-flex flex-wrap align-items-center gap-15">

										<span class="edit-gs-pro-badge">

											<?php echo esc_html(__('Advanced options are available in PRO', 'cf7-google-sheets-connector')); ?>

										</span>

										<span class="edit-gs-upgrade-btn">

											<a href="https://www.gsheetconnector.com/docs/cf7-gsheetconnector" target="_blank" class="text-decoration-none link-hover-white">

												<?php echo esc_html(__('Get Advanced Features', 'cf7-google-sheets-connector')); ?>

												<svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">

													<path d="M0.166016 10.6584L8.99102 1.83341H3.49935V0.166748H11.8327V8.50008H10.166V3.00841L1.34102 11.8334L0.166016 10.6584Z" fill="white"></path>

												</svg>

											</a>

										</span>

									</div>

								</div>

							</div>



							<!-- Toggle checkbox -->

							<input type="checkbox" id="toggle-features">



							<div class="edit-gs-pro-features p-20">



								<div class="edit-gs-feature-col">

									<div class="mb-20">

										<a href="#auto-googlesheet-configuration">

											<?php echo esc_html(__('Automatic Google Sheets Configuration', 'cf7-google-sheets-connector')); ?>

										</a>

									</div>



									<div class="gsc-pro-grid">

										<ul>

											<li><?php esc_html_e('Auto fetch Google Sheets list', 'cf7-google-sheets-connector'); ?></li>

											<li><?php esc_html_e('Auto detect sheet tabs', 'cf7-google-sheets-connector'); ?></li>

											<li><?php esc_html_e('One-click configuration', 'cf7-google-sheets-connector'); ?></li>

											<li><?php esc_html_e('Real-time entry sync', 'cf7-google-sheets-connector'); ?></li>

										</ul>

									</div>

								</div>



								<div class="edit-gs-feature-col">

									<div class="mb-20">

										<a href="#field-mapping">

											<?php echo esc_html(__('Select Fields to Sync', 'cf7-google-sheets-connector')); ?>

										</a>

									</div>



									<div class="gsc-pro-grid">

										<ul>

											<li><?php esc_html_e('Drag & drop field reordering', 'cf7-google-sheets-connector'); ?></li>

											<li><?php esc_html_e('Rename column headers', 'cf7-google-sheets-connector'); ?></li>

											<li><?php esc_html_e('Select specific fields to sync', 'cf7-google-sheets-connector'); ?></li>

										</ul>

									</div>

								</div>



								<div class="edit-gs-feature-col">

									<div class="mb-20">

										<a href="#conditional-logic">

											<?php echo esc_html(__('Conditional Logic', 'cf7-google-sheets-connector')); ?>

										</a>

									</div>



									<div class="gsc-pro-grid">

										<ul>

											<li><?php esc_html_e('Apply rules based on form values', 'cf7-google-sheets-connector'); ?></li>

											<li><?php esc_html_e('Sync data only when conditions match', 'cf7-google-sheets-connector'); ?></li>

											<li><?php esc_html_e('Filter unwanted or incomplete entries', 'cf7-google-sheets-connector'); ?></li>

											<li><?php esc_html_e('Create dynamic workflows automatically', 'cf7-google-sheets-connector'); ?></li>

											<li><?php esc_html_e('Map data conditionally to sheet columns', 'cf7-google-sheets-connector'); ?></li>

											<li><?php esc_html_e('Support multiple conditions (AND / OR)', 'cf7-google-sheets-connector'); ?></li>

											<li><?php esc_html_e('Improve accuracy and reduce extra data', 'cf7-google-sheets-connector'); ?></li>

										</ul>

									</div>

								</div>



								<div class="edit-gs-feature-col">

									<div class="mb-20">

										<a href="#header-settings-sheet-sorting">

											<?php echo esc_html(__('Header Settings', 'cf7-google-sheets-connector')); ?>

										</a>

									</div>



									<div class="gsc-pro-grid">

										<ul>

											<li><?php esc_html_e('Freeze header row', 'cf7-google-sheets-connector'); ?></li>

											<li><?php esc_html_e('Custom font styling', 'cf7-google-sheets-connector'); ?></li>

											<li><?php esc_html_e('Header & row color control', 'cf7-google-sheets-connector'); ?></li>

											<li><?php esc_html_e('Sort by any column', 'cf7-google-sheets-connector'); ?></li>

											<li><?php esc_html_e('Download spreadsheet as file', 'cf7-google-sheets-connector'); ?></li>

										</ul>

									</div>

								</div>



							</div>
							<div class="edit-gs-pro-footer">

								<label for="toggle-features" class="edit-gs-show-btn show">

									<?php echo esc_html(__('Show Features ▼', 'cf7-google-sheets-connector')); ?>

								</label>



								<label for="toggle-features" class="edit-gs-show-btn hide">

									<?php echo esc_html(__('Hide Features ▲', 'cf7-google-sheets-connector')); ?>

								</label>

							</div>



						</div>

						<!--End Pro Feature-->









						<div class="feed-informtion-inner shadow-box mt-40 p-30">

							<!-- Free setting End -->



							<div class="system-debug-logs" id="opener">

								<a href="https://www.gsheetconnector.com/cf7-google-sheets-connector" class="pro-link" target="_blank" style="text-decoration: none;"></a>

								<div class="auto-section shadow-box p-30" id="auto-googlesheet-configuration" name="auto-googlesheet-configuration" style="display:block;">

									<div class="gsc-fields">

										<div class="sheet-details ">

											<div class="heading mt-0"><?php echo esc_html(__('Automatic Google Sheets Configuration', 'cf7-google-sheets-connector')); ?>

												<span class="pro-ver"><?php echo esc_html(__('Pro', 'cf7-google-sheets-connector')); ?></span>

											</div>

											<p><?php echo esc_html(__('Automatic configure your Google Sheet and start syncing form submissions in real time.', 'cf7-google-sheets-connector')); ?></p>

											<div class="row">

												<div class="col-4">

													<div class="mr-10">

														<label><?php echo esc_html(__('Sheet Name', 'cf7-google-sheets-connector')); ?></label>

														<select name="gscf-ff[gsc-fluentform-sheet-id]" class="auto-select-display w-100 mt-5" id="gsc-fluentform-sheet-id">

															<option value="" disabled>

																<?php echo esc_html(__('Select', 'cf7-google-sheets-connector')); ?> </option>

															<option value="create_new" disabled>

																<?php echo esc_html(__('Create New', 'cf7-google-sheets-connector')); ?> </option>

														</select>

													</div>

												</div>

												<span class="error_msg" id="error_spread"></span>

												<span class="loading-sign">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>

												<i class="errorSelect errorSelectsheet"></i>

												<div class="col-4">

													<label><?php echo esc_html(__('Sheet Tab Name', 'cf7-google-sheets-connector')); ?></label>

													<select name="gscf-ff[gs-sheet-tab-name]" class="auto-select-display w-100 mt-5" id="gscf7-sheet-tab-name">

														<option value="" disabled>

															<?php echo esc_html(__('Select', 'cf7-google-sheets-connector')); ?> </option>

													</select>

												</div>

											</div>

										</div>

									</div>

								</div>

								<div class="feed-setting-pro-wrapper">

									<div class="form-fields-list gscf7-list-set shadow-box mt-40 p-30" id="field-mapping" name="field-mapping">

										<div class="gscf7-color-code">

											<div class="color-ffgs">

												<div class="heading"><?php echo esc_html('Select Fields to Sync', 'cf7-google-sheets-connector'); ?><span class="pro-ver"><?php echo esc_html(__('Pro', 'cf7-google-sheets-connector')); ?></span></div>

												<p class="gsc-pro-desc"><?php echo esc_html(__('Enable the fields you want to send to Google Sheets and rename columns if needed.', 'cf7-google-sheets-connector')); ?></p>



											</div>

											<div class="gscf7-color-code flex-wrap d-flex align-center gap-20 pt-10">

												<div class="color-ffgs field-type-form">

													<span class="field-list-pill align-center fw-700"><?php echo esc_html(__('Field List', 'cf7-google-sheets-connector')); ?></span>



												</div>

												<div class="color-ffgs field-type-system">

													<span class="align-center fw-700"><?php echo esc_html(__('Submission Info', 'cf7-google-sheets-connector')); ?></span>

												</div>

												<div class="color-ffgs custom-mail-tags">

													<span class="align-center fw-700"><?php echo esc_html(__('Custom Mail Tags', 'cf7-google-sheets-connector')); ?></span>

												</div>

											</div>

										</div>

										<div class="toggle-button select-all-toggle gsc-pro-content">

											<div class="mt-40 select-all-field mb-20">

												<label class="switch">

													<input type="checkbox" id="select-all-checkbox" checked disabled>

													<span class="slider round button-toggle"></span> </label>

												<span class="label-text"><?php echo esc_html('Select All Fields', 'cf7-google-sheets-connector'); ?></span>

											</div>

											<div id="gscf7-sortable" class="ui-sortable connected-sortable">

												<?php $this->display_form_fields($form_id, $post); ?>

											</div>

										</div>

									</div>



									<!-- SECTION 2:Conditional Logic-->

									<div class="settings-card  shadow-box mt-40 p-30" id="conditional-logic">



										<div class="mt-0 heading">

											<?php echo esc_html(__('Conditional Logic', 'cf7-google-sheets-connector')); ?>

											<span class="pro-ver"><?php echo esc_html(__('Pro', 'cf7-google-sheets-connector')); ?></span>

										</div>

										<p class="mb-30">

											<?php echo esc_html__('Control how and when your form data is sent to Google Sheets using powerful conditional rules. Automatic filter, route, and manage submissions based on user input.', 'cf7-google-sheets-connector'); ?>

										</p>



										<div class="gscfrmnt-cards gscfrmnt-card setting-row">



											<div class="misc-conditional-inner30 misc-options-inner-color-multi-cf7gs-hidden">



												<input type="hidden"

													id="getfieldList"

													value="">



												<div>

													<?php echo esc_html(__('Process this feed if', 'cf7-google-sheets-connector')); ?>

													<select name="cf7-gs30[enable_conditional_logic_type_feed]" class="enableConditionalLogic conditional-logic">

														<option value="all"><?php echo esc_html(__('All', 'cf7-google-sheets-connector')); ?></option>

														<option value="any" disabled><?php echo esc_html(__('Any', 'cf7-google-sheets-connector')); ?></option>

													</select>

													<?php echo esc_html(__('of the following match:', 'cf7-google-sheets-connector')); ?>

												</div>



												<div class="mt-30 d-flex flex-wrap gap-10">



													<select name="cf7-gs30[conditional_feed][0][enable_conditional_logic_field_name_feed]" class="enableConditionalLogic">

														<option value="your-name"><?php echo esc_html(__('your-name', 'cf7-google-sheets-connector')); ?></option>

														<option value="your-email" disabled><?php echo esc_html(__('your-email', 'cf7-google-sheets-connector')); ?></option>

														<option value="your-subject" disabled><?php echo esc_html(__('your-subject', 'cf7-google-sheets-connector')); ?></option>

														<option value="your-message" disabled><?php echo esc_html(__('your-message', 'cf7-google-sheets-connector')); ?></option>

													</select>



													<select name="cf7-gs30[conditional_feed][0][enable_conditional_logic_rule_select_feed]" class="enableConditionalLogic">

														<option value="is"><?php echo esc_html(__('is', 'cf7-google-sheets-connector')); ?></option>

														<option value="isnot" disabled><?php echo esc_html(__('is not', 'cf7-google-sheets-connector')); ?></option>

														<option value="greaterthan" disabled><?php echo esc_html(__('greater than', 'cf7-google-sheets-connector')); ?></option>

														<option value="lessthan" disabled><?php echo esc_html(__('less than', 'cf7-google-sheets-connector')); ?></option>

														<option value="contains" disabled><?php echo esc_html(__('contains', 'cf7-google-sheets-connector')); ?></option>

														<option value="starts_with" disabled><?php echo esc_html(__('starts with', 'cf7-google-sheets-connector')); ?></option>

														<option value="ends_with" disabled><?php echo esc_html(__('ends with', 'cf7-google-sheets-connector')); ?></option>

													</select>



													<input type="text"

														name="cf7-gs30[conditional_feed][0][enable_conditional_logic_rule_value_feed]"

														value=""

														placeholder="Enter a value"

														class="enableConditionalLogic form-control conditional-form-control" disabled>



													<button type="button"

														class="add_field_choice-multi circle-plus"

														data-id="30">

														+

													</button>



												</div>



												<div class="conditional-logic-container-multi30"></div>



												<table class="alt-color-fields gsheet-table three-cols">

													<input type="hidden"

														name="selected_field_lists_num_multi"

														class="selected_field_lists_num_multi"

														value="1">

												</table>



											</div>



										</div>



									</div>



									<div class="freez_order_sort form-fields-list gscf7-list-set shadow-box mt-40 p-30">

										<div id="header-settings-sheet-sorting" name="header-settings-sheet-sorting">

											<div class="heading mt-0"><?php echo esc_html(__('Header Settings', 'cf7-google-sheets-connector')); ?><span class="pro-ver"><?php echo esc_html(__('Pro', 'cf7-google-sheets-connector')); ?></span></div>

											<p><?php echo esc_html(__('Customize the appearance and behavior of your sheet headers and rows for better readability and organization.', 'cf7-google-sheets-connector')); ?></p>

											<!-- SECTION 1: Header Behavior -->

											<div class="header-styling-sheet d-flex gap-20">

												<div class="settings-card mb-20 w-100 bg-white">

													<div class="mt-0 header-settings-ineer-size fw-600 mb-20"><?php echo esc_html(__('Header Behavior', 'cf7-google-sheets-connector')); ?></div>

													<div class="gscfrmnt-cards gscfrmnt-card setting-row">

														<div class="toggle-button freeze-header-toggle d-flex align-items-center justify-between mb-15">

															<span class="label-text fw-400"><?php echo esc_html(__('Freeze Header', 'cf7-google-sheets-connector')); ?></span>

															<label class="switch">

																<input type="checkbox" id="freeze-header-checkbox" name="gscf-ff[freeze_header]" value="true" checked="" disabled>

																<span class="slider round button-toggle"></span>

															</label>

														</div>

													</div>

													<div class="sheet_sorting sheet_formatting mt-30 mb-30">

														<div class="gscfrmnt-cards">

															<div class="toggle-button sheet-sorting-toggle d-flex align-items-center justify-between">

																<span class="label-text fw-400"><?php echo esc_html(__('Sheet Sorting', 'cf7-google-sheets-connector')); ?></span>

																<label class="switch" for="sheet-sorting-checkbox">

																	<input type="checkbox" id="sheet-sorting-checkbox" name="gscf-ff[sheet_sorting]" value="1" checked="" disabled>

																	<span class="slider round button-toggle"></span>

																</label>

															</div>

														</div>

														<div class="sheet-sorting-settings" id="sheet-sorting-settings">

															<div class="settings-grid">

																<div class="gscfrmnt-row form-group">

																	<label for="sort-column-name">

																		<?php echo esc_html__('Sort Column', 'cf7-google-sheets-connector'); ?>

																	</label>

																	<select id="sort-column-name" name="gscf-ff[sort_column]">

																	</select>

																</div>

																<div class="gscfrmnt-row form-group">

																	<label for="sort-order"><?php echo esc_html__('Sort Order', 'cf7-google-sheets-connector'); ?></label>

																	<select id="sort-order" name="gscf-ff[sort_order]">

																		<option value="ASCENDING"><?php echo esc_html__('Ascending', 'cf7-google-sheets-connector'); ?></option>

																		<option value="DESCENDING" disabled><?php echo esc_html__('Descending', 'cf7-google-sheets-connector'); ?></option>

																	</select>

																</div>

															</div>

														</div>

													</div>



													<div class="mt-0 header-settings-ineer-size fw-600 mb-20"><?php echo esc_html__('Header Style', 'cf7-google-sheets-connector'); ?></div>

													<div class="sheet_formatting setting-row">

														<div class="gscfrmnt-sheet_formatting gscfrmnt-sheet_formatting">

															<div class="toggle-button sheet_formatting-header-toggle d-flex align-items-center justify-between mb-15">

																<span class="label-texts  fw-400"><?php echo esc_html(__('Header Appearance', 'cf7-google-sheets-connector')); ?> </span>

																<label class="switch" for="sheet_formatting-header-checkbox">

																	<input type="checkbox" id="sheet_formatting-header-checkbox" name="gscf-ff[sheet_formatting_header]" value="1" checked="" disabled>

																	<span class="slider round button-toggle"></span>

																</label>

															</div>

														</div>

														<div class="font-styling-settings" id="font-styling-settings">

															<div class="settings-grid">

																<div class="font-style row-format form-group">

																	<label for="font-size"><?php echo esc_html__('Font Style', 'cf7-google-sheets-connector'); ?></label>

																	<div class="d-flex gap-5">

																		<label class="style-btn active"><input type="checkbox" name="font_styles[]" class="toggle-input active" value="normal">

																			<?php echo esc_html__('Normal', 'cf7-google-sheets-connector'); ?></label>

																		<label class="style-btn"><input type="checkbox" name="font_styles[]" value="italic" disabled>

																			<?php echo esc_html__('Italic', 'cf7-google-sheets-connector'); ?></label>

																		<label class="style-btn"><input type="checkbox" name="font_styles[]" value="bold" disabled><?php echo esc_html__('Bold', 'cf7-google-sheets-connector'); ?></label>

																	</div>

																</div>



																<div class="font-size row-format form-group">

																	<label for="font-size"><?php echo esc_html__('Font Size', 'cf7-google-sheets-connector'); ?></label>

																	<div class="gs-font-size">

																		<div class="gs-select-box">

																			<select id="font-size" name="gscf-ff[font_size]" disabled>

																				<option value="" selected><?php echo esc_html__('Select size', 'cf7-google-sheets-connector'); ?></option>

																				<option><?php echo esc_html__('11', 'cf7-google-sheets-connector'); ?></option>

																				<option><?php echo esc_html__('12', 'cf7-google-sheets-connector'); ?></option>

																				<option><?php echo esc_html__('13', 'cf7-google-sheets-connector'); ?></option>

																				<option><?php echo esc_html__('14', 'cf7-google-sheets-connector'); ?></option>

																				<option><?php echo esc_html__('15', 'cf7-google-sheets-connector'); ?></option>

																			</select>

																		</div>

																	</div>

																</div>

																<div class="font-color row-format form-group">

																	<label for="font-color"><?php echo esc_html__('Font Color', 'cf7-google-sheets-connector'); ?></label>

																	<input type="color" id="font-color" name="gscf-ff[font_color]" class="bg-color-set-input" value="#000000" disabled>

																</div>



															</div>

														</div>

													</div>

												</div>



												<!-- SECTION 2: Header Appearance -->

												<div class="settings-card  mb-20 w-100 bg-white">

													<div class="sheet_formatting">

														<div class="mt-0 header-settings-ineer-size fw-600 mb-20"><?php echo esc_html__('Row Style', 'cf7-google-sheets-connector'); ?></div>

														<div class="gscfrmnt-sheet_formatting_row gscfrmnt-sheet_formatting_row">

															<div class="toggle-button sheet_formatting-row-toggle d-flex align-items-center justify-between">

																<span class="label-texts  fw-400"><?php echo esc_html__('Color Appearance', 'cf7-google-sheets-connector'); ?> </span>

																<label class="switch" for="sheet_formatting-row-checkbox">

																	<input type="checkbox" id="sheet_formatting-row-checkbox" name="gscf-ff[sheet_formatting_row]" value="1" checked="" disabled>

																	<span class="slider round button-toggle"></span>

																</label>

															</div>

														</div>

														<div class="font-styling-settings-row" id="font-styling-settings-row">

															<div class="settings-grid">



																<div class="gscfrmnt-cards-sbg gscfrmnt-cards-sbg form-group">



																	<label for="gscfrmnt-header-color" class="button-gscfrmnt-toggle-color"></label>

																	<label>

																		<?php echo esc_html__('Header Background', 'cf7-google-sheets-connector'); ?> </label>

																	<input type="color" id="header-color" name="gscf-ff[header-color]" class="bg-color-set-input" value="#000000" disabled>

																</div>

																<div class="settings-grid">

																	<!-- ODD ROW COLOR -->

																	<div class="form-group">

																		<label for="odd-color"><?php echo esc_html__('Odd Row Color', 'cf7-google-sheets-connector'); ?></label>

																		<input type="color"

																			id="odd-color"

																			name="gscf-ff[odd_color]"

																			class="bg-color-set-input"

																			value="#ffffff" disabled>

																	</div>



																	<!-- EVEN ROW COLOR -->

																	<div class="form-group">

																		<label for="even-color"><?php echo esc_html__('Even Row Color', 'cf7-google-sheets-connector'); ?></label>

																		<input type="color"

																			id="even-color"

																			name="gscf-ff[even_color]"

																			class="bg-color-set-input"

																			value="#f5f5f5" disabled>

																	</div>

																</div>

															</div>

														</div>

													</div>

													<!-- SECTION 3: Row Appearance -->

													<div class="sheet_formatting">



														<div class="gscfrmnt-sheet_formatting_row gscfrmnt-sheet_formatting_row">

															<div class="toggle-button sheet_formatting-row-toggle d-flex align-items-center justify-between">

																<span class="label-texts  fw-400"><?php echo esc_html__('Row Appearance', 'cf7-google-sheets-connector'); ?> </span>

																<label class="switch" for="sheet_formatting-row-checkbox">

																	<input type="checkbox" id="sheet_formatting-row-checkbox" name="gscf-ff[sheet_formatting_row]" value="1" checked="" disabled>

																	<span class="slider round button-toggle"></span>

																</label>

															</div>

														</div>

														<div class="font-styling-settings-row" id="font-styling-settings-row">

															<div class="settings-grid">



																<!-- FONT STYLE -->

																<div class="font-style row-format form-group">

																	<label><?php echo esc_html__('Font Style', 'cf7-google-sheets-connector'); ?></label>

																	<div class="d-flex gap-5">

																		<label class="style-btn active">

																			<input type="checkbox" name="font_styles[]" value="normal" checked> <?php echo esc_html__('Normal', 'cf7-google-sheets-connector'); ?>

																		</label>

																		<label class="style-btn">

																			<input type="checkbox" name="font_styles[]" value="italic" disabled> <?php echo esc_html__('Italic', 'cf7-google-sheets-connector'); ?>

																		</label>

																		<label class="style-btn">

																			<input type="checkbox" name="font_styles[]" value="bold" disabled> <?php echo esc_html__('Bold', 'cf7-google-sheets-connector'); ?>

																		</label>

																	</div>

																</div>



																<!-- FONT SIZE -->

																<div class="font-size row-format form-group">

																	<label for="font-size-row"><?php echo esc_html__('Font Size', 'cf7-google-sheets-connector'); ?></label>

																	<div class="gs-font-size">

																		<div class="gs-select-box">

																			<select id="font-size-row" name="gscf-ff[font_size_row]" disabled>

																				<option value="14" selected><?php echo esc_html__('Select size', 'cf7-google-sheets-connector'); ?></option>

																				<option><?php echo esc_html__('11', 'cf7-google-sheets-connector'); ?></option>

																				<option><?php echo esc_html__('12', 'cf7-google-sheets-connector'); ?></option>

																				<option><?php echo esc_html__('13', 'cf7-google-sheets-connector'); ?></option>

																				<option><?php echo esc_html__('14', 'cf7-google-sheets-connector'); ?></option>

																				<option><?php echo esc_html__('15', 'cf7-google-sheets-connector'); ?></option>

																			</select>

																		</div>

																	</div>

																</div>



																<!-- FONT COLOR -->

																<div class="font-color row-format form-group">

																	<label for="font-color-row"><?php echo esc_html__('Font Color', 'cf7-google-sheets-connector'); ?></label>

																	<input type="color"

																		id="font-color-row"

																		name="gscf-ff[font_color]"

																		class="bg-color-set-input"

																		value="#000000" disabled>

																</div>

															</div>

														</div>

													</div>



													<!-- SECTION 4: Spreadsheet Download -->

													<div id="spreadsheet-download-sync" name="spreadsheet-download-sync">

														<div class="settings-card mt-30 w-100 bg-white">

															<div class="toggle-button sheet-sorting-toggle d-flex align-items-center justify-between" id="downloads-toggle-checkbox">

																<span class="label-text  fw-400"><?php echo esc_html(__('Spreadsheet Download', 'cf7-google-sheets-connector')); ?></span>

																<label class="switch" for="download-toggle-checkbox">

																	<input type="checkbox" id="download-toggle-checkbox" name="gscf-ff[download_spreadsheet]" value="1" checked="" disabled>

																	<span class="slider round button-toggle"></span>

																</label>

															</div>

															<div id="download-button-wrapper" class="mt-15">

																<!-- style="display:none;"> -->

																<a class="sheet-url-fluentform common-sheet-url text-dark fw-700 download-spreadsheet" hover-tooltip="Spreadsheet Download">

																	<i class="fa-regular fa-file-zipper text-dark fw-500 mr-5"></i><?php echo esc_html(__('Download', 'cf7-google-sheets-connector')); ?> </a>

															</div>

														</div>

													</div>

												</div>

											</div>

										</div>



									</div>

								</div>



							</div>



						</div>



						<div id="opener" class="d-none">



						<?php

						include GS_CONNECTOR_PATH . 'includes/pages/gs-field-list.php';
					}

						?>



						</div>

					</div><!-- #end -->



					<!-- Multi sheet connection START-->

					<div class="cf7-sub-tab-multi cf7-sub-tab multisheetcf7">

						<div id="opener2">

							<?php

							if ($show_setting == 1) {

								include GS_CONNECTOR_PATH . 'includes/pages/multisheet-sheets-connection.php';
							}

							?>

						</div>

					</div>

					<!-- Multi sheet connection END-->



				<?php
			}



			/**

			 * Function - fetch contact form list that is connected with google sheet

			 * @since 2.1
			 */
			public function get_forms_connected_to_sheet()
			{
				global $wpdb;

				/*
				 * Row model: one row PER FEED -- the same join the feed list on the
				 * settings screen uses.
				 *
				 * cf7gs_feeds.form_id    -> forms (many feeds per form)
				 * cf7gs_settings.feed_id -> feeds (the settings row belongs to the
				 *                                  FEED, not directly to the form)
				 *
				 * Settings used to be joined to the form instead (`s.form_id = p.ID`
				 * next to `f.form_id = p.ID`), which is a cross join: every settings
				 * row of a form was paired with every feed of that form, so a form
				 * with N feeds produced N*N rows. On a site whose feeds/settings
				 * tables had been inflated by the repeating legacy migration that
				 * reached ~440k rows for a single form and exhausted the memory
				 * limit while wpdb built the result set. Joining settings through
				 * feed_id keeps it at one row per feed.
				 */
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table join, no caching needed.
				$query = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT
                       p.ID,
                       p.post_title,
                       s.sheet_name,
                       s.sheet_id,
                       s.tab_id,
                       s.tab_name,
                       f.form_id,
                       f.id AS feed_id,
                       f.feed_name,
                       f.status,
                       f.is_default
                   FROM `{$wpdb->posts}` p
                   INNER JOIN `{$wpdb->prefix}cf7gs_feeds` f
                       ON p.ID = f.form_id
                   INNER JOIN `{$wpdb->prefix}cf7gs_settings` s
                       ON s.feed_id = f.id
                   WHERE p.post_type = %s
                   ORDER BY p.ID DESC, f.id ASC",
						'wpcf7_contact_form'
					)
				);

				return $query;
			}

			/**

			 * Function - display contact form fields to be mapped to google sheet

			 * @param int $form_id

			 * @since 1.0
			 */
			public function display_form_fields($form_id)
			{

				?>



					<?php

					// ================================================================

					// ENTRY ID CARD — Always rendered last, after all form fields

					// ================================================================

					$entry_placeholder = 'entry-id';

					?>

					<div class="card form-field-toggle active ui-sortable-handle">

						<div class="card-content">

							<div class="toggle-button field_list_bg">

								<i class="dashicons dashicons-edit field-edit-icon"></i>



								<!-- Toggle: always checked + disabled (locked) -->

								<label for="gs-custom-ck"

									class="switch entry-lock">



									<input

										type="checkbox"

										class="fcheckBoxClass toggle-input child-toggle checkAllClass check-toggle-cf7"

										id="gs-custom-ck"

										name="gs-custom-ck"

										data-id="gs-cstm-chk"

										data-list="field_list"

										list-design="field_listClass"

										data-value="entry_id"

										data-place="<?php echo esc_attr($entry_placeholder); ?>"

										value="1"

										checked

										disabled>

									<span class="slider round button-toggle  field-list-new"></span>

								</label>



								<span class="drag-icon">

									<i class="fas fa-grip-vertical drag-icon"></i>

								</span>



								<!-- Label -->

								<div class="label label-text card-label">

									entry_id

								</div>



								<!-- Hidden: key -->

								<input

									type="hidden"

									value="entry_id"

									name="gs-custom-header-key">



								<!-- Hidden: placeholder -->

								<input

									type="hidden"

									value="<?php echo esc_attr($entry_placeholder); ?>"

									name="gs-custom-header-placeholde">



								<!-- Text input: always readonly, always "entry_id" -->

								<input

									type="text"

									data-id="gs-entry-id"

									class="field-input from-input-change d-none"

									name="gs-custom-header"

									value="entry_id"

									placeholder="<?php echo esc_attr($entry_placeholder); ?>"

									readonly>



								<!-- Hidden: custom-value -->

								<input

									type="hidden"

									name="custom-value"

									value="<?php echo esc_attr($entry_placeholder); ?>">



							</div>

						</div>

					</div>
					<?php

					$assoc_arr = array();

					$meta = get_post_meta($form_id, '_form', true);

					$fields = $this->get_contact_form_fields($meta);

					if ($fields) {

						foreach ($fields as $field) {

							$single = $this->get_field_assoc($field);

							if ($single) {

								$assoc_arr[] = $single;
							}
						}
					}

					?>



					<?php if (! empty($assoc_arr)) : ?>



						<?php

						$count = 0;

						foreach ($assoc_arr as $key => $value) {

							foreach ($value as $k => $v) {

								// ONLY render real field name

								if ($k !== 'name') {

									continue;
								}

								$saved_val = '';

								if (! empty($saved_mail_tags) && array_key_exists($v, $saved_mail_tags[0])) {

									$saved_val = $saved_mail_tags[0][$v];
								}

								$placeholder = preg_replace('/[\\_]|\\s+/', '-', $v);

								// FIXED

								$field_name = $value['name'];

								// FIXED

								$unique_id = 'gs-field-' . sanitize_key($v) . '-' . $count;

								$has_pipe = isset($value['pi']) ? $value['pi'] : 0;

						?>



								<div class="card form-field-toggle active ui-sortable-handle">

									<div class="card-content">

										<div class="toggle-button field_list_bg">

											<i class="dashicons dashicons-edit field-edit-icon"></i>

											<label class="switch">

												<input type="checkbox"

													class="fcheckBoxClass toggle-input"

													data-value="<?php echo esc_attr($saved_val); ?>"

													data-place="<?php echo esc_attr($placeholder); ?>"

													checked disabled>

												<span class="slider round button-toggle  field-list-new"></span>

											</label>



											<span class="drag-icon">

												<i class="fas fa-grip-vertical"></i>

											</span>



											<div class="label label-text card-label">

												<?php echo esc_html($v); ?>

											</div>



											<circle cx="14" cy="15" r="1.5" fill="currentColor"></circle>

											</svg>

											</span>

											<input type="hidden"

												value="<?php echo esc_attr($field_name); ?>"

												name="gs-custom-header-key">



											<input type="hidden"

												value="<?php echo esc_attr($placeholder); ?>"

												name="gs-custom-header-placeholder">



											<input type="text"

												data-id="<?php echo esc_attr($unique_id); ?>"

												class="field-input d-none"

												name="gs-custom-header[<?php echo esc_attr($count); ?>]"

												value="<?php echo esc_attr($saved_val); ?>"

												placeholder="<?php echo esc_attr($placeholder); ?>" disabled>



											<!--  PIPE UI -->

											<?php if ($has_pipe == 1) : ?>

												<div class="pipe-select-box">

													<select name="gs-custom-pi[<?php echo esc_attr($field_name); ?>]">

														<option value="after">After PIPE</option>

														<option value="before" disabled>Before PIPE</option>

														<option value="both" disabled>Both</option>

													</select>

												</div>

											<?php endif; ?>



										</div>

									</div>

								</div>



						<?php

								++$count;
							}
						}

						?>



					<?php else : ?>

						<p><span class="gs-info">No mail tags available.</span></p>

					<?php
					endif;

					$this->special_mail_tags;

					$tags_count = count($this->special_mail_tags);

					?>



					<?php if ($tags_count > 0) : ?>



						<?php

						foreach ($this->special_mail_tags as $count => $tag_name) :

							$is_entry_id = ($tag_name === 'entry_id');

							$placeholder = str_replace('_', '-', $tag_name);

							// Fetch saved value

							$saved_val = '';

							$checked = '';

							if (! empty($saved_mail_tags) && array_key_exists($tag_name, $saved_mail_tags[0])) {

								$saved_val = $saved_mail_tags[0][$tag_name];

								$checked = 'checked';
							}

						?>

							<div class="card form-field-toggle active ui-sortable-handle">

								<div class="card-content">

									<div class="toggle-button special_mail_tags_bg">

										<i class="dashicons dashicons-edit field-edit-icon"></i>

										<!-- Toggle Switch -->

										<label for="gs-st-ck"

											class="switch <?php echo $is_entry_id ? 'entry-lock' : ''; ?>">

											<input

												type="checkbox"

												class="fcheckBoxClass toggle-input child-toggle check-toggle-cf7"

												id="gs-st-ck-<?php echo esc_attr($count); ?>"

												name="gs-st-ck[<?php echo esc_attr($count); ?>]"

												value="1">

											<span class="slider round button-toggle  field-list-new"></span>

										</label>



										<span class="drag-icon">

											<i class="fas fa-grip-vertical drag-icon"></i>

										</span>



										<!-- Tag Label -->

										<div class="label label-text card-label">

											<?php echo esc_html($tag_name); ?>

										</div>



										<!-- Hidden: key -->

										<input

											type="hidden"

											value="<?php echo esc_attr($tag_name); ?>"

											name="gs-st-header-key[<?php echo esc_attr($count); ?>]">



										<!-- Hidden: placeholder -->

										<input

											type="hidden"

											value="<?php echo esc_attr($placeholder); ?>"

											name="gs-st-header-placeholder[<?php echo esc_attr($count); ?>]">



										<!-- Text Input -->

										<input

											type="text"

											data-id="gs-st-<?php echo esc_attr($tag_name); ?>"

											class="field-input from-input-change d-none"

											name="gs-st-custom-header[<?php echo esc_attr($count); ?>]"

											value="<?php echo $is_entry_id ? 'entry_id' : esc_attr($saved_val); ?>"

											placeholder="<?php echo esc_attr($placeholder); ?>"



											<?php echo $is_entry_id ? 'readonly' : ''; ?>>



										<!-- Hidden: custom-value -->

										<input

											type="hidden"

											name="gs-st-custom-value"

											value="<?php echo esc_attr($placeholder); ?>">



									</div>

								</div>

							</div>



						<?php endforeach; ?>



					<?php else : ?>



						<p>

							<span class="gs-info">

								<?php echo esc_html__('No special mail tags available.', 'cf7-google-sheets-connector'); ?>

							</span>

						</p>



					<?php
					endif;

					?>



					<?php

					$count = 999;

					$tag_name = '_datetime';

					$placeholder = 'Date & Time';

					?>



					<div class="card form-field-toggle active ui-sortable-handle">

						<div class="card-content">

							<div class="toggle-button custom-mail-tags-bg">



								<i class="dashicons dashicons-edit field-edit-icon"></i>



								<label for="gs-st-ck-<?php echo esc_attr($count); ?>" class="switch">



									<input

										type="checkbox"

										class="fcheckBoxClass toggle-input child-toggle check-toggle-cf7"

										id="gs-st-ck-<?php echo esc_attr($count); ?>"

										name="gs-st-ck[<?php echo esc_attr($count); ?>]"

										value="1">



									<span class="slider round button-toggle field-list-new"></span>

								</label>



								<span class="drag-icon">

									<i class="fas fa-grip-vertical drag-icon"></i>

								</span>



								<div class="label label-text card-label">

									<?php echo esc_html($tag_name); ?>

								</div>



								<input

									type="hidden"

									value="<?php echo esc_attr($tag_name); ?>"

									name="gs-st-header-key[<?php echo esc_attr($count); ?>]">



								<input

									type="hidden"

									value="<?php echo esc_attr($placeholder); ?>"

									name="gs-st-header-placeholder[<?php echo esc_attr($count); ?>]">



								<input

									type="text"

									data-id="gs-st-<?php echo esc_attr($tag_name); ?>"

									class="field-input from-input-change d-none"

									name="gs-st-custom-header[<?php echo esc_attr($count); ?>]"

									value="_datetime"

									placeholder="<?php echo esc_attr($placeholder); ?>">



								<input

									type="hidden"

									name="gs-st-custom-value"

									value="<?php echo esc_attr($placeholder); ?>">



							</div>

						</div>

					</div>

				<?php
			}

			/**

			 * Extract all Contact Form 7 fields from the form content.

			 * This function scans the form markup and returns all

			 * field tags enclosed in square brackets (e.g. [text your-name]).
			 *
			 * @since 1.0
			 *
			 * @param string $meta Contact Form 7 form content.

			 * @return array|false Returns array of matched fields or false if none found.
			 */
			public function get_contact_form_fields($meta)
			{

				// FIX: non-greedy regex

				$regexp = '/\[[^\]]+\]/';

				$arr = array();

				if (! preg_match_all($regexp, $meta, $arr)) {

					return false;
				}

				return $arr[0];
			}

			/**

			 * Get field type and name association from a CF7 field tag.

			 * This function parses a single Contact Form 7 field shortcode

			 * and extracts the field type and field name if it belongs

			 * to the allowed tags list.
			 *
			 * @since 1.0
			 *
			 * @param string $content Field shortcode content.

			 * @return array|false Returns associative array of field type and name or false.
			 */
			public function get_field_assoc($content)
			{

				$content = trim($content);

				$content = trim($content, '[]');

				$parts = preg_split('/\s+/', $content);

				if (empty($parts)) {
					return false;
				}

				// remove * from type

				$type = rtrim($parts[0], '*');

				if (! in_array($type, $this->allowed_tags)) {

					return false;
				}

				$name = '';

				foreach ($parts as $index => $part) {

					if ($index === 0) {
						continue;
					}

					// skip pipe values like CEO|email

					if (strpos($part, '|') !== false) {
						continue;
					}

					$name = $part;

					break;
				}

				if (empty($name)) {
					return false;
				}

				// FIXED PIPE DETECTION

				$has_pipe = 0;

				$pipe_allowed_types = array('select', 'radio', 'checkbox');

				if (in_array($type, $pipe_allowed_types) && strpos($content, '|') !== false) {

					$has_pipe = 1;
				}

				return array(

					'type' => $type,

					'name' => $name,

					'pi'   => $has_pipe,

				);
			}

			/**

			 * Function - display contact form special mail tags to be mapped to google sheet

			 * @since 2.6
			 */
			public function display_form_special_tags($form_id)
			{

				$tags_count = count($this->special_mail_tags);

				$num_of_cols = 1;

				?>

					<h2 class="inner-title"><span class="gs-info"><?php echo esc_html(__('Map special mail tags with custom header name and save automatically to google sheet. ', 'cf7-google-sheets-connector')); ?></span></h2>

					<ul class="gs-field-list special">

						<?php

						for ($i = 0; $i <= $tags_count; $i++) {

							if ($i == $tags_count) {

								break;
							}

							$tag_name = $this->special_mail_tags[$i];

							$placeholder = str_replace('_', '-', $tag_name);

							echo '<li>';

							echo '<div class="input-field">



                  <label for="enable-sorting-option" class="button-woo-toggle-cf7" id="sorting-toggle"></label>

                  </div>';

							echo '<div class="special-tags label">[_' . esc_attr($tag_name) . '] </div>';

							echo '<div class="gs-r-pad field-input  d-none"><input type="text" class="name-field" name="gs-st-custom-header[' . esc_attr($i) . ']" value="" disabled placeholder="' . esc_attr($placeholder) . '"> </div>';

							if ($i % $num_of_cols == 1) {

								echo '</li>';
							}
						}

						?>

					</ul>

					<?php
				}

				/**

				 * Display custom mail tags mapping fields for a CF7 form.

				 * This function fetches available custom mail tags through the

				 * `gscf7_special_mail_tags` filter and displays them in the

				 * admin UI so users can map them to Google Sheet headers.
				 *
				 * @since 1.0
				 *
				 * @param int $form_id Contact Form 7 form ID.

				 * @return void
				 */
				function display_form_custom_tag($form_id)
				{

					$custom_mail_tags = array();

					$num_of_cols = 2;

					if (has_filter('gscf7_special_mail_tags')) {

						// Filter hook for custom mail tags

						$custom_tags = apply_filters('gscf7_special_mail_tags', $custom_mail_tags, $form_id);

						$custom_tags_count = count($custom_tags);

						$num_of_cols = 2;

						// fetch saved fields

						$saved_cmail_tags = get_post_meta($form_id, 'gs_map_custom_mail_tags');

					?>

						<ul class="gs-field-list">

							<?php

							echo '<li>';

							for ($i = 0; $i <= $custom_tags_count; $i++) {

								if ($i == $custom_tags_count) {

									break;
								}

								$tag_name = $custom_tags[$i];

								$modify_tag = ltrim($tag_name, '_');

								$saved_val = '';

								$checked = '';

								if (! empty($saved_cmail_tags) && array_key_exists($modify_tag, $saved_cmail_tags[0])) :

									$saved_val = $saved_cmail_tags[0][$modify_tag];

									$checked = 'checked';

								endif;

								// hack - todo

								$placeholder_explode = explode('_', $tag_name, 2);

								$placeholder = str_replace('_', '-', $placeholder_explode[1]);

								echo '<div class="input-field">

                     <label for="enable-sorting-option" class="button-woo-toggle-cf7" id="sorting-toggle"></label>

                     </div>';

								echo '<div class="label">[' . esc_attr($tag_name) . ']</div>';

								echo '<div class="gs-r-pad field-input d-none"><input type="hidden" name="gs-ct-key[' . esc_attr($i) . ']" value="' . esc_attr($tag_name) . '" ><input type="hidden" name="gs-ct-placeholder[' . esc_attr($i) . ']" value="' . esc_attr($placeholder) . '" >

                     <input type="text" name="gs-ct-custom-header[' . esc_attr($i) . ']" value="' . esc_attr($saved_val) . '" placeholder="' . esc_attr($placeholder) . '" disabled>



                     </div>';

								if ($i % $num_of_cols == 1) {

									echo '</li>';
								}
							}

							?>

						</ul>

					<?php

					} else {

						echo '<p><span class="gs-info">' . esc_html__('No custom mail tags available.', 'cf7-google-sheets-connector') . '</span></p>';
					}
				}

				/**
				 * Display Conditional Logic toggle UI for CF7 form feed settings.
				 *
				 * This UI allows users to enable conditional logic so that
				 * submissions are sent to Google Sheets only when certain
				 * conditions are met.
				 *
				 * @since 1.0
				 *
				 * @param int    $form_id Contact Form 7 form ID.

				 * @param object $post    WordPress post object.

				 * @return void
				 */
				function display_form_conditional_logic($form_id, $post)
				{

					?>

					<div class="misc-conditional-row">

						<div class="misc-options-wrapper">

							<label for="enable-conditional-logic">

								<input type="checkbox" name="cf7-gs[enable_conditional_logic]" id="enable-conditional-logic" value="1"

									style="display: none;">

								<label for="enable-conditional-logic" class="button-woo-toggle-cf7" id="conditional-toggle"></label>

								<?php echo esc_html__('Conditional Logic', 'cf7-google-sheets-connector'); ?>

							</label>

							<span class="tooltip" style="display: inline !important;">

								<img src="<?php echo esc_url(GS_CONNECTOR_URL . 'assets/img/help.png'); ?>" class="help-icon">

								<span class="tooltiptext tooltip-right-msg">

									<?php
									echo esc_html__(
										'The Enable Conditional Logic option in the field settings allows you to create rules to dynamically display or hide the submission to Google Sheet based on values.',
										'cf7-google-sheets-connector'
									);
									?>

								</span>
							</span>
						</div>
					</div>
					<?php
				}



				/**

				 * Display upgrade notification for users.

				 * Shows an admin notice prompting users to reauthenticate

				 * with Google when API changes require new authorization.
				 *
				 * @since 1.0
				 *
				 * @return void
				 */
				public function display_upgrade_notice()
				{

					$get_notification_display_interval = get_option('gs_upgrade_notice_interval');

					$close_notification_interval = get_option('gs_close_upgrade_notice');

					if ($close_notification_interval === 'off') {

						return;
					}

					if (! empty($get_notification_display_interval)) {

						$adds_interval_date_object = DateTime::createFromFormat('Y-m-d', $get_notification_display_interval);

						$notice_interval_timestamp = $adds_interval_date_object->getTimestamp();
					}

					if (empty($get_notification_display_interval) || current_time('timestamp') > $notice_interval_timestamp) {

						$ajax_nonce = wp_create_nonce('gs_upgrade_ajax_nonce');

						$upgrade_text = '<div class="gs-adds-notice">';

						$upgrade_text .= '<span><b>CF7 Google Sheet Connector </b> ';

						$upgrade_text .= 'version 4.0 would required you to <a href="' . admin_url('admin.php?page=wpcf7-google-sheet-config') . '">reauthenticate</a> with your Google Account again due to update of Google API V3 to V4.<br/><br/>';

						$upgrade_text .= 'To avoid any loss of data redo the Google Sheet settings of each Contact Forms again with required sheet and tab details.</span>';

						$upgrade_text .= '<ul class="review-rating-list">';

						$upgrade_text .= '<li><a href="javascript:void(0);" class="cf7gsc_upgrade" title="Done">Yes, I have done.</a></li>';

						$upgrade_text .= '<li><a href="javascript:void(0);" class="cf7gsc_upgrade_later" title="Remind me later">Remind me later.</a></li>';

						$upgrade_text .= '</ul>';

						$upgrade_text .= '<input type="hidden" name="gs_upgrade_ajax_nonce" id="gs_upgrade_ajax_nonce" value="' . $ajax_nonce . '" />';

						$upgrade_text .= '</div>';

						$upgrade_block = Gs_Connector_Free_Utility::instance()->admin_notice(
							array(

								'type'    => 'upgrade',

								'message' => $upgrade_text,

							)
						);

						echo wp_kses_post($upgrade_block);
					}
				}

				/**

				 * Set upgrade notification reminder interval.

				 * Stores the next reminder date in the database.
				 *
				 * @since 1.0
				 *
				 * @return void
				 */
				public function set_upgrade_notification_interval()
				{

					// check nonce

					check_ajax_referer('gs_upgrade_ajax_nonce', 'security');

					$time_interval = gmdate(
						'Y-m-d',
						strtotime('+10 days')
					);

					update_option('gs_upgrade_notice_interval', $time_interval);

					wp_send_json_success();
				}

				/**

				 * Disable upgrade notification permanently.

				 * Updates the option so the upgrade notice

				 * is no longer displayed in the admin panel.
				 *
				 * @since 1.0
				 *
				 * @return void
				 */
				public function close_upgrade_notification_interval()
				{

					// check nonce

					check_ajax_referer('gs_upgrade_ajax_nonce', 'security');

					update_option('gs_close_upgrade_notice', 'off');

					wp_send_json_success();
				}

				/**
				 * AJAX handler for the Entries Dashboard's filterable, paginated,
				 * sortable "Recent Entries" table.
				 *
				 * Filters by form and read/unread status, then paginates the
				 * filtered set. Status lives inside the serialized `value` column
				 * (same as the rest of this plugin), so filtering happens in PHP
				 * after fetching candidate rows rather than in SQL.
				 *
				 * @since 5.3.0
				 *
				 * @return void Sends a JSON response via wp_send_json_success()/wp_send_json_error().
				 */
				public function gscf7_dashboard_entries_query()
				{
					check_ajax_referer('gscf7-dashboard-entries', 'security');

					if (! current_user_can('manage_options')) {
						wp_send_json_error(array('message' => __('Permission denied.', 'cf7-google-sheets-connector')));
						return;
					}

					$form_id  = isset($_POST['form_id']) ? absint($_POST['form_id']) : 0;
					$status   = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : 'all';
					$per_page = isset($_POST['per_page']) ? absint($_POST['per_page']) : 10;
					$paged    = isset($_POST['paged']) ? absint($_POST['paged']) : 1;
					$orderby  = isset($_POST['orderby']) ? sanitize_key(wp_unslash($_POST['orderby'])) : 'date';
					$order    = isset($_POST['order']) ? sanitize_key(wp_unslash($_POST['order'])) : 'desc';

					if (! in_array($status, array('all', 'read', 'unread'), true)) {
						$status = 'all';
					}
					if (! in_array($per_page, array(10, 20, 50, 100), true)) {
						$per_page = 10;
					}
					if ($paged < 1) {
						$paged = 1;
					}
					if (! in_array($orderby, array('id', 'date'), true)) {
						$orderby = 'date';
					}
					if (! in_array($order, array('asc', 'desc'), true)) {
						$order = 'desc';
					}

					global $wpdb;
					$table = $wpdb->prefix . 'cf7db_gsheet_forms';

					$cap = (int) apply_filters('gscf7_dashboard_entries_limit', 0);

					if ($form_id > 0) {
						if ($cap > 0) {
							// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name escaped with esc_sql(); form_id and LIMIT are bound via $wpdb->prepare() placeholders.
							$rows = $wpdb->get_results($wpdb->prepare('SELECT id, form_id, date, value FROM `' . esc_sql($table) . '` WHERE form_id = %d ORDER BY date DESC LIMIT %d', $form_id, $cap));
						} else {
							// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name escaped with esc_sql(); form_id is bound via a $wpdb->prepare() placeholder.
							$rows = $wpdb->get_results($wpdb->prepare('SELECT id, form_id, date, value FROM `' . esc_sql($table) . '` WHERE form_id = %d ORDER BY date DESC', $form_id));
						}
					} else {
						if ($cap > 0) {
							// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name escaped with esc_sql(); LIMIT is bound via a $wpdb->prepare() placeholder.
							$rows = $wpdb->get_results($wpdb->prepare('SELECT id, form_id, date, value FROM `' . esc_sql($table) . '` ORDER BY date DESC LIMIT %d', $cap));
						} else {
							// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Hardcoded literal query; table name escaped with esc_sql(), no other dynamic values.
							$rows = $wpdb->get_results('SELECT id, form_id, date, value FROM `' . esc_sql($table) . '` ORDER BY date DESC');
						}
					}

					$form_titles = array();
					$filtered    = array();

					foreach ($rows as $row) {
						$data       = @unserialize($row->value); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize -- Matches this plugin's existing storage format.
						$row_status = (is_array($data) && isset($data['cfdb7_status']) && 'read' === $data['cfdb7_status']) ? 'read' : 'unread';

						if ('all' !== $status && $row_status !== $status) {
							continue;
						}

						if (! isset($form_titles[$row->form_id])) {
							$title                       = get_the_title($row->form_id);
							$form_titles[$row->form_id] = $title ? $title : sprintf(
								/* translators: %d: form ID */
								__('Form #%d', 'cf7-google-sheets-connector'),
								$row->form_id
							);
						}

						$filtered[] = array(
							'id'      => (int) $row->id,
							'form_id' => (int) $row->form_id,
							'form'    => $form_titles[$row->form_id],
							'date'    => $row->date,
							'status'  => $row_status,
						);
					}

					/*
					 * Rows are already loaded into memory to apply the status
					 * filter above (the read/unread flag lives inside the
					 * serialized value blob, not a real column), so sorting is
					 * done here on that same in-memory array rather than via SQL.
					 */
					usort(
						$filtered,
						static function ($a, $b) use ($orderby, $order) {
							if ('id' === $orderby) {
								$result = $a['id'] <=> $b['id'];
							} else {
								$result = strcmp($a['date'], $b['date']);
							}

							return 'asc' === $order ? $result : -$result;
						}
					);

					$total       = count($filtered);
					$total_pages = max(1, (int) ceil($total / $per_page));
					$paged       = max(1, min($paged, $total_pages));
					$page_rows   = array_slice($filtered, ($paged - 1) * $per_page, $per_page);

					ob_start();

					if (empty($page_rows)) {
					?>
						<tr>
							<td colspan="6"><?php esc_html_e('No entries match the selected filters.', 'cf7-google-sheets-connector'); ?></td>
						</tr>
						<?php
					} else {
						foreach ($page_rows as $entry) {
						?>
							<tr>
								<th scope="row" class="check-column">
									<input type="checkbox" name="contact_form[]" value="<?php echo esc_attr($entry['id']); ?>" />
								</th>
								<td class="column-entry_id" data-value="<?php echo esc_attr($entry['id']); ?>">#<?php echo esc_html($entry['id']); ?></td>
								<td data-value="<?php echo esc_attr($entry['status']); ?>">
									<span class="gscf7-status-pill gscf7-status-<?php echo esc_attr($entry['status']); ?>">
										<?php echo esc_html('read' === $entry['status'] ? __('Read', 'cf7-google-sheets-connector') : __('Unread', 'cf7-google-sheets-connector')); ?>
									</span>
								</td>
								<td data-value="<?php echo esc_attr($entry['form']); ?>"><?php echo esc_html($entry['form']); ?></td>
								<td data-value="<?php echo esc_attr(strtotime($entry['date'])); ?>"><?php echo esc_html(mysql2date('d/m/Y H:i', $entry['date'])); ?></td>
								<td class="column-actions">
									<a class="button action" href="<?php
																	echo esc_url(
																		add_query_arg(
																			array(
																				'page'    => 'wpcf7-google-sheet-config',
																				'tab'     => 'cf7_db',
																				'formId'  => $entry['form_id'],
																				'entryId' => $entry['id'],
																			),
																			admin_url('admin.php')
																		)
																	);
																	?>"><?php esc_html_e('View', 'cf7-google-sheets-connector'); ?></a>
									<button type="button" class="button action sendToGoogleSheetCF7DB" data-id="<?php echo esc_attr($entry['id']); ?>" form-id="<?php echo esc_attr($entry['form_id']); ?>"><?php esc_html_e('Send To SpreadSheet', 'cf7-google-sheets-connector'); ?></button>
								</td>
							</tr>
					<?php
						}
					}

					$rows_html = ob_get_clean();

					/*
					 * Bulk actions (Delete/Read/Unread) operate on raw entry IDs with
					 * no form_id dependency, so they work identically here even
					 * though rows can span different forms -- reuses the same
					 * gscf7_cf7db_bulk_action AJAX endpoint and #gscf7-entries-bulk-form
					 * the per-form dynamic table already wired up, just with its own
					 * matching toolbar markup (this generic table isn't backed by
					 * GSCF7_FormEntry_Table, so there's no get_bulk_actions()/
					 * bulk_actions() to call into for it).
					 */
					ob_start();
					?>
					<label for="bulk-action-selector-top" class="screen-reader-text"><?php esc_html_e('Select bulk action', 'cf7-google-sheets-connector'); ?></label>
					<select name="action" id="bulk-action-selector-top">
						<option value="-1"><?php esc_html_e('Bulk Actions', 'cf7-google-sheets-connector'); ?></option>
						<option value="read"><?php esc_html_e('Read', 'cf7-google-sheets-connector'); ?></option>
						<option value="unread"><?php esc_html_e('Unread', 'cf7-google-sheets-connector'); ?></option>
						<option value="delete"><?php esc_html_e('Delete', 'cf7-google-sheets-connector'); ?></option>
						<option value="sendtospreadsheet"><?php esc_html_e('Spread Sheet', 'cf7-google-sheets-connector'); ?></option>
					</select>
					<input type="submit" id="doaction" class="button action" value="<?php echo esc_attr__('Apply', 'cf7-google-sheets-connector'); ?>">
					<a href="#" id="cf7gs-free-csv" class="button" style="float:right; margin:0;"><?php esc_html_e('Export CSV', 'cf7-google-sheets-connector'); ?></a>

					<?php
					/*
					 * The "Send to Sheet" and "Export CSV" upsell popups themselves
					 * -- GSCF7_FormEntry_Table::bulk_actions() renders the same two
					 * divs for the per-form dynamic table; this generic table isn't
					 * backed by that class, so its own toolbar needs its own copy
					 * (only one of the two toolbars is ever in the DOM at a time,
					 * so there's no duplicate-id conflict between them).
					 */
					?>
					<div id="cf7gs-free-pro" class="gs-popup-overlay d-none">
						<div class="gs-popups position-relative-popup text-center">
							<button type="button" class="gscf7-free-pro gsc-pro-close">&times;</button>
							<div class="gsc-pro-section">
								<div class="gsc-pro-card">
									<div class="gsc-pro-headers">
										<div class="gsc-pro-headers">
											<div class="gsc-modal-title"><?php esc_html_e('Want to send entries to Google Sheets?', 'cf7-google-sheets-connector'); ?></div>
											<p class="gsc-modal-text"><?php echo esc_html__('Export and sync your form submissions directly to Google Sheets to easily organize, filter, and manage your data in one place. Unlock this feature to simplify your workflow and access your entries anytime.', 'cf7-google-sheets-connector'); ?></p>
										</div>
										<a href="https://www.gsheetconnector.com/cf7-google-sheet-connector-pro" target="_blank" class="btn btn-primary text-decoration-none link-hover-white"><?php esc_html_e('Upgrade to Unlock', 'cf7-google-sheets-connector'); ?></a>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div id="cf7gs-free-pro-csv" class="gs-popup-overlay d-none">
						<div class="gs-popups position-relative-popup text-center">
							<button type="button" class="gscf7-free-pro-csv gsc-pro-close">&times;</button>
							<div class="gsc-pro-section">
								<div class="gsc-pro-card">
									<div class="gsc-pro-headers">
										<div class="gsc-modal-title"><?php esc_html_e('Want to download your form entries?', 'cf7-google-sheets-connector'); ?></div>
										<p class="gsc-modal-text"><?php echo esc_html__('Export your form entries as a CSV file and use them in Sheets. You can sort, filter, and manage everything more easily.', 'cf7-google-sheets-connector'); ?></p>
									</div>
									<a href="https://www.gsheetconnector.com/cf7-google-sheet-connector-pro" target="_blank" class="btn btn-primary text-decoration-none link-hover-white"><?php esc_html_e('Upgrade to Unlock', 'cf7-google-sheets-connector'); ?></a>
								</div>
							</div>
						</div>
					</div>
			<?php
					$toolbar_html = ob_get_clean();

					wp_send_json_success(
						array(
							'rows_html'    => $rows_html,
							'toolbar_html' => $toolbar_html,
							'total'        => $total,
							'paged'        => $paged,
							'total_pages'  => $total_pages,
							'per_page'     => $per_page,
						)
					);
				}

				/**
				 * Return stat counts + chart data for the Entries Dashboard's "Form"
				 * filter, scoped to a single form (or every form, when form_id is 0).
				 *
				 * @since 5.3.0
				 */
				public function gscf7_dashboard_stats_query()
				{
					check_ajax_referer('gscf7-dashboard-stats', 'security');

					if (! current_user_can('manage_options')) {
						wp_send_json_error(array('message' => __('Permission denied.', 'cf7-google-sheets-connector')));
						return;
					}

					$form_id = isset($_POST['form_id']) ? absint($_POST['form_id']) : 0;

					$cf7db         = new GS_CF7DB();
					$status_counts = $cf7db->gscf7_get_status_counts($form_id);
					$daily_counts  = $cf7db->gscf7_get_daily_submission_counts($form_id);
					$new_today     = $cf7db->gscf7_get_new_today_count($form_id);

					wp_send_json_success(
						array(
							'total'     => $status_counts['all'],
							'unread'    => $status_counts['unread'],
							'read'      => $status_counts['read'],
							'new_today' => $new_today,
							'labels'    => $daily_counts['labels'],
							'values'    => $daily_counts['values'],
						)
					);
				}

				/**
				 * Return dashboard-styled table HTML for the CF7 Database screen's
				 * per-form entries table (dynamic, form-specific columns).
				 *
				 * GSCF7_FormEntry_Table is built around a classic $_GET-driven page
				 * load. Rather than duplicating its column-detection/query/security
				 * logic, this shims the sanitized AJAX params into $_GET/$_REQUEST
				 * and reuses that class completely unchanged -- the same
				 * prepare_items() (incl. process_bulk_action() no-op for a plain
				 * filter/pagination request) and the new render_dashboard_style()
				 * that wraps its own get_columns()/single_row_columns()/column_*()
				 * methods in the Dashboard's card/pill markup.
				 *
				 * @since 5.3.0
				 * @return void
				 */
				public function gscf7_cf7db_entries_table_query()
				{
					check_ajax_referer('gscf7-cf7db-entries', 'security');

					if (! current_user_can('manage_options')) {
						wp_send_json_error(array('message' => __('Permission denied.', 'cf7-google-sheets-connector')));
						return;
					}

					$form_id  = isset($_POST['form_id']) ? absint($_POST['form_id']) : 0;
					$status   = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : 'all';
					$per_page = isset($_POST['per_page']) ? absint($_POST['per_page']) : 10;
					$paged    = isset($_POST['paged']) ? absint($_POST['paged']) : 1;
					$orderby  = isset($_POST['orderby']) ? sanitize_key(wp_unslash($_POST['orderby'])) : '';
					$order    = isset($_POST['order']) ? sanitize_key(wp_unslash($_POST['order'])) : 'desc';

					if ($form_id <= 0) {
						wp_send_json_error(array('message' => __('This view requires a specific form.', 'cf7-google-sheets-connector')));
						return;
					}

					if (! in_array($status, array('all', 'read', 'unread'), true)) {
						$status = 'all';
					}
					if (! in_array($per_page, array(10, 20, 50, 100), true)) {
						$per_page = 10;
					}
					if ($paged < 1) {
						$paged = 1;
					}
					if (! in_array($order, array('asc', 'desc'), true)) {
						$order = 'desc';
					}

					/*
					 * GSCF7_FormEntry_Table reads its filters straight from $_GET
					 * (and $_REQUEST for pagination/search) -- shim the sanitized,
					 * allow-listed AJAX params in so its existing logic applies
					 * identically here, then restore the superglobals afterward.
					 */
					$original_get     = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Snapshot for restoration below, not reading user input.
					$original_post    = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Snapshot for restoration below, not reading user input.
					$original_request = $_REQUEST; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Snapshot for restoration below, not reading user input.

					$_GET['formId']       = $form_id;
					$_GET['entry_status'] = $status;
					$_GET['order']        = $order;
					$_GET['per_page']     = $per_page;
					$_GET['paged']        = $paged;
					$_REQUEST['paged']    = $paged;
					unset($_GET['s'], $_REQUEST['s']);

					/*
					 * admin-ajax.php's own routing convention (the 'action'
					 * POST field that got this handler called in the first
					 * place) collides with WP_List_Table::current_action(),
					 * which also reads $_REQUEST['action'] to detect a bulk
					 * action (delete/read/unread/send-to-sheet) submission.
					 * Left in place, prepare_items() -> process_bulk_action()
					 * treats "gscf7_cf7db_table_query" as a bulk action, finds
					 * no _wpnonce field (this request sends 'security'
					 * instead), and wp_die()s with "Not valid..!!" -- which,
					 * since wp_die() short-circuits before this method's own
					 * cleanup runs, gets flushed straight into what's supposed
					 * to be a pure JSON response.
					 */
					unset($_POST['action'], $_REQUEST['action'], $_REQUEST['action2']);

					if ('date' === $orderby) {
						$_GET['orderby'] = 'date';
					} else {
						unset($_GET['orderby']);
					}

					/*
					 * GSCF7_FormEntry_Table::__construct() echoes a hidden
					 * <input> nonce field directly (fine on a normal page
					 * load; fatal here, since that stray HTML would land
					 * before the JSON body below and break res.json() on the
					 * JS side). Buffer and discard it -- this AJAX response
					 * doesn't need that field.
					 */
					ob_start();
					$list_table = new GSCF7_FormEntry_Table();
					$list_table->prepare_items();
					ob_end_clean();

					$rendered     = $list_table->render_dashboard_style();
					$total_items  = (int) $list_table->get_pagination_arg('total_items');
					$total_pages  = (int) $list_table->get_pagination_arg('total_pages');

					$_GET     = $original_get; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Restoring the snapshot taken above.
					$_POST    = $original_post; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Restoring the snapshot taken above.
					$_REQUEST = $original_request; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Restoring the snapshot taken above.

					wp_send_json_success(
						array(
							'head_html'    => $rendered['head_html'],
							'rows_html'    => $rendered['rows_html'],
							'toolbar_html' => $rendered['toolbar_html'],
							'total'        => $total_items,
							'paged'        => max(1, min($paged, max(1, $total_pages))),
							'total_pages'  => max(1, $total_pages),
							'per_page'     => $per_page,
						)
					);
				}

				/**
				 * Delete/Read/Unread bulk action for the CF7 Database screen,
				 * without a full page reload.
				 *
				 * Reuses GSCF7_FormEntry_Table::process_bulk_action() completely
				 * unchanged -- same DB writes, same file-deletion cleanup for
				 * "delete", same nonce check ('bulk-contact_forms', already
				 * verified once more here via check_ajax_referer). Only the
				 * superglobals it reads from are shimmed to match what the
				 * classic POST-and-reload flow would have provided.
				 *
				 * @since 5.3.0
				 * @return void
				 */
				public function gscf7_cf7db_bulk_action_query()
				{
					check_ajax_referer('bulk-contact_forms', '_wpnonce');

					if (! current_user_can('manage_options')) {
						wp_send_json_error(array('message' => __('Permission denied.', 'cf7-google-sheets-connector')));
						return;
					}

					$form_id      = isset($_POST['form_id']) ? absint($_POST['form_id']) : 0;
					$bulk_action  = isset($_POST['bulk_action']) ? sanitize_key(wp_unslash($_POST['bulk_action'])) : '';
					$entry_ids    = isset($_POST['entry_ids']) ? array_map('absint', (array) $_POST['entry_ids']) : array();

					if (! in_array($bulk_action, array('delete', 'read', 'unread'), true)) {
						wp_send_json_error(array('message' => __('Invalid bulk action.', 'cf7-google-sheets-connector')));
						return;
					}

					if (empty($entry_ids)) {
						wp_send_json_error(array('message' => __('No entries selected.', 'cf7-google-sheets-connector')));
						return;
					}

					$original_get     = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Snapshot for restoration below, not reading user input.
					$original_post    = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Snapshot for restoration below, not reading user input.
					$original_request = $_REQUEST; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Snapshot for restoration below, not reading user input.

					$_GET['formId']        = $form_id;
					$_POST['action']       = $bulk_action;
					$_POST['contact_form'] = $entry_ids;
					/*
					 * $_REQUEST is a separate snapshot taken at request start, not a
					 * live merge of $_GET/$_POST -- mutating $_POST above does not
					 * update it. WP_List_Table::current_action() (called by
					 * process_bulk_action()) reads $_REQUEST['action'], which without
					 * this line would still hold this AJAX call's own routing value
					 * ("gscf7_cf7db_bulk_action"), matching none of the
					 * delete/read/unread branches -- so the action silently no-op'd.
					 */
					$_REQUEST['action']       = $bulk_action;
					$_REQUEST['contact_form'] = $entry_ids;
					// $_POST['_wpnonce'] is already the value check_ajax_referer()
					// just verified above, so process_bulk_action()'s own internal
					// nonce check (same action, same field name) passes too.

					ob_start();
					$list_table = new GSCF7_FormEntry_Table();
					$list_table->process_bulk_action();
					ob_end_clean();

					$_GET     = $original_get; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Restoring the snapshot taken above.
					$_POST    = $original_post; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Restoring the snapshot taken above.
					$_REQUEST = $original_request; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Restoring the snapshot taken above.

					wp_send_json_success();
				}
			}

			/*
			 * Use the singleton, not `new`. This class's constructor registers
			 * ~20 hooks; since add_action()/add_filter() key their callback ID
			 * off the object hash, a second `new Gs_Connector_Service()`
			 * instance (e.g. Gs_Connector_Service::instance() called lazily
			 * elsewhere, as cf7gs-dashboard-widget.php does) registered every
			 * hook a second time instead of being deduplicated -- which is what
			 * caused the GDPR/Privacy notice to render twice below every form.
			 */
			$gscf7_connector_service = Gs_Connector_Service::instance();
