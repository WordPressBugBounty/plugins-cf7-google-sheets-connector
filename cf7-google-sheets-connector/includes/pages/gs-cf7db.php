<?php

if (! defined('ABSPATH')) {

	exit; // Exit if accessed directly

}

class GS_CF7DB
{



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
	public function create_gsheet_table()
	{
		try {
			$gs_cf7db_setting = get_option('gs_cf7db_setting');
			if ($gs_cf7db_setting == 1) {
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
				dbDelta($sql);
				$this->gscf7_add_entry_indexes($tbl_name);
				$is_error = empty($wpdb->last_error);
				return $is_error;
			}
		} catch (Exception $e) {
			$data['ERROR_MSG'] = $e->getMessage();
			$data['TRACE_STK'] = $e->getTraceAsString();
			Gs_Connector_Free_Utility::gs_debug_log($data);
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
	public function gscf7_add_entry_indexes($table_name)
	{
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

		if (! is_array($existing)) {
			$existing = array();
		}

		foreach ($indexes as $index_name => $columns) {

			if (in_array($index_name, $existing, true)) {
				continue;
			}

			$sql = 'ALTER TABLE `' . esc_sql($table_name) . '` ADD INDEX `' . esc_sql($index_name) . '` ' . $columns;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared -- ALTER TABLE cannot use placeholders; identifiers sanitized with esc_sql() and backticked, column list is a hardcoded literal.
			$wpdb->query($sql);
		}
	}

	/**
	 * Count total / read / unread entries.
	 *
	 * The read/unread flag lives inside the serialized `value` column
	 * rather than its own DB column, so it is matched via a fixed-length
	 * LIKE fragment on the serialized PHP array instead of a WHERE on a
	 * real column. The fragment length is constant (`cfdb7_status` is
	 * always 12 chars and `read` is always 4 chars), so this is an exact
	 * match, not a loose wildcard search.
	 *
	 * @since 5.3.0
	 *
	 * @param int $form_id Contact Form ID. 0 = aggregate across every form.
	 * @return array{all:int,unread:int,read:int}
	 */
	public function gscf7_get_status_counts($form_id = 0)
	{

		global $wpdb;

		$cfdb       = apply_filters('cfdb7_database', $wpdb);
		$table_name = $cfdb->prefix . 'cf7db_gsheet_forms';
		$form_id    = (int) $form_id;

		if ($form_id > 0) {

			$total = (int) $cfdb->get_var(
				$cfdb->prepare("SELECT COUNT(*) FROM $table_name WHERE form_id = %d", $form_id)
			);

			$read = (int) $cfdb->get_var(
				$cfdb->prepare(
					"SELECT COUNT(*) FROM $table_name WHERE form_id = %d AND value LIKE %s",
					$form_id,
					'%s:12:"cfdb7_status";s:4:"read";%'
				)
			);
		} else {

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Aggregate dashboard count, no user input.
			$total = (int) $cfdb->get_var("SELECT COUNT(*) FROM $table_name");

			$read = (int) $cfdb->get_var(
				$cfdb->prepare(
					"SELECT COUNT(*) FROM $table_name WHERE value LIKE %s",
					'%s:12:"cfdb7_status";s:4:"read";%'
				)
			);
		}

		// Entries without a recognised status (including legacy rows saved
		// before this flag existed) are treated as unread, matching the
		// 'unread' default set on every new submission.
		$unread = max(0, $total - $read);

		return array(
			'all'    => $total,
			'unread' => $unread,
			'read'   => $read,
		);
	}

	/**
	 * Build submission counts per day for the last 30 days, for the line chart.
	 *
	 * @since 5.3.0
	 *
	 * @param int $form_id Contact Form ID. 0 = aggregate across every form.
	 * @return array{labels:array,values:array,label:string}
	 */
	public function gscf7_get_daily_submission_counts($form_id = 0)
	{

		global $wpdb;

		$cfdb       = apply_filters('cfdb7_database', $wpdb);
		$table_name = $cfdb->prefix . 'cf7db_gsheet_forms';
		$form_id    = (int) $form_id;
		$days       = 30;

		if ($form_id > 0) {

			$rows = $cfdb->get_results(
				$cfdb->prepare(
					"SELECT DATE(date) AS d, COUNT(*) AS c FROM $table_name
					WHERE form_id = %d AND date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
					GROUP BY DATE(date)
					ORDER BY d ASC",
					$form_id,
					$days - 1
				),
				OBJECT
			);
		} else {

			$rows = $cfdb->get_results(
				$cfdb->prepare(
					"SELECT DATE(date) AS d, COUNT(*) AS c FROM $table_name
					WHERE date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
					GROUP BY DATE(date)
					ORDER BY d ASC",
					$days - 1
				),
				OBJECT
			);
		}

		$counts_by_date = array();

		foreach ((array) $rows as $row) {
			$counts_by_date[$row->d] = (int) $row->c;
		}

		$labels = array();
		$values = array();

		for ($i = $days - 1; $i >= 0; $i--) {

			$date = gmdate('Y-m-d', strtotime("-{$i} days", current_time('timestamp'))); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested

			$labels[] = date_i18n('M j', strtotime($date));
			$values[] = isset($counts_by_date[$date]) ? $counts_by_date[$date] : 0;
		}

		return array(
			'labels' => $labels,
			'values' => $values,
			'label'  => esc_html__('Submissions', 'cf7-google-sheets-connector'),
		);
	}

	/**
	 * Count entries created today.
	 *
	 * @since 5.3.0
	 *
	 * @param int $form_id Contact Form ID. 0 = aggregate across every form.
	 * @return int
	 */
	public function gscf7_get_new_today_count($form_id = 0)
	{

		global $wpdb;

		$cfdb       = apply_filters('cfdb7_database', $wpdb);
		$table_name = $cfdb->prefix . 'cf7db_gsheet_forms';
		$form_id    = (int) $form_id;
		$today      = current_time('Y-m-d');

		if ($form_id > 0) {
			return (int) $cfdb->get_var(
				$cfdb->prepare(
					"SELECT COUNT(*) FROM $table_name WHERE form_id = %d AND DATE(date) = %s",
					$form_id,
					$today
				)
			);
		}

		return (int) $cfdb->get_var(
			$cfdb->prepare(
				"SELECT COUNT(*) FROM $table_name WHERE DATE(date) = %s",
				$today
			)
		);
	}

	/**
	 * Render the analytics section (line + pie chart).
	 *
	 * Used both on the Dashboard tab (aggregate across every form, $form_id = 0)
	 * and on the CF7 Database screen ($form_id = 0 for "All Forms" or a specific
	 * form). Emits the same canvas ids and `#gscf7-dashboard-chart-data` JSON
	 * shape that dashboard.php uses, so the shared renderCharts()/initCharts()
	 * in gs-cf7db-charts.js draws it identically on every screen.
	 *
	 * @since 5.3.0
	 *
	 * @param int $form_id Contact Form ID. 0 = aggregate across every form.
	 * @return void
	 */
	public function gscf7_render_entries_analytics($form_id = 0)
	{

		$status_counts = $this->gscf7_get_status_counts($form_id);
		$daily_counts  = $this->gscf7_get_daily_submission_counts($form_id);
?>
		<div class="gscf7-chart-grid mb-30">
			<div class="gscf7-chart-card inner-wrap gscf7-chart-card-line p-20">
				<div class="para-heading fw-600 mb-20"><?php echo esc_html__('Entries Over Time (Last 30 Days)', 'cf7-google-sheets-connector'); ?></div>
				<canvas id="gscf7-entries-line-chart" height="110"></canvas>
			</div>

			<div class="gscf7-chart-card inner-wrap gscf7-chart-card-pie p-20">
				<div class="para-heading fw-600 mb-20"><?php echo esc_html__('Entries by Status', 'cf7-google-sheets-connector'); ?></div>
				<canvas id="gscf7-entries-pie-chart" height="180"></canvas>
			</div>
		</div>

		<script type="application/json" id="gscf7-dashboard-chart-data">
			<?php
			echo wp_json_encode(
				array(
					'labels' => $daily_counts['labels'],
					'values' => $daily_counts['values'],
					'read'   => $status_counts['read'],
					'unread' => $status_counts['unread'],
					'i18n'   => array(
						'entries' => esc_html__('Entries', 'cf7-google-sheets-connector'),
						'read'    => esc_html__('Read', 'cf7-google-sheets-connector'),
						'unread'  => esc_html__('Unread', 'cf7-google-sheets-connector'),
					),
				)
			);
			?>
		</script>
	<?php
	}

	/**
	 * Render the All / Unread Only / Read Only filter pills shown above
	 * the entries table.
	 *
	 * Rendered as buttons (not links) using the same `.gscf7-status-filter-btn`
	 * pill styling and `data-status` contract that the Dashboard tab's
	 * `initDashboardEntries()` (gs-cf7db-charts.js) already expects, so
	 * switching status re-queries via AJAX instead of a full page reload.
	 * $_GET['entry_status'] still decides which pill is active on first paint
	 * (e.g. a bookmarked/shared URL).
	 *
	 * @since 5.3.0
	 *
	 * @param int $form_id Contact Form ID.
	 * @return void
	 */
	public function gscf7_render_status_tabs($form_id)
	{

		$status_counts = $this->gscf7_get_status_counts($form_id);

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filter, not a form submission.
		$current = isset($_GET['entry_status']) ? sanitize_key(wp_unslash($_GET['entry_status'])) : 'all';

		if (! in_array($current, array('all', 'unread', 'read'), true)) {
			$current = 'all';
		}

		$tabs = array(
			'all'    => array('label' => esc_html__('All', 'cf7-google-sheets-connector'), 'count' => $status_counts['all']),
			'unread' => array('label' => esc_html__('Unread Only', 'cf7-google-sheets-connector'), 'count' => $status_counts['unread']),
			'read'   => array('label' => esc_html__('Read Only', 'cf7-google-sheets-connector'), 'count' => $status_counts['read']),
		);
	?>
		<div class="gscf7-status-filter-group">
			<?php foreach ($tabs as $key => $tab) : ?>
				<span
					class="gscf7-status-filter-btn<?php echo esc_attr($current === $key ? ' is-active' : ''); ?>"
					data-status="<?php echo esc_attr($key); ?>">
					<span class="gscf7-status-label">
						<?php echo esc_html($tab['label']); ?>
					</span>

					<span
						class="gscf7-status-count"
						data-status-count="<?php echo esc_attr($key); ?>">
						<?php echo "(" . esc_html($tab['count']) . ")"; ?>
					</span>
				</span>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * List every form that has at least one stored entry, for the "Form"
	 * filter dropdown. Reuses the same grouped-count query pattern already
	 * used by GSCF7_FormList_Table.
	 *
	 * @since 5.3.0
	 *
	 * @return array<int, array{id:int,title:string,count:int}>
	 */
	public function gscf7_get_forms_with_entries()
	{
		global $wpdb;

		$cfdb       = apply_filters('cfdb7_database', $wpdb);
		$table_name = $cfdb->prefix . 'cf7db_gsheet_forms';

		// Get entry counts grouped by form ID.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $cfdb->get_results(
			"SELECT form_id, COUNT(*) AS total FROM `$table_name` GROUP BY form_id",
			OBJECT
		);

		$entry_counts = array();

		foreach ((array) $rows as $row) {
			$entry_counts[(int) $row->form_id] = (int) $row->total;
		}

		// Get ALL published CF7 forms.
		$cf7_forms = get_posts(
			array(
				'post_type'      => 'wpcf7_contact_form',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$forms = array();

		foreach ($cf7_forms as $cf7_form) {
			$form_id = (int) $cf7_form->ID;

			$forms[] = array(
				'id'    => $form_id,
				'title' => get_the_title($form_id),
				'count' => isset($entry_counts[$form_id])
					? $entry_counts[$form_id]
					: 0,
			);
		}

		return $forms;
	}

	/**
	 * Render the unified CF7 Database entries screen: Form dropdown, chart,
	 * status pills, and the entries table + pagination shell. The table body
	 * itself is populated by JS immediately on load via AJAX (gscf7_dashboard
	 * _entries_query for "All Forms", gscf7_cf7db_table_query for a specific
	 * form), so nothing here needs to duplicate that query.
	 *
	 * Bulk actions (delete/read/unread/send-to-sheet/CSV export) still submit
	 * as a normal POST back to this same URL, so any such submission is
	 * processed up front via the unchanged GSCF7_FormEntry_Table::prepare_items()
	 * / process_bulk_action() before the shell is rendered.
	 *
	 * @since 5.3.0
	 *
	 * @param int $form_id Contact Form ID. 0 = "All Forms".
	 * @return void
	 */
	public function gscf7_render_cf7db_entries_page($form_id)
	{

		$form_id = (int) $form_id;

		if ($form_id > 0) {
			// Processes any bulk-action POST for this form (unchanged nonce +
			// capability checks). The resulting item list is not used for
			// rendering -- the table is populated by the AJAX call below.
			$list_table = new GSCF7_FormEntry_Table();
			$list_table->prepare_items();
		}

		$forms = $this->gscf7_get_forms_with_entries();

		global $wpdb;

		$gscf7_table = $wpdb->prefix . 'cf7db_gsheet_forms';
		/*
			* Entries Dashboard — at-a-glance stats, trend chart, and a sortable
			* recent-entries table for everything stored in the CF7 Database table.
			*
			* @since 5.2.4
			*/

		if (empty($gscf7_table)) { ?>
			<div class="gsc-cf7-wrapper mt-40">
				<div class="inner-wrap w-100 bg-white p-40">
					<div class="gscf7-entries-dashboard">

						<div class="welcome-heading mb-20">
							<span><?php echo esc_html__('CF7 Database', 'cf7-google-sheets-connector'); ?></span>
						</div>

						<p class="mb-30"><?php echo esc_html__('Browse, filter, and manage every Contact Form 7 submission stored in your database.', 'cf7-google-sheets-connector'); ?></p>
						<?php $this->gscf7_render_entries_analytics($form_id); ?>

						<form method="post" action="" id="gscf7-entries-bulk-form">

							<?php
							/*
						 * GSCF7_FormEntry_Table::process_bulk_action() checks a
						 * 'bulk-contact_forms' nonce, but nothing ever rendered
						 * that field -- so a bulk submit was always rejected by
						 * wp_verify_nonce() before this fix.
						 */
							wp_nonce_field('bulk-contact_forms');
							?>

							<div class="gscf7-menu-db d-flex justify-between align-center flex-wrap gap-15">
								<div class="gscf7-menu-db-left d-flex align-center flex-wrap gap-15">
									<div id="gscf7-entries-toolbar" class="d-flex align-center flex-wrap gap-10"></div>

									<div class="gscf7-filter-form">
										<label for="gscf7-entries-filter-form"><?php echo esc_html__('Form', 'cf7-google-sheets-connector'); ?></label>
										<select id="gscf7-entries-filter-form" class="gsc-select">
											<option value="0">
												<?php echo esc_html__('All Forms', 'cf7-google-sheets-connector'); ?>
											</option>

											<?php foreach ($forms as $gscf7_form) : ?>
												<option value="<?php echo esc_attr($gscf7_form['id']); ?>">
													<?php echo esc_html($gscf7_form['title']); ?>
												</option>
											<?php endforeach; ?>
										</select>
										<span id="gscf7-entries-loader" class="loading d-none" aria-hidden="true"></span>
									</div>
								</div>
								<?php $this->gscf7_render_status_tabs($form_id); ?>
							</div>

							<div class="gscf7-recent-entries-wrap mt-20" id="gscf7-entries-table-wrap">

								<div class="gscf7-table-scroll gscf7-freeze-col1">
									<table class="widefat gscf7-sortable-table" id="gscf7-entries-table">
										<thead id="gscf7-entries-thead">
											<tr>
												<th><?php esc_html_e('Loading…', 'cf7-google-sheets-connector'); ?></th>
											</tr>
										</thead>
										<tbody id="gscf7-entries-table-body">
											<tr>
												<td><?php esc_html_e('Loading…', 'cf7-google-sheets-connector'); ?></td>
											</tr>
										</tbody>
									</table>
								</div>

						</form>

						<div class="gscf7-entries-pagination">
							<div class="gscf7-pg-total"><?php echo esc_html__('Total', 'cf7-google-sheets-connector'); ?> <span id="gscf7-pg-total-count">0</span></div>
							<div class="gscf7-pg-controls">
								<select id="gscf7-entries-per-page" class="gscf7-pg-per-page auto-select">
									<?php foreach (array(10, 20, 50, 100) as $per_page_option) : ?>
										<option value="<?php echo esc_attr($per_page_option); ?>">
											<?php
											echo esc_html(
												sprintf(
													/* translators: %d: number of items per page */
													__('%d / Page', 'cf7-google-sheets-connector'),
													$per_page_option
												)
											);
											?>
										</option>
									<?php endforeach; ?>
								</select>
								<button type="button" class="gscf7-pg-arrow" id="gscf7-pg-prev" aria-label="<?php esc_attr_e('Previous page', 'cf7-google-sheets-connector'); ?>">&lsaquo;</button>
								<span class="gscf7-pg-current" id="gscf7-pg-current">1</span>
								<button type="button" class="gscf7-pg-arrow" id="gscf7-pg-next" aria-label="<?php esc_attr_e('Next page', 'cf7-google-sheets-connector'); ?>">&rsaquo;</button>
								<span class="gscf7-pg-goto-label"><?php esc_html_e('Go to', 'cf7-google-sheets-connector'); ?></span>
								<input type="number" min="1" class="gscf7-pg-goto-input" id="gscf7-pg-goto" value="1">
							</div>
						</div>

					</div>

				</div>
			</div>
			</div>
		<?php } ?>
		<input type="hidden" id="gscf7-dashboard-stats-nonce" value="<?php echo esc_attr(wp_create_nonce('gscf7-dashboard-stats')); ?>">
		<input type="hidden" id="gscf7-dashboard-entries-nonce" value="<?php echo esc_attr(wp_create_nonce('gscf7-dashboard-entries')); ?>">
		<input type="hidden" id="gscf7-cf7db-table-nonce" value="<?php echo esc_attr(wp_create_nonce('gscf7-cf7db-entries')); ?>">
		<?php
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
	public function show_enable_disable_set()
	{

		try {

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from Google.
			$formId = isset($_GET['formId']) ? intval($_GET['formId']) : 0;
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from Google.
			$entryId = isset($_GET['entryId']) ? intval($_GET['entryId']) : 0;

			// ==================================================

			// If formId present → Show Entries Page Only

			// ==================================================

			if ($formId && empty($entryId)) {

				$this->getFormEntries($formId);

				return;
			}

			// ==================================================

			// If entryId present → Show Entry Details Only

			// ==================================================

			if ($formId && $entryId) {

				$this->getFormEntryDetails();

				return;
			}

			// ==================================================

			// Normal Database Settings UI

			// ==================================================

			$gs_cf7db_setting = get_option('gs_cf7db_setting');

			$checked = ($gs_cf7db_setting == 1) ? 'checked' : '';

		?>



			<div class="gsc-cf7-wrapper wrap w-100 m-0">

				<div class="inner-wrap w-100 bg-white p-40">

					<form method="post">

						<div class="gs-form">

							<div class="gsc-access-wrapper">

								<div>

									<div class="heading mt-0">

										<?php echo esc_html__('CF7 Database Manager', 'cf7-google-sheets-connector'); ?>

									</div>



									<p>

										<?php echo esc_html__('Store and manage Contact Form 7 submissions securely inside your WordPress dashboard. Enable database storage to keep a backup of all form entries.', 'cf7-google-sheets-connector'); ?>

									</p>



									<div class="cf7-database-setting gsc-setting-text d-flex flex-wrap gap-20 justify-between align-center pt-15 pb-15 mt-30 bg-white">

										<div>

											<div class="systemifo fw-600 text-dark">

												<?php echo esc_html__('Enable Database Storage', 'cf7-google-sheets-connector'); ?>

											</div>

											<label class="fw-400">

												<?php echo esc_html__('Automatically save all form submissions to your WordPress database.', 'cf7-google-sheets-connector'); ?>

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

													autocomplete="off"

													<?php checked(get_option('gs_cf7db_setting'), '1'); ?>>



												<label for="gs_cf7db_setting" class="button-toggle"></label>

											</div>

										</div>

									</div>


								</div>



								<div class="gsc-access-info">

									<div class='para-heading fw-600 mb-20'>

										<?php esc_html_e('Data Management Guidelines', 'cf7-google-sheets-connector'); ?>

									</div>

									<ul class="mb-0">

										<li><?php esc_html_e('Enable storage before launching live forms', 'cf7-google-sheets-connector'); ?></li>

										<li><?php esc_html_e('Regularly remove spam entries to maintain performance', 'cf7-google-sheets-connector'); ?></li>

										<li><?php esc_html_e('Delete unused form data to reduce database load', 'cf7-google-sheets-connector'); ?></li>

									</ul>

								</div>

							</div>



							<div class="select-info text-right mt-30">

								<div id="gscf7-db-loader"></div>



								<input type="button"

									class="btn btn-primary"

									id="gs-cf7db-setting-btn"

									value="<?php echo esc_attr__('Save Settings', 'cf7-google-sheets-connector'); ?>" />



								<div id="gscf7-db-msg"

									class="gsc-msg gsc-success d-none fw-400 text-dark text-center pt-10 pb-10 manual-margin">

									<?php echo esc_html__('Save data successfully', 'cf7-google-sheets-connector'); ?>

								</div>



								<input type="hidden"

									id="gs-ajax-nonce"

									value="<?php echo esc_attr(wp_create_nonce('gs-ajax-nonce')); ?>" />

							</div>



						</div>

					</form>



		<?php

			// Show Form List if DB enabled

			if ($gs_cf7db_setting == 1) {

				$this->getAllFormList();
			}

			echo '</div></div>';
		} catch (Exception $e) {

			$data['ERROR_MSG'] = $e->getMessage();

			$data['TRACE_STK'] = $e->getTraceAsString();

			Gs_Connector_Free_Utility::gs_debug_log($data);
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
	public function getFormEntries($formId)
	{

		try {

			$this->gscf7_render_cf7db_entries_page((int) $formId);
		} catch (Exception $e) {

			$data['ERROR_MSG'] = $e->getMessage();

			$data['TRACE_STK'] = $e->getTraceAsString();

			Gs_Connector_Free_Utility::gs_debug_log($data);
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
	public function getFormEntryDetails()
	{

		try {

			$ListDetails = new GSCF7_FormEntDetail_Table();
		} catch (Exception $e) {

			$data['ERROR_MSG'] = $e->getMessage();

			$data['TRACE_STK'] = $e->getTraceAsString();

			Gs_Connector_Free_Utility::gs_debug_log($data);
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
	public function getAllFormList()
	{

		if (! class_exists('WPCF7_ContactForm')) {

			wp_die('Please activate <a href="https://wordpress.org/plugins/contact-form-7/" target="_blank">contact form 7</a> plugin.');
		}

		// No formId in the URL -- land on the same unified entries screen,
		// defaulting the Form dropdown to "All Forms" (0).
		$this->gscf7_render_cf7db_entries_page(0);
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
	function cfdb7_before_send_mail($form_tag, $gs_uploads)
	{

		global $wpdb;

		$cfdb = apply_filters('cfdb7_database', $wpdb);

		$table_name = $cfdb->prefix . 'cf7db_gsheet_forms';

		$time_now = time();

		$submission = WPCF7_Submission::get_instance();

		$contact_form = $submission->get_contact_form();

		$tags_names = array();

		$strict_keys = apply_filters('cfdb7_strict_keys', false);

		// Get Special mail tags

		$servicesgsc = Gs_Connector_Service::instance();

		$special_mail_tags = $servicesgsc->get_special_mail_tags();

		$SpMailTag = array();

		foreach ($special_mail_tags as $tagname) {

			$_tagname = sprintf('_%s', $tagname);

			$mail_tag = new WPCF7_MailTag(
				sprintf('[%s]', $_tagname),
				$_tagname,
				''
			);
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- CF7 core hook, not defined by this plugin.
			$SpMailTag[$tagname] = apply_filters('wpcf7_special_mail_tags', '', $_tagname, false, $mail_tag);
		}

		if ($submission) {

			$allowed_tags = array();

			if ($strict_keys) {

				$tags = $contact_form->scan_form_tags();

				foreach ($tags as $tag) {

					if (! empty($tag->name)) {
						$tags_names[] = $tag->name;
					}
				}

				$allowed_tags = $tags_names;
			}

			$not_allowed_tags = apply_filters('cfdb7_not_allowed_tags', array('g-recaptcha-response'));

			$allowed_tags = apply_filters('cfdb7_allowed_tags', $allowed_tags);

			$data = $submission->get_posted_data();

			// $uploaded_files   = $submission->uploaded_files();

			$uploaded_files = $gs_uploads;

			$form_data = array();

			$form_data['cfdb7_status'] = 'unread';

			foreach ($data as $key => $d) {

				if ($strict_keys && ! in_array($key, $allowed_tags)) {
					continue;
				}

				if (! in_array($key, $not_allowed_tags) && ! in_array($key, $uploaded_files)) {

					if (! empty($uploaded_files) && isset($uploaded_files[$key])) {

						$tmpD = $uploaded_files[$key];
					} else {

						$tmpD = $d;
					}

					if (! is_array($d)) {

						$bl = array('\"', "\'", '/', '\\', '"', "'");

						$wl = array('&quot;', '&#039;', '&#047;', '&#092;', '&quot;', '&#039;');

						$tmpD = str_replace($bl, $wl, $tmpD);
					}

					$form_data[$key] = $tmpD;
				}
			}

			/* cfdb7 before save data. */

			$form_data = apply_filters('cfdb7_before_save_data', $form_data);

			do_action('cfdb7_before_save', $form_data);

			$formAndSpMailTag = array_merge($form_data, $SpMailTag);

			$form_id = $form_tag->id();

			$value = serialize($formAndSpMailTag);

			$date = current_time('Y-m-d H:i:s');

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

			do_action('cfdb7_after_save_data', $insert_id);
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
if (is_admin()) {

	// WP_List_Table is not loaded automatically so we need to load it in our application

	if (! class_exists('WP_List_Table')) {

		require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
	}

	/*
	 * WP_List_Table::get_primary_column_name() (called by single_row_columns(),
	 * used by GSCF7_FormEntry_Table::render_dashboard_style()) calls
	 * get_column_headers(), which lives in wp-admin/includes/screen.php. A
	 * full admin page load pulls that file in as part of wp-admin/includes
	 * /admin.php, but admin-ajax.php does not -- so the gscf7_cf7db_table_query
	 * AJAX endpoint fatals on this with "Call to undefined function
	 * get_column_headers()" unless it's loaded explicitly here too.
	 */
	if (! function_exists('get_column_headers')) {

		require_once ABSPATH . 'wp-admin/includes/screen.php';
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
