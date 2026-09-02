<?php

class GSCF7_FormEntry_Table extends WP_List_Table {

	private $form_post_id;
	private $column_titles;

	/**
	 * Memoised result of get_columns().
	 *
	 * WP_List_Table calls get_columns() several times per render (prepare_items(),
	 * get_column_info(), display() and print_column_headers()). Without this the
	 * same database query ran 3-5 times for a single page load.
	 *
	 * @var array|null
	 */
	private $columns_cache = null;
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'contact_form',
				'plural'   => 'contact_forms',
				'ajax'     => false,
				/*
				 * Without an explicit screen, WP_List_Table falls back to
				 * get_current_screen() -- which is null inside admin-ajax.php
				 * (no WP_Screen is ever set up there). get_primary_column_name()
				 * then dereferences $this->screen->id on that null, and the
				 * resulting PHP warning gets echoed straight into what's
				 * supposed to be a pure JSON AJAX response, corrupting it.
				 */
				'screen'   => 'gscf7-contact-form-entries',
			)
		); ?>
		<input type="hidden" name="gs-ajax-nonce" id="gs-ajax-nonce"
			value="<?php echo esc_attr( wp_create_nonce( 'gs-ajax-nonce' ) ); ?>" />
		<?php
	}
	/**
	 * Prepare the items for the table to process
	 *
	 * @return Void
	 */
	public function prepare_items() {

		$this->form_post_id = isset( $_GET['formId'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			? absint( wp_unslash( $_GET['formId'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			: 0;

		$search = false;

		if ( isset( $_REQUEST['s'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$search = sanitize_text_field(
				wp_unslash( $_REQUEST['s'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			);
		}
		global $wpdb;
		$this->process_bulk_action();
		$cfdb       = apply_filters( 'cfdb7_database', $wpdb );
		$table_name = $cfdb->prefix . 'cf7db_gsheet_forms';
		$columns    = $this->get_columns();

		$hidden = $this->get_hidden_columns();

		$sortable = $this->get_sortable_columns();

		$perPage = 10;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display option, not a form submission.
		if ( isset( $_GET['per_page'] ) ) {

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display option, not a form submission.
			$requested_per_page = absint( wp_unslash( $_GET['per_page'] ) );

			if ( in_array( $requested_per_page, array( 10, 20, 50, 100 ), true ) ) {
				$perPage = $requested_per_page;
			}
		}

		$currentPage = max( 1, $this->get_pagenum() );

		$data = $this->table_data( $perPage, $currentPage );

		list( $status_where, $status_params ) = $this->get_entry_status_filter();

		if ( ! empty( $search ) ) {

			$like = '%' . $cfdb->esc_like( $search ) . '%';

			$totalItems = $cfdb->get_var(
				$cfdb->prepare(
					"SELECT COUNT(*) FROM $table_name WHERE value LIKE %s AND form_id = %d" . $status_where,
					array_merge( array( $like, $this->form_post_id ), $status_params )
				)
			);
		} else {
			/*
			 * Use the form ID resolved in this method.
			 *
			 * This previously read $_GET['form_post_id'], a parameter that is
			 * never set anywhere in the plugin, so the count was always taken
			 * for form_id 0 and pagination reported zero items.
			 */
			$totalItems = $cfdb->get_var(
				$cfdb->prepare(
					"SELECT COUNT(*) FROM $table_name WHERE form_id = %d" . $status_where,
					array_merge( array( $this->form_post_id ), $status_params )
				)
			);
		}

		$this->set_pagination_args(
			array(

				'total_items' => $totalItems,

				'per_page'    => $perPage,

				'total_pages' => ceil( $totalItems / $perPage ),

			)
		);

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->items = $data;
	}

	/**

	 * Override the parent columns method. Defines the columns to use in your listing table
	 *
	 * @return Array
	 */
	public function get_columns() {

		if ( null !== $this->columns_cache ) {

			return $this->columns_cache;
		}

		$form_post_id = absint( $this->form_post_id );

		global $wpdb;

		$cfdb = apply_filters( 'cfdb7_database', $wpdb );

		$table_name = $cfdb->prefix . 'cf7db_gsheet_forms';

		$results = $cfdb->get_results(
			$cfdb->prepare(
				"SELECT value FROM $table_name

        WHERE form_id = %d ORDER BY id DESC LIMIT 1",
				$form_post_id
			),
			OBJECT
		);

		$first_row = isset( $results[0] ) ? unserialize( $results[0]->value ) : 0;

		$columns = array();

        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Existing hook name maintained for backward compatibility.
		$rm_underscore = apply_filters( 'remove_underscore_data', true );
		// $rm_underscore = apply_filters( 'cf7gs_remove_underscore_data', true );

		if ( ! empty( $first_row ) ) {

			$columns['cb'] = '<input type="checkbox" />';

			// Entry ID column

			$columns['entry_id'] = 'Entry ID';

			// Status column (Read / Unread)
			$columns['status'] = esc_html__( 'Status', 'cf7-google-sheets-connector' );

			foreach ( $first_row as $key => $value ) {

				$matches = array();

				$key = esc_html( $key );

				if ( $key == 'cfdb7_status' ) {

					continue;
				}

				if ( $rm_underscore ) {

					preg_match( '/^_.*$/m', $key, $matches );
				}

				if ( ! empty( $matches[0] ) ) {

					continue;
				}

				$key_val = str_replace( array( 'your-', 'cfdb7_file' ), '', $key );

				$key_val = str_replace( array( '_', '-' ), ' ', $key_val );

				$columns[ $key ] = ucwords( $key_val );

				/*
				 * Store the raw key, not the display label.
				 *
				 * table_data() uses these entries to backfill fields that are
				 * missing from an individual row. Storing the transformed label
				 * meant the backfill wrote to a key that no column ever read, so
				 * rows lacking a field triggered undefined-key warnings.
				 */
				$this->column_titles[] = $key;
			}

			$columns['date'] = 'Date';

			$columns['actions'] = esc_html__( 'Actions', 'cf7-google-sheets-connector' );
		}

		$this->columns_cache = $columns;

		return $columns;
	}

	/**

	 * Define check box for bulk action (each row)

	 * @param $item

	 * @return checkbox
	 */
	public function column_cb( $item ) {

		/*
		 * Row data is keyed 'entry_id' (see get_columns()/table_data()), not
		 * 'id' -- this previously always fell through to the empty-string
		 * return below, so no row checkbox has ever actually rendered and
		 * every bulk action (delete/read/unread/send-to-sheet) has had no
		 * way to receive any entry IDs from the UI.
		 */
		if ( ! isset( $item['entry_id'] ) ) {

			return '';
		}

		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			esc_attr( $this->_args['singular'] ),
			esc_attr( $item['entry_id'] )
		);
	}

	/**
	 * Render the Status column as a Read / Unread badge.
	 *
	 * Reads the raw, unlinked status value stashed in `status_raw` by
	 * table_data() -- the generic per-field loop there wraps every value
	 * in an entry-detail link, so the flag itself is kept separately to
	 * avoid rendering a badge inside a link.
	 *
	 * @since 5.3.0
	 *
	 * @param array $item Row data.
	 * @return string
	 */
	public function column_status( $item ) {

		$status = isset( $item['status_raw'] ) && 'read' === $item['status_raw'] ? 'read' : 'unread';

		$label = ( 'read' === $status )
			? esc_html__( 'Read', 'cf7-google-sheets-connector' )
			: esc_html__( 'Unread', 'cf7-google-sheets-connector' );

		return sprintf(
			'<span class="gscf7-status-badge gscf7-status-%1$s">%2$s</span>',
			esc_attr( $status ),
			$label
		);
	}

	/**
	 * Render the Entry ID column, prefixed with a small unread-status dot.
	 *
	 * Purely presentational -- the read/unread flag itself is untouched and
	 * still lives in status_raw / column_status().
	 *
	 * @since 5.3.0
	 *
	 * @param array $item Row data.
	 * @return string
	 */
	public function column_entry_id( $item ) {

		$entry_id = isset( $item['entry_id'] ) ? absint( $item['entry_id'] ) : 0;
		$status   = isset( $item['status_raw'] ) && 'read' === $item['status_raw'] ? 'read' : 'unread';

		$dot = ( 'unread' === $status )
			? '<span class="gscf7-status-dot" aria-hidden="true"></span>'
			: '';

		return $dot . '#' . esc_html( $entry_id );
	}

	/**
	 * Render the Actions column: a "View" link to the existing entry-details
	 * screen, plus the existing "Send To SpreadSheet" button (unchanged
	 * markup/classes/data attributes).
	 *
	 * @since 5.3.0
	 *
	 * @param array $item Row data.
	 * @return string
	 */
	public function column_actions( $item ) {

		$entry_id = isset( $item['entry_id'] ) ? absint( $item['entry_id'] ) : 0;
		$form_id  = absint( $this->form_post_id );

		$view_url = add_query_arg(
			array(
				'page'    => 'wpcf7-google-sheet-config',
				'tab'     => 'cf7_db',
				'formId'  => $form_id,
				'entryId' => $entry_id,
			),
			admin_url( 'admin.php' )
		);

		return sprintf(
			'<a class="button action" href="%1$s">%2$s</a>
        <button type="button" class="button action sendToGoogleSheetCF7DB" data-id="%3$s" form-id="%4$s">%5$s</button>
        <span class="loading-sign-all loading-sign-%3$s">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
        <span class="msg-%3$s"></span>',
			esc_url( $view_url ),
			esc_html__( 'View', 'cf7-google-sheets-connector' ),
			esc_attr( $entry_id ),
			esc_attr( $form_id ),
			esc_html__( 'Send To SpreadSheet', 'cf7-google-sheets-connector' )
		);
	}

	/**
	 * Render this table in the Dashboard's card/pill style instead of the
	 * default WP_List_Table chrome, for both a normal page load and the
	 * gscf7_cf7db_table_query AJAX endpoint. Must be called after
	 * prepare_items(). Reuses get_columns()/single_row_columns()/column_*()
	 * and bulk_actions() unchanged -- only the outer markup differs.
	 *
	 * @since 5.3.0
	 *
	 * @return array{head_html:string,rows_html:string,toolbar_html:string}
	 */
	public function render_dashboard_style() {

		$columns  = $this->get_columns();
		$sortable = $this->get_sortable_columns();
		$hidden   = $this->get_hidden_columns();

		ob_start();
		?>
		<tr>
			<?php
			foreach ( $columns as $col_key => $col_label ) {

				if ( in_array( $col_key, $hidden, true ) ) {
					continue;
				}

				// The 'cb' column's label is a raw <input type="checkbox">
				// (see get_columns()) -- render it with the cb-select-all-1 id
				// convention core's own admin JS already wires up for
				// check/uncheck-all, rather than passing it through wp_kses_post()
				// (which would strip the <input> as a non-post-content tag).
				if ( 'cb' === $col_key ) {
					?>
					<th scope="col" class="manage-column column-cb check-column">
						<label class="screen-reader-text" for="cb-select-all-1"><?php esc_html_e( 'Select All', 'cf7-google-sheets-connector' ); ?></label>
						<input id="cb-select-all-1" type="checkbox">
					</th>
					<?php
					continue;
				}

				$is_sortable = isset( $sortable[ $col_key ] );
				?>
				<th scope="col"
					class="column-<?php echo esc_attr( $col_key ); ?><?php echo $is_sortable ? ' gscf7-sortable' : ''; ?>"
					<?php if ( $is_sortable ) : ?>data-orderby="<?php echo esc_attr( $col_key ); ?>"<?php endif; ?>>
					<?php echo $col_label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_columns() only ever returns already-escaped plain text or esc_html__()-wrapped labels here (the one raw-HTML case, 'cb', is handled above). ?>
					<?php if ( $is_sortable ) : ?><span class="gscf7-sort-arrow"></span><?php endif; ?>
				</th>
				<?php
			}
			?>
		</tr>
		<?php
		$head_html = ob_get_clean();

		ob_start();
		if ( empty( $this->items ) ) {
			?>
			<tr>
				<td colspan="<?php echo esc_attr( count( $columns ) ); ?>">
					<?php esc_html_e( 'No entries match the selected filters.', 'cf7-google-sheets-connector' ); ?>
				</td>
			</tr>
			<?php
		} else {
			foreach ( $this->items as $item ) {
				echo '<tr>';
				$this->single_row_columns( $item );
				echo '</tr>';
			}
		}
		$rows_html = ob_get_clean();

		ob_start();
		$this->bulk_actions( 'top' );
		$toolbar_html = ob_get_clean();

		return array(
			'head_html'    => $head_html,
			'rows_html'    => $rows_html,
			'toolbar_html' => $toolbar_html,
		);
	}
	/**

	 * Define which columns are hidden
	 *
	 * @return Array
	 */
	public function get_hidden_columns() {

		return array( 'id' );
	}

	/**

	 * Define the sortable columns
	 *
	 * @return Array
	 */
	public function get_sortable_columns() {

		/*
		 * table_data()'s $orderby only ever resolves to 'date' or 'id' (it
		 * sorts by 'id' whenever $_GET['orderby'] isn't literally 'date' --
		 * see its own comment), so 'entry_id' here just needs to produce a
		 * data-orderby value that isn't the string "date" for that fallback
		 * to kick in; the AJAX handler's shim treats any such value the same way.
		 */
		return array(
			'date'     => array( 'date', true ),
			'entry_id' => array( 'id', false ),
		);
	}

	/**

	 * Define bulk action

	 * @return Array
	 */
	public function get_bulk_actions() {

		return array(

			'read'              => esc_html__( 'Read', 'cf7-google-sheets-connector' ),

			'unread'            => esc_html__( 'Unread', 'cf7-google-sheets-connector' ),

			'delete'            => esc_html__( 'Delete', 'cf7-google-sheets-connector' ),

			// Key must match the value compared in process_bulk_action().
			'sendtospreadsheet' => esc_html__( 'Spread Sheet', 'cf7-google-sheets-connector' ),

		);
	}

	/**
	 * Build the SQL fragment (and its bound param) for the entry_status filter.
	 *
	 * The read/unread flag is stored inside the serialized `value` blob, so
	 * it's matched as a fixed-length LIKE fragment rather than a real column.
	 * 'unread' is matched as "does not have the read fragment", which also
	 * covers legacy rows saved before this flag existed (they default to
	 * unread, same as column_status()).
	 *
	 * @since 5.3.0
	 *
	 * @return array{0:string,1:array} SQL fragment and its bound params.
	 */
	private function get_entry_status_filter() {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filter, not a form submission.
		$status = isset( $_GET['entry_status'] ) ? sanitize_key( wp_unslash( $_GET['entry_status'] ) ) : 'all';

		if ( ! in_array( $status, array( 'unread', 'read' ), true ) ) {
			return array( '', array() );
		}

		$fragment = '%s:12:"cfdb7_status";s:4:"read";%';

		if ( 'read' === $status ) {
			return array( ' AND value LIKE %s', array( $fragment ) );
		}

		return array( ' AND value NOT LIKE %s', array( $fragment ) );
	}

	/**

	 * Get the table data
	 *
	 * @return Array
	 */
	private function table_data( $perPage = 10, $currentPage = 1 ) {

		$data = array();

		global $wpdb;

		$cfdb = apply_filters( 'cfdb7_database', $wpdb );
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from Google.
		$search = empty( $_REQUEST['s'] ) ? false : sanitize_text_field( wp_unslash( $_REQUEST['s'] ) );

		$table_name = $cfdb->prefix . 'cf7db_gsheet_forms';

		$form_post_id = (int) $this->form_post_id;

		// pagination offset

		$offset = ( $currentPage - 1 ) * $perPage;

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from Google.
		$orderby = isset( $_GET['orderby'] ) ? 'date' : 'id';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from Google.
		$order = ( isset( $_GET['order'] ) && $_GET['order'] == 'asc' ) ? 'ASC' : 'DESC';

		// Build query safely

		list( $status_where, $status_params ) = $this->get_entry_status_filter();

		if ( ! empty( $search ) ) {

			$like = '%' . $cfdb->esc_like( $search ) . '%';

			$query = $cfdb->prepare(
				"SELECT id, form_id, value, date FROM $table_name

             WHERE value LIKE %s

             AND form_id = %d" . $status_where . "

             ORDER BY $orderby $order

             LIMIT %d, %d",
				array_merge( array( $like, $form_post_id ), $status_params, array( $offset, $perPage ) )
			);
		} else {

			$query = $cfdb->prepare(
				"SELECT id, form_id, value, date FROM $table_name

             WHERE form_id = %d" . $status_where . "

             ORDER BY $orderby $order

             LIMIT %d, %d",
				array_merge( array( $form_post_id ), $status_params, array( $offset, $perPage ) )
			);
		}

		$results = $cfdb->get_results( $query, OBJECT );

		foreach ( $results as $result ) {

			$form_value = unserialize( $result->value );

			$form_values = array();

			$link = "<b><a href='admin.php?page=wpcf7-google-sheet-config&tab=cf7_db&formId=%s&entryId=%s'>%s</a></b>";

			if ( isset( $form_value['cfdb7_status'] ) && ( $form_value['cfdb7_status'] === 'read' ) ) {

				$link = "<a href='admin.php?page=wpcf7-google-sheet-config&tab=cf7_db&formId=%s&entryId=%s'>%s</a>";
			}

			$fid = $result->form_id;

			$form_values['entry_id'] = $result->id;

			/*
			 * Keep the raw status separately (unlinked, unescaped-for-link).
			 *
			 * The generic loop below wraps every field value in an entry-detail
			 * anchor tag, which would turn 'read'/'unread' into HTML instead of
			 * a plain flag that column_status() can key off cleanly.
			 */
			$form_values['status_raw'] = ( isset( $form_value['cfdb7_status'] ) && 'read' === $form_value['cfdb7_status'] )
				? 'read'
				: 'unread';

			if ( ! empty( $this->column_titles ) ) {

				foreach ( $this->column_titles as $col_title ) {

					$form_value[ $col_title ] = isset( $form_value[ $col_title ] ) ? $form_value[ $col_title ] : '';
				}
			}

			if ( ! empty( $form_value ) ) {

				foreach ( $form_value as $k => $value ) {

					if ( is_array( $value ) || is_object( $value ) ) {

						foreach ( $value as $val ) {

							$val = esc_html( $val );

							$val = ( strlen( $val ) > 150 ) ? substr( $val, 0, 150 ) . '...' : $val;

							$form_values[ $k ] = sprintf( $link, $fid, $result->id, $val );
						}
					} else {

						$value = esc_html( $value );

						$value = ( strlen( $value ) > 150 ) ? substr( $value, 0, 150 ) . '...' : $value;

						$form_values[ $k ] = sprintf( $link, $fid, $result->id, $value );
					}
				}

				/*
				 * Format the date using Settings > General.
				 *
				 * The entry date is stored as site-local time by
				 * current_time('Y-m-d H:i:s'). The previous code ran it through
				 * strtotime(), which parses in the default timezone (UTC under
				 * WordPress), and then through wp_date(), which applied the site
				 * offset a second time - so entries displayed with the timezone
				 * offset added twice on any site not running on UTC.
				 *
				 * mysql2date() interprets the stored string as site-local and
				 * formats it in the site timezone, applying the offset once, and
				 * localises month and day names.
				 */
				$date_format = get_option( 'date_format' );

				$time_format = get_option( 'time_format' );

				$formatted_date = mysql2date( $date_format . ' ' . $time_format, $result->date );

				$form_values['date'] = sprintf( $link, $fid, $result->id, $formatted_date );

				$data[] = $form_values;
			}
		}

		return $data;
	}

	/**

	 * Define bulk action
	 */
	public function process_bulk_action() {

		global $wpdb;

		$cfdb = apply_filters( 'cfdb7_database', $wpdb );

		$table_name = $cfdb->prefix . 'cf7db_gsheet_forms';

		$action = $this->current_action();

		if ( ! empty( $action ) ) {

			/*
			 * FILTER_SANITIZE_STRING is deprecated as of PHP 8.1 and removed in
			 * PHP 9. It also mangled the value by encoding quotes before the
			 * nonce was ever compared.
			 */
			$nonce = isset( $_POST['_wpnonce'] )
				? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) )
				: '';

			$nonce_action = 'bulk-' . $this->_args['plural'];

			if ( ! wp_verify_nonce( $nonce, $nonce_action ) ) {

				wp_die( 'Not valid..!!' );
			}
		}

		$entry_ids = isset( $_POST['contact_form'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['contact_form'] ) ) : array();

		$form_id = isset( $_GET['formId'] ) ? intval( $_GET['formId'] ) : '';

		if ( 'delete' === $action ) {

			foreach ( $entry_ids as $entry_id ) :

				$entry_id = (int) $entry_id;

				$results = $cfdb->get_results(
					$cfdb->prepare( "SELECT id, value FROM $table_name WHERE id = %d LIMIT 1", $entry_id ),
					OBJECT
				);

				if ( empty( $results[0] ) ) {
					continue;
				}

				$result_value = $results[0]->value;

				$result_values = unserialize( $result_value );

				if ( ! is_array( $result_values ) ) {
					$result_values = array();
				}

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
					array( 'id' => $entry_id ),
					array( '%d' )
				);

			endforeach;
		} elseif ( 'read' === $action ) {

			foreach ( $entry_ids as $entry_id ) :

				$entry_id = (int) $entry_id;

				$results = $cfdb->get_results(
					$cfdb->prepare( "SELECT id, value FROM $table_name WHERE id = %d LIMIT 1", $entry_id ),
					OBJECT
				);

				if ( empty( $results[0] ) ) {
					continue;
				}

				$result_value = $results[0]->value;

				$result_values = unserialize( $result_value );

				if ( ! is_array( $result_values ) ) {
					$result_values = array();
				}

				$result_values['cfdb7_status'] = 'read';

				$form_data = serialize( $result_values );

				$cfdb->query(
					$cfdb->prepare(
						"UPDATE $table_name SET value = %s WHERE id = %d",
						$form_data,
						$entry_id
					)
				);

			endforeach;
		} elseif ( 'unread' === $action ) {

			foreach ( $entry_ids as $entry_id ) :

				$entry_id = (int) $entry_id;

				$results = $cfdb->get_results(
					$cfdb->prepare( "SELECT id, value FROM $table_name WHERE id = %d LIMIT 1", $entry_id ),
					OBJECT
				);

				if ( empty( $results[0] ) ) {
					continue;
				}

				$result_value = $results[0]->value;

				$result_values = unserialize( $result_value );

				if ( ! is_array( $result_values ) ) {
					$result_values = array();
				}

				$result_values['cfdb7_status'] = 'unread';

				$form_data = serialize( $result_values );

				$cfdb->query(
					$cfdb->prepare(
						"UPDATE $table_name SET value = %s WHERE id = %d LIMIT 1",
						$form_data,
						$entry_id
					)
				);

			endforeach;
		} elseif ( 'sendtospreadsheet' === $action ) {

			$gs_connector_service = Gs_Connector_Service::instance();

			/*
			 * These bulk helpers only exist in the Pro build. Calling them
			 * unguarded raised a fatal "call to undefined method" error.
			 */
			$singlesheet_response = method_exists( $gs_connector_service, 'send_to_spreadsheet_bulk' )
				? $gs_connector_service->send_to_spreadsheet_bulk( $entry_ids, $form_id )
				: null;

			$multisheet_response = method_exists( $gs_connector_service, 'send_to_spreadsheet_bulk_multisheet' )
				? $gs_connector_service->send_to_spreadsheet_bulk_multisheet( $entry_ids, $form_id )
				: null;

			if ( null === $singlesheet_response && null === $multisheet_response ) {

				set_transient(
					'gs_sync_notice',
					array(
						'type'    => 'warning',
						'message' => esc_html__( 'Sending entries to Google Sheets in bulk is available in the Pro version.', 'cf7-google-sheets-connector' ),
					),
					30
				);

				$redirect_url = isset( $_SERVER['REQUEST_URI'] )
					? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) )
					: '';
				wp_safe_redirect( $redirect_url );

				exit;
			}

			if ( is_wp_error( $singlesheet_response ) ) {

				set_transient(
					'gs_sync_notice',
					array(

						'type'    => 'error',

						'message' => sprintf(
							'%s %s',
							esc_html__( 'Single Sheet connection Sync Error:', 'cf7-google-sheets-connector' ),
							esc_html( $singlesheet_response->get_error_message() )
						),

					),
					30
				);
			} elseif ( isset( $singlesheet_response->updates ) && $singlesheet_response->updates->updatedRows > 0 ) {

				set_transient(
					'gs_sync_notice',
					array(

						'type'    => 'success',

						'message' => esc_html__( 'Data synced successfully for Single Sheet Connection.', 'cf7-google-sheets-connector' ),

					),
					30
				);
			} else {

				set_transient(
					'gs_sync_notice',
					array(

						'type'    => 'warning',

						'message' => esc_html__( 'Unknown response from Google Sheets API for Single Sheet Connection.', 'cf7-google-sheets-connector' ),

					),
					30
				);
			}

			if ( is_wp_error( $multisheet_response ) ) {

				set_transient(
					'gs_sync_notice_multi',
					array(

						'type'    => 'error',

						'message' => sprintf(
							'%s %s',
							esc_html__( 'Multi Sheet connection Sync Error:', 'cf7-google-sheets-connector' ),
							esc_html( $multisheet_response->get_error_message() )
						),

					),
					30
				);
			} elseif ( isset( $multisheet_response->updates ) && $multisheet_response->updates->updatedRows > 0 ) {

				set_transient(
					'gs_sync_notice_multi',
					array(

						'type'    => 'success',

						'message' => esc_html__( 'Data synced successfully for Multi Sheet Connection.', 'cf7-google-sheets-connector' ),

					),
					30
				);
			} else {

				set_transient(
					'gs_sync_notice_multi',
					array(

						'type'    => 'warning',

						'message' => esc_html__( 'Unknown response from Google Sheets API for Multi Sheet Connection.', 'cf7-google-sheets-connector' ),

					),
					30
				);
			}

			$redirect_url = isset( $_SERVER['REQUEST_URI'] )
				? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) )
				: '';
			wp_safe_redirect( $redirect_url );

			exit;
		}
	}

	/**

	 * Define what data to show on each column of the table
	 *
	 * @param Array  $item Data

	 * @param String $column_name - Current column name
	 *
	 * @return Mixed
	 */
	public function column_default( $item, $column_name ) {

		return isset( $item[ $column_name ] ) ? $item[ $column_name ] : '';
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
	protected function bulk_actions( $which = '' ) {

		if ( is_null( $this->_actions ) ) {

			$this->_actions = $this->get_bulk_actions();

            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WordPress core hook, not defined by this plugin.
			$this->_actions = apply_filters( "bulk_actions-{$this->screen->id}", $this->_actions );

			$two = '';
		} else {

			$two = '2';
		}

		if ( empty( $this->_actions ) ) {

			return;
		}

		// Screen reader label

		?>

		<label for="bulk-action-selector-<?php echo esc_attr( $which ); ?>" class="screen-reader-text">

			<?php echo esc_html__( 'Select bulk action', 'cf7-google-sheets-connector' ); ?>

		</label>



		<select name="action<?php echo esc_attr( $two ); ?>" id="bulk-action-selector-<?php echo esc_attr( $which ); ?>">

			<option value="-1"><?php echo esc_html__( 'Bulk Actions', 'cf7-google-sheets-connector' ); ?></option>

			<?php
			foreach ( $this->_actions as $name => $title ) :

				$class = 'edit' === $name ? ' class="hide-if-no-js"' : '';

				?>

				<option value="<?php echo esc_attr( $name ); ?>" <?php echo wp_kses( $class, array( 'class' => array() ) ); ?>>

					<?php echo esc_html( $title ); ?>

				</option>

			<?php endforeach; ?>

		</select>



		<?php

		// Submit button
		//
		// Previously hardcoded disabled="disabled" with nothing in the UI ever
		// re-enabling it, so bulk actions (delete/read/unread/send-to-sheet)
		// were unreachable through this button regardless of selection --
		// combined with the column_cb() key bug fixed above (row checkboxes
		// never rendered either), the entire bulk-actions UI has been inert.
		submit_button(
			esc_html__( 'Apply', 'cf7-google-sheets-connector' ),
			'action',
			'',
			false,
			array(
				'id' => "doaction$two",
			)
		);

		// Export CSV button
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from Google.
		$formId = isset( $_GET['formId'] ) ? intval( $_GET['formId'] ) : 0;

		if ( $formId > 0 ) {

			echo '<a href="#" id="cf7gs-free-csv" class="button" style="float:right; margin:0;">' .
				esc_html__( 'Export CSV', 'cf7-google-sheets-connector' ) .
				'</a>';
		}

		do_action( 'cfdb7_after_export_button' );

		?>



		<div id="cf7gs-free-pro" class="gs-popup-overlay d-none">

			<div class="gs-popups position-relative-popup text-center">

				<button type="button" class="gscf7-free-pro gsc-pro-close">×</button>

				<div class="gsc-pro-section">

					<div class="gsc-pro-card">

						<div class="gsc-pro-headers">

							<div class="gsc-pro-headers">

								<div class="gsc-modal-title">

									<?php esc_html_e( 'Want to send entries to Google Sheets?', 'cf7-google-sheets-connector' ); ?>

								</div>

								<p class="gsc-modal-text"><?php echo esc_html__( 'Export and sync your form submissions directly to Google Sheets to easily organize, filter, and manage your data in one place. Unlock this feature to simplify your workflow and access your entries anytime.', 'cf7-google-sheets-connector' ); ?>

								</p>

							</div>

							<a href="https://www.gsheetconnector.com/cf7-google-sheet-connector-pro" target="_blank" class="btn btn-primary text-decoration-none link-hover-white"><?php esc_html_e( 'Upgrade to Unlock', 'cf7-google-sheets-connector' ); ?></a>

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

								<?php esc_html_e( 'Want to download your form entries?', 'cf7-google-sheets-connector' ); ?>

							</div>

							<p class="gsc-modal-text"><?php echo esc_html__( 'Export your form entries as a CSV file and use them in Sheets. You can sort, filter, and manage everything more easily.', 'cf7-google-sheets-connector' ); ?>

							</p>

						</div>

						<a href="https://www.gsheetconnector.com/cf7-google-sheet-connector-pro" target="_blank" class="btn btn-primary text-decoration-none link-hover-white"><?php esc_html_e( 'Upgrade to Unlock', 'cf7-google-sheets-connector' ); ?></a>

					</div>

				</div>



			</div>

		</div>

		<?php
	}
} ?>
