<?php

/**
 * Service class for Google Sheet Connector
 *
 * @since 1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
/**
 * Gs_Connector_Service Class
 *
 * @since 1.0
 */
class Gs_Connector_Service {


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
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private $allowed_tags      = array( 'text', 'email', 'url', 'tel', 'number', 'range', 'date', 'textarea', 'select', 'checkbox', 'radio', 'acceptance', 'quiz', 'file', 'hidden' );
	private $special_mail_tags = array( 'date', 'time', 'serial_number', 'remote_ip', 'user_agent', 'url', 'post_id', 'post_name', 'post_title', 'post_url', 'post_author', 'post_author_email', 'site_title', 'site_description', 'site_url', 'site_admin_email', 'user_login', 'user_email', 'user_display_name' );
	protected $gs_uploads      = array();

	/**
	 *  Set things up.
	 *
	 *  @since 1.0
	 */
	public function __construct() {

		// AJAX action to verify Google Sheets integration status.
		add_action(
			'wp_ajax_verify_gs_integation',
			array( $this, 'verify_gs_integation' )
		);

		// AJAX action to deactivate Google Sheets integration.
		add_action(
			'wp_ajax_deactivate_gs_integation',
			array( $this, 'deactivate_gs_integation' )
		);

		// clear debug logs method using ajax for system status tab
		add_action( 'wp_ajax_cf7_clear_debug_log', array( $this, 'cf7_clear_debug_logs' ) );

		// Add new tab to contact form 7 editors panel
		add_filter( 'wpcf7_editor_panels', array( $this, 'cf7_gs_editor_panels' ) );

		// Save Google Sheets settings when a Contact Form 7 form is updated.
		add_action( 'wpcf7_after_save', array( $this, 'save_gs_settings' ) );

		// Store uploaded files locally before form submission is processed.
		add_action( 'wpcf7_before_send_mail', array( $this, 'save_uploaded_files_local' ) );

		// Send Contact Form 7 submission data to Google Sheets after successful submission.
		add_action( 'wpcf7_mail_sent', array( $this, 'cf7_save_to_google_sheets' ) );

		// AJAX action to save plugin uninstall settings.
		add_action( 'wp_ajax_gscf7_save_uninstall_settings', array( $this, 'gscf7_save_uninstall_settings' ) );

		// AJAX actions for admin notice management.
		add_action( 'wp_ajax_gscf7_dismiss_notice', array( $this, 'gscf7_dismiss_notice_callback' ) );
		add_action( 'wp_ajax_gscf7_snooze_notice', array( $this, 'gscf7_snooze_notice_callback' ) );

		// AJAX action to save the selected Google authentication method.
		add_action( 'wp_ajax_save_method_api_cf7', array( $this, 'save_method_api_cf7' ) );

		// AJAX actions for Service Account authentication management.
		add_action( 'wp_ajax_save_service_account_json_cf7', array( $this, 'save_service_account_json_cf7' ) );
		add_action( 'wp_ajax_deactivate_service_account_cf7', array( $this, 'deactivate_service_account_cf7' ) );

		// pagination
		add_action(
			'wp_ajax_gscf7_paginate_feed_list',
			array( $this, 'gscf7_paginate_feed_list' )
		);
		add_action( 'wp_ajax_gscf7_clear_logs', array( $this, 'ajax_clear_logs' ) );
	}
	public function ajax_clear_logs() {

		check_ajax_referer( 'gs-ajax-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Permission denied.', 'cf7-google-sheets-connector' ),
				),
				403
			);
		}

		global $wpdb;

		$table = $wpdb->prefix . 'gscf7_error_logs';

		$result = $wpdb->query( "TRUNCATE TABLE `{$table}`" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( false === $result ) {
			wp_send_json_error(
				array(
					'message' => __( 'Failed to clear logs.', 'cf7-google-sheets-connector' ),
				)
			);
		}

		wp_send_json_success(
			array(
				'message' => __( 'Logs cleared successfully.', 'cf7-google-sheets-connector' ),
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
	public static function gscf7_validate_service_account_json( $json ) {
		$required = array(
			'client_email',
			'private_key',
			'type',
			'project_id',
			'token_uri',
		);

		foreach ( $required as $key ) {
			if ( ! isset( $json[ $key ] ) || ! is_scalar( $json[ $key ] ) || '' === trim( (string) $json[ $key ] ) ) {
				return __( 'Your uploaded JSON key is invalid.', 'cf7-google-sheets-connector' );
			}
		}

		$client_email = trim( (string) $json['client_email'] );
		$private_key  = (string) $json['private_key'];

		if (
		! preg_match( '/\A[^\s@]+@[^\s@]+\.iam\.gserviceaccount\.com\z/', $client_email )
		|| false === strpos( $private_key, 'BEGIN PRIVATE KEY' )
		) {
			return __( 'Your uploaded JSON key is invalid.', 'cf7-google-sheets-connector' );
		}

		if ( 'service_account' !== trim( (string) $json['type'] ) ) {
			return __( 'Invalid JSON: file is not a service_account type.', 'cf7-google-sheets-connector' );
		}

		return '';
	}

	/**
	 * Save Google Service Account JSON credentials via AJAX.
	 * Validates the JSON content and stores it in the WordPress options table.
	 *
	 * @return void
	 */
	public function save_service_account_json_cf7() {

		// Verify AJAX nonce.
		check_ajax_referer( 'gs-ajax-nonce', 'security' );

		// Ensure the current user has permission to manage plugin settings.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'error' => __( 'Permission denied', 'cf7-google-sheets-connector' ),
				)
			);
		}

		// Get and sanitize the submitted JSON credentials.
		$json = isset( $_POST['json'] )
		? sanitize_textarea_field( wp_unslash( $_POST['json'] ) )
		: '';

		// Decode JSON to validate its structure.
		$decoded = json_decode( $json, true );

		// Return an error if the JSON is invalid.
		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
			wp_send_json_error(
				array(
					'error' => __( 'Invalid JSON format', 'cf7-google-sheets-connector' ),
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
		$validation_error = self::gscf7_validate_service_account_json( $decoded );

		if ( '' !== $validation_error ) {
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
			wp_json_encode( $decoded ),
			false
		);
		update_option( 'gs_verify_service', 'valid' );
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
	public function deactivate_service_account_cf7() {

		// Verify AJAX nonce for security.
		check_ajax_referer( 'gs-ajax-nonce', 'security' );

		// Ensure the current user has permission to manage plugin settings.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'error' => __( 'Permission denied', 'cf7-google-sheets-connector' ),
				)
			);
		}

		// Remove the stored Service Account JSON credentials.
		delete_option( 'gs_cf7_service_account_json' );
		delete_option( 'gs_verify_service' );
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
	public function save_method_api_cf7() {
		try {
			$msg = array();
			check_ajax_referer( 'gs-ajax-nonce', 'security' );
		   // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from Google.
			$method = isset( $_POST['method_api_cf7'] ) ? sanitize_text_field( wp_unslash( $_POST['method_api_cf7'] ) ) : '';
			// Store the current selected method
			update_option( 'gs_cf7_auth_method', $method );
			wp_send_json_success();
		} catch ( Exception $e ) {
			$msg['ERROR_MSG'] = $e->getMessage();
			$msg['TRACE_STK'] = $e->getTraceAsString();
			Gs_Connector_Free_Utility::gs_debug_log( $msg );
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
	public function gscf7_dismiss_notice_callback() {
		check_ajax_referer( 'gs-ajax-nonce', 'security' );
		if ( ! isset( $_POST['key'] ) ) {
			wp_send_json_error( 'Missing key' );
			return;
		}
		$key = sanitize_text_field( wp_unslash( $_POST['key'] ) );
		update_option( 'gscf7_free_notice_' . $key, 'dismissed', false );
		wp_send_json_success();
	}

	/**
	 * Handles the AJAX request for paginating the dashboard feed list.
	 *
	 * Verifies security nonces, processes current page parameters, fetches feed entries
	 * from the database, and returns the rendered HTML payload.
	 *
	 * @since 5.2.0
	 *
	 * @return void Sends a JSON response using wp_send_json_success().
	 */
	public function gscf7_paginate_feed_list() {

		check_ajax_referer( 'gscf7-pagination', 'security' );

		/*
		* This response contains the Google Spreadsheet IDs for every connected form.
		* Restrict it to users who may manage the plugin's settings.
		*/
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'error' => __( 'Permission denied', 'cf7-google-sheets-connector' ),
				)
			);
			return;
		}

		$paged            = isset( $_POST['paged'] ) ? absint( $_POST['paged'] ) : 1;
		$first_page_count = 3;
		$per_page         = 4;

		global $wpdb;

		/*
		* Row model: one row PER FEED.
		* cf7gs_feeds.form_id -> forms (many feeds per form)
		* cf7gs_settings.feed_id -> feeds (one settings row per feed, matched by feed_id, NOT form_id)
		*
		* Only the rows for the requested page are fetched via LIMIT/OFFSET,
		* rather than fetching everything and slicing in PHP.
		*/
	   // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$total_rows = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*)
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->prefix}cf7gs_feeds f
                ON p.ID = f.form_id
            WHERE p.post_type = %s",
				'wpcf7_contact_form'
			)
		);

		if ( $total_rows <= $first_page_count ) {
			$total_pages = 1;
		} else {
			$total_pages = 1 + (int) ceil( ( $total_rows - $first_page_count ) / $per_page );
		}

		$paged = max( 1, min( $paged, $total_pages ) );

		if ( 1 === $paged ) {
			$offset       = 0;
			$slice_length = $first_page_count;
		} else {
			$offset       = $first_page_count + ( ( $paged - 2 ) * $per_page );
			$slice_length = $per_page;
		}

	   // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$query = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
                p.ID,
                p.post_title,
                f.id AS feed_id,
                f.feed_name,
                f.form_id,
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
            ORDER BY p.ID ASC, f.id ASC
            LIMIT %d OFFSET %d",
				'wpcf7_contact_form',
				$slice_length,
				$offset
			)
		);

		// Rows are already limited to the current page.
		$result = $this->gscf7_render_feed_page(
			$query,
			$paged,
			$first_page_count,
			$per_page,
			$total_rows
		);

		wp_send_json_success( $result );
	}
	/**
	 * Renders the HTML output for the feed table rows and pagination controls.
	 *
	 * Offsets results dynamically to accommodate different item counts between page 1
	 * and subsequent pages.
	 *
	 * @since 5.2.0
	 *
	 * @return array {
	 *     Structured HTML data and state status.
	 *
	 *     @type string $rows_html       Rendered HTML for the <tr> table rows.
	 *     @type string $pagination_html Rendered HTML for pagination buttons.
	 *     @type bool   $has_feeds       Whether any database rows exist.
	 * }
	 */
	public function gscf7_render_feed_page( $rows, $paged, $first_page_count = 3, $per_page = 4, $total_rows = null ) {

		/*
		* When $total_rows is supplied, $rows already contains only the current
		* page (the query applied LIMIT/OFFSET). Otherwise fall back to slicing in
		* PHP so existing callers keep working unchanged.
		*/
		$rows_are_paged = ( null !== $total_rows );

		if ( ! $rows_are_paged ) {
			$total_rows = count( $rows );
		}

		// Calculate total pages with standard + custom first page count
		if ( $total_rows <= $first_page_count ) {
			$total_pages = 1;
		} else {
			$remaining_rows = $total_rows - $first_page_count;
			$total_pages    = 1 + (int) ceil( $remaining_rows / $per_page );
		}

		$paged = max( 1, min( $paged, $total_pages ) );

		if ( $rows_are_paged ) {

			$paged_rows = $rows;
		} else {

			// Compute dynamic offset based on current page
			if ( $paged === 1 ) {
				$offset       = 0;
				$slice_length = $first_page_count;
			} else {
				$offset       = $first_page_count + ( ( $paged - 2 ) * $per_page );
				$slice_length = $per_page;
			}

			$paged_rows = array_slice( $rows, $offset, $slice_length );
		}

		// 1. Render Table Rows HTML
		ob_start();

		if ( empty( $paged_rows ) ) {
			?>
		<tr>
			<td colspan="3" class="gscf7-feed-empty-cell">
				<div class="gscf7-feed-empty text-center">
					<div class="heading">
					<?php esc_html_e( 'No Form Feeds Created Yet', 'cf7-google-sheets-connector' ); ?>
					</div>
					<p>
					<?php
						esc_html_e(
							'Connect your form to Google Sheets to automatically sync submissions in real time. Create a feed to start sending data to your spreadsheet.',
							'cf7-google-sheets-connector'
						);
					?>
					</p>
				</div>
			</td>
		</tr>
			<?php
		} else {
			foreach ( $paged_rows as $row ) {
				?>
			<tr>
				<td>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcf7&post=' . $row->ID . '&action=edit' ) ); ?>">
					<?php echo esc_html( $row->post_title ); ?>
					</a>
				</td>
				<td>
					<?php echo esc_html( $row->feed_name ); ?>
				</td>
				<td>
					<?php if ( ! empty( $row->sheet_id ) ) : ?>
					<a target="_blank"
						href="<?php echo esc_url( 'https://docs.google.com/spreadsheets/d/' . $row->sheet_id . '/edit#gid=' . $row->tab_id ); ?>">
						<?php echo esc_html( $row->sheet_name ); ?>
					</a>
					<?php else : ?>
					<span class="gscf7-not-connected"><?php echo esc_html__( 'Not connected', 'cf7-google-sheets-connector' ); ?></span>
					<?php endif; ?>
				</td>
			</tr>
				<?php
			}
		}

		$rows_html = ob_get_clean();

		// 2. Render Pagination HTML
		ob_start();

		if ( $total_pages > 1 ) {
			for ( $i = 1; $i <= $total_pages; $i++ ) {
				?>
			<a href="javascript:void(0)"
				class="gscf7-page-link <?php echo $i == $paged ? 'active' : ''; ?>"
				data-page="<?php echo esc_attr( $i ); ?>">
				<?php echo esc_html( $i ); ?>
			</a>
				<?php
			}
		}

		$pagination_html = ob_get_clean();

		return array(
			'rows_html'       => $rows_html,
			'pagination_html' => $pagination_html,
			// Reflects whether any feeds exist at all, not just on this page.
			'has_feeds'       => $total_rows > 0,
		);
	}

	/**
	 * Snooze an admin notice temporarily.
	 *
	 * Stores the current timestamp to delay redisplaying
	 * the specified notice.
	 *
	 * @return void
	 */
	public function gscf7_snooze_notice_callback() {
		check_ajax_referer( 'gs-ajax-nonce', 'security' );
		if ( ! isset( $_POST['key'] ) ) {
			wp_send_json_error( 'Missing key' );
			return;
		}
		$key = sanitize_text_field( wp_unslash( $_POST['key'] ) );
		update_option( 'gscf7_free_notice_' . $key . '_time', time(), false );
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
	public function cfdb7_before_send_mail( $form_tag ) {
		$gs_cf7db_setting = get_option( 'gs_cf7db_setting' );
		if ( $gs_cf7db_setting == 1 ) {
			$cf7db = new GS_CF7DB();
			$cf7db->cfdb7_before_send_mail( $form_tag, $this->gs_uploads );
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
	public function gscf7_save_uninstall_settings() {
		/* Verify security nonce to prevent CSRF */
		check_ajax_referer( 'gs-ajax-nonce', 'security' );
		/* Check user capability */
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied' );
		}
		/*
		* Get uninstall setting value from AJAX request
		* Default value is 'No' if not provided
		*/
		$setting = isset( $_POST['uninstall_setting'] )

		? sanitize_text_field( wp_unslash( $_POST['uninstall_setting'] ) )

		: 'No';
		/* Save uninstall setting to WordPress options table */
		update_option( 'gscf7_uninstall_settings_free', $setting );
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
	public function get_cf7gs_system_info() {
		global $wpdb;
		// Get WordPress version
		$wp_version         = get_bloginfo( 'version' );
		$theme_data         = wp_get_theme();
		$theme_name_version = $theme_data->get( 'Name' ) . ' ' . $theme_data->get( 'Version' );
		$parent_theme       = $theme_data->get( 'Template' );
		if ( ! empty( $parent_theme ) ) {
			$parent_theme_data         = wp_get_theme( $parent_theme );
			$parent_theme_name_version = $parent_theme_data->get( 'Name' ) . ' ' . $parent_theme_data->get( 'Version' );
		} else {
			$parent_theme_name_version = 'N/A';
		}
		// Check plugin version and subscription plan
		$plugin_version    = defined( 'GS_CONNECTOR_VERSION' ) ? GS_CONNECTOR_VERSION : 'N/A';
		$subscription_plan = 'FREE';
		// Check Google Account Authentication
		$api_token_auto     = get_option( 'gs_token' );
		$gs_cf7_auth_method = get_option( 'gs_cf7_auth_method' );
		$cf7_service_email  = '';
		$api_token_service  = get_option( 'gs_cf7_service_account_json' );
		$selected_method    = '';
		if ( $gs_cf7_auth_method == 'cf7_existing' ) {
			$selected_method = esc_html__( 'Authenticated Using Existing Method', 'cf7-google-sheets-connector' );
		} elseif ( $gs_cf7_auth_method == 'cf7_service' ) {

			$selected_method = esc_html__( 'Authenticated Using Service Account', 'cf7-google-sheets-connector' );
		}
		if ( $gs_cf7_auth_method == 'cf7_existing' ) {
			// The user is authenticated through the auto method
			$google_sheet_auto  = new CF7GSC_googlesheet();
			$email_account_auto = $google_sheet_auto->gsheet_print_google_account_email();
			$connected_email    = ! empty( $email_account_auto ) ? esc_html( $email_account_auto ) : 'Not Connected';
		} elseif ( $gs_cf7_auth_method == 'cf7_service' ) {
			$decoded_json = json_decode( $api_token_service, true );
			if ( json_last_error() === JSON_ERROR_NONE && isset( $decoded_json['client_email'] ) ) {
				$cf7_service_email = $decoded_json['client_email'];
				$connected_email   = $cf7_service_email;
				// $cf7_service_valid = true;
			}
		} else {
			// Neither auto nor manual authentication is available
			$connected_email = 'Not Connected';
		}
		$gs_verify_status  = get_option( 'gs_verify' );
		$search_permission = ( $gs_verify_status === 'valid' ) ? 'Granted' : 'Denied';
		$system_info       = '<div class="system-statuswc">';
		$system_info      .= '<div class="mb-20 mt-20"><button id="cf7-free-show-info-button" class="info-button">GSheetConnector<span class="dashicons dashicons-arrow-down"></span></div>';
		$system_info      .= '<div id="info-container" class="info-content shadow-box pt-20 pb-20 pl-30 pr-30" style="display:none;">';
		$system_info      .= '<table>';
		$system_info      .= '<tr><td>Plugin Name</td><td class="fw-600 common-badge-table info-name-blue">CF7 Google Sheet Connector</td></tr>';
		$system_info      .= '<tr><td>Plugin Version</td><td class="fw-600 common-badge-table info-name-blue">' . esc_html( $plugin_version ) . '</td></tr>';
		$system_info      .= '<tr><td>Plugin Subscription Plan</td><td class="fw-600 common-badge-table pro-badge">' . esc_html( $subscription_plan ) . '</td></tr>';
		$system_info      .= '<tr><td>Connected Email Account</td><td class="fw-600">' . $connected_email . '</td></tr>';
		$system_info      .= '<tr><td>Authentication method for connecting to Google Sheets</td><td class="fw-600">' . esc_html( $selected_method ) . '</td></tr>';
		$gscpclass         = ' permission-not-given';
		if ( $search_permission == 'Granted' ) {
			$gscpclass = ' permission-given';
		}
		if ( $gs_cf7_auth_method == 'cf7_existing' ) {
			$system_info .= '<tr><td>Google Drive Permission</td><td class="fw-700 permission-badge' . $gscpclass . '">' . esc_html( $search_permission ) . '</td></tr>';

			$system_info .= '<tr><td>Google Sheet Permission</td><td class="fw-700 permission-badge' . $gscpclass . '">' . esc_html( $search_permission ) . '</td></tr>';
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

		$system_info .= '<tr><td>Version</td><td class="fw-600 common-badge-table info-name-blue">' . get_bloginfo( 'version' ) . '</td></tr>';

		$system_info .= '<tr><td>Site Language</td><td class="fw-600">' . get_bloginfo( 'language' ) . '</td></tr>';

		$system_info .= '<tr><td>Debug Mode</td><td class="fw-600 common-badge-table info-name-yellow">' . ( WP_DEBUG ? 'Enabled' : 'Disabled' ) . '</td></tr>';

		$system_info .= '<tr><td>Home URL</td><td class="fw-600 common-badge-table info-name-blue">' . get_home_url() . '</td></tr>';

		$system_info .= '<tr><td>Site URL</td><td class="fw-600 common-badge-table info-name-blue">' . get_site_url() . '</td></tr>';

		$system_info .= '<tr><td>Permalink structure</td><td class="fw-600">' . get_option( 'permalink_structure' ) . '</td></tr>';

		$system_info .= '<tr><td>Is this site using HTTPS?</td><td class="fw-600">' . ( is_ssl() ? 'Yes' : 'No' ) . '</td></tr>';

		$system_info .= '<tr><td>Is this a multisite?</td><td class="fw-600">' . ( is_multisite() ? 'Yes' : 'No' ) . '</td></tr>';

		$system_info .= '<tr><td>Can anyone register on this site?</td><td class="fw-600">' . ( get_option( 'users_can_register' ) ? 'Yes' : 'No' ) . '</td></tr>';

		$system_info .= '<tr><td>Is this site discouraging search engines?</td><td class="fw-600">' . ( get_option( 'blog_public' ) ? 'No' : 'Yes' ) . '</td></tr>';

		$system_info .= '<tr><td>Default comment status</td><td class="fw-600">' . get_option( 'default_comment_status' ) . '</td></tr>';
		$server_ip    = '';

		if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {

			$server_ip = sanitize_text_field(
				wp_unslash( $_SERVER['REMOTE_ADDR'] )
			);
		}

		if ( $server_ip == '127.0.0.1' || $server_ip == '::1' ) {

			$environment_type = 'localhost';
		} else {

			$environment_type = 'production';
		}

		$system_info .= '<tr><td>Environment type</td><td class="fw-600 common-badge-table info-name-yellow">' . esc_html( $environment_type ) . '</td></tr>';

		$user_count = count_users();

		$total_users = $user_count['total_users'];

		$system_info .= '<tr><td>User Count</td><td class="fw-600">' . esc_html( $total_users ) . '</td></tr>';

		$system_info .= '<tr><td>Communication with WordPress.org</td><td class="fw-600">' . ( get_option( 'blog_publicize' ) ? 'Yes' : 'No' ) . '</td></tr>';

		$system_info .= '</table>';

		$system_info .= '</div>';

		// info about active theme

		$active_theme = wp_get_theme();

		$system_info .= '<div class="mb-20 mt-20"><button id="show-active-info-button" class="info-button">Active Theme<span class="dashicons dashicons-arrow-down"></span></div>';

		$system_info .= '<div id="active-info-container" class="info-content shadow-box pt-20 pb-20 pl-30 pr-30" style="display:none;">';

		$system_info .= '<table>';

		$system_info .= '<tr><td>Name</td><td class="fw-600 common-badge-table info-name-blue">' . $active_theme->get( 'Name' ) . '</td></tr>';

		$system_info .= '<tr><td>Version</td><td class="fw-600 common-badge-table info-name-blue">' . $active_theme->get( 'Version' ) . '</td></tr>';

		$system_info .= '<tr><td>Author</td><td class="fw-600">' . $active_theme->get( 'Author' ) . '</td></tr>';

		$system_info .= '<tr><td>Author website</td><td class="fw-600">' . $active_theme->get( 'AuthorURI' ) . '</td></tr>';

		$system_info .= '<tr><td>Theme directory location</td><td class="fw-600">' . $active_theme->get_template_directory() . '</td></tr>';

		$system_info .= '</table>';

		$system_info .= '</div>';
		// Get a list of other plugins you want to check compatibility with

		$other_plugins = array(

			'plugin-folder/plugin-file.php', // Replace with the actual plugin slug

		// Add more plugins as needed

		);
		// Network Active Plugins

		if ( is_multisite() ) {

			$network_active_plugins = get_site_option( 'active_sitewide_plugins', array() );

			if ( ! empty( $network_active_plugins ) ) {

				$system_info .= '<div class="mb-20 mt-20"><button id="show-netplug-info-button" class="info-button">Network Active plugins<span class="dashicons dashicons-arrow-down"></span></div>';

				$system_info .= '<div id="netplug-info-container" class="info-content shadow-box pt-20 pb-20 pl-30 pr-30" style="display:none;">';

				$system_info .= '<table>';

				foreach ( $network_active_plugins as $plugin => $plugin_data ) {

					$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );

					$system_info .= '<tr><td>' . $plugin_data['Name'] . '</td><td>' . $plugin_data['Version'] . '</td></tr>';
				}

				// Add more network active plugin statuses here...

				$system_info .= '</table>';

				$system_info .= '</div>';
			}
		}

		// Active plugins.
		$active_plugins = get_option( 'active_plugins', array() );

		// Total active plugins count
		$total_active_plugins = count( $active_plugins );
		$system_info         .= '<div class="mb-20 mt-20">

      <button id="show-acplug-info-button" class="info-button">'

		. esc_html__( 'Active plugins', 'cf7-google-sheets-connector' ) .

		' (' . esc_html( $total_active_plugins ) . ') 

      <span class="dashicons dashicons-arrow-down"></span>

      </button>

      </div>';

		$system_info .= '<div id="acplug-info-container" class="info-content shadow-box pt-20 pb-20 pl-30 pr-30" style="display:none;">';

		$system_info .= '<table>';
		// Retrieve all active plugins data

		$active_plugins_data = array();

		$active_plugins = get_option( 'active_plugins', array() );

		foreach ( $active_plugins as $plugin ) {

			$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );

			$active_plugins_data[ $plugin ] = array(

				'name'    => $plugin_data['Name'],

				'version' => $plugin_data['Version'],

				'count'   => 0, // Initialize the count to zero

			);
		}
		// Count the number of active installations for each plugin

		$all_plugins = get_plugins();

		foreach ( $all_plugins as $plugin_file => $plugin_data ) {

			if ( array_key_exists( $plugin_file, $active_plugins_data ) ) {

				++$active_plugins_data[ $plugin_file ]['count'];
			}
		}

		// Sort plugins based on the number of active installations (descending order)

		uasort(
			$active_plugins_data,
			function ( $a, $b ) {

				return $b['count'] - $a['count'];
			}
		);

		// Display the top 5 most used plugins

		$counter = 0;

		foreach ( $active_plugins_data as $plugin_data ) {

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

		$system_info .= '<tr><td>Server Architecture</td><td class="fw-600">' . esc_html( php_uname( 's' ) ) . '</td></tr>';

		$server_software = '';

		if ( isset( $_SERVER['SERVER_SOFTWARE'] ) ) {

			$server_software = sanitize_text_field(
				wp_unslash( $_SERVER['SERVER_SOFTWARE'] )
			);
		}

		$system_info .= '<tr><td>Web Server</td><td class="fw-600">' . esc_html( $server_software ) . '</td></tr>';

		$system_info .= '<tr><td>PHP Version</td><td class="fw-600 common-badge-table info-name-blue">' . esc_html( phpversion() ) . '</td></tr>';

		$system_info .= '<tr><td>PHP SAPI</td><td class="fw-600">' . esc_html( php_sapi_name() ) . '</td></tr>';

		$system_info .= '<tr><td>PHP Max Input Variables</td><td class="fw-600">' . esc_html( ini_get( 'max_input_vars' ) ) . '</td></tr>';

		$system_info .= '<tr><td>PHP Time Limit</td><td class="fw-600">' . esc_html( ini_get( 'max_execution_time' ) ) . ' seconds</td></tr>';

		$system_info .= '<tr><td>PHP Memory Limit</td><td class="fw-600">' . esc_html( ini_get( 'memory_limit' ) ) . '</td></tr>';

		$system_info .= '<tr><td>Max Input Time</td><td class="fw-600">' . esc_html( ini_get( 'max_input_time' ) ) . ' seconds</td></tr>';

		$system_info .= '<tr><td>Upload Max Filesize</td><td class="fw-600">' . esc_html( ini_get( 'upload_max_filesize' ) ) . '</td></tr>';

		$system_info .= '<tr><td>PHP Post Max Size</td><td class="fw-600">' . esc_html( ini_get( 'post_max_size' ) ) . '</td></tr>';

		$system_info .= '<tr><td>cURL Version</td><td class="fw-600">' . esc_html( curl_version()['version'] ) . '</td></tr>';

		$system_info .= '<tr><td>Is SUHOSIN Installed?</td><td class="fw-600">' . ( extension_loaded( 'suhosin' ) ? 'Yes' : 'No' ) . '</td></tr>';

		$system_info .= '<tr><td>Is the Imagick Library Available?</td><td class="fw-600">' . ( extension_loaded( 'imagick' ) ? 'Yes' : 'No' ) . '</td></tr>';

		$system_info .= '<tr><td>Are Pretty Permalinks Supported?</td><td class="fw-600">' . ( get_option( 'permalink_structure' ) ? 'Yes' : 'No' ) . '</td></tr>';

		$htaccess_path = ABSPATH . '.htaccess';

		$system_info .= sprintf(
			'<tr><td>%s</td><td>%s</td></tr>',
			esc_html__( '.htaccess Rules', 'cf7-google-sheets-connector' ),
			esc_html( wp_is_writable( $htaccess_path ) ? 'Writable' : 'Non Writable' )
		);

		$system_info .= '<tr><td>Current Time</td><td class="fw-600">' . esc_html( current_time( 'mysql' ) ) . '</td></tr>';

		$system_info .= '<tr><td>Current UTC Time</td><td class="fw-600">' . esc_html( current_time( 'mysql', true ) ) . '</td></tr>';

		$system_info .= '<tr><td>Current Server Time</td><td class="fw-600">' . esc_html( gmdate( 'Y-m-d H:i:s' ) ) . '</td></tr>';

		$system_info .= '</table>';

		$system_info .= '</div>';

		// Database Configuration
		$system_info .= '<div class="mb-20 mt-20"><button id="show-database-info-button" class="info-button">Database<span class="dashicons dashicons-arrow-down"></span></div>';

		$system_info .= '<div id="database-info-container" class="info-content shadow-box pt-20 pb-20 pl-30 pr-30" style="display:none;">';

		$system_info .= '<table>';

		$database_extension = 'mysqli';
	   // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking whether a custom plugin table exists.
		$database_server_version = $wpdb->get_var( 'SELECT VERSION() as version' );

		$database_client_version = $wpdb->db_version();

		$database_username = DB_USER;

		$database_host = DB_HOST;

		$database_name = DB_NAME;

		$table_prefix = $wpdb->prefix;

		$database_charset = $wpdb->charset;

		$database_collation = $wpdb->collate;
	   // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking whether a custom plugin table exists.
		$max_allowed_packet_size = $wpdb->get_var( "SHOW VARIABLES LIKE 'max_allowed_packet'" );
	   // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking whether a custom plugin table exists.
		$max_connections_number = $wpdb->get_var( "SHOW VARIABLES LIKE 'max_connections'" );

		$system_info .= '<tr><td>Extension</td><td class="fw-600">' . esc_html( $database_extension ) . '</td></tr>';

		$system_info .= '<tr><td>Server Version</td><td class="fw-600 common-badge-table info-name-blue">' . esc_html( $database_server_version ) . '</td></tr>';

		$system_info .= '<tr><td>Client Version</td><td class="fw-600 common-badge-table info-name-blue">' . esc_html( $database_client_version ) . '</td></tr>';

		$system_info .= '<tr><td>Database Username</td><td class="fw-600">' . esc_html( $database_username ) . '</td></tr>';

		$system_info .= '<tr><td>Database Host</td><td class="fw-600 common-badge-table info-name-yellow">' . esc_html( $database_host ) . '</td></tr>';

		$system_info .= '<tr><td>Database Name</td><td class="fw-600 common-badge-table info-name-blue">' . esc_html( $database_name ) . '</td></tr>';

		$system_info .= '<tr><td>Table Prefix</td><td class="fw-600">' . esc_html( $table_prefix ) . '</td></tr>';

		$system_info .= '<tr><td>Database Charset</td><td class="fw-600">' . esc_html( $database_charset ) . '</td></tr>';

		$system_info .= '<tr><td>Database Collation</td><td class="fw-600">' . esc_html( $database_collation ) . '</td></tr>';

		$system_info .= '<tr><td>Max Allowed Packet Size</td><td class="fw-600">' . esc_html( $max_allowed_packet_size ) . '</td></tr>';

		$system_info .= '<tr><td>Max Connections Number</td><td class="fw-600">' . esc_html( $max_connections_number ) . '</td></tr>';

		$system_info .= '</table>';

		$system_info .= '</div>';

		// WordPress constants

		$system_info .= '<div class="mb-20 mt-20"><button id="show-wrcons-info-button" class="info-button">WordPress Constants<span class="dashicons dashicons-arrow-down"></span></div>';

		$system_info .= '<div id="wrcons-info-container" class="info-content shadow-box pt-20 pb-20 pl-30 pr-30" style="display:none;">';

		$system_info .= '<table>';

		// Add WordPress Constants information

		$system_info .= '<tr><td>ABSPATH</td><td class="fw-600">' . esc_html( ABSPATH ) . '</td></tr>';

		$system_info .= '<tr><td>WP_HOME</td><td class="fw-600 common-badge-table info-name-blue">' . esc_html( home_url() ) . '</td></tr>';

		$system_info .= '<tr><td>WP_SITEURL</td><td class="fw-600">' . esc_html( site_url() ) . '</td></tr>';

		$system_info .= '<tr><td>WP_CONTENT_DIR</td><td class="fw-600">' . esc_html( WP_CONTENT_DIR ) . '</td></tr>';

		$system_info .= '<tr><td>WP_PLUGIN_DIR</td><td class="fw-600">' . esc_html( WP_PLUGIN_DIR ) . '</td></tr>';

		$system_info .= '<tr><td>WP_MEMORY_LIMIT</td><td class="fw-600">' . esc_html( WP_MEMORY_LIMIT ) . '</td></tr>';

		$system_info .= '<tr><td>WP_MAX_MEMORY_LIMIT</td><td class="fw-600">' . esc_html( WP_MAX_MEMORY_LIMIT ) . '</td></tr>';

		$system_info .= '<tr><td>WP_DEBUG</td><td class="fw-600">' . ( defined( 'WP_DEBUG' ) && WP_DEBUG ? 'Yes' : 'No' ) . '</td></tr>';

		$system_info .= '<tr><td>WP_DEBUG_DISPLAY</td><td class="fw-600">' . ( defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY ? 'Yes' : 'No' ) . '</td></tr>';

		$system_info .= '<tr><td>SCRIPT_DEBUG</td><td class="fw-600">' . ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? 'Yes' : 'No' ) . '</td></tr>';

		$system_info .= '<tr><td>WP_CACHE</td><td class="fw-600">' . ( defined( 'WP_CACHE' ) && WP_CACHE ? 'Yes' : 'No' ) . '</td></tr>';

		$system_info .= '<tr><td>CONCATENATE_SCRIPTS</td><td class="fw-600">' . ( defined( 'CONCATENATE_SCRIPTS' ) && CONCATENATE_SCRIPTS ? 'Yes' : 'No' ) . '</td></tr>';

		$system_info .= '<tr><td>COMPRESS_SCRIPTS</td><td class="fw-600">' . ( defined( 'COMPRESS_SCRIPTS' ) && COMPRESS_SCRIPTS ? 'Yes' : 'No' ) . '</td></tr>';

		$system_info .= '<tr><td>COMPRESS_CSS</td><td class="fw-600">' . ( defined( 'COMPRESS_CSS' ) && COMPRESS_CSS ? 'Yes' : 'No' ) . '</td></tr>';

		// Manually define the environment type (example values: 'development', 'staging', 'production')

		$environment_type = 'development';

		// Display the environment type

		$system_info .= '<tr><td>WP_ENVIRONMENT_TYPE</td><td class="fw-600">' . esc_html( $environment_type ) . '</td></tr>';

		$system_info .= '<tr><td>WP_DEVELOPMENT_MODE</td><td class="fw-600">' . ( defined( 'WP_DEVELOPMENT_MODE' ) && WP_DEVELOPMENT_MODE ? 'Yes' : 'No' ) . '</td></tr>';

		$system_info .= '<tr><td>DB_CHARSET</td><td class="fw-600">' . esc_html( DB_CHARSET ) . '</td></tr>';

		$system_info .= '<tr><td>DB_COLLATE</td><td class="fw-600">' . esc_html( DB_COLLATE ) . '</td></tr>';

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
			esc_html__( 'The main WordPress directory', 'cf7-google-sheets-connector' ),
			esc_html( ABSPATH ),
			esc_html( wp_is_writable( ABSPATH ) ? 'Writable' : 'Not Writable' )
		);

		$system_info .= sprintf(
			'<tr><td>%s</td><td>%s</td><td>%s</td></tr>',
			esc_html__( 'The wp-content directory', 'cf7-google-sheets-connector' ),
			esc_html( WP_CONTENT_DIR ),
			esc_html( wp_is_writable( WP_CONTENT_DIR ) ? 'Writable' : 'Not Writable' )
		);

		$system_info .= sprintf(
			'<tr><td>%s</td><td>%s</td><td>%s</td></tr>',
			esc_html__( 'The uploads directory', 'cf7-google-sheets-connector' ),
			esc_html( $upload_dir['basedir'] ),
			esc_html( wp_is_writable( $upload_dir['basedir'] ) ? 'Writable' : 'Not Writable' )
		);

		$system_info .= sprintf(
			'<tr><td>%s</td><td>%s</td><td>%s</td></tr>',
			esc_html__( 'The plugins directory', 'cf7-google-sheets-connector' ),
			esc_html( WP_PLUGIN_DIR ),
			esc_html( wp_is_writable( WP_PLUGIN_DIR ) ? 'Writable' : 'Not Writable' )
		);

		$system_info .= sprintf(
			'<tr><td>%s</td><td>%s</td><td>%s</td></tr>',
			esc_html__( 'The themes directory', 'cf7-google-sheets-connector' ),
			esc_html( get_theme_root() ),
			esc_html( wp_is_writable( get_theme_root() ) ? 'Writable' : 'Not Writable' )
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
	public function display_error_cf7_log() {
		ob_start();
		$debug_log_file = WP_CONTENT_DIR . '/debug.log';
		$rows           = array();
		// If file exists → process logs
		if ( file_exists( $debug_log_file ) ) {
			$debug_log_contents = file_get_contents( $debug_log_file );
			$log_lines          = explode( "\n", $debug_log_contents );
			$last_lines         = array_slice( array_reverse( $log_lines ), 0, 100 );
			foreach ( $last_lines as $line ) {
				if ( empty( $line ) ) {
					continue;
				}
				preg_match(
					'/\[(.*?)\]\sPHP\s(.*?):\s(.*?)\sin\s(.*?)\son\sline\s(\d+)/',
					$line,
					$matches
				);
				if ( ! empty( $matches ) ) {

					$raw_date = $matches[1];

					/*
					 * debug.log timestamps carry their own timezone suffix, e.g.
					 * "28-Jul-2026 19:38:00 UTC". wp_date() converts the parsed
					 * moment into the site's configured timezone (Settings > General)
					 * and appends its abbreviation, matching the Error Log screen.
					 */
					$timestamp = strtotime( $raw_date );

					$formatted_date = $timestamp
						? wp_date(
							get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) . ' (T)',
							$timestamp
						)
						: $raw_date;

						$rows[] = array(

							'date'    => $formatted_date,

							'type'    => $matches[2],

							'message' => wp_strip_all_tags( $matches[3] ),

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

		if ( empty( $rows ) ) {

			echo '<tr>

         <td colspan="4" style="text-align:center;">

         No debug log data found.

         </td>

         </tr>';
		} else {

			foreach ( $rows as $row ) {

				echo '<tr>

            <td>' . esc_html( $row['date'] ) . '</td>

            <td>' . esc_html( $row['type'] ) . '</td>

            <td>' . esc_html( $row['message'] ) . '</td>

            <td>' . esc_html( $row['file'] ) . '</td>

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
	public function verify_gs_integation() {

		// nonce checksave_gs_settings
		check_ajax_referer( 'gs-ajax-nonce', 'security' );
		$Code = '';
		if ( isset( $_POST['code'] ) ) {
			$Code = sanitize_text_field(
				wp_unslash( $_POST['code'] )
			);
		}
		update_option( 'gs_access_code', $Code, false );
		if ( get_option( 'gs_access_code' ) != '' ) {

			include_once GS_CONNECTOR_ROOT . '/lib/google-sheets.php';

			cf7gsc_googlesheet::preauth( get_option( 'gs_access_code' ) );

			update_option( 'gs_cf7_manual_setting', '0' );

			CF7GSC_googlesheet::gsc_flush_meta_cache();

			wp_send_json_success();
		} else {

			update_option( 'gs_verify', 'invalid' );

			wp_send_json_error();
		}
	}

	/**
	 * AJAX function - deactivate activation
	 *
	 * @since 4.2
	 */
	public function deactivate_gs_integation() {

		check_ajax_referer( 'gs-ajax-nonce', 'security' );
		if ( get_option( 'gs_token' ) !== '' ) {
			delete_option( 'gs_feeds' );
			delete_option( 'gs_sheetId' );
			delete_option( 'gs_token' );
			delete_option( 'cf7gf_email_account' );
			delete_option( 'gs_access_code' );
			delete_option( 'gs_verify' );
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
	public function cf7_clear_debug_logs() {

		// Nonce check
		check_ajax_referer( 'gs-ajax-nonce', 'security' );

		global $wp_filesystem;

		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';

			/*
			* WP_Filesystem() returns false when credentials are unavailable and
			* leaves $wp_filesystem null. Calling a method on it then raised a
			* fatal error.
			*/
			if ( ! WP_Filesystem() ) {
				wp_send_json_error(
					array(
						'error' => __( 'Filesystem access is unavailable on this site.', 'cf7-google-sheets-connector' ),
					)
				);
			}
		}

		if ( ! is_object( $wp_filesystem ) ) {
			wp_send_json_error(
				array(
					'error' => __( 'Filesystem access is unavailable on this site.', 'cf7-google-sheets-connector' ),
				)
			);
		}

		/*
		* Honour a custom WP_DEBUG_LOG path. Hardcoding wp-content/debug.log
		* truncated a file the site may not even be writing to.
		*/
		$log_path = ( defined( 'WP_DEBUG_LOG' ) && is_string( WP_DEBUG_LOG ) && '' !== WP_DEBUG_LOG )
		? WP_DEBUG_LOG
		: WP_CONTENT_DIR . '/debug.log';

		$wp_filesystem->put_contents( $log_path, '', FS_CHMOD_FILE );
		wp_send_json_success();
	}

	/**
	 * Add new tab to contact form 7 editors panel
	 *
	 * @since 1.0
	 */
	public function cf7_gs_editor_panels( $panels ) {

		$selected_method = '';

		$authenticated = get_option( 'gs_token' );

		$cf7_manual = get_option( 'cf7_manual' );

		$auth_method = get_option( 'gs_cf7_auth_method' );

		$authenticatedService = get_option( 'gs_cf7_service_account_json' );

		$email_account = '';

		$gsc_is_valid = get_option( 'gs_verify' );

		if ( ! empty( $authenticated ) && $gsc_is_valid == 'valid' && $auth_method === 'cf7_existing' ) {

			$google_sheet = new CF7GSC_googlesheet();

			$email_account = $google_sheet->gsheet_print_google_account_email();

			if ( $email_account ) {

				$selected_method = ' (' . __( 'Existing', 'cf7-google-sheets-connector' ) . ')';
			}
		} elseif ( ! empty( $authenticatedService ) && $auth_method === 'cf7_service' ) {

			$selected_method = ' (' . __( 'Service', 'cf7-google-sheets-connector' ) . ')';
		}

		if ( current_user_can( 'wpcf7_edit_contact_forms' ) ) {

			$panels['google_sheets'] = array(

				'title'    => __( 'Google Sheets', 'cf7-google-sheets-connector' ) . $selected_method,

				'callback' => array( $this, 'cf7_editor_panel_google_sheet' ),

			);
		}

		return $panels;
	}

	/**
	 * Set Google sheet settings with contact form
	 *
	 * @since 1.0
	 */
	public function save_gs_settings( $post ) {

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
		if ( ! isset( $_POST['cf7-gs'] ) ) {

			return;
		}

		$sheet_data = array_map(
			'sanitize_text_field',
			wp_unslash( (array) $_POST['cf7-gs'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce and capability verified by Contact Form 7 before this hook fires.
		);

		// update_post_meta($post->id(), 'gs_settings', $sheet_data);

		$form_id = $post->id();

		$feeds_table = $wpdb->prefix . 'cf7gs_feeds';

		$settings_table = $wpdb->prefix . 'cf7gs_settings';
	   // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking whether a custom plugin table exists.
		$feed_id = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT id FROM `' . esc_sql( $wpdb->prefix . 'cf7gs_feeds' ) . '` WHERE form_id = %d',
				$form_id
			)
		);

		if ( ! $feed_id ) {
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
				'SELECT id FROM `' . esc_sql( $wpdb->prefix . 'cf7gs_settings' ) . '` WHERE feed_id = %d',
				$feed_id
			)
		);

		if ( $exists ) {

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
				array( 'feed_id' => $feed_id )
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
		if ( ! empty( $sheet_id ) && class_exists( 'CF7GSC_googlesheet' ) ) {
			CF7GSC_googlesheet::gsc_flush_meta_cache( $sheet_id );
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
	public function save_uploaded_files_local( $form_data ) {
		$upload = wp_upload_dir();

		if ( get_option( 'uploads_use_yearmonth_folders' ) ) {
			$time             = current_time( 'mysql' );
			$y                = substr( $time, 0, 4 );
			$m                = substr( $time, 5, 2 );
			$upload['subdir'] = "/$y/$m";
		}

		$upload['subdir'] = '/cf7gs' . $upload['subdir'];
		$upload['path']   = $upload['basedir'] . $upload['subdir'];
		$upload['url']    = $upload['baseurl'] . $upload['subdir'];

		if ( ! is_dir( $upload['path'] ) ) {
			wp_mkdir_p( $upload['path'] );
		}

		$htaccess_file = sprintf( '%s/.htaccess', $upload['path'] );

		if ( ! file_exists( $htaccess_file ) ) :
			file_put_contents( $htaccess_file, 'Options -Indexes' );
		endif;

		$time_now = time();

		$form = WPCF7_Submission::get_instance();

		if ( $form ) {
			$files = $form->uploaded_files();

			$uploads_stored = array();

			if ( ! empty( $files ) ) {
				foreach ( $files as $name => $paths ) {
					// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Read-only presence check on a Contact Form 7 submission already validated by CF7's own nonce handling; no data is taken from the superglobal.
					if ( ! isset( $_FILES[ $name ] ) || empty( $paths ) ) {
						continue;
					}

					$paths = is_array( $paths ) ? $paths : array( $paths );

					foreach ( $paths as $path ) {
						if ( ! file_exists( $path ) ) {
							continue;
						}

						$file_name       = sanitize_file_name( basename( $path ) );
						$destination     = $upload['path'] . '/' . $time_now . '-' . $file_name;
						$destination_url = sprintf( '%s/%s', $upload['url'], $time_now . '-' . $file_name );

						$uploads_stored[ $name ][] = $destination_url;

						copy( $path, $destination );
					}
				}

				$this->gs_uploads = $uploads_stored;
			}
		}
	}
	public function get_special_mail_tags() {

		return $this->special_mail_tags;
	}

	/**
	 * Function - To send contact form data to google spreadsheet
	 *
	 * @param object $form
	 * @since 1.0
	 */
	public function cf7_save_to_google_sheets( $form ) {

		$form_id = $form->id();

		$enable_entry_id = get_option( 'gs_cf7db_setting' ) == '1';

		$next_entry_id = 0;

		if ( $enable_entry_id ) {

			global $wpdb;

			$cfdb = apply_filters( 'cfdb7_database', $wpdb );

			$table_name = $cfdb->prefix . 'cf7db_gsheet_forms';

			$latest_entry_id = $cfdb->get_var(
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking whether a custom plugin table exists.
				$cfdb->prepare(
					"SELECT MAX(id) FROM $table_name WHERE form_id = %d",
					$form_id
				)
			);

			$latest_entry_id = intval( $latest_entry_id );

			if ( $latest_entry_id <= 0 ) {

				$next_entry_id = 1;
			} else {

				$next_entry_id = $latest_entry_id + 1;
			}
		}

		$this->cfdb7_before_send_mail( $form );

		$submission = WPCF7_Submission::get_instance();

		global $wpdb;

		$table_name = $wpdb->prefix . 'cf7gs_settings';

	   // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table, caching not appropriate here.
		$form_data = $wpdb->get_row(
			$wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name sanitized with esc_sql() and backticks.
				'SELECT * FROM `' . esc_sql( $table_name ) . '` WHERE form_id = %d',
				$form_id
			),
			ARRAY_A
		);

		if ( empty( $form_data ) ) {

			return;
		}

		$sheet_name = $form_data['sheet_name'] ?? '';

		$tab_name = $form_data['tab_name'] ?? '';

		$sheet_id = $form_data['sheet_id'] ?? '';

		$tab_id = $form_data['tab_id'] ?? '';

		$data = array();

		if ( $submission && ! empty( $sheet_name ) && ! empty( $tab_name ) ) {

			$posted_data = $submission->get_posted_data();

			try {

				include_once GS_CONNECTOR_ROOT . '/lib/google-sheets.php';

				$doc = new cf7gsc_googlesheet();

				$doc->auth();

				$doc->setSpreadsheetId( $sheet_id );

				$doc->setWorkTabId( $tab_id );

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

				foreach ( $special_mail_tags as $smt ) {

					$tagname = sprintf( '_%s', $smt );

					$mail_tag = new WPCF7_MailTag( sprintf( '[%s]', $tagname ), $tagname, '' );
				   // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- CF7 core hook, not defined by this plugin.
					$meta[ $smt ] = apply_filters( 'wpcf7_special_mail_tags', '', $tagname, false, $mail_tag );
				}

				if ( ! empty( $meta ) ) {

					$data['date'] = $meta['date'];

					$data['time'] = $meta['time'];

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

					$data['Time'] = $meta['time'];

					$data['DATE'] = $meta['date'];

					$data['TIME'] = $meta['time'];
				}

				// ========================

				// POSTED FORM DATA

				// ========================

				foreach ( $posted_data as $key => $value ) {

					// skip CF7 internal fields

					if ( strpos( $key, '_wpcf7' ) !== false || strpos( $key, '_wpnonce' ) !== false ) {

						continue;
					}

					// handle file uploads

					$uploaded_file = $this->gs_uploads;

					if ( array_key_exists( $key, $uploaded_file ) ) {

						$file_value = $uploaded_file[ $key ];

						if ( is_array( $file_value ) ) {
							// Multiple files on this field: sheet shows filenames only, comma-separated.
							$data[ $key ] = implode( ', ', array_map( 'basename', $file_value ) );
						} else {
							$data[ $key ] = basename( $file_value );
						}

						continue;
					}

					// handle arrays (checkbox, multi-select)

					if ( is_array( $value ) ) {

						$data[ $key ] = sanitize_text_field( implode( ', ', $value ) );
					} else {

						$data[ $key ] = sanitize_textarea_field( stripcslashes( $value ) );
					}
				}

				// ========================

				// STRIP FORMULA INJECTION

				// ========================

				foreach ( $data as $key => $value ) {

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
					if ( '' !== $value && false !== strpos( "=+-@\t\r", $value[0] ) ) {

						$data[ $key ] = "'" . $value;
					}
				}

				// ========================

				// FIX 3: entry_id injected FIRST using array_merge

				// ========================

				if ( $enable_entry_id ) {

					$data = array_merge(
						array(

							'entry_id' => $next_entry_id,

							'Entry ID' => $next_entry_id,

						),
						$data
					);
				}

			   // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- CF7 core hook, not defined by this plugin.
				$data = apply_filters( 'gsc_filter_form_data', $data, $form );

				$gsc_result = $doc->add_row( $data );

				/*
				* Surface a failed write. add_row() previously returned silently on
				* error, so a submission could be lost with nothing recorded.
				*/
				if ( is_wp_error( $gsc_result ) ) {

					Gs_Connector_Free_Utility::gs_debug_log(
						array(
							'context' => 'cf7_save_to_google_sheets',
							'form_id' => $form_id,
							'fields'  => array_keys( $data ),
							'error'   => $gsc_result->get_error_message(),
						)
					);
				}
			} catch ( Exception $e ) {

				/*
				* Log diagnostic context only. $data holds the visitor's submission,
				* so field names are recorded but never their values.
				*/
				Gs_Connector_Free_Utility::gs_debug_log(
					array(
						'context' => 'cf7_save_to_google_sheets',
						'form_id' => $form_id,
						'fields'  => array_keys( $data ),
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

	public function cf7_editor_panel_google_sheet( $post ) {

		// Fall back to the form object when the screen has no `post` query arg
		// (e.g. the "Add New Form" screen), so $form_id is always defined below.
		$form_id = is_object( $post ) && method_exists( $post, 'id' ) ? $post->id() : 0;
		?>

		<div id="gscf7-free-metabox" class="postbox d-none gscf7-upgrade-pro-informationdiv gscf7-free">

		<div class="inside">

			<div>

				<div class="gsheet-header-logo d-flex justify-center mt-20 mb-20">

					<img src="<?php echo esc_url( GS_CONNECTOR_URL ); ?>/assets/img/gsc-logo.webp" width="150px">

				</div>

			</div>

			<ul class="mt-10 mb-20">

				<li><?php echo esc_html( __( 'Automatic sheet configuration', 'cf7-google-sheets-connector' ) ); ?></li>

				<li><?php echo esc_html( __( 'Smart field mapping', 'cf7-google-sheets-connector' ) ); ?></li>

				<li><?php echo esc_html( __( 'Conditional logic support', 'cf7-google-sheets-connector' ) ); ?></li>

				<li><?php echo esc_html( __( 'Header and row customization', 'cf7-google-sheets-connector' ) ); ?></li>

				<li><?php echo esc_html( __( 'Advanced mail tags', 'cf7-google-sheets-connector' ) ); ?></li>

				<li><?php echo esc_html( __( 'Real-time submission sync', 'cf7-google-sheets-connector' ) ); ?></li>

				<li><?php echo esc_html( __( 'Spreadsheet download option', 'cf7-google-sheets-connector' ) ); ?></li>

			</ul>

			<div class="text-center">

				<a class="btn btn-primary link-hover-white text-center" href="https://www.gsheetconnector.com/cf7-google-sheet-connector-pro" target="_blank">

					<?php echo esc_html__( 'Upgrade to Pro', 'cf7-google-sheets-connector' ); ?>

				</a>

			</div>

		</div>

		</div>

		<?php

		// Check if the user is authenticated

		$authenticated = get_option( 'gs_token' );

		$per = get_option( 'gs_verify' );

		// check user is authenticated when save existing api method

		$show_setting = 0;

		$selected_method = '';

		$selected_method_display = '';

		$authenticated = get_option( 'gs_token' );

		$cf7_manual = get_option( 'cf7_manual' );

		$auth_method = get_option( 'gs_cf7_auth_method' );

		$authenticatedService = get_option( 'gs_cf7_service_account_json' );

		if ( $auth_method == 'cf7_existing' ) {

			// The user is authenticated through the auto method

			$google_sheet_auto = new CF7GSC_googlesheet();

			$selected_method_display = esc_html__( 'Authenticated Using Existing Method', 'cf7-google-sheets-connector' );

			$email_account_auto = $google_sheet_auto->gsheet_print_google_account_email();

			$connected_email = ! empty( $email_account_auto ) ? esc_html( $email_account_auto ) : 'Not Connected';
		} elseif ( $auth_method == 'cf7_service' ) {

			$decoded_json = json_decode( $authenticatedService, true );

			if ( json_last_error() === JSON_ERROR_NONE && isset( $decoded_json['client_email'] ) ) {

				$cf7_service_email = $decoded_json['client_email'];

				$connected_email = $cf7_service_email;

				// $cf7_service_valid = true;

				$selected_method_display = esc_html__( 'Service account', 'cf7-google-sheets-connector' );
			}
		} else {

			// Neither auto nor manual authentication is available

			$connected_email = 'Not Connected';
		}

		// Check if the user is authenticated when saving existing API method

		if ( ( ! empty( $authenticated ) && $per == 'valid' && $auth_method === 'cf7_existing' ) || ( $auth_method === 'cf7_service' && ! empty( $authenticatedService ) && json_decode( $authenticatedService, true ) ) ) {

			$show_setting = 1;
		} else {

			?>

			<?php

			if ( $auth_method == 'cf7_existing' ) {

				$selected_method = __( 'Existing Client / Secret Key (Auto Setup).', 'cf7-google-sheets-connector' );
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

						<div class="feed-alert-header"><?php esc_html_e( 'Google Sheets Setup Required', 'cf7-google-sheets-connector' ); ?></div>

						<p><?php esc_html_e( 'your selected Method is : ', 'cf7-google-sheets-connector' ); ?><?php echo esc_html( $selected_method ); ?></p>

						<p><?php esc_html_e( 'To start sending form entries to Google Sheets, please connect your Google account first.', 'cf7-google-sheets-connector' ); ?></p>

						<ul>

							<li><?php esc_html_e( '✔ Click on the Sign in with Google button', 'cf7-google-sheets-connector' ); ?></li>

							<li><?php esc_html_e( '✔ Log in using your Google account', 'cf7-google-sheets-connector' ); ?></li>

							<li><?php esc_html_e( '✔ Select the Google account where your Sheets are stored', 'cf7-google-sheets-connector' ); ?></li>

							<li><?php esc_html_e( '✔ Grant access to: Google Drive & Google Sheets', 'cf7-google-sheets-connector' ); ?></li>

							<li><?php esc_html_e( '✔ Save the authentication code if prompted', 'cf7-google-sheets-connector' ); ?></li>

						</ul>

						<a href="admin.php?page=wpcf7-google-sheet-config&tab=integration" class="gsc-alert-btn link-hover-white">

								<?php esc_html_e( 'Go to Integration Setup', 'cf7-google-sheets-connector' ); ?>

						</a>

					</div>



					</div>

				</div>

			</div>

				<?php

			} elseif ( $auth_method == 'cf7_service' ) {

				$selected_method = __( 'Service Account (Recommended).', 'cf7-google-sheets-connector' );
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

						<div class="feed-alert-header"><?php esc_html_e( 'Google Sheets Setup Required', 'cf7-google-sheets-connector' ); ?></div>

						<p><?php esc_html_e( 'your selected Method is : ', 'cf7-google-sheets-connector' ); ?><?php echo esc_html( $selected_method ); ?></p>

						<p><?php esc_html_e( 'To connect Google Sheets using Service Account:', 'cf7-google-sheets-connector' ); ?></p>

						<ul>

							<li><?php esc_html_e( '✔ Go to Google Cloud Console', 'cf7-google-sheets-connector' ); ?></li>

							<li><?php esc_html_e( '✔ Create or select a project', 'cf7-google-sheets-connector' ); ?></li>

							<li><?php esc_html_e( '✔ Enable Google Sheets & Drive APIs', 'cf7-google-sheets-connector' ); ?></li>

							<li><?php esc_html_e( '✔ Create a Service Account', 'cf7-google-sheets-connector' ); ?></li>

							<li><?php esc_html_e( '✔ Generate and download the JSON key', 'cf7-google-sheets-connector' ); ?></li>

							<li><?php esc_html_e( '✔ Upload the JSON file here', 'cf7-google-sheets-connector' ); ?></li>

							<li><?php esc_html_e( '✔ Share your Google Sheet with the Service Account email', 'cf7-google-sheets-connector' ); ?></li>

						</ul>

						<a href="<?php echo esc_html( admin_url( 'admin.php?page=wpcf7-google-sheet-config&tab=integration' ) ); ?>" class="gsc-alert-btn link-hover-white">

							<?php esc_html_e( 'Go to Integration Setup', 'cf7-google-sheets-connector' ); ?>

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

		if ( $show_setting == 1 ) {

			$form_data = '';
		   // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from Google.
			if ( isset( $_GET['post'] ) ) {

				$form_id = '';

			   // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from Google.
				if ( isset( $_GET['post'] ) ) {

					$form_id = sanitize_text_field(
					// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from Google.
						wp_unslash( $_GET['post'] )
					);
				}

				global $wpdb;
			   // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from Google.
				$form_id = isset( $_GET['post'] )

				? sanitize_text_field( wp_unslash( $_GET['post'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from Google.

				: '';
				$feeds_table    = $wpdb->prefix . 'cf7gs_feeds';
				$settings_table = $wpdb->prefix . 'cf7gs_settings';
			   // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking whether a custom plugin table exists.
				$feed_id = $wpdb->get_var(

               // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
					$wpdb->prepare(
						'SELECT id FROM `' . esc_sql( $wpdb->prefix . 'cf7gs_feeds' ) . '` WHERE form_id = %d LIMIT 1',
						$form_id
					)
				);

				// Get settings data

				$form_data = '';

				if ( $feed_id ) {
					 // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking whether a custom plugin table exists.
					$row = $wpdb->get_row(

					 // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
						$wpdb->prepare(
							'SELECT * FROM `' . esc_sql( $wpdb->prefix . 'cf7gs_settings' ) . '` WHERE feed_id = %d LIMIT 1',
							$feed_id
						),
						ARRAY_A
					);

					if ( ! empty( $row ) ) {

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

						<?php esc_html_e( 'Single Sheet Connection', 'cf7-google-sheets-connector' ); ?>

					</a>

					</li>

					<li class="ui-tab cf7-sub-tab-multi-li">

					<a href="#" class="cf7gs-tab-toggle" data-tab="cf7-sub-tab-multi">

						<?php esc_html_e( 'Multi Sheet Connection', 'cf7-google-sheets-connector' ); ?>

						<span class="edit-form-pro"><?php esc_html_e( 'Pro', 'cf7-google-sheets-connector' ); ?></span>

					</a>

					</li>

				</ul>

				<div class="cf7-sub-tab-single cf7-sub-tab" style="display:block">


					<div class="gscf7-integration-box">

					<div class="gsc-google-auth-card mt-30 mb-30">
						<div>
							<div class="heading mt-0 mb-30"> <?php echo esc_html( __( 'Google Account Connection', 'cf7-google-sheets-connector' ) ); ?>
								<span class="badge"><?php echo esc_attr( $selected_method_display ); ?></span>
							</div>
						</div>

						<div class="d-flex flex-wrap gap-20 justify-between align-center">

							<div class="gsc-google-auth-left d-flex flex-wrap align-center gap-15">

								<div class="gsc-google-icon">G</div>

								<div class="connected-account">

								<div class="gsc-connected-left d-flex">

									<span class="gsc-connected-label">
										<?php echo esc_html( __( 'Connected Email Account', 'cf7-google-sheets-connector' ) ); ?>

									</span>

									<span class="connected-account-manual gsc-connected-email">

										<?php echo esc_html( $connected_email ); ?>
									</span>

								</div>

								</div>

							</div>

							<div class="gsc-google-auth-right">

								<div class="gsc-connected-pill">

								<span class="dot"></span>

								<?php echo esc_html( __( ' Connected', 'cf7-google-sheets-connector' ) ); ?>
								</div>

							</div>
						</div>

					</div>

					</div>
					<!-- Single sheet connection START -->

					<form method="post">



					<div class="gs-fields shadow-box mt-30 p-30">

						<div class="heading mt-0">

							<?php echo esc_html( __( 'Manual Google Sheets Configuration', 'cf7-google-sheets-connector' ) ); ?>

						</div>

						<p><?php echo esc_html( __( 'Connect your Google Sheet by entering the required sheet information.', 'cf7-google-sheets-connector' ) ); ?></p>

						<div class="row">

							<div class="col-6 res-top-20">

								<div class="form-group field-row mr-10">

								<label><?php echo esc_html( __( 'Sheet Name', 'cf7-google-sheets-connector' ) ); ?>

									<span class="tooltip"

										data-tooltip="<?php echo esc_html( __( 'Enter the exact name of your Google Spreadsheet (as shown in Google Sheets).', 'cf7-google-sheets-connector' ) ); ?>"

										data-tooltip-pos="right" data-tooltip-length="medium">

										<i class="fa-solid fa-circle-question help-icon"></i>

									</span>

								</label>

								<input type="text" class="form-control" name="cf7-gs[sheet-name]" id="gs-sheet-name"

									value="<?php echo ( isset( $form_data['sheet-name'] ) ) ? esc_attr( $form_data['sheet-name'] ) : ''; ?>" />

								<div class="input-msg d-none">

									<?php echo esc_html__( 'Please fill out this field', 'cf7-google-sheets-connector' ); ?>

								</div>

								</div>

							</div>

							<div class="col-6 res-top-20">

								<div class="form-group field-row mr-10">

								<label><?php echo esc_html( __( 'Sheet ID', 'cf7-google-sheets-connector' ) ); ?>

									<span class="tooltip"

										data-tooltip="<?php echo esc_html( __( 'Paste the Spreadsheet ID from your Google Sheet URL. (Example: ', 'cf7-google-sheets-connector' ) ); ?> https://docs.google.com/spreadsheets/d/**SPREADSHEET_ID**/edit)"

										data-tooltip-pos="right" data-tooltip-length="medium">

										<i class="fa-solid fa-circle-question help-icon"></i>

									</span>

								</label>

								<input type="text" class="form-control" name="cf7-gs[sheet-id]" id="gs-sheet-id"

									value="<?php echo ( isset( $form_data['sheet-id'] ) ) ? esc_attr( $form_data['sheet-id'] ) : ''; ?>" />

								<div class="input-msg d-none">

									<?php echo esc_html__( 'Please fill out this field', 'cf7-google-sheets-connector' ); ?>

								</div>

								</div>

							</div>

							<div class="col-6 mt-20">

								<div class="form-group field-row mr-10">

								<label

									for="edit-tab-name"><?php echo esc_html( __( 'Tab Name', 'cf7-google-sheets-connector' ) ); ?>

									<span class="tooltip"

										data-tooltip="<?php echo esc_html( __( 'Enter the exact sheet tab name (e.g., Sheet1) from the bottom of your Google Spreadsheet.', 'cf7-google-sheets-connector' ) ); ?>"

										data-tooltip-pos="right" data-tooltip-length="medium">

										<i class="fa-solid fa-circle-question help-icon"></i>

									</span>

								</label>

								<input type="text" class="form-control" id="cf7-gs[edit-tab-name]" name="cf7-gs[sheet-tab-name]"

									value="<?php echo ( isset( $form_data['sheet-tab-name'] ) ) ? esc_attr( $form_data['sheet-tab-name'] ) : ''; ?>">

								<div class="input-msg d-none">

									<?php echo esc_html__( 'Please fill out this field', 'cf7-google-sheets-connector' ); ?>

								</div>

								</div>

							</div>

							<div class="col-6 mt-20">

								<div class="form-group field-row mr-10">

								<label><?php echo esc_html( __( 'Tab ID', 'cf7-google-sheets-connector' ) ); ?>

									<span class="tooltip"

										data-tooltip="<?php echo esc_html( __( 'Get the Tab ID from your sheet URL after gid=.', 'cf7-google-sheets-connector' ) ); ?>"

										data-tooltip-pos="right" data-tooltip-length="medium">

										<i class="fa-solid fa-circle-question help-icon"></i>

									</span>

								</label>

								<input type="text" class="form-control" name="cf7-gs[tab-id]" id="gs-tab-id"



									value="<?php echo ( isset( $form_data['tab-id'] ) ) ? esc_attr( $form_data['tab-id'] ) : ''; ?>" />

								<div class="input-msg d-none">

									<?php echo esc_html__( 'Please fill out this field', 'cf7-google-sheets-connector' ); ?>

								</div>

								</div>

							</div>

						</div>



						<div class="sheet-url field-row">

							<?php
							if ( ( isset( $form_data['sheet-name'] ) ) && ! empty( $form_data['sheet-name'] ) && ( isset( $form_data['sheet-id'] ) ) && ( ! empty( $form_data['sheet-id'] ) ) && ( isset( $form_data['sheet-tab-name'] ) ) && ( ! empty( $form_data['sheet-tab-name'] ) ) && ( isset( $form_data['tab-id'] ) ) ) {

								$link = 'https://docs.google.com/spreadsheets/d/' . $form_data['sheet-id'] . '/edit#gid=' . $form_data['tab-id'];

								?>

								<a class=" sheet-url-cf7 common-sheet-url btn text-dark text-decoration-none mt-30" href="<?php echo esc_url( $link ); ?>" target="_blank" class="cf7_gs_link" title="<?php echo esc_html__( 'View Spreadsheet', 'cf7-google-sheets-connector' ); ?>">
								<i class="fa-regular fa-eye"></i></a>

							<?php } ?>

							<?php
							if ( ( isset( $form_data['sheet-name'] ) ) && ! empty( $form_data['sheet-name'] ) && ( isset( $form_data['sheet-id'] ) ) && ( ! empty( $form_data['sheet-id'] ) ) && ( isset( $form_data['sheet-tab-name'] ) ) && ( ! empty( $form_data['sheet-tab-name'] ) ) && ( isset( $form_data['tab-id'] ) ) ) {

								?>


								<?php } ?>

						</div>

					</div>

					</form>

					<?php

					$cf7_service_json = get_option( 'gs_cf7_service_account_json' );

					$gs_cf7_auth_method = get_option( 'gs_cf7_auth_method' );

					$cf7_service_email = '';

					// Decode JSON safely

					if ( ! empty( $cf7_service_json ) ) {

						$decoded = json_decode( $cf7_service_json, true );

						if ( ! empty( $decoded ) && isset( $decoded['client_email'] ) ) {

							$cf7_service_email = trim( $decoded['client_email'] );
						}
					}

					// Correct auth method check

					if ( ! empty( $cf7_service_email ) && $gs_cf7_auth_method === 'cf7_service' ) {
						?>



					<div class="sheet-email cf7gsc-notes cf7gsc-notes2" id="sheet-email">

							<?php

							$doc = new CF7GSC_googlesheet();

							$doc->auth();

							$sheet_id = ! empty( $form_data ) && isset( $form_data['sheet-id'] )

							? $form_data['sheet-id']

							: '';

							// Fallback to saved sheet

							if ( empty( $sheet_id ) ) {

								$getsheets_id = get_option( 'gs_sheetId' );

								if ( ! empty( $getsheets_id ) && ! empty( $saved_sheet_name ) && isset( $getsheets_id[ $saved_sheet_name ] ) ) {

									$sheet_details = $getsheets_id[ $saved_sheet_name ];

									$sheet_id = isset( $sheet_details['id'] ) ? $sheet_details['id'] : '';
								}
							}

							$sheet_id = trim( $sheet_id );

							$result = $doc->check_sheet_access( $sheet_id );

							// Safe result check

							if ( ! empty( $result ) && isset( $result['status'] ) && $result['status'] ) {
								?>



							<div class="gsc-sheet-info mt-30">

								<!-- Header -->

								<div class="gsc-sheet-status-header">

								<div class="gsc-status-headings fw-600">

										<?php echo esc_html__( 'Google Sheets Connection', 'cf7-google-sheets-connector' ); ?>

								</div>

								</div>



								<!-- Description -->

								<p class="gsc-sheet-status-desc">

									<?php echo esc_html__( 'Your Google Spreadsheet is securely connected and ready to receive form submissions in real time.', 'cf7-google-sheets-connector' ); ?>

								</p>



								<!-- Email Box -->

								<div class="gsc-sheet-email-box">

								<span class="email-text email-success-service">

										<?php echo esc_html( $cf7_service_email ); ?>

								</span>

								<span class="gsc-sheet-badge connected">

										<?php echo esc_html__( 'Connected Successfully', 'cf7-google-sheets-connector' ); ?>

								</span>

								</div>



								<!-- Help Info -->

								<ul class="gsc-sheet-status-info">

								<li class="success">

										<?php echo esc_html__( 'Green status means the spreadsheet access is configured correctly.', 'cf7-google-sheets-connector' ); ?>

								</li>

								<li class="error">

										<?php echo esc_html__( 'Red status means permission is missing. Please grant editor access.', 'cf7-google-sheets-connector' ); ?>

								</li>

								</ul>

							</div>



							<?php } else { ?>



							<div class="gsc-sheet-info mt-30">

								<div class="gsc-status-headings fw-600">

								<?php echo esc_html__( 'Sharing Required', 'cf7-google-sheets-connector' ); ?>

								</div>



								<p>

								<?php echo esc_html__( 'Please share your Google Spreadsheet with the following service account to enable automatic syncing.', 'cf7-google-sheets-connector' ); ?>

								</p>



								<p>

								<?php echo esc_html__( 'After sharing the spreadsheet, refresh the page to view the updated sharing status.', 'cf7-google-sheets-connector' ); ?>

								</p>



								<div class="gsc-email-box d-flex align-center justify-between email-unsuccess-service">

								<div class="gsc-service-email">

									<?php echo esc_html( $cf7_service_email ); ?>

								</div>



								<a

									data-email="<?php echo esc_attr( $cf7_service_email ); ?>"

									class="gsc-copy-btn-feed-setting text-decoration-none link-hover-white"

									id="copy-service-email">

									<?php echo esc_html__( 'Copy', 'cf7-google-sheets-connector' ); ?>

								</a>



								<div class="gsc-copy-msg d-none">

									<?php echo esc_html__( 'Copied successfully!', 'cf7-google-sheets-connector' ); ?>

								</div>

								</div>



								<ul class="gsc-status-list">

								<li class="success">

									<?php echo esc_html__( 'Green email indicates the spreadsheet is shared successfully.', 'cf7-google-sheets-connector' ); ?>

								</li>

								<li class="error">

									<?php echo esc_html__( 'Red email indicates access is missing. Please grant permission.', 'cf7-google-sheets-connector' ); ?>

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

								<?php echo esc_html( __( 'Unlock Advanced Features with Form Feeds', 'cf7-google-sheets-connector' ) ); ?>

							</div>

							<div class="d-flex flex-wrap align-items-center gap-15">

								<span class="edit-gs-pro-badge">

									<?php echo esc_html( __( 'Advanced options are available in PRO', 'cf7-google-sheets-connector' ) ); ?>

								</span>

								<span class="edit-gs-upgrade-btn">

								<a href="https://www.gsheetconnector.com/docs/cf7-gsheetconnector" target="_blank" class="text-decoration-none link-hover-white">

										<?php echo esc_html( __( 'Get Advanced Features', 'cf7-google-sheets-connector' ) ); ?>

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

									<?php echo esc_html( __( 'Automatic Google Sheets Configuration', 'cf7-google-sheets-connector' ) ); ?>

								</a>

							</div>



							<div class="gsc-pro-grid">

								<ul>

								<li><?php esc_html_e( 'Auto fetch Google Sheets list', 'cf7-google-sheets-connector' ); ?></li>

								<li><?php esc_html_e( 'Auto detect sheet tabs', 'cf7-google-sheets-connector' ); ?></li>

								<li><?php esc_html_e( 'One-click configuration', 'cf7-google-sheets-connector' ); ?></li>

								<li><?php esc_html_e( 'Real-time entry sync', 'cf7-google-sheets-connector' ); ?></li>

								</ul>

							</div>

						</div>



						<div class="edit-gs-feature-col">

							<div class="mb-20">

								<a href="#field-mapping">

									<?php echo esc_html( __( 'Select Fields to Sync', 'cf7-google-sheets-connector' ) ); ?>

								</a>

							</div>



							<div class="gsc-pro-grid">

								<ul>

								<li><?php esc_html_e( 'Drag & drop field reordering', 'cf7-google-sheets-connector' ); ?></li>

								<li><?php esc_html_e( 'Rename column headers', 'cf7-google-sheets-connector' ); ?></li>

								<li><?php esc_html_e( 'Select specific fields to sync', 'cf7-google-sheets-connector' ); ?></li>

								</ul>

							</div>

						</div>



						<div class="edit-gs-feature-col">

							<div class="mb-20">

								<a href="#conditional-logic">

									<?php echo esc_html( __( 'Conditional Logic', 'cf7-google-sheets-connector' ) ); ?>

								</a>

							</div>



							<div class="gsc-pro-grid">

								<ul>

								<li><?php esc_html_e( 'Apply rules based on form values', 'cf7-google-sheets-connector' ); ?></li>

								<li><?php esc_html_e( 'Sync data only when conditions match', 'cf7-google-sheets-connector' ); ?></li>

								<li><?php esc_html_e( 'Filter unwanted or incomplete entries', 'cf7-google-sheets-connector' ); ?></li>

								<li><?php esc_html_e( 'Create dynamic workflows automatically', 'cf7-google-sheets-connector' ); ?></li>

								<li><?php esc_html_e( 'Map data conditionally to sheet columns', 'cf7-google-sheets-connector' ); ?></li>

								<li><?php esc_html_e( 'Support multiple conditions (AND / OR)', 'cf7-google-sheets-connector' ); ?></li>

								<li><?php esc_html_e( 'Improve accuracy and reduce extra data', 'cf7-google-sheets-connector' ); ?></li>

								</ul>

							</div>

						</div>



						<div class="edit-gs-feature-col">

							<div class="mb-20">

								<a href="#header-settings-sheet-sorting">

									<?php echo esc_html( __( 'Header Settings', 'cf7-google-sheets-connector' ) ); ?>

								</a>

							</div>



							<div class="gsc-pro-grid">

								<ul>

								<li><?php esc_html_e( 'Freeze header row', 'cf7-google-sheets-connector' ); ?></li>

								<li><?php esc_html_e( 'Custom font styling', 'cf7-google-sheets-connector' ); ?></li>

								<li><?php esc_html_e( 'Header & row color control', 'cf7-google-sheets-connector' ); ?></li>

								<li><?php esc_html_e( 'Sort by any column', 'cf7-google-sheets-connector' ); ?></li>

								<li><?php esc_html_e( 'Download spreadsheet as file', 'cf7-google-sheets-connector' ); ?></li>

								</ul>

							</div>

						</div>



					</div>
					<div class="edit-gs-pro-footer">

						<label for="toggle-features" class="edit-gs-show-btn show">

							<?php echo esc_html( __( 'Show Features ▼', 'cf7-google-sheets-connector' ) ); ?>

						</label>



						<label for="toggle-features" class="edit-gs-show-btn hide">

							<?php echo esc_html( __( 'Hide Features ▲', 'cf7-google-sheets-connector' ) ); ?>

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

								<div class="heading mt-0"><?php echo esc_html( __( 'Automatic Google Sheets Configuration', 'cf7-google-sheets-connector' ) ); ?>

									<span class="pro-ver"><?php echo esc_html( __( 'Pro', 'cf7-google-sheets-connector' ) ); ?></span>

								</div>

								<p><?php echo esc_html( __( 'Automatic configure your Google Sheet and start syncing form submissions in real time.', 'cf7-google-sheets-connector' ) ); ?></p>

								<div class="row">

									<div class="col-4">

										<div class="mr-10">

											<label><?php echo esc_html( __( 'Sheet Name', 'cf7-google-sheets-connector' ) ); ?></label>

											<select name="gscf-ff[gsc-fluentform-sheet-id]" class="auto-select-display w-100 mt-5" id="gsc-fluentform-sheet-id">

											<option value="" disabled>

													<?php echo esc_html( __( 'Select', 'cf7-google-sheets-connector' ) ); ?> </option>

											<option value="create_new" disabled>

													<?php echo esc_html( __( 'Create New', 'cf7-google-sheets-connector' ) ); ?> </option>

											</select>

										</div>

									</div>

									<span class="error_msg" id="error_spread"></span>

									<span class="loading-sign">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>

									<i class="errorSelect errorSelectsheet"></i>

									<div class="col-4">

										<label><?php echo esc_html( __( 'Sheet Tab Name', 'cf7-google-sheets-connector' ) ); ?></label>

										<select name="gscf-ff[gs-sheet-tab-name]" class="auto-select-display w-100 mt-5" id="gscf7-sheet-tab-name">

											<option value="" disabled>

												<?php echo esc_html( __( 'Select', 'cf7-google-sheets-connector' ) ); ?> </option>

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

									<div class="heading"><?php echo esc_html( 'Select Fields to Sync', 'cf7-google-sheets-connector' ); ?><span class="pro-ver"><?php echo esc_html( __( 'Pro', 'cf7-google-sheets-connector' ) ); ?></span></div>

									<p class="gsc-pro-desc"><?php echo esc_html( __( 'Enable the fields you want to send to Google Sheets and rename columns if needed.', 'cf7-google-sheets-connector' ) ); ?></p>



								</div>

								<div class="gscf7-color-code flex-wrap d-flex align-center gap-20 pt-10">

									<div class="color-ffgs field-type-form">

										<span class="field-list-pill align-center fw-700"><?php echo esc_html( __( 'Field List', 'cf7-google-sheets-connector' ) ); ?></span>



									</div>

									<div class="color-ffgs field-type-system">

										<span class="align-center fw-700"><?php echo esc_html( __( 'Submission Info', 'cf7-google-sheets-connector' ) ); ?></span>

									</div>

									<div class="color-ffgs custom-mail-tags">

										<span class="align-center fw-700"><?php echo esc_html( __( 'Custom Mail Tags', 'cf7-google-sheets-connector' ) ); ?></span>

									</div>

								</div>

								</div>

								<div class="toggle-button select-all-toggle gsc-pro-content">

								<div class="mt-40 select-all-field mb-20">

									<label class="switch">

										<input type="checkbox" id="select-all-checkbox" checked disabled>

										<span class="slider round button-toggle"></span> </label>

									<span class="label-text"><?php echo esc_html( 'Select All Fields', 'cf7-google-sheets-connector' ); ?></span>

								</div>

								<div id="gscf7-sortable" class="ui-sortable connected-sortable">

										<?php $this->display_form_fields( $form_id, $post ); ?>

								</div>

								</div>

							</div>



							<!-- SECTION 2:Conditional Logic-->

							<div class="settings-card  shadow-box mt-40 p-30" id="conditional-logic">



								<div class="mt-0 heading">

									<?php echo esc_html( __( 'Conditional Logic', 'cf7-google-sheets-connector' ) ); ?>

								<span class="pro-ver"><?php echo esc_html( __( 'Pro', 'cf7-google-sheets-connector' ) ); ?></span>

								</div>

								<p class="mb-30">

									<?php echo esc_html__( 'Control how and when your form data is sent to Google Sheets using powerful conditional rules. Automatic filter, route, and manage submissions based on user input.', 'cf7-google-sheets-connector' ); ?>

								</p>



								<div class="gscfrmnt-cards gscfrmnt-card setting-row">



								<div class="misc-conditional-inner30 misc-options-inner-color-multi-cf7gs-hidden">



									<input type="hidden"

										id="getfieldList"

										value="">



									<div>

										<?php echo esc_html( __( 'Process this feed if', 'cf7-google-sheets-connector' ) ); ?>

										<select name="cf7-gs30[enable_conditional_logic_type_feed]" class="enableConditionalLogic conditional-logic">

											<option value="all"><?php echo esc_html( __( 'All', 'cf7-google-sheets-connector' ) ); ?></option>

											<option value="any" disabled><?php echo esc_html( __( 'Any', 'cf7-google-sheets-connector' ) ); ?></option>

										</select>

										<?php echo esc_html( __( 'of the following match:', 'cf7-google-sheets-connector' ) ); ?>

									</div>



									<div class="mt-30 d-flex flex-wrap gap-10">



										<select name="cf7-gs30[conditional_feed][0][enable_conditional_logic_field_name_feed]" class="enableConditionalLogic">

											<option value="your-name"><?php echo esc_html( __( 'your-name', 'cf7-google-sheets-connector' ) ); ?></option>

											<option value="your-email" disabled><?php echo esc_html( __( 'your-email', 'cf7-google-sheets-connector' ) ); ?></option>

											<option value="your-subject" disabled><?php echo esc_html( __( 'your-subject', 'cf7-google-sheets-connector' ) ); ?></option>

											<option value="your-message" disabled><?php echo esc_html( __( 'your-message', 'cf7-google-sheets-connector' ) ); ?></option>

										</select>



										<select name="cf7-gs30[conditional_feed][0][enable_conditional_logic_rule_select_feed]" class="enableConditionalLogic">

											<option value="is"><?php echo esc_html( __( 'is', 'cf7-google-sheets-connector' ) ); ?></option>

											<option value="isnot" disabled><?php echo esc_html( __( 'is not', 'cf7-google-sheets-connector' ) ); ?></option>

											<option value="greaterthan" disabled><?php echo esc_html( __( 'greater than', 'cf7-google-sheets-connector' ) ); ?></option>

											<option value="lessthan" disabled><?php echo esc_html( __( 'less than', 'cf7-google-sheets-connector' ) ); ?></option>

											<option value="contains" disabled><?php echo esc_html( __( 'contains', 'cf7-google-sheets-connector' ) ); ?></option>

											<option value="starts_with" disabled><?php echo esc_html( __( 'starts with', 'cf7-google-sheets-connector' ) ); ?></option>

											<option value="ends_with" disabled><?php echo esc_html( __( 'ends with', 'cf7-google-sheets-connector' ) ); ?></option>

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

								<div class="heading mt-0"><?php echo esc_html( __( 'Header Settings', 'cf7-google-sheets-connector' ) ); ?><span class="pro-ver"><?php echo esc_html( __( 'Pro', 'cf7-google-sheets-connector' ) ); ?></span></div>

								<p><?php echo esc_html( __( 'Customize the appearance and behavior of your sheet headers and rows for better readability and organization.', 'cf7-google-sheets-connector' ) ); ?></p>

								<!-- SECTION 1: Header Behavior -->

								<div class="header-styling-sheet d-flex gap-20">

									<div class="settings-card mb-20 w-100 bg-white">

										<div class="mt-0 header-settings-ineer-size fw-600 mb-20"><?php echo esc_html( __( 'Header Behavior', 'cf7-google-sheets-connector' ) ); ?></div>

										<div class="gscfrmnt-cards gscfrmnt-card setting-row">

											<div class="toggle-button freeze-header-toggle d-flex align-items-center justify-between mb-15">

											<span class="label-text fw-400"><?php echo esc_html( __( 'Freeze Header', 'cf7-google-sheets-connector' ) ); ?></span>

											<label class="switch">

												<input type="checkbox" id="freeze-header-checkbox" name="gscf-ff[freeze_header]" value="true" checked="" disabled>

												<span class="slider round button-toggle"></span>

											</label>

											</div>

										</div>

										<div class="sheet_sorting sheet_formatting mt-30 mb-30">

											<div class="gscfrmnt-cards">

											<div class="toggle-button sheet-sorting-toggle d-flex align-items-center justify-between">

												<span class="label-text fw-400"><?php echo esc_html( __( 'Sheet Sorting', 'cf7-google-sheets-connector' ) ); ?></span>

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

														<?php echo esc_html__( 'Sort Column', 'cf7-google-sheets-connector' ); ?>

													</label>

													<select id="sort-column-name" name="gscf-ff[sort_column]">

													</select>

												</div>

												<div class="gscfrmnt-row form-group">

													<label for="sort-order"><?php echo esc_html__( 'Sort Order', 'cf7-google-sheets-connector' ); ?></label>

													<select id="sort-order" name="gscf-ff[sort_order]">

														<option value="ASCENDING"><?php echo esc_html__( 'Ascending', 'cf7-google-sheets-connector' ); ?></option>

														<option value="DESCENDING" disabled><?php echo esc_html__( 'Descending', 'cf7-google-sheets-connector' ); ?></option>

													</select>

												</div>

											</div>

											</div>

										</div>



										<div class="mt-0 header-settings-ineer-size fw-600 mb-20"><?php echo esc_html__( 'Header Style', 'cf7-google-sheets-connector' ); ?></div>

										<div class="sheet_formatting setting-row">

											<div class="gscfrmnt-sheet_formatting gscfrmnt-sheet_formatting">

											<div class="toggle-button sheet_formatting-header-toggle d-flex align-items-center justify-between mb-15">

												<span class="label-texts  fw-400"><?php echo esc_html( __( 'Header Appearance', 'cf7-google-sheets-connector' ) ); ?> </span>

												<label class="switch" for="sheet_formatting-header-checkbox">

													<input type="checkbox" id="sheet_formatting-header-checkbox" name="gscf-ff[sheet_formatting_header]" value="1" checked="" disabled>

													<span class="slider round button-toggle"></span>

												</label>

											</div>

											</div>

											<div class="font-styling-settings" id="font-styling-settings">

											<div class="settings-grid">

												<div class="font-style row-format form-group">

													<label for="font-size"><?php echo esc_html__( 'Font Style', 'cf7-google-sheets-connector' ); ?></label>

													<div class="d-flex gap-5">

														<label class="style-btn active"><input type="checkbox" name="font_styles[]" class="toggle-input active" value="normal">

															<?php echo esc_html__( 'Normal', 'cf7-google-sheets-connector' ); ?></label>

														<label class="style-btn"><input type="checkbox" name="font_styles[]" value="italic" disabled>

															<?php echo esc_html__( 'Italic', 'cf7-google-sheets-connector' ); ?></label>

														<label class="style-btn"><input type="checkbox" name="font_styles[]" value="bold" disabled><?php echo esc_html__( 'Bold', 'cf7-google-sheets-connector' ); ?></label>

													</div>

												</div>



												<div class="font-size row-format form-group">

													<label for="font-size"><?php echo esc_html__( 'Font Size', 'cf7-google-sheets-connector' ); ?></label>

													<div class="gs-font-size">

														<div class="gs-select-box">

														<select id="font-size" name="gscf-ff[font_size]" disabled>

															<option value="" selected><?php echo esc_html__( 'Select size', 'cf7-google-sheets-connector' ); ?></option>

															<option><?php echo esc_html__( '11', 'cf7-google-sheets-connector' ); ?></option>

															<option><?php echo esc_html__( '12', 'cf7-google-sheets-connector' ); ?></option>

															<option><?php echo esc_html__( '13', 'cf7-google-sheets-connector' ); ?></option>

															<option><?php echo esc_html__( '14', 'cf7-google-sheets-connector' ); ?></option>

															<option><?php echo esc_html__( '15', 'cf7-google-sheets-connector' ); ?></option>

														</select>

														</div>

													</div>

												</div>

												<div class="font-color row-format form-group">

													<label for="font-color"><?php echo esc_html__( 'Font Color', 'cf7-google-sheets-connector' ); ?></label>

													<input type="color" id="font-color" name="gscf-ff[font_color]" class="bg-color-set-input" value="#000000" disabled>

												</div>



											</div>

											</div>

										</div>

									</div>



									<!-- SECTION 2: Header Appearance -->

									<div class="settings-card  mb-20 w-100 bg-white">

										<div class="sheet_formatting">

											<div class="mt-0 header-settings-ineer-size fw-600 mb-20"><?php echo esc_html__( 'Row Style', 'cf7-google-sheets-connector' ); ?></div>

											<div class="gscfrmnt-sheet_formatting_row gscfrmnt-sheet_formatting_row">

											<div class="toggle-button sheet_formatting-row-toggle d-flex align-items-center justify-between">

												<span class="label-texts  fw-400"><?php echo esc_html__( 'Color Appearance', 'cf7-google-sheets-connector' ); ?> </span>

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

														<?php echo esc_html__( 'Header Background', 'cf7-google-sheets-connector' ); ?> </label>

													<input type="color" id="header-color" name="gscf-ff[header-color]" class="bg-color-set-input" value="#000000" disabled>

												</div>

												<div class="settings-grid">

													<!-- ODD ROW COLOR -->

													<div class="form-group">

														<label for="odd-color"><?php echo esc_html__( 'Odd Row Color', 'cf7-google-sheets-connector' ); ?></label>

														<input type="color"

														id="odd-color"

														name="gscf-ff[odd_color]"

														class="bg-color-set-input"

														value="#ffffff" disabled>

													</div>



													<!-- EVEN ROW COLOR -->

													<div class="form-group">

														<label for="even-color"><?php echo esc_html__( 'Even Row Color', 'cf7-google-sheets-connector' ); ?></label>

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

												<span class="label-texts  fw-400"><?php echo esc_html__( 'Row Appearance', 'cf7-google-sheets-connector' ); ?> </span>

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

													<label><?php echo esc_html__( 'Font Style', 'cf7-google-sheets-connector' ); ?></label>

													<div class="d-flex gap-5">

														<label class="style-btn active">

														<input type="checkbox" name="font_styles[]" value="normal" checked> <?php echo esc_html__( 'Normal', 'cf7-google-sheets-connector' ); ?>

														</label>

														<label class="style-btn">

														<input type="checkbox" name="font_styles[]" value="italic" disabled> <?php echo esc_html__( 'Italic', 'cf7-google-sheets-connector' ); ?>

														</label>

														<label class="style-btn">

														<input type="checkbox" name="font_styles[]" value="bold" disabled> <?php echo esc_html__( 'Bold', 'cf7-google-sheets-connector' ); ?>

														</label>

													</div>

												</div>



												<!-- FONT SIZE -->

												<div class="font-size row-format form-group">

													<label for="font-size-row"><?php echo esc_html__( 'Font Size', 'cf7-google-sheets-connector' ); ?></label>

													<div class="gs-font-size">

														<div class="gs-select-box">

														<select id="font-size-row" name="gscf-ff[font_size_row]" disabled>

															<option value="14" selected><?php echo esc_html__( 'Select size', 'cf7-google-sheets-connector' ); ?></option>

															<option><?php echo esc_html__( '11', 'cf7-google-sheets-connector' ); ?></option>

															<option><?php echo esc_html__( '12', 'cf7-google-sheets-connector' ); ?></option>

															<option><?php echo esc_html__( '13', 'cf7-google-sheets-connector' ); ?></option>

															<option><?php echo esc_html__( '14', 'cf7-google-sheets-connector' ); ?></option>

															<option><?php echo esc_html__( '15', 'cf7-google-sheets-connector' ); ?></option>

														</select>

														</div>

													</div>

												</div>



												<!-- FONT COLOR -->

												<div class="font-color row-format form-group">

													<label for="font-color-row"><?php echo esc_html__( 'Font Color', 'cf7-google-sheets-connector' ); ?></label>

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

												<span class="label-text  fw-400"><?php echo esc_html( __( 'Spreadsheet Download', 'cf7-google-sheets-connector' ) ); ?></span>

												<label class="switch" for="download-toggle-checkbox">

													<input type="checkbox" id="download-toggle-checkbox" name="gscf-ff[download_spreadsheet]" value="1" checked="" disabled>

													<span class="slider round button-toggle"></span>

												</label>

											</div>

											<div id="download-button-wrapper" class="mt-15">

												<!-- style="display:none;"> -->

												<a class="sheet-url-fluentform common-sheet-url text-dark fw-700 download-spreadsheet" hover-tooltip="Spreadsheet Download">

													<i class="fa-regular fa-file-zipper text-dark fw-500 mr-5"></i><?php echo esc_html( __( 'Download', 'cf7-google-sheets-connector' ) ); ?> </a>

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

					if ( $show_setting == 1 ) {

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
	public function get_forms_connected_to_sheet() {
		global $wpdb;

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
                       s.form_id,
                       f.id AS feed_id,
                       f.feed_name,
                       f.status,
                       f.is_default
                   FROM `{$wpdb->posts}` p
                   INNER JOIN `{$wpdb->prefix}cf7gs_settings` s
                       ON p.ID = s.form_id
                   LEFT JOIN `{$wpdb->prefix}cf7gs_feeds` f
                       ON p.ID = f.form_id
                   WHERE p.post_type = %s
                   ORDER BY p.ID DESC",
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
	public function display_form_fields( $form_id ) {

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

								data-place="<?php echo esc_attr( $entry_placeholder ); ?>"

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

							value="<?php echo esc_attr( $entry_placeholder ); ?>"

							name="gs-custom-header-placeholde">



						<!-- Text input: always readonly, always "entry_id" -->

						<input

							type="text"

							data-id="gs-entry-id"

							class="field-input from-input-change d-none"

							name="gs-custom-header"

							value="entry_id"

							placeholder="<?php echo esc_attr( $entry_placeholder ); ?>"

							readonly>



						<!-- Hidden: custom-value -->

						<input

							type="hidden"

							name="custom-value"

							value="<?php echo esc_attr( $entry_placeholder ); ?>">



					</div>

					</div>

				</div>
				<?php

				$assoc_arr = array();

				$meta = get_post_meta( $form_id, '_form', true );

				$fields = $this->get_contact_form_fields( $meta );

				if ( $fields ) {

					foreach ( $fields as $field ) {

							$single = $this->get_field_assoc( $field );

						if ( $single ) {

							$assoc_arr[] = $single;
						}
					}
				}

				?>



				<?php if ( ! empty( $assoc_arr ) ) : ?>



					<?php

					$count = 0;

					foreach ( $assoc_arr as $key => $value ) {

						foreach ( $value as $k => $v ) {

							// ONLY render real field name

							if ( $k !== 'name' ) {

								continue;
							}

							$saved_val = '';

							if ( ! empty( $saved_mail_tags ) && array_key_exists( $v, $saved_mail_tags[0] ) ) {

								$saved_val = $saved_mail_tags[0][ $v ];
							}

							$placeholder = preg_replace( '/[\\_]|\\s+/', '-', $v );

							// FIXED

							$field_name = $value['name'];

							// FIXED

							$unique_id = 'gs-field-' . sanitize_key( $v ) . '-' . $count;

							$has_pipe = isset( $value['pi'] ) ? $value['pi'] : 0;

							?>



						<div class="card form-field-toggle active ui-sortable-handle">

							<div class="card-content">

								<div class="toggle-button field_list_bg">

								<i class="dashicons dashicons-edit field-edit-icon"></i>

								<label class="switch">

									<input type="checkbox"

										class="fcheckBoxClass toggle-input"

										data-value="<?php echo esc_attr( $saved_val ); ?>"

										data-place="<?php echo esc_attr( $placeholder ); ?>"

										checked disabled>

									<span class="slider round button-toggle  field-list-new"></span>

								</label>



								<span class="drag-icon">

									<i class="fas fa-grip-vertical"></i>

								</span>



								<div class="label label-text card-label">

									<?php echo esc_html( $v ); ?>

								</div>



								<circle cx="14" cy="15" r="1.5" fill="currentColor"></circle>

								</svg>

								</span>

								<input type="hidden"

									value="<?php echo esc_attr( $field_name ); ?>"

									name="gs-custom-header-key">



								<input type="hidden"

									value="<?php echo esc_attr( $placeholder ); ?>"

									name="gs-custom-header-placeholder">



								<input type="text"

									data-id="<?php echo esc_attr( $unique_id ); ?>"

									class="field-input d-none"

									name="gs-custom-header[<?php echo esc_attr( $count ); ?>]"

									value="<?php echo esc_attr( $saved_val ); ?>"

									placeholder="<?php echo esc_attr( $placeholder ); ?>" disabled>



								<!--  PIPE UI -->

								<?php if ( $has_pipe == 1 ) : ?>

									<div class="pipe-select-box">

										<select name="gs-custom-pi[<?php echo esc_attr( $field_name ); ?>]">

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

				$tags_count = count( $this->special_mail_tags );

				?>



				<?php if ( $tags_count > 0 ) : ?>



					<?php

					foreach ( $this->special_mail_tags as $count => $tag_name ) :

						$is_entry_id = ( $tag_name === 'entry_id' );

						$placeholder = str_replace( '_', '-', $tag_name );

						// Fetch saved value

						$saved_val = '';

						$checked = '';

						if ( ! empty( $saved_mail_tags ) && array_key_exists( $tag_name, $saved_mail_tags[0] ) ) {

							$saved_val = $saved_mail_tags[0][ $tag_name ];

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

									id="gs-st-ck-<?php echo esc_attr( $count ); ?>"

									name="gs-st-ck[<?php echo esc_attr( $count ); ?>]"

									value="1">

								<span class="slider round button-toggle  field-list-new"></span>

								</label>



								<span class="drag-icon">

								<i class="fas fa-grip-vertical drag-icon"></i>

								</span>



								<!-- Tag Label -->

								<div class="label label-text card-label">

								<?php echo esc_html( $tag_name ); ?>

								</div>



								<!-- Hidden: key -->

								<input

								type="hidden"

								value="<?php echo esc_attr( $tag_name ); ?>"

								name="gs-st-header-key[<?php echo esc_attr( $count ); ?>]">



								<!-- Hidden: placeholder -->

								<input

								type="hidden"

								value="<?php echo esc_attr( $placeholder ); ?>"

								name="gs-st-header-placeholder[<?php echo esc_attr( $count ); ?>]">



								<!-- Text Input -->

								<input

								type="text"

								data-id="gs-st-<?php echo esc_attr( $tag_name ); ?>"

								class="field-input from-input-change d-none"

								name="gs-st-custom-header[<?php echo esc_attr( $count ); ?>]"

								value="<?php echo $is_entry_id ? 'entry_id' : esc_attr( $saved_val ); ?>"

								placeholder="<?php echo esc_attr( $placeholder ); ?>"



								<?php echo $is_entry_id ? 'readonly' : ''; ?>>



								<!-- Hidden: custom-value -->

								<input

								type="hidden"

								name="gs-st-custom-value"

								value="<?php echo esc_attr( $placeholder ); ?>">



							</div>

						</div>

					</div>



					<?php endforeach; ?>



				<?php else : ?>



					<p>

					<span class="gs-info">

						<?php echo esc_html__( 'No special mail tags available.', 'cf7-google-sheets-connector' ); ?>

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



						<label for="gs-st-ck-<?php echo esc_attr( $count ); ?>" class="switch">



							<input

								type="checkbox"

								class="fcheckBoxClass toggle-input child-toggle check-toggle-cf7"

								id="gs-st-ck-<?php echo esc_attr( $count ); ?>"

								name="gs-st-ck[<?php echo esc_attr( $count ); ?>]"

								value="1">



							<span class="slider round button-toggle field-list-new"></span>

						</label>



						<span class="drag-icon">

							<i class="fas fa-grip-vertical drag-icon"></i>

						</span>



						<div class="label label-text card-label">

						<?php echo esc_html( $tag_name ); ?>

						</div>



						<input

							type="hidden"

							value="<?php echo esc_attr( $tag_name ); ?>"

							name="gs-st-header-key[<?php echo esc_attr( $count ); ?>]">



						<input

							type="hidden"

							value="<?php echo esc_attr( $placeholder ); ?>"

							name="gs-st-header-placeholder[<?php echo esc_attr( $count ); ?>]">



						<input

							type="text"

							data-id="gs-st-<?php echo esc_attr( $tag_name ); ?>"

							class="field-input from-input-change d-none"

							name="gs-st-custom-header[<?php echo esc_attr( $count ); ?>]"

							value="_datetime"

							placeholder="<?php echo esc_attr( $placeholder ); ?>">



						<input

							type="hidden"

							name="gs-st-custom-value"

							value="<?php echo esc_attr( $placeholder ); ?>">



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
	public function get_contact_form_fields( $meta ) {

		// FIX: non-greedy regex

		$regexp = '/\[[^\]]+\]/';

		$arr = array();

		if ( ! preg_match_all( $regexp, $meta, $arr ) ) {

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
	public function get_field_assoc( $content ) {

		$content = trim( $content );

		$content = trim( $content, '[]' );

		$parts = preg_split( '/\s+/', $content );

		if ( empty( $parts ) ) {
			return false;
		}

		// remove * from type

		$type = rtrim( $parts[0], '*' );

		if ( ! in_array( $type, $this->allowed_tags ) ) {

				return false;
		}

			$name = '';

		foreach ( $parts as $index => $part ) {

			if ( $index === 0 ) {
				continue;
			}

				// skip pipe values like CEO|email

			if ( strpos( $part, '|' ) !== false ) {
				continue;
			}

				$name = $part;

				break;
		}

		if ( empty( $name ) ) {
			return false;
		}

			// FIXED PIPE DETECTION

			$has_pipe = 0;

			$pipe_allowed_types = array( 'select', 'radio', 'checkbox' );

		if ( in_array( $type, $pipe_allowed_types ) && strpos( $content, '|' ) !== false ) {

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
	public function display_form_special_tags( $form_id ) {

		$tags_count = count( $this->special_mail_tags );

		$num_of_cols = 1;

		?>

				<h2 class="inner-title"><span class="gs-info"><?php echo esc_html( __( 'Map special mail tags with custom header name and save automatically to google sheet. ', 'cf7-google-sheets-connector' ) ); ?></span></h2>

				<ul class="gs-field-list special">

			<?php

			for ( $i = 0; $i <= $tags_count; $i++ ) {

				if ( $i == $tags_count ) {

					break;
				}

				$tag_name = $this->special_mail_tags[ $i ];

				$placeholder = str_replace( '_', '-', $tag_name );

				echo '<li>';

				echo '<div class="input-field">



                  <label for="enable-sorting-option" class="button-woo-toggle-cf7" id="sorting-toggle"></label>

                  </div>';

				echo '<div class="special-tags label">[_' . esc_attr( $tag_name ) . '] </div>';

				echo '<div class="gs-r-pad field-input  d-none"><input type="text" class="name-field" name="gs-st-custom-header[' . esc_attr( $i ) . ']" value="" disabled placeholder="' . esc_attr( $placeholder ) . '"> </div>';

				if ( $i % $num_of_cols == 1 ) {

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
	function display_form_custom_tag( $form_id ) {

		$custom_mail_tags = array();

		$num_of_cols = 2;

		if ( has_filter( 'gscf7_special_mail_tags' ) ) {

					// Filter hook for custom mail tags

					$custom_tags = apply_filters( 'gscf7_special_mail_tags', $custom_mail_tags, $form_id );

					$custom_tags_count = count( $custom_tags );

					$num_of_cols = 2;

					// fetch saved fields

					$saved_cmail_tags = get_post_meta( $form_id, 'gs_map_custom_mail_tags' );

			?>

					<ul class="gs-field-list">

					<?php

						echo '<li>';

					for ( $i = 0; $i <= $custom_tags_count; $i++ ) {

						if ( $i == $custom_tags_count ) {

							break;
						}

						$tag_name = $custom_tags[ $i ];

						$modify_tag = ltrim( $tag_name, '_' );

						$saved_val = '';

						$checked = '';

						if ( ! empty( $saved_cmail_tags ) && array_key_exists( $modify_tag, $saved_cmail_tags[0] ) ) :

							$saved_val = $saved_cmail_tags[0][ $modify_tag ];

							$checked = 'checked';

							endif;

						// hack - todo

						$placeholder_explode = explode( '_', $tag_name, 2 );

						$placeholder = str_replace( '_', '-', $placeholder_explode[1] );

						echo '<div class="input-field">

                     <label for="enable-sorting-option" class="button-woo-toggle-cf7" id="sorting-toggle"></label>

                     </div>';

						echo '<div class="label">[' . esc_attr( $tag_name ) . ']</div>';

						echo '<div class="gs-r-pad field-input d-none"><input type="hidden" name="gs-ct-key[' . esc_attr( $i ) . ']" value="' . esc_attr( $tag_name ) . '" ><input type="hidden" name="gs-ct-placeholder[' . esc_attr( $i ) . ']" value="' . esc_attr( $placeholder ) . '" >

                     <input type="text" name="gs-ct-custom-header[' . esc_attr( $i ) . ']" value="' . esc_attr( $saved_val ) . '" placeholder="' . esc_attr( $placeholder ) . '" disabled>



                     </div>';

						if ( $i % $num_of_cols == 1 ) {

							echo '</li>';
						}
					}

					?>

					</ul>

				<?php

		} else {

			echo '<p><span class="gs-info">' . esc_html__( 'No custom mail tags available.', 'cf7-google-sheets-connector' ) . '</span></p>';
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
	function display_form_conditional_logic( $form_id, $post ) {

		?>

				<div class="misc-conditional-row">

					<div class="misc-options-wrapper">

					<label for="enable-conditional-logic">

						<input type="checkbox" name="cf7-gs[enable_conditional_logic]" id="enable-conditional-logic" value="1"

							style="display: none;">

						<label for="enable-conditional-logic" class="button-woo-toggle-cf7" id="conditional-toggle"></label>

				<?php echo esc_html__( 'Conditional Logic', 'cf7-google-sheets-connector' ); ?>

					</label>

					<span class="tooltip" style="display: inline !important;">

						<img src="<?php echo esc_url( GS_CONNECTOR_URL . 'assets/img/help.png' ); ?>" class="help-icon">

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
	public function display_upgrade_notice() {

		$get_notification_display_interval = get_option( 'gs_upgrade_notice_interval' );

		$close_notification_interval = get_option( 'gs_close_upgrade_notice' );

		if ( $close_notification_interval === 'off' ) {

					return;
		}

		if ( ! empty( $get_notification_display_interval ) ) {

			$adds_interval_date_object = DateTime::createFromFormat( 'Y-m-d', $get_notification_display_interval );

			$notice_interval_timestamp = $adds_interval_date_object->getTimestamp();
		}

		if ( empty( $get_notification_display_interval ) || current_time( 'timestamp' ) > $notice_interval_timestamp ) {

			$ajax_nonce = wp_create_nonce( 'gs_upgrade_ajax_nonce' );

			$upgrade_text = '<div class="gs-adds-notice">';

			$upgrade_text .= '<span><b>CF7 Google Sheet Connector </b> ';

			$upgrade_text .= 'version 4.0 would required you to <a href="' . admin_url( 'admin.php?page=wpcf7-google-sheet-config' ) . '">reauthenticate</a> with your Google Account again due to update of Google API V3 to V4.<br/><br/>';

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

			echo wp_kses_post( $upgrade_block );
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
	public function set_upgrade_notification_interval() {

		// check nonce

		check_ajax_referer( 'gs_upgrade_ajax_nonce', 'security' );

		$time_interval = gmdate(
			'Y-m-d',
			strtotime( '+10 days' )
		);

		update_option( 'gs_upgrade_notice_interval', $time_interval );

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
	public function close_upgrade_notification_interval() {

		// check nonce

		check_ajax_referer( 'gs_upgrade_ajax_nonce', 'security' );

		update_option( 'gs_close_upgrade_notice', 'off' );

		wp_send_json_success();
	}
}

		$gscf7_connector_service = new Gs_Connector_Service();
