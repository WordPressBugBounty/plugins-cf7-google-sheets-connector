<?php

if ( ! defined( 'ABSPATH' ) ) {

	exit; // Exit if accessed directly

}

class GS_CF7DB {



	/**
	 * Create CF7 database table for storing form entries.
	 *
	 * This function creates the required database table
	 * to store Contact Form 7 submissions if database
	 * storage is enabled in plugin settings.
	 *
	 * @since 1.0
	 *
	 * @return bool|null
	 */
	public function create_gsheet_table() {
		try {
			$gs_cf7db_setting = get_option( 'gs_cf7db_setting' );
			if ( $gs_cf7db_setting == 1 ) {
				global $wpdb;
				// set the default character set and collation for the table
				$charset_collate = $wpdb->get_charset_collate();
				/*
				 * Use the site prefix, not the network prefix.
				 *
				 * Every read and write in this plugin uses $wpdb->prefix, so
				 * creating the table with $wpdb->base_prefix meant that on
				 * multisite each sub-site wrote to a table that was never
				 * created, and its submissions were silently discarded.
				 */
				$tbl_name = $wpdb->prefix . 'cf7db_gsheet_forms';
				// Check that the table does not already exist before continuing
				$sql = "CREATE TABLE IF NOT EXISTS `$tbl_name` (
				  		id bigint(20) NOT NULL AUTO_INCREMENT,
			            form_id bigint(20) NOT NULL,
			            value longtext NOT NULL COLLATE utf8mb4_unicode_520_ci,
			            date datetime DEFAULT current_timestamp() NOT NULL,
			            PRIMARY KEY  (id),
			            KEY form_id (form_id),
			            KEY form_id_date (form_id, date),
			            KEY form_id_id (form_id, id)
				  ) $charset_collate;";
				require_once ABSPATH . 'wp-admin/includes/upgrade.php';
				dbDelta( $sql );
				$this->gscf7_add_entry_indexes( $tbl_name );
				$is_error = empty( $wpdb->last_error );
				return $is_error;
			}
		} catch ( Exception $e ) {
			$data['ERROR_MSG'] = $e->getMessage();
			$data['TRACE_STK'] = $e->getTraceAsString();
			Gs_Connector_Free_Utility::gs_debug_log( $data );
		}
	}

	/**
	 * Ensure the entries table carries the indexes its queries rely on.
	 *
	 * The table originally shipped with only PRIMARY KEY (id), while every query
	 * filters on form_id and several also sort by date or id. Without these
	 * indexes each of those queries performs a full table scan.
	 *
	 * dbDelta will create the keys for new installs, but it does not reliably add
	 * composite keys to an existing table, so they are added explicitly here.
	 *
	 * @since 5.2.1
	 *
	 * @param string $table_name Fully prefixed table name.
	 * @return void
	 */
	public function gscf7_add_entry_indexes( $table_name ) {
		global $wpdb;

		$indexes = array(
			'form_id'      => '(`form_id`)',
			'form_id_date' => '(`form_id`, `date`)',
			'form_id_id'   => '(`form_id`, `id`)',
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reading index metadata for a custom plugin table.
		$existing = $wpdb->get_col(
			$wpdb->prepare(
				'SELECT DISTINCT INDEX_NAME
				FROM INFORMATION_SCHEMA.STATISTICS
				WHERE table_schema = DATABASE()
				AND table_name = %s',
				$table_name
			)
		);

		if ( ! is_array( $existing ) ) {
			$existing = array();
		}

		foreach ( $indexes as $index_name => $columns ) {

			if ( in_array( $index_name, $existing, true ) ) {
				continue;
			}

			$sql = 'ALTER TABLE `' . esc_sql( $table_name ) . '` ADD INDEX `' . esc_sql( $index_name ) . '` ' . $columns;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared -- ALTER TABLE cannot use placeholders; identifiers sanitized with esc_sql() and backticked, column list is a hardcoded literal.
			$wpdb->query( $sql );
		}
	}

	/**

	 * Display database settings UI and handle form entry routing.

	 * Depending on query parameters it will:

	 * - Display form entries list

	 * - Display single entry details

	 * - Display database enable/disable settings UI
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function show_enable_disable_set() {

		try {

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from Google.
			$formId = isset( $_GET['formId'] ) ? intval( $_GET['formId'] ) : 0;
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from Google.
			$entryId = isset( $_GET['entryId'] ) ? intval( $_GET['entryId'] ) : 0;

			// ==================================================

			// If formId present → Show Entries Page Only

			// ==================================================

			if ( $formId && empty( $entryId ) ) {

				$this->getFormEntries( $formId );

				return;
			}

			// ==================================================

			// If entryId present → Show Entry Details Only

			// ==================================================

			if ( $formId && $entryId ) {

				$this->getFormEntryDetails();

				return;
			}

			// ==================================================

			// Normal Database Settings UI

			// ==================================================

			$gs_cf7db_setting = get_option( 'gs_cf7db_setting' );

			$checked = ( $gs_cf7db_setting == 1 ) ? 'checked' : '';

			?>



			<div class="gsc-cf7-wrapper wrap w-100 m-0">

				<div class="inner-wrap w-100 bg-white p-40">

					<form method="post">

						<div class="gs-form">

							<div class="gsc-access-wrapper">

								<div>

									<div class="heading mt-0">

										<?php echo esc_html__( 'CF7 Database Manager', 'cf7-google-sheets-connector' ); ?>

									</div>



									<p>

										<?php echo esc_html__( 'Store and manage Contact Form 7 submissions securely inside your WordPress dashboard. Enable database storage to keep a backup of all form entries.', 'cf7-google-sheets-connector' ); ?>

									</p>



									<div class="cf7-database-setting gsc-setting-text d-flex flex-wrap gap-20 justify-between align-center pt-15 pb-15 mt-30 bg-white">

										<div>

											<div class="systemifo fw-600 text-dark">

												<?php echo esc_html__( 'Enable Database Storage', 'cf7-google-sheets-connector' ); ?>

											</div>

											<label class="fw-400">

												<?php echo esc_html__( 'Automatically save all form submissions to your WordPress database.', 'cf7-google-sheets-connector' ); ?>

											</label>

										</div>



										<div>

											<input type="hidden" name="gs_cf7db_setting" value="0">



											<div class="custom-check">

												<input type="checkbox"

													id="gs_cf7db_setting"

													class="check-toggle dbtoggle-checkbox"

													name="gs_cf7db_setting"

													value="1"

													<?php checked( get_option( 'gs_cf7db_setting' ), '1' ); ?>>



												<label for="gs_cf7db_setting" class="button-toggle"></label>

											</div>

										</div>

									</div>

								</div>



								<div class="gsc-access-info">

									<div class='para-heading fw-600 mb-20'>

										<?php esc_html_e( 'Data Management Guidelines', 'cf7-google-sheets-connector' ); ?>

									</div>

									<ul class="mb-0">

										<li><?php esc_html_e( 'Enable storage before launching live forms', 'cf7-google-sheets-connector' ); ?></li>

										<li><?php esc_html_e( 'Regularly remove spam entries to maintain performance', 'cf7-google-sheets-connector' ); ?></li>

										<li><?php esc_html_e( 'Delete unused form data to reduce database load', 'cf7-google-sheets-connector' ); ?></li>

									</ul>

								</div>

							</div>



							<div class="select-info text-right mt-30">

								<div id="gscf7-db-loader"></div>



								<input type="button"

									class="btn btn-primary"

									id="gs-cf7db-setting-btn"

									value="<?php echo esc_attr__( 'Save Settings', 'cf7-google-sheets-connector' ); ?>" />



								<div id="gscf7-db-msg"

									class="gsc-msg gsc-success d-none fw-400 text-dark text-center pt-10 pb-10 manual-margin">

									<?php echo esc_html__( 'Save data successfully', 'cf7-google-sheets-connector' ); ?>

								</div>



								<input type="hidden"

									id="gs-ajax-nonce"

									value="<?php echo esc_attr( wp_create_nonce( 'gs-ajax-nonce' ) ); ?>" />

							</div>



						</div>

					</form>



					<?php

					// Show Form List if DB enabled

					if ( $gs_cf7db_setting == 1 ) {

						$this->getAllFormList();
					}

					echo '</div></div>';
		} catch ( Exception $e ) {

			$data['ERROR_MSG'] = $e->getMessage();

			$data['TRACE_STK'] = $e->getTraceAsString();

			Gs_Connector_Free_Utility::gs_debug_log( $data );
		}
	}

			/**

			 * Display entries list for a specific CF7 form.

			 * This loads the WP_List_Table implementation

			 * used to show form submissions stored in the database.
			 *
			 * @since 1.0
			 *
			 * @param int $formId Contact Form ID.

			 * @return void
			 */
	public function getFormEntries( $formId ) {

		try {

			$ListTable = new GSCF7_FormEntry_Table();

			$ListTable->prepare_items();

			?>

					<div class="gsc-cf7-wrapper wrap w-100 m-0">

						<div class="inner-wrap w-100 bg-white p-40">

							<div class="wrap-gsc">

								<div class="forms-detail">



									<!--  Back Button -->

									<div class="mb-15">

										<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcf7-google-sheet-config&tab=cf7_db' ) ); ?>"

											class="back-btn btn-spacer btn btn-primary text-decoration-none d-inline-flex align-center gap-10">



											<svg class="back-icon" width="18" height="18" viewBox="0 0 24 24" fill="none"

												xmlns="http://www.w3.org/2000/svg">

												<path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2.5"

													stroke-linecap="round" stroke-linejoin="round"></path>

											</svg>



											<span><?php echo esc_html__( 'Back to Forms', 'cf7-google-sheets-connector' ); ?></span>



										</a>

									</div>



									<div id="icon-users" class="icon32"></div>



									<div class="heading mt-30">

								<?php echo esc_html( get_the_title( $formId ) ); ?>

									</div>



									<form method="post" action="">

								<?php $ListTable->search_box( 'Search', 'search' ); ?>

								<?php $ListTable->display(); ?>

									</form>



								</div>

							</div>

						</div>

					</div>

				<?php

		} catch ( Exception $e ) {

			$data['ERROR_MSG'] = $e->getMessage();

			$data['TRACE_STK'] = $e->getTraceAsString();

			Gs_Connector_Free_Utility::gs_debug_log( $data );
		}
	}

			/**

			 * Display detailed view of a single form entry.

			 * Loads the entry details table class which

			 * handles rendering entry data in admin UI.
			 *
			 * @since 1.0
			 *
			 * @return void
			 */
	public function getFormEntryDetails() {

		try {

			$ListDetails = new GSCF7_FormEntDetail_Table();
		} catch ( Exception $e ) {

			$data['ERROR_MSG'] = $e->getMessage();

			$data['TRACE_STK'] = $e->getTraceAsString();

			Gs_Connector_Free_Utility::gs_debug_log( $data );
		}
	}

			/**

			 * Display all Contact Form 7 forms that store entries in database.

			 * Shows a list table with forms that currently have

			 * database entries enabled.
			 *
			 * @since 1.0
			 *
			 * @return void
			 */
	public function getAllFormList() {

		if ( ! class_exists( 'WPCF7_ContactForm' ) ) {

			wp_die( 'Please activate <a href="https://wordpress.org/plugins/contact-form-7/" target="_blank">contact form 7</a> plugin.' );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from Google.
		$fid = empty( $_GET['formId'] ) ? 0 : (int) $_GET['formId'];

		$ListTable = new GSCF7_FormList_Table();

		$ListTable->prepare_items();

		?>

				<div class="contact-forms-info">

					<div id="icon-users" class="icon32"></div>

					<div class="heading mt-0"><?php esc_html_e( 'Contact Forms List', 'cf7-google-sheets-connector' ); ?></div>

					<p><?php echo esc_html__( 'View all Contact Form 7 forms that are currently saving submissions to the database. Select a form below to access and manage its stored entries.', 'cf7-google-sheets-connector' ); ?></p>

			<?php $ListTable->display(); ?>

				</div>





		<?php
	}

			/**

			 * Save Contact Form 7 submission data to database.

			 * This function runs before email is sent and stores

			 * submission data including special mail tags in the

			 * plugin's database table.
			 *
			 * @since 1.0
			 *
			 * @param object $form_tag   Contact Form instance.

			 * @param array  $gs_uploads Uploaded files data.
			 *
			 * @return void
			 */
	function cfdb7_before_send_mail( $form_tag, $gs_uploads ) {

		global $wpdb;

		$cfdb = apply_filters( 'cfdb7_database', $wpdb );

		$table_name = $cfdb->prefix . 'cf7db_gsheet_forms';

		$time_now = time();

		$submission = WPCF7_Submission::get_instance();

		$contact_form = $submission->get_contact_form();

		$tags_names = array();

		$strict_keys = apply_filters( 'cfdb7_strict_keys', false );

		// Get Special mail tags

		$servicesgsc = Gs_Connector_Service::instance();

		$special_mail_tags = $servicesgsc->get_special_mail_tags();

		$SpMailTag = array();

		foreach ( $special_mail_tags as $tagname ) {

			$_tagname = sprintf( '_%s', $tagname );

			$mail_tag = new WPCF7_MailTag(
				sprintf( '[%s]', $_tagname ),
				$_tagname,
				''
			);
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- CF7 core hook, not defined by this plugin.
			$SpMailTag[ $tagname ] = apply_filters( 'wpcf7_special_mail_tags', '', $_tagname, false, $mail_tag );
		}

		if ( $submission ) {

			$allowed_tags = array();

			if ( $strict_keys ) {

				$tags = $contact_form->scan_form_tags();

				foreach ( $tags as $tag ) {

					if ( ! empty( $tag->name ) ) {
						$tags_names[] = $tag->name;
					}
				}

				$allowed_tags = $tags_names;
			}

			$not_allowed_tags = apply_filters( 'cfdb7_not_allowed_tags', array( 'g-recaptcha-response' ) );

			$allowed_tags = apply_filters( 'cfdb7_allowed_tags', $allowed_tags );

			$data = $submission->get_posted_data();

			// $uploaded_files   = $submission->uploaded_files();

			$uploaded_files = $gs_uploads;

			$form_data = array();

			$form_data['cfdb7_status'] = 'unread';

			foreach ( $data as $key => $d ) {

				if ( $strict_keys && ! in_array( $key, $allowed_tags ) ) {
					continue;
				}

				if ( ! in_array( $key, $not_allowed_tags ) && ! in_array( $key, $uploaded_files ) ) {

					if ( ! empty( $uploaded_files ) && isset( $uploaded_files[ $key ] ) ) {

						$tmpD = $uploaded_files[ $key ];
					} else {

						$tmpD = $d;
					}

					if ( ! is_array( $d ) ) {

						$bl = array( '\"', "\'", '/', '\\', '"', "'" );

						$wl = array( '&quot;', '&#039;', '&#047;', '&#092;', '&quot;', '&#039;' );

						$tmpD = str_replace( $bl, $wl, $tmpD );
					}

					$form_data[ $key ] = $tmpD;
				}
			}

			/* cfdb7 before save data. */

			$form_data = apply_filters( 'cfdb7_before_save_data', $form_data );

			do_action( 'cfdb7_before_save', $form_data );

			$formAndSpMailTag = array_merge( $form_data, $SpMailTag );

			$form_id = $form_tag->id();

			$value = serialize( $formAndSpMailTag );

			$date = current_time( 'Y-m-d H:i:s' );

			$cfdb->insert(
				$table_name,
				array(

					'form_id' => $form_id,

					'value'   => $value,

					'date'    => $date,

				)
			);

			/* cfdb7 after save data */

			$insert_id = $cfdb->insert_id;

			do_action( 'cfdb7_after_save_data', $insert_id );
		}
	}
}

		/*
		 * The list-table screens below are admin-only. They were previously loaded
		 * on every request, including front-end page views, which pulled
		 * wp-admin/includes/class-wp-list-table.php into public requests.
		 *
		 * The GS_CF7DB class itself stays available everywhere because
		 * cfdb7_before_send_mail() runs during front-end form submission.
		 */
if ( is_admin() ) {

	// WP_List_Table is not loaded automatically so we need to load it in our application

	if ( ! class_exists( 'WP_List_Table' ) ) {

		require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
	}

	// ============================================== list of All Forms ========================

	include_once 'class-gs-cf7db-formList.php';

	// //============================================== list of Form Entries ========================

	include_once 'class-gs-cf7db-formEntryList.php';

	// ==============================================  Entries Details ========================

	include_once 'class-gs-cf7db-formEntryDetails.php';

	// ==============================================    CSV ====================================

	include_once 'class-gs-cf7db-export-csv.php';
}
