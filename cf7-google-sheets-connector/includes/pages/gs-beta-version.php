<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<!--Start Pro Setting-->
<div class="gsc-pro-promo ml-15 mr-pro-15">

	<div class="gsc-pro-header">
		<div class="gsc-pro-icon">
			<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#28a745" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
				<path d="M5 19c-1 1-2 1-3 1 0-1 0-2 1-3l4-4"></path>
				<path d="M14 3l7 7"></path>
				<path d="M9 18l-4 4"></path>
				<path d="M15 3c2 0 6 4 6 6-2 2-6 6-8 8l-6-6c2-2 6-8 8-8z"></path>
				<circle cx="15" cy="9" r="1.5"></circle>
			</svg>

		</div>

		<div>
			<div class="unlock-header"><?php echo esc_html( __( 'Unlock Beta Version Control', 'cf7-google-sheets-connector' ) ); ?></div>
			<span class="gsc-pro-badge"><?php echo esc_html( __( 'Test upcoming features before official release', 'cf7-google-sheets-connector' ) ); ?></span>
		</div>
	</div>

	<!-- Feature Tabs -->
	<div class="gsc-pro-tabs pt-20 pb-20 pl-20 pr-20">
		<div>
			<div class="mb-20 fw-600 text-dark pro-roll-sub-header"><?php echo esc_html( __( 'Early Access', 'cf7-google-sheets-connector' ) ); ?></div>
			<div class="gsc-pro-grid">
				<ul>
					<li><?php esc_html_e( 'Get updates before public release', 'cf7-google-sheets-connector' ); ?></li>
					<li><?php esc_html_e( 'Test new features in advance', 'cf7-google-sheets-connector' ); ?></li>
					<li><?php esc_html_e( 'Try improvements early', 'cf7-google-sheets-connector' ); ?></li>
					<li><?php esc_html_e( 'Stay ahead with new changes', 'cf7-google-sheets-connector' ); ?></li>
				</ul>
			</div>
		</div>

		<div>
			<div class="mb-20 fw-600 text-dark pro-roll-sub-header"><?php echo esc_html( __( 'Testing Purpose', 'cf7-google-sheets-connector' ) ); ?></div>
			<div class="gsc-pro-grid">
				<ul>
					<li><?php esc_html_e( 'Features may still be under testing', 'cf7-google-sheets-connector' ); ?></li>
					<li><?php esc_html_e( 'Some options may change later', 'cf7-google-sheets-connector' ); ?></li>
					<li><?php esc_html_e( 'Minor issues may occur', 'cf7-google-sheets-connector' ); ?></li>
					<li><?php esc_html_e( 'Used for feedback and improvements', 'cf7-google-sheets-connector' ); ?></li>
				</ul>
			</div>
		</div>

		<div>
			<div class="mb-20 fw-600 text-dark pro-roll-sub-header"><?php echo esc_html( __( 'Safety Notice', 'cf7-google-sheets-connector' ) ); ?></div>
			<div class="gsc-pro-grid">
				<ul>
					<li><?php esc_html_e( 'Recommended for staging sites', 'cf7-google-sheets-connector' ); ?></li>
					<li><?php esc_html_e( 'Avoid enabling on live websites', 'cf7-google-sheets-connector' ); ?></li>
					<li><?php esc_html_e( 'Take backup before testing', 'cf7-google-sheets-connector' ); ?></li>
					<li><?php esc_html_e( 'Disable anytime if needed', 'cf7-google-sheets-connector' ); ?></li>
				</ul>
			</div>
		</div>

		<div>
			<div class="mb-20 fw-600 text-dark pro-roll-sub-header"><?php echo esc_html( __( 'Update Control', 'cf7-google-sheets-connector' ) ); ?></div>
			<div class="gsc-pro-grid">
				<ul>
					<li><?php esc_html_e( 'Receive beta notifications', 'cf7-google-sheets-connector' ); ?></li>
					<li><?php esc_html_e( 'Updates are not installed automatically', 'cf7-google-sheets-connector' ); ?></li>
					<li><?php esc_html_e( 'You control when to update', 'cf7-google-sheets-connector' ); ?></li>
					<li><?php esc_html_e( 'Switch on/off anytime', 'cf7-google-sheets-connector' ); ?></li>
				</ul>
			</div>
		</div>
	</div>

	<!-- CTA -->
	<div class="gsc-pro-footer text-center">
		<a href="https://www.gsheetconnector.com/cf7-google-sheet-connector-pro"
			target="_blank"
			class="btn btn-primary text-decoration-none link-hover-white">
			<?php echo esc_html( __( 'Upgrade to Unlock', 'cf7-google-sheets-connector' ) ); ?>
		</a>
	</div>

</div>
<!--End Pro Setting-->

<div class="wrap w-100 m-0 blur-pro-feature gs-form" id="opener">
	<div class="system-general_setting  inner-wrap w-100 bg-white p-40" id="googlesheet">
		<div class="gs-form opacity-down">
			<div class="gsc-access-wrapper">
				<div>
					<div class="heading mt-0">
						<?php echo esc_html__( 'Beta Program Access', 'cf7-google-sheets-connector' ); ?>
					</div>
					<p><?php echo esc_html( __( 'Get early access to upcoming features and improvements before they are officially released. Beta versions may include experimental updates and should be used for testing purposes only.', 'cf7-google-sheets-connector' ) ); ?>
					<div class="gsc-setting-text d-flex justify-between align-center pt-15 pb-15 mt-30 bg-white">
						<div>
							<div class="systemifo fw-600 text-dark">
								<?php echo esc_html__( 'Enable Beta Updates', 'cf7-google-sheets-connector' ); ?>
							</div>
							<label for="gscf7_fluentform_beta" class="fw-400">
								<?php echo esc_html__( 'Receive notifications and access to beta releases. Updates will not be installed automatically.', 'cf7-google-sheets-connector' ); ?>
							</label>
						</div>
						<div>
							<input type="hidden" name="gscf7_fluentform_beta" value="No">
							<div class="custom-check">
								<input type="checkbox"
									class="check-toggle"
									id="gscf7_fluentform_beta"
									name="gscf7_fluentform_beta"
									value="Yes">
								<label for="gscf7_fluentform_beta" class="button-toggle"></label>
							</div>
						</div>
					</div>
				</div>
				<div class="gsc-access-info">
					<div class='para-heading fw-600 mb-20'><?php esc_html_e( 'Beta Usage Notice', 'cf7-google-sheets-connector' ); ?></div>
					<ul>
						<li>
							<?php echo esc_html__( 'Beta versions may include unfinished or experimental features', 'cf7-google-sheets-connector' ); ?>
						</li>
						<li>
							<?php echo esc_html__( 'Some features may change or be removed in future updates', 'cf7-google-sheets-connector' ); ?>
						</li>
						<li>
							<?php echo esc_html__( 'Minor bugs or performance issues may occur', 'cf7-google-sheets-connector' ); ?>
						</li>
						<li>
							<?php echo esc_html__( 'We recommend enabling beta only on test or staging sites', 'cf7-google-sheets-connector' ); ?>
						</li>
						<li>
							<?php echo esc_html__( 'Beta versions are intended for testing purposes. Do not enable on live production websites without proper backups.', 'cf7-google-sheets-connector' ); ?>
						</li>
					</ul>

				</div>
			</div>
			<div class="text-right mt-30">
				<input type="button" class="btn btn-primary"
					name="gscf7_fluentform_save_beta" id="gscf7_fluentform_save_beta"
					value="<?php echo esc_html__( 'Save Settings', 'cf7-google-sheets-connector' ); ?>" />
				<div id="gscf7-beta-popup" class="gscf7-beta-popup d-none">
					<p id="gscf7-beta-msg"></p>
				</div>
			</div>
			<input type="hidden" name="gscf7-fluentform-setting-ajax-nonce" id="gscf7-fluentform-setting-ajax-nonce"
				value="<?php echo esc_attr( wp_create_nonce( 'gscf7-fluentform-setting-ajax-nonce' ) ); ?>" />
		</div>
	</div>

</div>
