<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

$gscf7_Gs_Connector_Service = Gs_Connector_Service::instance();
?>



<div class="system-statuswc">
	<div class="wrap w-100 m-0">
		<div class="info-container inner-wrap w-100 bg-white p-40">
			<div class="systemifo inner-title heading mt-0"><?php echo esc_html( __( 'System Information', 'cf7-google-sheets-connector' ) ); ?></div>
			<p><?php echo esc_html__( 'View detailed information about your plugin, server, and WordPress setup for troubleshooting and support.', 'cf7-google-sheets-connector' ); ?></p>
			<div class="text-right d-flex justify-end">
				<button id="gscf7-copy-info" class="btn btn-primary d-flex align-center gap-10 copy">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="gsc-copy-icon">
						<rect x="9" y="9" width="13" height="13" rx="2" stroke="currentColor" stroke-width="2"></rect>
						<rect x="2" y="2" width="13" height="13" rx="2" stroke="currentColor" stroke-width="2"></rect>
					</svg>
					<?php echo esc_html( __( 'Copy System Info', 'cf7-google-sheets-connector' ) ); ?>
				</button>
				<div class="gsc-copy-msg d-none"><?php echo esc_html__( 'Copied successfully', 'cf7-google-sheets-connector' ); ?></div>
			</div>
			<?php
			echo wp_kses_post(
				$gscf7_Gs_Connector_Service->get_cf7gs_system_info()
			);
			?>
			<?php
			// ADD THIS BLOCK HERE (TOP of section)
			$gscf7_show_log_section = false;

			$gscf7_debug_log_file = WP_CONTENT_DIR . '/debug.log';

			if ( file_exists( $gscf7_debug_log_file ) ) {
				$gscf7_debug_log_contents = file( $gscf7_debug_log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );

				if ( ! empty( $gscf7_debug_log_contents ) ) {
					$gscf7_show_log_section = true;
				}
			}
			?>

		</div>
		<?php $gscf7_log_html = $gscf7_Gs_Connector_Service->display_error_cf7_log(); ?>
		<div class="system-Error shadow-box mt-40 p-30">
			<div class="error-container">
				<div class="error-log-head flex-wrap gap-20">
					<div class="heading mt-0 mb-0"><?php esc_html_e( 'Debug Log', 'cf7-google-sheets-connector' ); ?></div>
					<?php
					$gscf7_show_log_section = false;

					$gscf7_debug_log_file = WP_CONTENT_DIR . '/debug.log';

					// Check if debug.log exists and has content
					if ( file_exists( $gscf7_debug_log_file ) && filesize( $gscf7_debug_log_file ) > 0 ) {
						$gscf7_show_log_section = true;
					}
					?>

					<?php if ( $gscf7_show_log_section ) { ?>

						<div class="gsc-log-actions">

							<button class="button btn-logs gscf7-free-clear-content-logs">
								<?php echo esc_html__( 'Clear Logs', 'cf7-google-sheets-connector' ); ?>
							</button>

							<button type="button" class="button button-primary" id="gscf7-free-csv-info">
								<?php echo esc_html__( 'Download CSV', 'cf7-google-sheets-connector' ); ?>
							</button>

							<button id="gscf7-free-error-copy" class="copy button btn-logs">
								<?php echo esc_html__( 'Copy Logs', 'cf7-google-sheets-connector' ); ?>
							</button>

							<div class="gsc-copy-msg d-none">
								<?php echo esc_html__( 'Copied successfully', 'cf7-google-sheets-connector' ); ?>
							</div>

						</div>

					<?php } ?>

					<input type="hidden" name="gs-ajax-nonce" id="gs-ajax-nonce"
						value="<?php echo esc_attr( wp_create_nonce( 'gs-ajax-nonce' ) ); ?>" />

				</div>
				<?php
				echo wp_kses_post( $gscf7_Gs_Connector_Service->display_error_cf7_log() );
				?>
			</div>
		</div>
	</div>
</div>
