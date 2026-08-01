<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 *
 */
class GSCF7_FormEntDetail_Table {

	private $id;
	private $form_id;


	public function __construct() {
	  // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from Google.
		$this->form_id = isset( $_GET['formId'] ) ? (int) $_GET['formId'] : 0;
	  // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from Google.
		$this->id = isset( $_GET['entryId'] ) ? (int) $_GET['entryId'] : 0;

		$this->form_details_page();
	}

	public function form_details_page() {
		global $wpdb;
		$cfdb       = apply_filters( 'cfdb7_database', $wpdb );
		$table_name = $cfdb->prefix . 'cf7db_gsheet_forms';
		$upload_dir = wp_upload_dir();
		/*
		* Must match the directory used when entry files are removed in
		* GSCF7_FormEntry_Table::process_bulk_action(), which uses '/cf7gs'.
		*/
		$cfdb7_dir_url = $upload_dir['baseurl'] . '/cf7gs';
		$rm_underscore = apply_filters( 'cfdb7_remove_underscore_data', true );
		$results       = $cfdb->get_results(
			$cfdb->prepare(
				"SELECT * FROM $table_name WHERE form_id = %d AND id = %d LIMIT 1",
				$this->form_id,
				$this->id
			),
			OBJECT
		);

		/*
		* Validate before dereferencing.
		*
		* This check previously ran after $results[0]->value had already been read,
		* so a missing or mismatched entryId produced a run of PHP 8 warnings before
		* wp_die() was ever reached.
		*/
		if ( empty( $results[0] ) ) {
			wp_die( esc_html__( 'Not valid contact form', 'cf7-google-sheets-connector' ) );
		}

		$form_data = unserialize( $results[0]->value );

		if ( ! is_array( $form_data ) ) {
			$form_data = array();
		}

		$fields_html           = '';
		$special_mail_tag_html = '';

		$servicesgsc           = Gs_Connector_Service::instance();
		$special_mail_tags_arr = $servicesgsc->get_special_mail_tags();

		foreach ( $form_data as $key => $data ) {
			$matches = array();
			$key     = esc_html( $key );

			if ( $key == 'cfdb7_status' ) {
				continue;
			}
			if ( $rm_underscore ) {
				preg_match( '/^_.*$/m', $key, $matches );
			}
			if ( ! empty( $matches[0] ) ) {
				continue;
			}

			if ( strpos( $key, 'cfdb7_file' ) !== false ) {

				$key_val = str_replace( 'cfdb7_file', '', $key );
				$key_val = str_replace( 'your-', '', $key_val );
				$key_val = str_replace( array( '-', '_' ), ' ', $key_val );
				$key_val = ucwords( $key_val );

				$file_name = is_scalar( $data ) ? (string) $data : '';
				$file_url  = esc_url( $cfdb7_dir_url . '/' . $file_name );
				$file_text = esc_html( $file_name );

				if ( ! in_array( $key, $special_mail_tags_arr ) ) {
					$fields_html .= '<tr>
                            <th class="field-title"><b>' . esc_html( $key_val ) . '</b></th>
                            <td class="field-value"><a href="' . $file_url . '">' . $file_text . '</a></td>
                        </tr>';
				} else {
					$special_mail_tag_html .= '<tr>
                        <th class="field-title"><b>' . esc_html( $key_val ) . '</b></th>
                        <td class="field-value"><a href="' . $file_url . '">' . $file_text . '</a></td>
                    </tr>';
				}
			} elseif ( is_array( $data ) ) {

					$key_val      = str_replace( 'your-', '', $key );
					$key_val      = str_replace( array( '-', '_' ), ' ', $key_val );
					$key_val      = ucwords( $key_val );
					$arr_str_data = implode( ', ', $data );
					$arr_str_data = esc_html( $arr_str_data );
				if ( ! in_array( $key, $special_mail_tags_arr ) ) {
					$fields_html .= '<tr>
                            <th class="field-title"><b>' . $key_val . '</b></th>
                            <td class="field-value">' . nl2br( $arr_str_data ) . '</td>
                        </tr>';
				} else {
					$special_mail_tag_html .= '<tr>
                            <th class="field-title"><b>' . $key_val . '</b></th>
                            <td class="field-value">' . nl2br( $arr_str_data ) . '</td>
                        </tr>';
				}
			} else {

				$key_val = str_replace( 'your-', '', $key );
				$key_val = str_replace( array( '-', '_' ), ' ', $key_val );

				$key_val = ucwords( $key_val );
				$data    = esc_html( $data );
				if ( ! in_array( $key, $special_mail_tags_arr ) ) {
					$fields_html .= '<tr>
                        <th class="field-title"><b>' . $key_val . '</b></th>
                        <td class="field-value">' . nl2br( $data ) . '</td>
                        </tr>';
				} else {
					$special_mail_tag_html .= '<tr>
                            <th class="field-title"><b>' . $key_val . '</b></th>
                            <td class="field-value">' . nl2br( $data ) . '</td>
                        </tr>';
				}
			}
		}

		$form_data['cfdb7_status'] = 'read';
		$form_data                 = serialize( $form_data );
		$id                        = $results[0]->id;

		$cfdb->query(
			$cfdb->prepare(
				"UPDATE $table_name SET value = %s WHERE id = %d LIMIT 1",
				$form_data,
				$id
			)
		);
		?>
	<input type="hidden" name="gs-ajax-nonce" id="gs-ajax-nonce"
		value="<?php echo esc_attr( wp_create_nonce( 'gs-ajax-nonce' ) ); ?>" />

	<div id="poststuff" class="wrap w-100 m-0">
		<div id="post-body" class="metabox-holder columns-2 inner-wrap w-100 bg-white p-40">
		<div id="post-body-content">
			<?php
			do_action( 'cfdb7_before_formdetails_title', $this->form_id );

			$title = get_the_title( $this->form_id );

			$link      = '<a href="%s" class="back-btn btn-spacer btn btn-primary text-decoration-none d-inline-flex align-center gap-10">
<span class="dashicons dashicons-arrow-left-alt"></span> %s
</a>';
			$link_href = sprintf(
				$link,
				esc_url( admin_url( 'admin.php?page=wpcf7-google-sheet-config&tab=cf7_db&formId=' . $this->form_id ) ),
				esc_html__( 'Back to Forms', 'cf7-google-sheets-connector' )
			);
			echo wp_kses_post( $link_href );
			?>
			<div class="heading mt-30"><?php echo esc_html( $title ); ?></div>
			<?php do_action( 'cfdb7_after_formdetails_title', $this->form_id ); ?>
		</div>
		<div id="postbox-container-2" class="postbox-container">
			<div id="normal-sortables" class="meta-box-sortables ui-sortable">
			<div id="cf7gscpro" class="postbox">
				<div class="postbox-header">
				<div class="hndle ui-sortable-handle"><?php echo esc_html__( 'Fields', 'cf7-google-sheets-connector' ); ?>
				</div>
				<div class="handle-actions hide-if-no-js">
					<span class="hidden" id="cf7gscpro-handle-order-lower-description"><?php echo esc_html__( 'Move Fields box down', 'cf7-google-sheets-connector' ); ?>
					</span>
					<button type="button" class="handlediv" aria-expanded="false">
					<span class="screen-reader-text"><?php echo esc_html__( 'Toggle panel: Fields', 'cf7-google-sheets-connector' ); ?>
					</span>
					<span class="toggle-indicator" id="field-toggle-cf7gscpro" aria-hidden="true"></span>
					</button>
				</div>
				</div>
				<div class="inside" id="fields-cf7gscpro">
				<table class="widefat message-fields striped">
					<tbody>
						<?php echo wp_kses_post( $fields_html ); ?>
					</tbody>
				</table>
				</div>
			</div>
			<div id="cf7gscpro" class="postbox">
				<div class="postbox-header">
				<div class="hndle ui-sortable-handle"><?php echo esc_html__( 'Special Mail Tags', 'cf7-google-sheets-connector' ); ?>
				</div>
				<div class="handle-actions hide-if-no-js">
					<span class="hidden" id="cf7gscpro-handle-order-lower-description"><?php echo esc_html__( 'Move Fields box down', 'cf7-google-sheets-connector' ); ?>
					</span>
					<button type="button" class="handlediv" aria-expanded="false">
					<span class="screen-reader-text"><?php echo esc_html__( 'Toggle panel: Fields', 'cf7-google-sheets-connector' ); ?>
					</span>
					<span class="toggle-indicator" id="special-toggle-cf7gscpro" aria-hidden="true"></span>
					</button>
				</div>
				</div>
				<div class="inside" id="specialMail-cf7gscpro">
				<table class="widefat message-fields striped">
					<tbody>
						<?php echo wp_kses_post( $special_mail_tag_html ); ?>
					</tbody>
				</table>
				</div>
			</div>
			</div>
			<div id="advanced-sortables" class="meta-box-sortables ui-sortable empty-container"></div>
		</div>
		<!-- #postbox-container-2 -->
		</div>
		<!-- #post-body -->

	</div>
		<?php
		do_action( 'cfdb7_after_formdetails', $this->form_id );
	}
}
