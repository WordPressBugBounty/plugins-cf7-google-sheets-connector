<?php
/**
 * CFDB7 CSV Export
 *
 * Note: this file previously called ob_start() at include time, which left an
 * output buffer open on every request site-wide. download_send_headers() clears
 * any active buffer itself, so the global call was unnecessary.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CF7GSCPRO_Export_CSV {

	// Set headers for CSV download
	public function download_send_headers( $filename ) {

		if ( ob_get_length() ) {
			ob_end_clean();
		}

		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Transfer-Encoding: binary' );
	}

	// Handle CSV download
	public function download_csv_file( $formId ) {

		global $wpdb;

		$cfdb       = apply_filters( 'cfdb7_database', $wpdb );
		$table_name = $cfdb->prefix . 'cf7db_gsheet_forms';

		if ( $formId > 0 ) {

			// Get first entry for headers
			$first_entry = $cfdb->get_row(
				$cfdb->prepare(
					"SELECT value FROM {$table_name} WHERE form_id = %d LIMIT 1",
					$formId
				),
				ARRAY_A
			);

			if ( ! $first_entry ) {
				return;
			}

			$data    = maybe_unserialize( $first_entry['value'] );
			$headers = array_keys( $data );

			// Get all entries
			$entries = $cfdb->get_results(
				$cfdb->prepare(
					"SELECT value FROM {$table_name} WHERE form_id = %d",
					$formId
				),
				ARRAY_A
			);

			if ( ! $entries ) {
				return;
			}

			// Send CSV headers
			$this->download_send_headers( 'cfdb7-' . gmdate( 'Y-m-d' ) . '.csv' );

			$df = fopen( 'php://output', 'w' );

			// Write CSV header
			fputcsv( $df, $headers );

			foreach ( $entries as $entry ) {

				$data = maybe_unserialize( $entry['value'] );
				$row  = array();

				foreach ( $headers as $header ) {

					if ( isset( $data[ $header ] ) && is_array( $data[ $header ] ) ) {
						$row[] = implode( ', ', $data[ $header ] );
					} else {
						$row[] = isset( $data[ $header ] ) ? $data[ $header ] : '';
					}
				}

				fputcsv( $df, $row );
			}

			if ( is_resource( $df ) ) {
				fclose( $df ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			}

			exit;
		}
	}
}
