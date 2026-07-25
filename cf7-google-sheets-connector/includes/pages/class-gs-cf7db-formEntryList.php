<?php

class GSCF7_FormEntry_Table extends WP_List_Table
{
    private $form_post_id;
    private $column_titles;
    public function __construct()
    {
        parent::__construct(
            array(
                'singular' => 'contact_form',
                'plural' => 'contact_forms',
                'ajax' => false
            )
        ); ?>
        <input type="hidden" name="gs-ajax-nonce" id="gs-ajax-nonce"
            value="<?php echo esc_attr(wp_create_nonce('gs-ajax-nonce')); ?>" />
    <?php
    }
    /**
     * Prepare the items for the table to process
     *
     * @return Void
     */
    public function prepare_items()
    {

        $this->form_post_id = isset($_GET['formId']) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            ? absint(wp_unslash($_GET['formId'])) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            : 0;

        $search = false;

        if (isset($_REQUEST['s'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $search = sanitize_text_field(
                wp_unslash($_REQUEST['s']) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            );
        }
        global $wpdb;
        $this->process_bulk_action();
        $cfdb = apply_filters('cfdb7_database', $wpdb);
        $table_name = $cfdb->prefix . 'cf7db_gsheet_forms';
        $columns  = $this->get_columns();

        $hidden   = $this->get_hidden_columns();

        $sortable = $this->get_sortable_columns();


        $perPage     = 10;

        $currentPage = max(1, $this->get_pagenum());



        $data = $this->table_data($perPage, $currentPage);



        if (!empty($search)) {

            $totalItems = $cfdb->get_var(

                "SELECT COUNT(*) FROM $table_name WHERE value LIKE '%$search%' AND form_id = '$form_post_id'"

            );
        } else {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading form ID from admin page URL.
            $form_post_id = isset($_GET['form_post_id'])
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading form ID from admin page URL.
                ? absint(wp_unslash($_GET['form_post_id']))
                : 0;
            $totalItems = $cfdb->get_var(
                $cfdb->prepare(
                    "SELECT COUNT(*) FROM $table_name WHERE form_id = %d",
                    $form_post_id
                )
            );
        }



        $this->set_pagination_args(array(

            'total_items' => $totalItems,

            'per_page'    => $perPage,

            'total_pages' => ceil($totalItems / $perPage)

        ));



        $this->_column_headers = array($columns, $hidden, $sortable);



        $this->items = $data;
    }

    /**

     * Override the parent columns method. Defines the columns to use in your listing table

     *

     * @return Array

     */

    public function get_columns()

    {

        $form_post_id = $this->form_post_id;



        global $wpdb;

        $cfdb = apply_filters('cfdb7_database', $wpdb);

        $table_name = $cfdb->prefix . 'cf7db_gsheet_forms';



        $results = $cfdb->get_results(

            "SELECT * FROM $table_name

        WHERE form_id = $form_post_id ORDER BY id DESC LIMIT 1",

            OBJECT

        );



        $first_row = isset($results[0]) ? unserialize($results[0]->value) : 0;



        $columns = array();

        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Existing hook name maintained for backward compatibility.
        $rm_underscore = apply_filters('remove_underscore_data', true);
        // $rm_underscore = apply_filters( 'cf7gs_remove_underscore_data', true );


        if (!empty($first_row)) {



            $columns['cb'] = '<input type="checkbox" />';



            // Entry ID column

            $columns['entry_id'] = 'Entry ID';



            foreach ($first_row as $key => $value) {



                $matches = array();

                $key = esc_html($key);



                if ($key == 'cfdb7_status') {

                    continue;
                }



                if ($rm_underscore) {

                    preg_match('/^_.*$/m', $key, $matches);
                }



                if (!empty($matches[0])) {

                    continue;
                }



                $key_val = str_replace(array('your-', 'cfdb7_file'), '', $key);

                $key_val = str_replace(array('_', '-'), ' ', $key_val);



                $columns[$key] = ucwords($key_val);



                $this->column_titles[] = $key_val;



                if (sizeof($columns) > 5) {

                    break;
                }
            }



            $columns['date'] = 'Date';

            $columns['sent_sheet'] = '<label>Send to SpreadSheet</label>';
        }



        return $columns;
    }

    /**

     * Define check box for bulk action (each row)

     * @param $item

     * @return checkbox

     */

    public function column_cb($item)

    {

        if (!isset($item['id'])) {

            return '';
        }



        return sprintf(

            '<input type="checkbox" name="%1$s[]" value="%2$s" />',

            esc_attr($this->_args['singular']),

            esc_attr($item['id'])

        );
    }

    public function column_sent_sheet($item)
    {

        $entry_id = isset($item['id']) ? absint($item['id']) : 0;

        return sprintf(
            '<button type="button" class="button action sendToGoogleSheetCF7DB" data-id="%2$s" form-id="%1$s">%3$s</button>
        <span class="loading-sign-all loading-sign-%2$s">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
        <span class="msg-%2$s"></span>',
            esc_attr($this->form_post_id),
            esc_attr($entry_id),
            esc_html__('Send To SpreadSheet', 'cf7-google-sheets-connector')
        );
    }
    /**

     * Define which columns are hidden

     *

     * @return Array

     */

    public function get_hidden_columns()

    {

        return array('id');
    }

    /**

     * Define the sortable columns

     *

     * @return Array

     */

    public function get_sortable_columns()

    {

        return array('date' => array('date', true));
    }

    /**

     * Define bulk action

     * @return Array

     */

    public function get_bulk_actions()

    {

        return array(

            'read'              => esc_html__('Read', 'cf7-google-sheets-connector'),

            'unread'            => esc_html__('Unread', 'cf7-google-sheets-connector'),

            'delete'            => esc_html__('Delete', 'cf7-google-sheets-connector'),

            'spread sheet'            => esc_html__('Spread Sheet', 'cf7-google-sheets-connector')





        );
    }

    /**

     * Get the table data

     *

     * @return Array

     */

    private function table_data($perPage = 10, $currentPage = 1)

    {

        $data = array();

        global $wpdb;



        $cfdb = apply_filters('cfdb7_database', $wpdb);
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from Google.
        $search = empty($_REQUEST['s']) ? false : sanitize_text_field(wp_unslash($_REQUEST['s']));

        $table_name = $cfdb->prefix . 'cf7db_gsheet_forms';



        $form_post_id = (int) $this->form_post_id;



        // pagination offset

        $offset = ($currentPage - 1) * $perPage;


        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from Google.
        $orderby = isset($_GET['orderby']) ? 'date' : 'id';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from Google.
        $order   = (isset($_GET['order']) && $_GET['order'] == 'asc') ? 'ASC' : 'DESC';



        // Build query safely

        if (!empty($search)) {

            $like = '%' . $cfdb->esc_like($search) . '%';



            $query = $cfdb->prepare(

                "SELECT * FROM $table_name

             WHERE value LIKE %s

             AND form_id = %d

             ORDER BY $orderby $order

             LIMIT %d, %d",

                $like,

                $form_post_id,

                $offset,

                $perPage

            );
        } else {

            $query = $cfdb->prepare(

                "SELECT * FROM $table_name

             WHERE form_id = %d

             ORDER BY $orderby $order

             LIMIT %d, %d",

                $form_post_id,

                $offset,

                $perPage

            );
        }



        $results = $cfdb->get_results($query, OBJECT);



        foreach ($results as $result) {



            $form_value = unserialize($result->value);

            $form_values = array();



            $link = "<b><a href='admin.php?page=wpcf7-google-sheet-config&tab=cf7_db&formId=%s&entryId=%s'>%s</a></b>";



            if (isset($form_value['cfdb7_status']) && ($form_value['cfdb7_status'] === 'read')) {

                $link = "<a href='admin.php?page=wpcf7-google-sheet-config&tab=cf7_db&formId=%s&entryId=%s'>%s</a>";
            }



            $fid = $result->form_id;

            $form_values['entry_id'] = $result->id;



            if (!empty($this->column_titles)) {

                foreach ($this->column_titles as $col_title) {

                    $form_value[$col_title] = isset($form_value[$col_title]) ? $form_value[$col_title] : '';
                }
            }



            if (!empty($form_value)) {

                foreach ($form_value as $k => $value) {



                    if (is_array($value) || is_object($value)) {

                        foreach ($value as $val) {

                            $val = esc_html($val);

                            $val = (strlen($val) > 150) ? substr($val, 0, 150) . '...' : $val;

                            $form_values[$k] = sprintf($link, $fid, $result->id, $val);
                        }
                    } else {

                        $value = esc_html($value);

                        $value = (strlen($value) > 150) ? substr($value, 0, 150) . '...' : $value;

                        $form_values[$k] = sprintf($link, $fid, $result->id, $value);
                    }
                }



                // Format date using WP settings

                $timestamp = strtotime($result->date);

                $date_format = get_option('date_format');

                $time_format = get_option('time_format');



                $formatted_date = wp_date($date_format . ' ' . $time_format, $timestamp);



                $form_values['date'] = sprintf($link, $fid, $result->id, $formatted_date);



                $data[] = $form_values;
            }
        }



        return $data;
    }

    /**

     * Define bulk action

     *

     */

    public function process_bulk_action()

    {



        global $wpdb;

        $cfdb = apply_filters('cfdb7_database', $wpdb);

        $table_name = $cfdb->prefix . 'cf7db_gsheet_forms';

        $action = $this->current_action();



        if (!empty($action)) {



            $nonce = filter_input(INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING);

            $nonce_action = 'bulk-' . $this->_args['plural'];



            if (!wp_verify_nonce($nonce, $nonce_action)) {



                wp_die('Not valid..!!');
            }
        }



        $entry_ids = isset($_POST['contact_form']) ? array_map('sanitize_text_field', wp_unslash($_POST['contact_form'])) : array();

        $form_id = isset($_GET['formId']) ? intval($_GET['formId']) : '';





        if ('delete' === $action) {



            foreach ($entry_ids as $entry_id) :



                $entry_id = (int) $entry_id;

                $results = $cfdb->get_results("SELECT * FROM $table_name WHERE id = '$entry_id' LIMIT 1", OBJECT);

                $result_value = $results[0]->value;

                $result_values = unserialize($result_value);

                $upload_dir = wp_upload_dir();

                $cfdb7_dirname = $upload_dir['basedir'] . '/cf7gs';



               foreach ( $result_values as $key => $result ) {

    if ( ( strpos( $key, 'cfdb7_file' ) !== false ) &&
        ! empty( $result ) &&
        file_exists( $cfdb7_dirname . '/' . $result )
    ) {

        // Use WordPress native file deletion function instead of PHP's unlink()
        wp_delete_file( $cfdb7_dirname . '/' . $result );
    }
}


                $cfdb->delete(

                    $table_name,

                    array('id' => $entry_id),

                    array('%d')

                );

            endforeach;
        } else if ('read' === $action) {



            foreach ($entry_ids as $entry_id) :



                $entry_id = (int) $entry_id;

                $results = $cfdb->get_results("SELECT * FROM $table_name WHERE id = '$entry_id' LIMIT 1", OBJECT);

                $result_value = $results[0]->value;

                $result_values = unserialize($result_value);

                $result_values['cfdb7_status'] = 'read';

                $form_data = serialize($result_values);

                $cfdb->query(

                    "UPDATE $table_name SET value = '$form_data' WHERE id = '$entry_id'"

                );



            endforeach;
        } else if ('unread' === $action) {



            foreach ($entry_ids as $entry_id) :



                $entry_id = (int) $entry_id;

                $results = $cfdb->get_results("SELECT * FROM $table_name WHERE id = '$entry_id' LIMIT 1", OBJECT);

                $result_value = $results[0]->value;

                $result_values = unserialize($result_value);

                $result_values['cfdb7_status'] = 'unread';

                $form_data = serialize($result_values);

                $cfdb->query(

                    "UPDATE $table_name SET value = '$form_data' WHERE id = '$entry_id' LIMIT 1"

                );

            endforeach;
        } else if ('sendtospreadsheet' === $action) {

            $gs_connector_service = new Gs_Connector_Service();

            $singlesheet_response = $gs_connector_service->send_to_spreadsheet_bulk($entry_ids, $form_id);

            $multisheet_response = $gs_connector_service->send_to_spreadsheet_bulk_multisheet($entry_ids, $form_id);



            if (is_wp_error($singlesheet_response)) {

                set_transient('gs_sync_notice', [

                    'type'    => 'error',

                    'message' => sprintf(

                        '%s %s',

                        esc_html__('Single Sheet connection Sync Error:', 'cf7-google-sheets-connector'),

                        esc_html($singlesheet_response->get_error_message())

                    ),

                ], 30);
            } elseif (isset($singlesheet_response->updates) && $singlesheet_response->updates->updatedRows > 0) {

                set_transient('gs_sync_notice', [

                    'type'    => 'success',

                    'message' => esc_html__('Data synced successfully for Single Sheet Connection.', 'cf7-google-sheets-connector'),

                ], 30);
            } else {

                set_transient('gs_sync_notice', [

                    'type'    => 'warning',

                    'message' => esc_html__('Unknown response from Google Sheets API for Single Sheet Connection.', 'cf7-google-sheets-connector'),

                ], 30);
            }





            if (is_wp_error($multisheet_response)) {

                set_transient('gs_sync_notice_multi', [

                    'type'    => 'error',

                    'message' => sprintf(

                        '%s %s',

                        esc_html__('Multi Sheet connection Sync Error:', 'cf7-google-sheets-connector'),

                        esc_html($multisheet_response->get_error_message())

                    ),

                ], 30);
            } elseif (isset($multisheet_response->updates) && $multisheet_response->updates->updatedRows > 0) {

                set_transient('gs_sync_notice_multi', [

                    'type'    => 'success',

                    'message' => esc_html__('Data synced successfully for Multi Sheet Connection.', 'cf7-google-sheets-connector'),

                ], 30);
            } else {

                set_transient('gs_sync_notice_multi', [

                    'type'    => 'warning',

                    'message' => esc_html__('Unknown response from Google Sheets API for Multi Sheet Connection.', 'cf7-google-sheets-connector'),

                ], 30);
            }



            $redirect_url = isset($_SERVER['REQUEST_URI'])
                ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI']))
                : '';
            wp_safe_redirect($redirect_url);

            exit;
        }
    }

    /**

     * Define what data to show on each column of the table

     *

     * @param Array $item Data

     * @param String $column_name - Current column name

     *

     * @return Mixed

     */

    public function column_default($item, $column_name)

    {

        return $item[$column_name];
    }



    /**

     * Display the bulk actions dropdown.

     *

     * @since 3.1.0

     * @access protected

     *

     * @param string $which The location of the bulk actions: 'top' or 'bottom'.

     * This is designated as optional for backward compatibility.

     */

    protected function bulk_actions($which = '')

    {

        if (is_null($this->_actions)) {

            $this->_actions = $this->get_bulk_actions();

            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WordPress core hook, not defined by this plugin.
            $this->_actions = apply_filters("bulk_actions-{$this->screen->id}", $this->_actions);

            $two = '';
        } else {

            $two = '2';
        }



        if (empty($this->_actions)) {

            return;
        }



        // Screen reader label 

    ?>

        <label for="bulk-action-selector-<?php echo esc_attr($which); ?>" class="screen-reader-text">

            <?php echo esc_html__('Select bulk action', 'cf7-google-sheets-connector'); ?>

        </label>



        <select name="action<?php echo esc_attr($two); ?>" id="bulk-action-selector-<?php echo esc_attr($which); ?>">

            <option value="-1"><?php echo esc_html__('Bulk Actions', 'cf7-google-sheets-connector'); ?></option>

            <?php foreach ($this->_actions as $name => $title) :

                $class = 'edit' === $name ? ' class="hide-if-no-js"' : '';

            ?>

                <option value="<?php echo esc_attr($name); ?>" <?php echo wp_kses($class, array('class' => array())); ?> disabled>

                    <?php echo esc_html($title); ?>

                </option>

            <?php endforeach; ?>

        </select>



        <?php

        // Submit button

        submit_button(

            esc_html__('Apply', 'cf7-google-sheets-connector'),

            'action',

            '',

            false,

            array('id' => "doaction$two", 'disabled' => 'disabled')

        );



        // Export CSV button
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from Google.
        $formId = isset($_GET['formId']) ? intval($_GET['formId']) : 0;


        if ($formId > 0) {

            $request_uri = isset($_SERVER['REQUEST_URI'])
                ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI']))
                : '';

            $url = add_query_arg(
                array(
                    'csv'    => 'true',
                    'formId' => $formId,
                ),
                $request_uri
            );

            echo '<a href="#" id="cf7gs-free-csv" class="button" style="float:right; margin:0;">' .
                esc_html__('Export CSV', 'cf7-google-sheets-connector') .
                '</a>';
        }


        do_action('cfdb7_after_export_button');

        ?>



        <div id="cf7gs-free-pro" class="gs-popup-overlay d-none">

            <div class="gs-popups position-relative-popup text-center">

                <button type="button" class="gscf7-free-pro gsc-pro-close">×</button>

                <div class="gsc-pro-section">

                    <div class="gsc-pro-card">

                        <div class="gsc-pro-headers">

                            <div class="gsc-pro-headers">

                                <div class="gsc-modal-title">

                                    <?php esc_html_e('Want to send entries to Google Sheets?', 'cf7-google-sheets-connector'); ?>

                                </div>

                                <p class="gsc-modal-text"><?php echo esc_html__('Export and sync your form submissions directly to Google Sheets to easily organize, filter, and manage your data in one place. Unlock this feature to simplify your workflow and access your entries anytime.', 'cf7-google-sheets-connector'); ?>

                                </p>

                            </div>

                            <a href="https://www.gsheetconnector.com/cf7-google-sheet-connector-pro" target="_blank" class="btn btn-primary text-decoration-none link-hover-white"><?php esc_html_e('Upgrade to Unlock', 'cf7-google-sheets-connector'); ?></a>

                        </div>

                    </div>

                </div>


            </div>

        </div>

        <div id="cf7gs-free-pro-csv" class="gs-popup-overlay d-none">

            <div class="gs-popups position-relative-popup text-center">

                <button type="button" class="gscf7-free-pro-csv gsc-pro-close">×</button>

                <div class="gsc-pro-section">

                    <div class="gsc-pro-card">

                        <div class="gsc-pro-headers">

                            <div class="gsc-modal-title">

                                <?php esc_html_e('Want to download your form entries?', 'cf7-google-sheets-connector'); ?>

                            </div>

                            <p class="gsc-modal-text"><?php echo esc_html__('Export your form entries as a CSV file and use them in Sheets. You can sort, filter, and manage everything more easily.', 'cf7-google-sheets-connector'); ?>

                            </p>

                        </div>

                        <a href="https://www.gsheetconnector.com/cf7-google-sheet-connector-pro" target="_blank" class="btn btn-primary text-decoration-none link-hover-white"><?php esc_html_e('Upgrade to Unlock', 'cf7-google-sheets-connector'); ?></a>

                    </div>

                </div>



            </div>

        </div>

<?php

    }
} ?>