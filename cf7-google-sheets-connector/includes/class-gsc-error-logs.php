<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
if ( ! class_exists( 'gscf7_error_logs' ) ) {
	class gscf7_error_logs {

		public function __construct() {
			add_action( 'admin_post_gscf7_clear_logs', array( $this, 'clear_logs' ) );
			add_action( 'admin_post_gscf7_download_logs', array( $this, 'download_logs' ) );
		}
		/*
		=====================================================
		* STATIC ENTRY POINT
		* ===================================================== */
		public static function render_page() {
			( new self() )->gscf7_render_page_html();
		}
		/*
		=====================================================
		* MAIN DB LOGGER
		* ===================================================== */
		public static function log_to_db( $error_id, $code, $message, $details = array() ) {
			global $wpdb;

			$table = $wpdb->prefix . 'gscf7_error_logs';

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking whether a custom plugin table exists.
			$exists = $wpdb->get_var(
				$wpdb->prepare(
					'SHOW TABLES LIKE %s',
					$table
				)
			);
			if ( $exists !== $table ) {
				return false;
			}
			// IMPORTANT FIX START
			if ( is_string( $details ) ) {
				$decoded = json_decode( $details, true );

				if ( json_last_error() === JSON_ERROR_NONE ) {
					$details = $decoded; // already JSON → convert to array
				} else {
					$details = array( 'raw_error' => $details );
				}
			}
			// IMPORTANT FIX END
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking whether a custom plugin table exists.
			$recent_log = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM `' . esc_sql( $table ) . '` WHERE error_id = %s AND code = %d AND message = %s AND created_at >= %s',
					$error_id,
					$code,
					$message,
					gmdate( 'Y-m-d H:i:s', strtotime( '-30 minutes' ) )
				)
			);

			if ( ! empty( $recent_log ) ) {
				return false;
			}
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking whether a custom plugin table exists.
			return $wpdb->insert(
				$table,
				array(
					'error_id'   => (string) $error_id,
					'code'       => (int) $code,
					'message'    => (string) $message,
					'details'    => wp_json_encode( $details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ),
					// Store in UTC/GMT; converted to the site timezone on display.
					// Storing local time meant that changing Settings > General >
					// Timezone silently reinterpreted every historical row.
					'created_at' => current_time( 'mysql', true ),
				),
				array( '%s', '%d', '%s', '%s', '%s' )
			);
		}


		/**
		 * Capture request context for error logging
		 */
		public static function get_request_context() {
			return array(
				'request_url'    => esc_url_raw(
					isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : ''
				),
				'request_method' => isset( $_SERVER['REQUEST_METHOD'] )
					? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) )
					: '',
				'status_code'    => http_response_code(),
				'remote_ip'      => isset( $_SERVER['REMOTE_ADDR'] )
					? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
					: '',
				'user_agent'     => isset( $_SERVER['HTTP_USER_AGENT'] )
					? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) )
					: '',
				'referrer'       => isset( $_SERVER['HTTP_REFERER'] )
					? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) )
					: '',
				'timestamp'      => current_time( 'mysql' ),
			);
		}


		/*
		=====================================================
		* DEBUG → DB NORMALIZER
		* ===================================================== */
		public static function log_from_debug( $error ) {
			// JSON string hoy to decode try karo
			if ( is_string( $error ) ) {
				$decoded = json_decode( $error, true );

				if ( json_last_error() === JSON_ERROR_NONE ) {
					$error = $decoded;
				}
			}

			if ( is_array( $error ) || is_object( $error ) ) {

				self::log_to_db(
					'Cf7_gsheet_error',
					500,
					'Cf7 Google Sheets Error',
					(array) $error
				);
			} else {

				self::log_to_db(
					'Cf7_gsheet_error',
					500,
					'Cf7 Google Sheets Error',
					array(
						'type'      => 'error',
						'raw_error' => trim( (string) $error ),
					)
				);
			}
		}


		/*
		=====================================================
		* ADMIN PAGE
		* ===================================================== */
		public function gscf7_render_page_html() {
			global $wpdb;

			$table = esc_sql( $wpdb->prefix . 'gscf7_error_logs' );
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking whether a custom plugin table exists.
			$exists = $wpdb->get_var(
				$wpdb->prepare(
					'SHOW TABLES LIKE %s',
					$table
				)
			);

			if ( $exists !== $table ) {
				echo '<div class="notice notice-error"><p>Log table not found.</p></div>';
				return;
			}

			$table = esc_sql( $table );

			/*
			 * Paginate. This query previously had no LIMIT, so the entire log
			 * table - including every LONGTEXT details blob - was loaded into
			 * memory on each page view.
			 */
			$per_page = 25;

            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only pagination argument on an admin screen.
			$current_page = isset( $_GET['log_page'] ) ? max( 1, absint( wp_unslash( $_GET['log_page'] ) ) ) : 1;

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom plugin log table.
			$total_logs = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM `' . esc_sql( $table ) . '`' );

			$total_pages  = $total_logs > 0 ? (int) ceil( $total_logs / $per_page ) : 1;
			$current_page = min( $current_page, max( 1, $total_pages ) );
			$offset       = ( $current_page - 1 ) * $per_page;

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom plugin log table, no caching needed for admin log view.
			$logs = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM `' . esc_sql( $table ) . '` ORDER BY created_at DESC LIMIT %d OFFSET %d',
					$per_page,
					$offset
				),
				ARRAY_A
			);
			?>
			<div class="error-log-main shadow-box mt-40 p-30">

				<!-- Header -->
				<div class="error-log-head flex-wrap gap-20">
					<div>
						<div class="heading mt-0">
							<?php echo esc_html__( 'Error Log', 'cf7-google-sheets-connector' ); ?>
						</div>
						<p><?php echo esc_html__( 'Error logs are saved in the database. Please clear them regularly to avoid increasing the database size.', 'cf7-google-sheets-connector' ); ?></p>
					</div>
					<?php if ( ! empty( $logs ) ) : ?>
						<div class="errorlog-button-list">
							<button type="button" id="gsc-clear-logs" class="button btn-logs">
								<?php esc_html_e( 'Clear Logs', 'cf7-google-sheets-connector' ); ?>
							</button>
							<input type="hidden" id="gs-ajax-nonce"
								value="<?php echo esc_attr( wp_create_nonce( 'gs-ajax-nonce' ) ); ?>">

							<a href="
							<?php
							echo esc_url(
								wp_nonce_url(
									admin_url( 'admin-post.php?action=gscf7_download_logs' ),
									'gsc_download_logs_nonce'
								)
							);
							?>
										" class="button button-primary">
								<?php echo esc_html__( 'Download CSV', 'cf7-google-sheets-connector' ); ?>
							</a>

							<button type="button" id="gsc-copy-logs-free" class="button btn-logs">
								<?php echo esc_html__( 'Copy Logs', 'cf7-google-sheets-connector' ); ?>
							</button>
							<div class="gsc-copy-msg d-none"></div>
						</div>
					<?php endif; ?>
				</div>
				<!-- Table -->
				<div class="debug-log-div">

					<table class="widefat striped error-log-table mt-30">
						<thead>
							<tr>
								<th><?php echo esc_html__( 'Date', 'cf7-google-sheets-connector' ); ?></th>
								<th><?php echo esc_html__( 'Error ID', 'cf7-google-sheets-connector' ); ?></th>
								<th><?php echo esc_html__( 'Code', 'cf7-google-sheets-connector' ); ?></th>
								<th><?php echo esc_html__( 'Message', 'cf7-google-sheets-connector' ); ?></th>
								<th><?php echo esc_html__( 'Details', 'cf7-google-sheets-connector' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( ! empty( $logs ) ) : ?>
								<?php foreach ( $logs as $log ) : ?>
									<tr>
										<td>
											<?php
											$format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
											// created_at is stored in UTC; wp_date() converts it to the site's configured timezone (Settings > General).
											$gscf7_ts = strtotime( $log['created_at'] . ' UTC' );
											echo $gscf7_ts
												? esc_html( wp_date( $format . ' (T)', $gscf7_ts ) )
												: esc_html( $log['created_at'] );
											?>
										</td>
										<td><?php echo esc_html( $log['error_id'] ); ?></td>
										<td>
											<span class="sb-error-code" data-code="<?php echo esc_attr( $log['code'] ); ?>">
												<?php echo esc_html( $log['code'] ); ?>
											</span>
										</td>
										<td><?php echo esc_html( $log['message'] ); ?></td>
										<td>
											<?php
											$details = json_decode( $log['details'], true );
											if ( json_last_error() === JSON_ERROR_NONE && is_array( $details ) ) :
												$decoded = $details;
												$display = '';
												if ( ! empty( $decoded['raw_error'] ) ) {
													$raw = $decoded['raw_error'];
													if ( strpos( $raw, 'message:' ) !== false ) {
														$parts   = explode( 'message:', $raw );
														$display = trim( end( $parts ) );
													} else {
														$display = wp_strip_all_tags( $raw );
													}
												} else {
													$display = wp_strip_all_tags( $log['details'] );
												}
												echo esc_html( $display );
											else :
												echo esc_html( $log['details'] );
											endif;
											?>
										</td>
									</tr>
								<?php endforeach; ?>
							<?php else : ?>
								<!--  NO DATA ROW -->
								<tr>
									<td colspan="5" style="text-align:center;">
										<?php echo esc_html__( 'No error logs found.', 'cf7-google-sheets-connector' ); ?>
									</td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>

					<?php if ( $total_pages > 1 ) : ?>
						<div class="tablenav gscf7-log-pagination">
							<div class="tablenav-pages">
								<span class="displaying-num">
									<?php
									printf(
										/* translators: %s: number of log entries. */
										esc_html( _n( '%s item', '%s items', $total_logs, 'cf7-google-sheets-connector' ) ),
										esc_html( number_format_i18n( $total_logs ) )
									);
									?>
								</span>
								<span class="pagination-links">
									<?php
									echo wp_kses_post(
										paginate_links(
											array(
												'base'    => esc_url_raw(
													add_query_arg( 'log_page', '%#%' )
												),
												'format'  => '',
												'prev_text' => '&laquo;',
												'next_text' => '&raquo;',
												'total'   => $total_pages,
												'current' => $current_page,
											)
										)
									);
									?>
								</span>
							</div>
						</div>
					<?php endif; ?>
				</div>
			</div>
			<?php
		}
		
		/**
		 * Recursively sanitize a decoded JSON payload before it is stored.
		 *
		 * Keys are reduced to safe identifiers and every scalar leaf is passed
		 * through sanitize_text_field(). Nesting is capped so a hostile payload
		 * cannot exhaust memory during traversal.
		 *
		 * @param mixed $value Value to sanitize.
		 * @param int   $depth Current recursion depth.
		 * @return mixed Sanitized value.
		 */
		private static function sanitize_log_payload( $value, $depth = 0 ) {
			if ( $depth > 5 ) {
				return '';
			}

			if ( is_array( $value ) ) {
				$clean = array();
				foreach ( $value as $key => $item ) {
					$clean[ sanitize_key( $key ) ] = self::sanitize_log_payload( $item, $depth + 1 );
				}
				return $clean;
			}

			if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) ) {
				return $value;
			}

			if ( is_scalar( $value ) ) {
				return sanitize_text_field( (string) $value );
			}

			return '';
		}

		public static function log_js_error() {
			// Verify the request originated from this site before doing any work.
			check_ajax_referer( 'gs-ajax-nonce', 'security' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error();
			}

			$log = isset( $_POST['log'] )
				? json_decode( sanitize_textarea_field( wp_unslash( $_POST['log'] ) ), true )
				: array();

			if ( is_string( $log ) ) {
				$decoded = json_decode( $log, true );
				if ( json_last_error() === JSON_ERROR_NONE ) {
					$log = $decoded;
				}
			}

			if ( ! is_array( $log ) ) {
				$log = array();
			}

			$log = self::sanitize_log_payload( $log );

			self::log_to_db(
				'js_error',
				intval( $log['status'] ?? 400 ),
				sanitize_text_field( $log['message'] ?? 'JavaScript Error' ),
				array(
					'type'    => $log['type'] ?? 'js',
					'request' => self::get_request_context(),
					'payload' => $log,
				)
			);
			wp_send_json_success();
		}
		/**
		 * Neutralize a value before it is written to a CSV cell.
		 *
		 * Spreadsheet applications evaluate any cell beginning with =, +, -, @,
		 * tab or carriage return as a formula. Prefixing such a value with a
		 * single quote forces it to be treated as literal text without altering
		 * the value that is displayed.
		 *
		 * @param mixed $value Raw cell value.
		 * @return string Value safe to write to a CSV cell.
		 */
		private static function csv_escape( $value ) {
			$value = (string) $value;

			if ( '' !== $value && false !== strpos( "=+-@\t\r", $value[0] ) ) {
				return "'" . $value;
			}

			return $value;
		}

		public function download_logs() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Permission denied.', 'cf7-google-sheets-connector' ) );
			}
			check_admin_referer( 'gsc_download_logs_nonce' );
			global $wpdb;
			$table = $wpdb->prefix . 'gscf7_error_logs';

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin log table.
			$total = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM `' . esc_sql( $table ) . '`' );

			if ( 0 === $total ) {
				wp_safe_redirect( wp_get_referer() );
				exit;
			}

			nocache_headers();
			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename=error-log.csv' );
			// Open output stream
			$output = fopen( 'php://output', 'w' );
			// CSV Header
			fputcsv( $output, array( 'Date', 'Error ID', 'Code', 'Message', 'Details' ) );

			/*
			 * Stream in batches. Loading the whole table at once could exhaust
			 * PHP's memory limit once the log grew to tens of thousands of rows.
			 */
			$batch_size = 1000;
			$csv_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

			for ( $offset = 0; $offset < $total; $offset += $batch_size ) {

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom plugin log table.
				$logs = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT * FROM `' . esc_sql( $table ) . '` ORDER BY id ASC LIMIT %d OFFSET %d',
						$batch_size,
						$offset
					),
					ARRAY_A
				);

				if ( empty( $logs ) ) {
					break;
				}

				foreach ( $logs as $log ) {
					$message = str_replace( array( "\\n", "\\r", "\n", "\r" ), ' ', $log['message'] );
					$details = str_replace( array( "\\n", "\\r", "\n", "\r" ), ' ', $log['details'] );

					// created_at is stored in UTC; convert to the site timezone for the export.
					$csv_ts   = strtotime( $log['created_at'] . ' UTC' );
					$csv_date = $csv_ts
						? wp_date( $csv_format . ' (T)', $csv_ts )
						: $log['created_at'];

					fputcsv(
						$output,
						array_map(
							array( __CLASS__, 'csv_escape' ),
							array(
								$csv_date,
								$log['error_id'],
								$log['code'],
								$message,
								$details,
							)
						)
					);
				}

				unset( $logs );
			}

			if ( is_resource( $output ) ) {
				fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			}
			exit;
		}
	}
	new gscf7_error_logs();
}
add_action( 'wp_ajax_gsc_log_js_error', array( 'gscf7_error_logs', 'log_js_error' ) );
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
