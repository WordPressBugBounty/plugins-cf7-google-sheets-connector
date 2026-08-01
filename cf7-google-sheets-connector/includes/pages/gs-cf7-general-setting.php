<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
// Get the saved value from the options table
$gscf7_uninstall_settings_free = get_option( 'gscf7_uninstall_settings_free', 'No' );

?>
<!-- uninstall plugin settings -->
<div class="wrap w-100 m-0">
	<div class="system-general_setting  inner-wrap w-100 bg-white p-40">
		<div class="info-container">
		<form method="post">
			<div class="gsc-access-wrapper">
				<div>
					<div class="heading mt-0">
					<?php echo esc_html__( 'Plugin Preferences', 'cf7-google-sheets-connector' ); ?>
					</div>
					<p><?php echo esc_html( __( 'Manage how plugin settings and data are handled when the plugin is uninstalled.', 'cf7-google-sheets-connector' ) ); ?>
					<div class="gsc-setting-text d-flex justify-between align-center pt-15 pb-15 mt-30 bg-white">
					<div>
						<div class="systemifo fw-600 text-dark">
							<?php echo esc_html__( 'Delete Plugin Data on Uninstall', 'cf7-google-sheets-connector' ); ?>
						</div>
						<label for="gscf7_uninstall_settings_free" class="fw-400">
							<?php echo esc_html__( 'Removes all plugin data (options, metadata) when the plugin is deleted.', 'cf7-google-sheets-connector' ); ?>
						</label>
					</div>
					<div>

						<input type="hidden" name="gscf7_uninstall_settings_free" value="No">
						<div class="custom-check">
							<input type="checkbox"
								class="check-toggle"
								id="gscf7_uninstall_settings_free"
								name="gscf7_uninstall_settings_free"
								value="Yes" <?php echo ( $gscf7_uninstall_settings_free === 'Yes' ) ? 'checked' : ''; ?>>

							<label for="gscf7_uninstall_settings_free" class="button-toggle"></label>
						</div>
					</div>
					</div>
				</div>

				<div class="gsc-access-info">
					<div class='para-heading fw-600 mb-20'><?php esc_html_e( 'Uninstall Data Notice', 'cf7-google-sheets-connector' ); ?></div>

					<ul>
					<li><?php echo esc_html__( 'Enable this option only if you want a complete cleanup', 'cf7-google-sheets-connector' ); ?></li>
					<li><?php echo esc_html__( 'All plugin settings and data will be permanently removed', 'cf7-google-sheets-connector' ); ?></li>
					<li><?php echo esc_html__( 'Incorrect settings can delete all sensitive form and user data', 'cf7-google-sheets-connector' ); ?></li>
					</ul>

				</div>
			</div>
			<div class="text-right mt-30">
				<span class="loading-uninstall-free"></span>
				<input type="button" class="btn btn-primary uninstall-settings-save-free common-disable"
					name="gscf7_save_uninstall_settings_free"
					value="<?php echo esc_html__( 'Save Settings', 'cf7-google-sheets-connector' ); ?>" disabled />
				<div id="gscf7-uninstall-msg-free" class="gsc-msg gsc-success d-none fw-400 text-dark text-center pt-10 pb-10 manual-margin"> <?php echo esc_html__( 'Plugin preferences updated successfully', 'cf7-google-sheets-connector' ); ?></div>
				<input type="hidden" name="gs-ajax-nonce" id="gs-ajax-nonce"
					value="<?php echo esc_attr( wp_create_nonce( 'gs-ajax-nonce' ) ); ?>" />
			</div>
		</form>
		</div>
	</div>

	<div id="cf7gs_uninstall-free" class="gs-popup-overlay d-none">
		<div class="gs-popup text-center free-to-pro-data">

		<div class="gsc-modal-title  gsc-uninstall-modal-title"><?php echo esc_html__( 'Confirm Data Deletion', 'cf7-google-sheets-connector' ); ?></div>
		<p class="gsc-modal-text"><?php echo esc_html__( 'Enabling this option will permanently delete all plugin data, including settings and integrations, when the plugin is uninstalled.', 'cf7-google-sheets-connector' ); ?>
		</p>
		<p class="gsc-modal-text"><?php echo esc_html__( 'If you plan to upgrade from Free to Pro, you may lose your existing configuration data.', 'cf7-google-sheets-connector' ); ?>
		</p>

		<p class="gsc-modal-text"><?php echo esc_html__( 'Proceed only if you want a complete cleanup. This action cannot be undone.', 'cf7-google-sheets-connector' ); ?>
		</p>



		<div class="popup-actions d-flex justify-center gap-10">
			<button class="btn deactivate-btn uninstall-cancel">
				<?php esc_html_e( 'Cancel', 'cf7-google-sheets-connector' ); ?>
			</button>
			<button class="btn btn-primary uninstall-confirm">
				<?php esc_html_e( 'Enable Deletion', 'cf7-google-sheets-connector' ); ?>
			</button>
		</div>
		</div>
	</div>

</div>
