<?php
/*
 * Entries Dashboard — at-a-glance stats, trend chart, and a sortable
 * recent-entries table for everything stored in the CF7 Database table.
 *
 * @since 5.3.0
 */
if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

/**
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
 */

global $wpdb;

$gscf7_table = $wpdb->prefix . 'cf7db_gsheet_forms';

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name only (from $wpdb->prefix), not user input; escaped since identifiers cannot use $wpdb->prepare() placeholders.
$gscf7_total_entries = (int) $wpdb->get_var('SELECT COUNT(*) FROM `' . esc_sql($gscf7_table) . '`');

/**
 * Cap the number of rows pulled to compute status counts / the trend chart.
 * 0 = no cap. Large installs can filter this down for performance.
 *
 * @since 5.3.0
 */
$gscf7_dashboard_cap = (int) apply_filters('gscf7_dashboard_entries_limit', 0);

// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name only, escaped with esc_sql(); not user input.
$gscf7_sql = 'SELECT id, form_id, date, value FROM `' . esc_sql($gscf7_table) . '` ORDER BY date DESC';

if ($gscf7_dashboard_cap > 0) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin table.
	$gscf7_rows = $wpdb->get_results($wpdb->prepare($gscf7_sql . ' LIMIT %d', $gscf7_dashboard_cap)); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- $gscf7_sql's only interpolated value is the table name, escaped with esc_sql() above; the query itself is passed through $wpdb->prepare().
} else {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Custom plugin table, hardcoded literal query; $gscf7_sql's only interpolated value is the table name, escaped with esc_sql() above.
	$gscf7_rows = $wpdb->get_results($gscf7_sql);
}

$gscf7_unread_count = 0;
$gscf7_read_count   = 0;
$gscf7_new_today    = 0;
$gscf7_today        = current_time('Y-m-d');

// Build 30 empty day-buckets (oldest first) for the trend line chart.
$gscf7_daily_counts = array();
for ($gscf7_i = 29; $gscf7_i >= 0; $gscf7_i--) {
	$gscf7_day                          = gmdate('Y-m-d', strtotime("-{$gscf7_i} days", current_time('timestamp'))); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- Site-local "today" is intentional here.
	$gscf7_daily_counts[$gscf7_day] = 0;
}

$gscf7_form_titles    = array(); // form_id => cached title.
$gscf7_recent_entries = array(); // rows for the table (capped separately from the stats loop).

foreach ($gscf7_rows as $gscf7_row) {
	$gscf7_data   = @unserialize($gscf7_row->value); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize -- Matches the storage format used across the rest of this plugin.
	$gscf7_status = (is_array($gscf7_data) && isset($gscf7_data['cfdb7_status']) && 'read' === $gscf7_data['cfdb7_status']) ? 'read' : 'unread';

	if ('read' === $gscf7_status) {
		++$gscf7_read_count;
	} else {
		++$gscf7_unread_count;
	}

	$gscf7_day = gmdate('Y-m-d', strtotime($gscf7_row->date));

	if (isset($gscf7_daily_counts[$gscf7_day])) {
		++$gscf7_daily_counts[$gscf7_day];
	}

	if ($gscf7_day === $gscf7_today) {
		++$gscf7_new_today;
	}

	if (count($gscf7_recent_entries) < 50) {
		if (! isset($gscf7_form_titles[$gscf7_row->form_id])) {
			$gscf7_title                               = get_the_title($gscf7_row->form_id);
			$gscf7_form_titles[$gscf7_row->form_id] = $gscf7_title ? $gscf7_title : sprintf(
				/* translators: %d: form ID */
				esc_html__('Form #%d', 'cf7-google-sheets-connector'),
				$gscf7_row->form_id
			);
		}

		$gscf7_recent_entries[] = array(
			'id'      => (int) $gscf7_row->id,
			'form_id' => (int) $gscf7_row->form_id,
			'form'    => $gscf7_form_titles[$gscf7_row->form_id],
			'date'    => $gscf7_row->date,
			'status'  => $gscf7_status,
		);
	}
}

$gscf7_chart_labels = array_map(
	function ($gscf7_d) {
		return gmdate('M j', strtotime($gscf7_d));
	},
	array_keys($gscf7_daily_counts)
);
$gscf7_chart_values = array_values($gscf7_daily_counts);
?>
<div class="gscf7-free">
	<div class="w-100 m-0">
		<div class="inner-wrap w-100 bg-white p-40">
			<div class="gscf7-entries-dashboard">

				<div class="welcome-heading mb-20">
					<span><?php echo esc_html__('Entries Dashboard', 'cf7-google-sheets-connector'); ?></span>
				</div>
				<p class="mb-30"><?php echo esc_html__('Track how your Contact Form 7 submissions are coming in and which ones still need attention.', 'cf7-google-sheets-connector'); ?></p>



				<!-- ============================= -->
				<!-- CHARTS                         -->
				<!-- ============================= -->
				<div class="gscf7-chart-grid mb-30">
					<div class="gscf7-chart-card gscf7-chart-card-line">
						<div class="para-heading fw-600 mb-20"><?php echo esc_html__('Entries Over Time (Last 30 Days)', 'cf7-google-sheets-connector'); ?></div>
						<canvas id="gscf7-entries-line-chart" height="110"></canvas>
					</div>

					<div class="gscf7-chart-card gscf7-chart-card-pie">
						<div class="para-heading fw-600 mb-20"><?php echo esc_html__('Entries by Status', 'cf7-google-sheets-connector'); ?></div>
						<canvas id="gscf7-entries-pie-chart" height="180"></canvas>
					</div>
				</div>

				<!-- ============================= -->
				<!-- RECENT ENTRIES (sortable)      -->
				<!-- ============================= -->
				<div class="gscf7-recent-entries-wrap">
					<div class="para-heading fw-600 mb-20"><?php echo esc_html__('Recent Entries', 'cf7-google-sheets-connector'); ?></div>

					<?php if (empty($gscf7_recent_entries)) : ?>
						<p><?php echo esc_html__('No entries have been recorded yet.', 'cf7-google-sheets-connector'); ?></p>
					<?php else : ?>
						<table class="widefat gscf7-sortable-table" id="gscf7-entries-table">
							<thead>
								<tr>
									<th class="gscf7-sortable" data-sort="number"><?php echo esc_html__('ID', 'cf7-google-sheets-connector'); ?> <span class="gscf7-sort-arrow"></span></th>
									<th class="gscf7-sortable" data-sort="date"><?php echo esc_html__('Date', 'cf7-google-sheets-connector'); ?> <span class="gscf7-sort-arrow"></span></th>
									<th class="gscf7-sortable" data-sort="text"><?php echo esc_html__('Form', 'cf7-google-sheets-connector'); ?> <span class="gscf7-sort-arrow"></span></th>
									<th class="gscf7-sortable" data-sort="text"><?php echo esc_html__('Status', 'cf7-google-sheets-connector'); ?> <span class="gscf7-sort-arrow"></span></th>
									<th><?php echo esc_html__('Actions', 'cf7-google-sheets-connector'); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($gscf7_recent_entries as $gscf7_entry) : ?>
									<tr>
										<td data-value="<?php echo esc_attr($gscf7_entry['id']); ?>">#<?php echo esc_html($gscf7_entry['id']); ?></td>
										<td data-value="<?php echo esc_attr(strtotime($gscf7_entry['date'])); ?>"><?php echo esc_html(mysql2date('d/m/Y H:i', $gscf7_entry['date'])); ?></td>
										<td data-value="<?php echo esc_attr($gscf7_entry['form']); ?>"><?php echo esc_html($gscf7_entry['form']); ?></td>
										<td data-value="<?php echo esc_attr($gscf7_entry['status']); ?>">
											<span class="gscf7-status-pill gscf7-status-<?php echo esc_attr($gscf7_entry['status']); ?>">
												<?php echo esc_html('read' === $gscf7_entry['status'] ? esc_html__('Read', 'cf7-google-sheets-connector') : esc_html__('Unread', 'cf7-google-sheets-connector')); ?>
											</span>
										</td>
										<td>
											<a class="button action" href="<?php
																			echo esc_url(
																				add_query_arg(
																					array(
																						'page'    => 'wpcf7-google-sheet-config',
																						'tab'     => 'cf7_db',
																						'formId'  => $gscf7_entry['form_id'],
																						'entryId' => $gscf7_entry['id'],
																					),
																					admin_url('admin.php')
																				)
																			);
																			?>"><?php echo esc_html__('View', 'cf7-google-sheets-connector'); ?></a>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</div>

				<script type="application/json" id="gscf7-dashboard-chart-data">
					<?php
					echo wp_json_encode(
						array(
							'labels' => $gscf7_chart_labels,
							'values' => $gscf7_chart_values,
							'read'   => $gscf7_read_count,
							'unread' => $gscf7_unread_count,
							'i18n'   => array(
								'entries' => esc_html__('Entries', 'cf7-google-sheets-connector'),
								'read'    => esc_html__('Read', 'cf7-google-sheets-connector'),
								'unread'  => esc_html__('Unread', 'cf7-google-sheets-connector'),
							),
						)
					);
					?>
				</script>

			</div>
		</div>
	</div>
</div>