<?php
if (!defined('ABSPATH')) {
    exit;
}
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
if (!class_exists('gscf7_error_logs')) {
    class gscf7_error_logs
    {
        public function __construct()
        {
            add_action('admin_post_gscf7_clear_logs', [$this, 'clear_logs']);
            add_action('admin_post_gscf7_download_logs', [$this, 'download_logs']);
        }
        /* =====================================================
        * STATIC ENTRY POINT
        * ===================================================== */
        public static function render_page()
        {
            (new self())->gscf7_render_page_html();
        }
        /* =====================================================
        * MAIN DB LOGGER
        * ===================================================== */
        public static function log_to_db($error_id, $code, $message, $details = [])
        {
            global $wpdb;

            $table = $wpdb->prefix . 'gscf7_error_logs';

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking whether a custom plugin table exists.
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    'SHOW TABLES LIKE %s',
                    $table
                )
            );
            if ($exists !== $table) {
                return false;
            }
            //    IMPORTANT FIX START
            if (is_string($details)) {
                $decoded = json_decode($details, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    $details = $decoded; // already JSON → convert to array
                } else {
                    $details = ['raw_error' => $details];
                }
            }
            //    IMPORTANT FIX END
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking whether a custom plugin table exists.
            $recent_log = $wpdb->get_var(
                $wpdb->prepare(
                    'SELECT COUNT(*) FROM `' . esc_sql($table) . '` WHERE error_id = %s AND code = %d AND message = %s AND created_at >= %s',
                    $error_id,
                    $code,
                    $message,
                    gmdate('Y-m-d H:i:s', strtotime('-30 minutes'))
                )
            );

            if (! empty($recent_log)) {
                return false;
            }
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking whether a custom plugin table exists.
            return $wpdb->insert(
                $table,
                [
                    'error_id'   => (string) $error_id,
                    'code'       => (int) $code,
                    'message'    => (string) $message,
                    'details'    => wp_json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'created_at' => current_time('mysql'),
                ],
                ['%s', '%d', '%s', '%s', '%s']
            );
        }


        /**
         * Capture request context for error logging
         */
        public static function get_request_context()
        {
            return [
                'request_url' => esc_url_raw(
                    isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : ''
                ),
                'request_method' => isset($_SERVER['REQUEST_METHOD'])
                    ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD']))
                    : '',
                'status_code'    => http_response_code(),
                'remote_ip' => isset($_SERVER['REMOTE_ADDR'])
                    ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']))
                    : '',
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT'])
                    ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT']))
                    : '',
                'referrer' => isset($_SERVER['HTTP_REFERER'])
                    ? esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER']))
                    : '',
                'timestamp'      => current_time('mysql'),
            ];
        }


        /* =====================================================
     * DEBUG → DB NORMALIZER
     * ===================================================== */
        public static function log_from_debug($error)
        {
            // JSON string hoy to decode try karo
            if (is_string($error)) {
                $decoded = json_decode($error, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    $error = $decoded;
                }
            }

            if (is_array($error) || is_object($error)) {

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
                    [
                        'type'      => 'error',
                        'raw_error' => trim((string) $error),
                    ]
                );
            }
        }


        /* =====================================================
     * ADMIN PAGE
     * ===================================================== */
        public function gscf7_render_page_html()
        {
            global $wpdb;

            $table = esc_sql($wpdb->prefix . 'gscf7_error_logs');
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking whether a custom plugin table exists.
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    'SHOW TABLES LIKE %s',
                    $table
                )
            );

            if ($exists !== $table) {
                echo '<div class="notice notice-error"><p>Log table not found.</p></div>';
                return;
            }

            $table = esc_sql($table);

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom plugin log table, no caching needed for admin log view.
            $logs = $wpdb->get_results(
                "SELECT * FROM `" . esc_sql($table) . "` ORDER BY created_at DESC",
                ARRAY_A
            );
?>
            <div class="error-log-main shadow-box mt-40 p-30">

                <!-- Header -->
                <div class="error-log-head flex-wrap gap-20">
                    <div>
                        <div class="heading mt-0">
                            <?php echo esc_html__("Error Log", 'cf7-google-sheets-connector'); ?>
                        </div>
                        <p><?php echo esc_html__('Error logs are saved in the database. Please clear them regularly to avoid increasing the database size.', 'cf7-google-sheets-connector'); ?></p>
                    </div>
                    <?php if (!empty($logs)) : ?>
                        <div class="errorlog-button-list">

                            <a href="<?php echo esc_url(
                                            wp_nonce_url(
                                                admin_url('admin-post.php?action=gscf7_clear_logs'),
                                                'gsc_clear_logs_nonce'
                                            )
                                        ); ?>" class="button btn-logs">
                                <?php echo esc_html__("Clear Logs", 'cf7-google-sheets-connector'); ?>
                            </a>

                            <a href="<?php echo esc_url(
                                            wp_nonce_url(
                                                admin_url('admin-post.php?action=gscf7_download_logs'),
                                                'gsc_download_logs_nonce'
                                            )
                                        ); ?>" class="button button-primary">
                                <?php echo esc_html__("Download CSV", 'cf7-google-sheets-connector'); ?>
                            </a>

                            <button type="button" id="gsc-copy-logs-free" class="button btn-logs">
                                <?php echo esc_html__("Copy Logs", 'cf7-google-sheets-connector'); ?>
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
                                <th><?php echo esc_html__("Date", 'cf7-google-sheets-connector'); ?></th>
                                <th><?php echo esc_html__("Error ID", 'cf7-google-sheets-connector'); ?></th>
                                <th><?php echo esc_html__("Code", 'cf7-google-sheets-connector'); ?></th>
                                <th><?php echo esc_html__("Message", 'cf7-google-sheets-connector'); ?></th>
                                <th><?php echo esc_html__("Details", 'cf7-google-sheets-connector'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($logs)) : ?>
                                <?php foreach ($logs as $log) : ?>
                                    <tr>
                                        <td>
                                            <?php
                                            $format = get_option('date_format') . ' ' . get_option('time_format');
                                            echo esc_html(mysql2date($format, $log['created_at'], false));
                                            ?>
                                        </td>
                                        <td><?php echo esc_html($log['error_id']); ?></td>
                                        <td>
                                            <span class="sb-error-code" data-code="<?php echo esc_attr($log['code']); ?>">
                                                <?php echo esc_html($log['code']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo esc_html($log['message']); ?></td>
                                        <td>
                                            <?php
                                            $details = json_decode($log['details'], true);
                                            if (json_last_error() === JSON_ERROR_NONE && is_array($details)) :
                                                $decoded = $details;
                                                $display = '';
                                                if (!empty($decoded['raw_error'])) {
                                                    $raw = $decoded['raw_error'];
                                                    if (strpos($raw, 'message:') !== false) {
                                                        $parts = explode('message:', $raw);
                                                        $display = trim(end($parts));
                                                    } else {
                                                        $display = wp_strip_all_tags($raw);
                                                    }
                                                } else {
                                                    $display = wp_strip_all_tags($log['details']);
                                                }
                                                echo esc_html($display);
                                            else :
                                                echo esc_html($log['details']);
                                            endif;
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <!--  NO DATA ROW -->
                                <tr>
                                    <td colspan="5" style="text-align:center;">
                                        <?php echo esc_html__('No error logs found.', 'cf7-google-sheets-connector'); ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
<?php
        }
        /* =====================================================
        * ACTIONS
        * ===================================================== */
        public function clear_logs()
        {
            if (! current_user_can('manage_options')) {
                wp_die(esc_html__('Permission denied.', 'cf7-google-sheets-connector'));
            }
            check_admin_referer('gsc_clear_logs_nonce');
            global $wpdb;
            $table = $wpdb->prefix . 'gscf7_error_logs';
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $wpdb->query('TRUNCATE TABLE `' . esc_sql($table) . '`');
            wp_safe_redirect(wp_get_referer());
            exit;
        }
        public static function log_js_error()
        {
            if (!current_user_can('manage_options')) {
                wp_send_json_error();
            }
            // phpcs:disable WordPress.Security.NonceVerification.Missing
            // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $log = isset($_POST['log'])
                ? json_decode(wp_unslash($_POST['log']), true)
                : array();
            // phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            // phpcs:enable WordPress.Security.NonceVerification.Missing
            if (is_string($log)) {
                $decoded = json_decode($log, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $log = $decoded;
                }
            }
            self::log_to_db(
                'js_error',
                intval($log['status'] ?? 400),
                sanitize_text_field($log['message'] ?? 'JavaScript Error'),
                [
                    'type'    => $log['type'] ?? 'js',
                    'request' => self::get_request_context(),
                    'payload' => $log,
                ]
            );
            wp_send_json_success();
        }
        public function download_logs()
        {
            if (! current_user_can('manage_options')) {
                wp_die(esc_html__('Permission denied.', 'cf7-google-sheets-connector'));
            }
            check_admin_referer('gsc_download_logs_nonce');
            global $wpdb;
            $table = $wpdb->prefix . 'gscf7_error_logs';
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checking whether a custom plugin table exists.
            $logs = $wpdb->get_results('SELECT * FROM `' . esc_sql($table) . '`', ARRAY_A);
            if (empty($logs)) {
                wp_safe_redirect(wp_get_referer());
                exit;
            }
            nocache_headers();
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=error-log.csv');
            // Open output stream
            $output = fopen('php://output', 'w');
            // CSV Header
            fputcsv($output, array('Date', 'Error ID', 'Code', 'Message', 'Details'));
            foreach ($logs as $log) {
                $message = str_replace(array("\\n", "\\r", "\n", "\r"), ' ', $log['message']);
                $details = str_replace(array("\\n", "\\r", "\n", "\r"), ' ', $log['details']);
                fputcsv(
                    $output,
                    array(
                        $log['created_at'],
                        $log['error_id'],
                        $log['code'],
                        $message,
                        $details,
                    )
                );
            }
            if (is_resource($output)) {
                fclose($output); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
            }
            exit;
        }
    }
    new gscf7_error_logs();
}
add_action('wp_ajax_gsc_log_js_error', ['gscf7_error_logs', 'log_js_error']);
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
