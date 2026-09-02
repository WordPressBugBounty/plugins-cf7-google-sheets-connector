<?php if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly
}
/**
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
 */

?>
<div class="wrap w-100 m-0">
	<div class="inner-wrap  w-100 bg-white p-40">
		<div class="gsc-dashboard">

			<div class="row">
				<div class="col-6">
					<div class="dashboard-left-wrapper mr-15">
						<!---Start Welcome-Header Section--->
						<div class="welcome-wrapper mb-30">
							<div class="welcome-content">
								<div class="welcome-heading mb-20">
									<span><?php echo esc_html__('Welcome To GSheetConnector', 'cf7-google-sheets-connector'); ?></span>
								</div>
								<p>
									<?php echo esc_html__('GSheetConnector is a powerful automation plugin that syncs WordPress data with Google Sheets in real time. It supports WooCommerce, Easy Digital Downloads, and popular form plugins such as Contact Form 7, Gravity Forms, Elementor Forms, along with 10+ additional WordPress integrations for efficient data management.', 'cf7-google-sheets-connector'); ?>
								</p>
							</div>
							<?php
							$gscf7_is_authenticated     = false;
							$gscf7_selected_method      = '';
							$gscf7_authenticated        = get_option('gs_token');
							$gscf7_manual               = get_option('cf7_manual');
							$gscf7_authenticatedService = get_option('gs_cf7_service_account_json');
							$gscf7_auth_method          = get_option('gs_cf7_auth_method');
							$gscf7_per                  = get_option('gs_verify');
							$gscf7_email_account        = '';

							// Check if the user is authenticated when saving existing API method
							if ((Gs_Connector_Free_Utility::instance()->has_live_google_token() && $gscf7_per == 'valid' && $gscf7_auth_method === 'cf7_existing')) {
								$gscf7_google_sheet  = new CF7GSC_googlesheet();
								$gscf7_email_account = $gscf7_google_sheet->gsheet_print_google_account_email();
								if ($gscf7_email_account) {
									$gscf7_is_authenticated = true;
									$gscf7_selected_method  = esc_html(__('Existing Client / Secret Key (Auto Setup)', 'cf7-google-sheets-connector'));
								}
							} elseif ((! empty($gscf7_authenticatedService) && $gscf7_auth_method === 'cf7_service')) {
								$gscf7_is_authenticated = true;
								$gscf7_selected_method  = esc_html(__('Service Account (Recommended)', 'cf7-google-sheets-connector'));
							} else {
								$gscf7_selected_method = esc_html(__('Auth Required', 'cf7-google-sheets-connector'));
							}
							?>
							<div class="unlock-pro-button-sections mt-20">
								<?php if ($gscf7_email_account) { ?>
									<div class="gscf7-integration-box">
										<div class="gsc-google-auth-card mt-30 mb-30">
											<div>
												<div class="heading mt-0 mb-30"> <?php echo esc_html(__('Google Account Connection', 'cf7-google-sheets-connector')); ?>
													<span class="badge"><?php echo esc_attr($gscf7_selected_method); ?></span>
												</div>
											</div>
											<div class="d-flex flex-wrap gap-20 justify-between align-center">

												<div class="gsc-google-auth-left d-flex flex-wrap align-center gap-15">

													<div class="gsc-google-icon">G</div>

													<div class="connected-account">

														<div class="gsc-connected-left d-flex">

															<span class="gsc-connected-label">
																<?php echo esc_html(__('Connected Email Account', 'cf7-google-sheets-connector')); ?>

															</span>

															<span class="connected-account-manual gsc-connected-email">

																<?php echo esc_html($gscf7_email_account); ?>
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
								<?php } elseif (! $gscf7_is_authenticated) { ?>
									<a class="btn btn-primary link-hover-white" href="<?php echo esc_html(admin_url('admin.php?page=wpcf7-google-sheet-config&tab=integration')); ?>">
										<?php echo esc_html__("Let's Connect", 'cf7-google-sheets-connector'); ?>
									</a>
								<?php } ?>

								<?php if ($gscf7_is_authenticated) { ?>

									<div class="gscf7-feed-table-wrap max-height">
										<?php

										echo Gs_Connector_Service::instance()->gscf7_render_connected_feeds_table(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										?>
									</div>
								<?php } ?>
							</div>
						</div>
						<!---End Welcome-Header Section--->

						<!-- HERO -->
						<div class="set-up-guid-wrapper welcome-wrapper">
							<div class="welcome-content">
								<div class="welcome-heading mb-10">
									<span><?php echo esc_html__('Setup Guide & Troubleshooting', 'cf7-google-sheets-connector'); ?></span>
								</div>
								<p>
									<?php echo esc_html__('Sync Contact Form 7 data with Google Sheets in real-time effortlessly and accurately.', 'cf7-google-sheets-connector'); ?>
								</p>
							</div>

							<div class="setup-content-data mt-20">
								<div class="setup-row d-flex justify-between gap-20">
									<div class="google-api-setting-guide">
										<div class="dashboard-pro-small-head"><?php echo esc_html__('Getting Started', 'cf7-google-sheets-connector'); ?></div>
										<ul>
											<li><a href="https://www.gsheetconnector.com/docs/cf7-gsheetconnector/installation-process-free-version" target="_blank"><?php echo esc_html__('Installation Process', 'cf7-google-sheets-connector'); ?></a></li>
											<li><a href="https://www.gsheetconnector.com/docs/cf7-gsheetconnector/integration-with-google-existing-method" target="_blank"><?php echo esc_html__('Integration with Google (Existing Method)', 'cf7-google-sheets-connector'); ?></a></li>
											<li><a href="https://www.gsheetconnector.com/docs/cf7-gsheetconnector/service-account-setting-pro-version" target="_blank"><?php echo esc_html__('Integration with Google (Service Method)', 'cf7-google-sheets-connector'); ?></a></li>
											<li><a href="https://www.gsheetconnector.com/docs/cf7-gsheetconnector/plugin-settings-free-version" target="_blank"><?php echo esc_html__('Integration of Contact Form with Google Sheet', 'cf7-google-sheets-connector'); ?></a></li>
										</ul>
									</div>
									<div class="google-api-setting-guide">
										<div class="dashboard-pro-small-head"><?php echo esc_html__('Docs & Troubleshooting', 'cf7-google-sheets-connector'); ?></div>
										<ul>
											<li><a href="https://www.gsheetconnector.com/docs/general/how-to-enable-debugging-in-wordpress" target="_blank"><?php echo esc_html__('How to Enable Debugging in WordPress', 'cf7-google-sheets-connector'); ?></a></li>
											<li><a href="https://www.gsheetconnector.com/docs/general/common-errors-issues#toc-heading-1" target="_blank"><?php echo esc_html__('Invalid OAuth2 token', 'cf7-google-sheets-connector'); ?></a></li>
											<li><a href="https://www.gsheetconnector.com/docs/general/how-to-change-date-time-format-and-time-zone-in-google-sheets" target="_blank"><?php echo esc_html__('Change Date/Time Format and Time Zone in Google Sheets', 'cf7-google-sheets-connector'); ?></a></li>
											<li><a href="https://www.gsheetconnector.com/docs/cf7-gsheetconnector/how-to-save-uploaded-files-to-google-drive" target="_blank"><?php echo esc_html__('How to Save Uploaded Files to Google Drive', 'cf7-google-sheets-connector'); ?></a></li>
										</ul>
									</div>
								</div>

								<div class="setup-row">
									<div class="google-api-setting-guide">
										<div class="dashboard-pro-small-head"><?php echo esc_html__('Additional Resources', 'cf7-google-sheets-connector'); ?></div>
										<ul>
											<li><a href="https://www.gsheetconnector.com/docs/cf7-gsheetconnector/integration-with-google-manual-method" target="_blank"><?php echo esc_html__('Integration with Google (Manual Method)', 'cf7-google-sheets-connector'); ?></a></li>
											<li><a href="https://www.gsheetconnector.com/docs/cf7-gsheetconnector/plugin-settings-pro-version" target="_blank"><?php echo esc_html__('Plugin Settings – PRO Version', 'cf7-google-sheets-connector'); ?></a></li>
											<li><a href="https://www.gsheetconnector.com/docs/cf7-gsheetconnector/role-settings-pro-version" target="_blank"><?php echo esc_html__('Role Settings – PRO Version', 'cf7-google-sheets-connector'); ?></a></li>
											<li><a href="https://www.gsheetconnector.com/docs/cf7-gsheetconnector/cf7-database-pro-version" target="_blank"><?php echo esc_html__('CF7 Database – PRO Version', 'cf7-google-sheets-connector'); ?></a></li>
											<li><a href="https://www.gsheetconnector.com/docs/cf7-gsheetconnector/cf7-conditional-logic-pro-version" target="_blank"><?php echo esc_html__('CF7 Conditional Logic – PRO Version', 'cf7-google-sheets-connector'); ?></a></li>
										</ul>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="col-6">
					<div class="plugin-category-wrapper welcome-wrapper ml-15">
						<div class="welcome-heading mb-10">
							<span><?php echo esc_html__('Plugins by Category', 'cf7-google-sheets-connector'); ?></span>
						</div>
						<p>
							<?php echo esc_html__('Find the perfect connector for your WordPress workflow.', 'cf7-google-sheets-connector'); ?>
						</p>
						<div class="plugin-category-section mt-30">
							<a href="https://www.gsheetconnector.com/plugins#contactform" target="_blank" class="plugin-category-box text-decoration-none">
								<div class="plugin-category-icon">
									<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-text w-6 h-6 text-emerald-600" aria-hidden="true">
										<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path>
										<path d="M14 2v4a2 2 0 0 0 2 2h4"></path>
										<path d="M10 9H8"></path>
										<path d="M16 13H8"></path>
										<path d="M16 17H8"></path>
									</svg>
								</div>
								<div class="plugin-category-content">
									<div class="plugin-category-name fw-600">
										<?php echo esc_html__('Contact Form Connectors', 'cf7-google-sheets-connector'); ?>
									</div>
									<div class="plugin-category-badge">
										<?php echo esc_html__('6 plugins available', 'cf7-google-sheets-connector'); ?>
									</div>
								</div>
								<div class="plugin-category-arrow">
									<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-right w-5 h-5 text-slate-400 group-hover:text-emerald-600 group-hover:translate-x-0.5 transition-all" aria-hidden="true">
										<path d="M5 12h14"></path>
										<path d="m12 5 7 7-7 7"></path>
									</svg>
								</div>
							</a>

							<a href="https://www.gsheetconnector.com/plugins#ecommerce" target="_blank" class="plugin-category-box text-decoration-none">
								<div class="plugin-category-icon">
									<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shopping-cart w-6 h-6 text-emerald-600" aria-hidden="true">
										<circle cx="8" cy="21" r="1"></circle>
										<circle cx="19" cy="21" r="1"></circle>
										<path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"></path>
									</svg>
								</div>
								<div class="plugin-category-content">
									<div class="plugin-category-name fw-600">
										<?php echo esc_html__('eCommerce Connectors', 'cf7-google-sheets-connector'); ?>
									</div>
									<div class="plugin-category-badge">
										<?php echo esc_html__('2 plugins available', 'cf7-google-sheets-connector'); ?>
									</div>
								</div>
								<div class="plugin-category-arrow">
									<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-right w-5 h-5 text-slate-400 group-hover:text-emerald-600 group-hover:translate-x-0.5 transition-all" aria-hidden="true">
										<path d="M5 12h14"></path>
										<path d="m12 5 7 7-7 7"></path>
									</svg>
								</div>
							</a>

							<a href="https://www.gsheetconnector.com/plugins#pagebuilderform" target="_blank" class="plugin-category-box text-decoration-none">
								<div class="plugin-category-icon">
									<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-panels-top-left w-6 h-6 text-emerald-600" aria-hidden="true">
										<rect width="18" height="18" x="3" y="3" rx="2"></rect>
										<path d="M3 9h18"></path>
										<path d="M9 21V9"></path>
									</svg>
								</div>
								<div class="plugin-category-content">
									<div class="plugin-category-name fw-600">
										<?php echo esc_html__('Page Builder Forms', 'cf7-google-sheets-connector'); ?>
									</div>
									<div class="plugin-category-badge">
										<?php echo esc_html__('3 plugins available', 'cf7-google-sheets-connector'); ?>
									</div>
								</div>
								<div class="plugin-category-arrow">
									<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-right w-5 h-5 text-slate-400 group-hover:text-emerald-600 group-hover:translate-x-0.5 transition-all" aria-hidden="true">
										<path d="M5 12h14"></path>
										<path d="m12 5 7 7-7 7"></path>
									</svg>
								</div>
							</a>
							<a href="https://www.gsheetconnector.com/gsheetconnector-for-wp-core" target="_blank" class="plugin-category-box text-decoration-none">
								<div class="plugin-category-icon">
									<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-database w-6 h-6 text-emerald-600" aria-hidden="true">
										<ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
										<path d="M3 5V19A9 3 0 0 0 21 19V5"></path>
										<path d="M3 12A9 3 0 0 0 21 12"></path>
									</svg>
								</div>
								<div class="plugin-category-content">
									<div class="plugin-category-name fw-600">
										<?php echo esc_html__('WP Core Connector', 'cf7-google-sheets-connector'); ?>
									</div>
									<div class="plugin-category-badge">
										<?php echo esc_html__('1 plugin available', 'cf7-google-sheets-connector'); ?>
									</div>
								</div>
								<div class="plugin-category-arrow">
									<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-right w-5 h-5 text-slate-400 group-hover:text-emerald-600 group-hover:translate-x-0.5 transition-all" aria-hidden="true">
										<path d="M5 12h14"></path>
										<path d="m12 5 7 7-7 7"></path>
									</svg>
								</div>
							</a>

							<a href="https://profiles.wordpress.org/gsheetconnector/" target="_blank" class="plugin-category-box text-decoration-none">
								<div class="plugin-category-icon">
									<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-gift w-6 h-6 text-emerald-600" aria-hidden="true">
										<rect x="3" y="8" width="18" height="4" rx="1"></rect>
										<path d="M12 8v13"></path>
										<path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"></path>
										<path d="M7.5 8a2.5 2.5 0 0 1 0-5A4.8 8 0 0 1 12 8a4.8 8 0 0 1 4.5-5 2.5 2.5 0 0 1 0 5"></path>
									</svg>
								</div>
								<div class="plugin-category-content">
									<div class="plugin-category-name fw-600">
										<?php echo esc_html__('Free Plugins', 'cf7-google-sheets-connector'); ?>
									</div>
									<div class="plugin-category-badge">
										<?php echo esc_html__('12 plugins available', 'cf7-google-sheets-connector'); ?>
									</div>
								</div>
								<div class="plugin-category-arrow">
									<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-right w-5 h-5 text-slate-400 group-hover:text-emerald-600 group-hover:translate-x-0.5 transition-all" aria-hidden="true">
										<path d="M5 12h14"></path>
										<path d="m12 5 7 7-7 7"></path>
									</svg>
								</div>
							</a>
						</div>
					</div>
					<!---Start Support  ticket--->

					<div class="gsc-support-card mt-30 welcome-wrapper ml-15">

						<!-- LEFT SIDE -->
						<div class="gsc-support-left">

							<div class="gsc-support-icon d-flex justify-center align-center">
								<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M19.8335 14V3.50004C19.8335 3.19062 19.7106 2.89388 19.4918 2.67508C19.273 2.45629 18.9762 2.33337 18.6668 2.33337H3.50016C3.19074 2.33337 2.894 2.45629 2.6752 2.67508C2.45641 2.89388 2.3335 3.19062 2.3335 3.50004V19.8334L7.00016 15.1667H18.6668C18.9762 15.1667 19.273 15.0438 19.4918 14.825C19.7106 14.6062 19.8335 14.3095 19.8335 14ZM24.5002 7.00004H22.1668V17.5H7.00016V19.8334C7.00016 20.1428 7.12308 20.4395 7.34187 20.6583C7.56066 20.8771 7.85741 21 8.16683 21H21.0002L25.6668 25.6667V8.16671C25.6668 7.85729 25.5439 7.56054 25.3251 7.34175C25.1063 7.12296 24.8096 7.00004 24.5002 7.00004Z" fill="#141B38"></path>
								</svg>
							</div>

							<div class="gsc-content">
								<div class="support-headings"><?php echo esc_html__('Need more support? We\'re here to help.', 'cf7-google-sheets-connector'); ?></div>

								<a href="https://wordpress.org/support/plugin/cf7-google-sheets-connector" target="_blank" class="btn btn-primary mt-10 link-hover-white">
									<?php echo esc_html__('Submit a Support Ticket', 'cf7-google-sheets-connector'); ?>
									<svg width="10" height="10" viewBox="0 0 6 8" fill="#fff" xmlns="http://www.w3.org/2000/svg">
										<path d="M1.66681 0L0.726807 0.94L3.78014 4L0.726807 7.06L1.66681 8L5.66681 4L1.66681 0Z"></path>
									</svg>
								</a>
							</div>

						</div>

						<!-- RIGHT SIDE -->
						<div class="gsc-support-right">

							<div class="gsc-avatars justify-center">
								<img src="<?php echo esc_url(GS_CONNECTOR_URL); ?>/assets/img/avatar-2.jfif" alt="">
								<img src="<?php echo esc_url(GS_CONNECTOR_URL); ?>/assets/img/avatar-3.png" alt="">
								<img src="<?php echo esc_url(GS_CONNECTOR_URL); ?>/assets/img/avatar-5.jfif" alt="">
								<img src=" <?php echo esc_url(GS_CONNECTOR_URL); ?>/assets/img/avatar-4.png" alt="">
								<img src="<?php echo esc_url(GS_CONNECTOR_URL); ?>/assets/img/avatar.jpeg" alt="">
							</div>

							<p class="text-center"><?php echo esc_html__('Our fast and friendly support team is always happy to help!', 'cf7-google-sheets-connector'); ?></p>

						</div>

					</div>

					<!---End Support  ticket--->
				</div>
			</div>
			<?php
			global $wpdb;

			$gscf7_table = $wpdb->prefix . 'cf7db_gsheet_forms';
			/*
			* Entries Dashboard — at-a-glance stats, trend chart, and a sortable
			* recent-entries table for everything stored in the CF7 Database table.
			*
			* @since 5.2.4
			*/

			if (empty($gscf7_table)) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name only (from $wpdb->prefix), not user input; escaped since identifiers cannot use $wpdb->prepare() placeholders.
				$gscf7_total_entries = (int) $wpdb->get_var('SELECT COUNT(*) FROM `' . esc_sql($gscf7_table) . '`');

				/**
				 * Cap the number of rows pulled to compute status counts / the trend chart.
				 * 0 = no cap. Large installs can filter this down for performance.
				 *
				 * @since 5.2.4
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

				$gscf7_form_titles = array(); // form_id => cached title, used for the daily-stats loop only.

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
				}

				// Forms list for the "Form" filter dropdown on the Recent Entries table.
				$gscf7_forms = get_posts(
					array(
						'post_type'      => 'wpcf7_contact_form',
						'post_status'    => 'publish',
						'posts_per_page' => -1,
						'orderby'        => 'title',
						'order'          => 'ASC',
					)
				);

				$gscf7_chart_labels = array_map(
					function ($gscf7_d) {
						return gmdate('M j', strtotime($gscf7_d));
					},
					array_keys($gscf7_daily_counts)
				);
				$gscf7_chart_values = array_values($gscf7_daily_counts);
			?>

				<div class="welcome-wrapper w-100 p-40 mb-30 mt-30">
					<div class="gscf7-entries-dashboard">

						<div class="welcome-heading mb-20">
							<span><?php echo esc_html__('Entries Dashboard', 'cf7-google-sheets-connector'); ?></span>
						</div>
						<p class="mb-30"><?php echo esc_html__('Track how your Contact Form 7 submissions are coming in and which ones still need attention.', 'cf7-google-sheets-connector'); ?></p>

						<div class="gscf7-filter-form mb-20">
							<label for="gscf7-entries-filter-form"><?php echo esc_html__('Form', 'cf7-google-sheets-connector'); ?></label>

							<select id="gscf7-entries-filter-form" class="gsc-select">
								<option value="0"><?php echo esc_html__('All Forms', 'cf7-google-sheets-connector'); ?></option>

								<?php foreach ($gscf7_forms as $gscf7_form) : ?>
									<option value="<?php echo esc_attr($gscf7_form->ID); ?>"><?php echo esc_html(get_the_title($gscf7_form)); ?></option>
								<?php endforeach; ?>
							</select>
							<span id="gscf7-entries-loader" class="loading d-none" aria-hidden="true"></span>
						</div>

						<input type="hidden" id="gscf7-dashboard-stats-nonce" value="<?php echo esc_attr(wp_create_nonce('gscf7-dashboard-stats')); ?>">

						<!-- ============================= -->
						<!-- STAT CARDS                     -->
						<!-- ============================= -->
						<div class="gscf7-stat-grid mb-30">
							<div class="gscf7-stat-card">
								<div class="gscf7-stat-icon gscf7-stat-icon-total">
									<span class="dashicons dashicons-list-view"></span>
								</div>
								<div class="gscf7-stat-body">
									<div class="gscf7-stat-label"><?php echo esc_html__('Total Entries', 'cf7-google-sheets-connector'); ?></div>
									<div class="gscf7-stat-number" id="gscf7-stat-total"><?php echo esc_html(number_format_i18n($gscf7_total_entries)); ?></div>
								</div>
							</div>

							<div class="gscf7-stat-card">
								<div class="gscf7-stat-icon gscf7-stat-icon-unread">
									<span class="dashicons dashicons-email-alt"></span>
								</div>
								<div class="gscf7-stat-body">
									<div class="gscf7-stat-label"><?php echo esc_html__('Unread', 'cf7-google-sheets-connector'); ?></div>
									<div class="gscf7-stat-number" id="gscf7-stat-unread"><?php echo esc_html(number_format_i18n($gscf7_unread_count)); ?></div>
								</div>
							</div>

							<div class="gscf7-stat-card">
								<div class="gscf7-stat-icon gscf7-stat-icon-read">
									<span class="dashicons dashicons-yes"></span>
								</div>
								<div class="gscf7-stat-body">
									<div class="gscf7-stat-label"><?php echo esc_html__('Read', 'cf7-google-sheets-connector'); ?></div>
									<div class="gscf7-stat-number" id="gscf7-stat-read"><?php echo esc_html(number_format_i18n($gscf7_read_count)); ?></div>
								</div>
							</div>

							<div class="gscf7-stat-card">
								<div class="gscf7-stat-icon gscf7-stat-icon-new">
									<span class="dashicons dashicons-plus-alt2"></span>
								</div>
								<div class="gscf7-stat-body">
									<div class="gscf7-stat-label"><?php echo esc_html__('New Today', 'cf7-google-sheets-connector'); ?></div>
									<div class="gscf7-stat-number" id="gscf7-stat-new-today"><?php echo esc_html(number_format_i18n($gscf7_new_today)); ?></div>
								</div>
							</div>
						</div>

						<!-- ============================= -->
						<!-- CHARTS                         -->
						<!-- ============================= -->


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

						<div class="text-right">
							<a href="<?php echo esc_url(admin_url('admin.php?page=wpcf7-google-sheet-config&tab=cf7_db')); ?>"
								class="button gscf7-view-all-entries">
								<?php echo esc_html__('View All Entries', 'cf7-google-sheets-connector'); ?>
							</a>
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
			<?php } ?>

			<!---Start PRO FEATURE--->
			<div class="pro-container mt-30 welcome-wrapper">
				<span class="pro-badge"><?php echo esc_html(__('Amazing Key Features', 'cf7-google-sheets-connector')); ?></span>
				<div class="welcome-heading mb-15 mt-20"><?php echo esc_html(__('Everything You Need to Sync Data', 'cf7-google-sheets-connector')); ?></div>
				<p>
					<?php echo esc_html(__('Common features shared across every GSheetConnector Pro add-on built for reliability, flexibility, and scale.', 'cf7-google-sheets-connector')); ?>
				</p>

				<!-- LEFT -->
				<div class="d-flex gap-30 mt-30 d-flex-responsiveness">
					<div class="pro-left w-50">
						<div class="list dashboard-pro-features">
							<ul>
								<li><?php echo esc_html__('Google Sheets API v4', 'cf7-google-sheets-connector'); ?></li>
								<li><?php echo esc_html__('One-Click Authentication', 'cf7-google-sheets-connector'); ?></li>
								<li><?php echo esc_html__('Authenticated Email Display', 'cf7-google-sheets-connector'); ?></li>
								<li><?php echo esc_html__('Click & Fetch Automation', 'cf7-google-sheets-connector'); ?></li>
								<li><?php echo esc_html__('Create New Spreadsheet', 'cf7-google-sheets-connector'); ?></li>
								<li><?php echo esc_html__('Manual Sheet / Tab Name', 'cf7-google-sheets-connector'); ?></li>
							</ul>
							<ul>
								<li><?php echo esc_html__('Automated Sheet & Tab', 'cf7-google-sheets-connector'); ?></li>
								<li><?php echo esc_html__('Multiple Feeds to Sheets', 'cf7-google-sheets-connector'); ?></li>
								<li><?php echo esc_html__('Drag-and-Drop Column Order', 'cf7-google-sheets-connector'); ?></li>
								<li><?php echo esc_html__('Headers On / Off + Rename', 'cf7-google-sheets-connector'); ?></li>
								<li><?php echo esc_html__('Image / PDF Attachment Link', 'cf7-google-sheets-connector'); ?></li>
								<li><?php echo esc_html__('Freeze & Color Headers', 'cf7-google-sheets-connector'); ?></li>
							</ul>
							<ul>
								<li><?php echo esc_html__('Sync Past Entries', 'cf7-google-sheets-connector'); ?></li>
								<li><?php echo esc_html__('Role Management', 'cf7-google-sheets-connector'); ?></li>
								<li><?php echo esc_html__('Quick Configuration', 'cf7-google-sheets-connector'); ?></li>
								<li><?php echo esc_html__('Multi-Language Support', 'cf7-google-sheets-connector'); ?></li>
								<li><?php echo esc_html__('Multi-Site Support', 'cf7-google-sheets-connector'); ?></li>
								<li><?php echo esc_html__('Latest WP & PHP Support', 'cf7-google-sheets-connector'); ?></li>
							</ul>
						</div>


						<div class="pro-actions mt-30 gap-20">
							<a href="https://www.gsheetconnector.com/cf7-google-sheet-connector-pro" target="_blank" class="pro-btn text-decoration-none link-hover-white"><?php echo esc_html(__('Upgrade to Pro', 'cf7-google-sheets-connector')); ?></a>
							<a href="https://www.gsheetconnector.com/cf7-google-sheet-connector-pro#features" target="_blank"><?php echo esc_html(__('View Full Features', 'cf7-google-sheets-connector')); ?></a>
						</div>

					</div>

					<!-- RIGHT -->
					<div class="pro-right w-50">

						<div class="right-card">

							<!-- FLOW -->
							<div class="flow-ui">
								<div class="flow-step"><?php echo esc_html(__('Form', 'cf7-google-sheets-connector')); ?></div>
								<div class="line"></div>
								<div class="flow-step mid"><?php echo esc_html(__('Processing', 'cf7-google-sheets-connector')); ?></div>
								<div class="line"></div>
								<div class="flow-step success"><?php echo esc_html(__('Sheet', 'cf7-google-sheets-connector')); ?></div>
							</div>

							<!-- STATS -->
							<div class="sync-stats">
								<div>
									<strong><?php echo esc_html(__('Instant', 'cf7-google-sheets-connector')); ?></strong>
									<p><?php echo esc_html(__('Real-time updates', 'cf7-google-sheets-connector')); ?></p>
								</div>
								<div>
									<strong><?php echo esc_html(__('100%', 'cf7-google-sheets-connector')); ?></strong>
									<p><?php echo esc_html(__('Accuracy', 'cf7-google-sheets-connector')); ?></p>
								</div>
								<div>
									<strong><?php echo esc_html(__('Flexible', 'cf7-google-sheets-connector')); ?></strong>
									<p><?php echo esc_html(__('Custom mapping', 'cf7-google-sheets-connector')); ?></p>
								</div>
							</div>
						</div>

					</div>


				</div>
			</div>
			<!---End PRO FEATURE--->


			<!---Start Video Tutorial Section--->
			<div class="video-section-wrapper mt-30 welcome-wrapper">
				<div class="welcome-heading mb-30">
					<span><?php echo esc_html__('Video Tutorials', 'cf7-google-sheets-connector'); ?></span>
				</div>
				<div class="video-grid">
					<div class="video-item">
						<iframe class="w-100" height="200" src="https://www.youtube.com/embed/E_dVAQHyBlw" title="Integration of Google Sheets with WordPress Contact form 7 | Step by Step Guide | FREE Version" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
					</div>

					<div class="video-item">
						<iframe class="w-100" height="200" src="https://www.youtube.com/embed/vF3qHmNrT5o" title="Introducing CF7 Google Sheet Connector for WordPress based Contact Form 7 - by GSheetConnector" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
					</div>

					<div class="video-item">
						<iframe class="w-100" height="200" src="https://www.youtube.com/embed/t6zUg6OKJQI" title="CF7 Google Sheet Connector Stopped Working after an update to v4" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
					</div>
				</div>
			</div>
			<!---End Video Tutorial Section--->

		</div>
	</div>
</div>