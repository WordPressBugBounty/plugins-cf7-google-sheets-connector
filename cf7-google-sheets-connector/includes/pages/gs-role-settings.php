<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<!--Start Pro Setting(Roll Permissions)-->
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
		<div class="unlock-header"><?php echo esc_html( __( 'Unlock Role-Based Access Control', 'cf7-google-sheets-connector' ) ); ?></div>
		<span class="gsc-pro-badge"><?php echo esc_html( __( 'Advanced options are available in PRO', 'cf7-google-sheets-connector' ) ); ?></span>
	</div>
	</div>
	<!-- Feature Tabs -->
	<div class="gsc-pro-tabs pt-20 pb-20 pl-20 pr-20">
	<div>
		<div class="mb-20 fw-600 text-dark pro-roll-sub-header"><?php echo esc_html( __( 'Role Permissions', 'cf7-google-sheets-connector' ) ); ?></div>
		<div class="gsc-pro-grid">
		<ul>
			<li><?php esc_html_e( 'Allow specific WordPress roles', 'cf7-google-sheets-connector' ); ?></li>
			<li><?php esc_html_e( 'Enable/disable integration access', 'cf7-google-sheets-connector' ); ?></li>
			<li><?php esc_html_e( 'Control form feed visibility', 'cf7-google-sheets-connector' ); ?></li>
			<li><?php esc_html_e( 'Restrict settings management', 'cf7-google-sheets-connector' ); ?></li>
		</ul>
		</div>
	</div>
	<div>
		<div class="mb-20 fw-600 text-dark pro-roll-sub-header"><?php echo esc_html( __( 'Security Control', 'cf7-google-sheets-connector' ) ); ?></div>
		<div class="gsc-pro-grid">
		<ul>
			<li><?php esc_html_e( 'Prevent unauthorized changes', 'cf7-google-sheets-connector' ); ?></li>
			<li><?php esc_html_e( 'Secure Google Sheet credentials', 'cf7-google-sheets-connector' ); ?></li>
			<li><?php esc_html_e( 'Role-based configuration control', 'cf7-google-sheets-connector' ); ?></li>
			<li><?php esc_html_e( 'Protect integration settings', 'cf7-google-sheets-connector' ); ?></li>
		</ul>
		</div>
	</div>
	<div>
		<div class="mb-20 fw-600 text-dark pro-roll-sub-header"><?php echo esc_html( __( 'Management Benefits', 'cf7-google-sheets-connector' ) ); ?></div>
		<div class="gsc-pro-grid">
		<ul>
			<li><?php esc_html_e( 'Grant access to trusted editors', 'cf7-google-sheets-connector' ); ?></li>
			<li><?php esc_html_e( 'Hide settings from subscribers', 'cf7-google-sheets-connector' ); ?></li>
			<li><?php esc_html_e( 'Team-based permission structure', 'cf7-google-sheets-connector' ); ?></li>
			<li><?php esc_html_e( 'Improved dashboard security', 'cf7-google-sheets-connector' ); ?></li>
		</ul>
		</div>
	</div>
	<div>
		<div class="mb-20 fw-600 text-dark pro-roll-sub-header"><?php echo esc_html( __( 'Audit & Monitoring', 'cf7-google-sheets-connector' ) ); ?></div>
		<div class="gsc-pro-grid">
		<ul>
			<li><?php esc_html_e( 'Track role-based changes', 'cf7-google-sheets-connector' ); ?></li>
			<li><?php esc_html_e( 'Monitor integration access', 'cf7-google-sheets-connector' ); ?></li>
			<li><?php esc_html_e( 'Review permission updates', 'cf7-google-sheets-connector' ); ?></li>
			<li><?php esc_html_e( 'Maintain admin accountability', 'cf7-google-sheets-connector' ); ?></li>
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
<!--End Pro Setting(Roll Permissions)-->
<div class="gscf7-role-settings" id="gsc-googlesheet">
	<div class="wrap w-100 m-0">
	<div class="inner-wrap w-100 bg-white p-40 blur-pro-feature">
		<div class="heading mt-0"><?php echo esc_html__( 'Access Management', 'cf7-google-sheets-connector' ); ?></div>
		<p><?php echo esc_html__( 'Plugin Access controls main plugin settings, while Form Permissions controls access inside Contact Form 7 forms.', 'cf7-google-sheets-connector' ); ?></p>
		<div class="gscf7_fluentform-card">
		<div class="gsc-access-wrapper mt-30">
			<div class="gsc-access-box bg-white pt-15 pb-15 pl-15 pr-15">
			<div class="para-heading fw-600 mb-20"><?php echo esc_html__( 'Plugin Access Control', 'cf7-google-sheets-connector' ); ?></div>
			<p><?php echo esc_html__( 'Control who can access and manage the GSheetConnector plugin settings.', 'cf7-google-sheets-connector' ); ?></p>
			<div class="gsc-role-card mb-10">
				<div class="custom-check d-flex justify-between alien-center">
				<label class="role-label gsc-switch"><?php echo esc_html__( 'Administrator', 'cf7-google-sheets-connector' ); ?></label><input type="checkbox" class="check-toggle" disabled="disabled" checked="checked"><label class="button-toggle"></label>
				</div>
			</div>
			<div class="gsc-role-card mb-10">
				<div class="custom-check d-flex justify-between alien-center"><label class="role-label gsc-switch"><?php echo esc_html__( 'Editor', 'cf7-google-sheets-connector' ); ?></label><input type="checkbox"><label class="button-toggle"></label></div>
			</div>
			<div class="gsc-role-card mb-10">
				<div class="custom-check d-flex justify-between alien-center"><label class="role-label gsc-switch"><?php echo esc_html__( 'Author', 'cf7-google-sheets-connector' ); ?></label><input type="checkbox"><label class="button-toggle"></label></div>
			</div>
			<div class="gsc-role-card mb-10">
				<div class="custom-check d-flex justify-between alien-center"><label class="role-label gsc-switch"><?php echo esc_html__( 'Contributor', 'cf7-google-sheets-connector' ); ?></label><input type="checkbox"><label class="button-toggle"></label></div>
			</div>
			</div>
			<div class="gsc-access-info">
			<div class="para-heading fw-600 mb-20"><?php echo esc_html__( 'Permission Guidelines', 'cf7-google-sheets-connector' ); ?></div>
			<ul>
				<li><?php echo esc_html__( 'Control which user roles can access the GSheetConnector plugin', 'cf7-google-sheets-connector' ); ?></li>
				<li><?php echo esc_html__( 'Allow selected users to manage Google Sheets integration settings', 'cf7-google-sheets-connector' ); ?></li>
				<li><?php echo esc_html__( 'Restrict access to sensitive features like feeds, logs, and configurations', 'cf7-google-sheets-connector' ); ?></li>
				<li><?php echo esc_html__( 'Only enabled roles will see the plugin menu in the admin panel', 'cf7-google-sheets-connector' ); ?></li>
				<li><?php echo esc_html__( 'Recommended: Allow only Administrators and trusted Editors', 'cf7-google-sheets-connector' ); ?></li>
			</ul>
			</div>
		</div>
		<div class="select-info text-right mt-30">
		</div>
		</div>
		<div class="gscf7_fluentform-card">
		<div class="gsc-access-wrapper mt-30">
			<div class="gsc-access-box bg-white pt-15 pb-15 pl-15 pr-15">
			<div class="para-heading fw-600 mb-20"><?php echo esc_html__( 'Form Permissions', 'cf7-google-sheets-connector' ); ?></div>
			<p><?php echo esc_html__( 'Control who can configure Google Sheets integration inside Contact Form 7 forms.', 'cf7-google-sheets-connector' ); ?></p>
			<div class="gsc-role-card mb-10">
				<div class="custom-check d-flex justify-between alien-center">
				<label class="role-label gsc-switch"><?php echo esc_html__( 'Administrator', 'cf7-google-sheets-connector' ); ?></label><input type="checkbox" class="check-toggle" disabled="disabled" checked="checked"><label class="button-toggle"></label>
				</div>
			</div>
			<div class="gsc-role-card mb-10">
				<div class="custom-check d-flex justify-between alien-center"><label class="role-label gsc-switch"><?php echo esc_html__( 'Editor', 'cf7-google-sheets-connector' ); ?></label><input type="checkbox"><label class="button-toggle"></label></div>
			</div>
			<div class="gsc-role-card mb-10">
				<div class="custom-check d-flex justify-between alien-center"><label class="role-label gsc-switch"><?php echo esc_html__( 'Author', 'cf7-google-sheets-connector' ); ?></label><input type="checkbox"><label class="button-toggle"></label></div>
			</div>
			<div class="gsc-role-card mb-10">
				<div class="custom-check d-flex justify-between alien-center"><label class="role-label gsc-switch"><?php echo esc_html__( 'Contributor', 'cf7-google-sheets-connector' ); ?></label><input type="checkbox"><label class="button-toggle"></label></div>
			</div>
			</div>
			<div class="gsc-access-info">
			<div class="para-heading fw-600 mb-20"><?php echo esc_html__( 'Permission Guidelines', 'cf7-google-sheets-connector' ); ?></div>
			<ul>
				<li><?php echo esc_html__( 'Control which user roles can access Google Sheet settings inside Contact Form 7', 'cf7-google-sheets-connector' ); ?></li>
				<li><?php echo esc_html__( 'Allow users to configure sheet connections in individual forms', 'cf7-google-sheets-connector' ); ?></li>
				<li><?php echo esc_html__( 'Manage access to "Google Sheet Pro" tab in CF7 form editor', 'cf7-google-sheets-connector' ); ?></li>
				<li><?php echo esc_html__( 'Prevent unauthorized users from modifying form integrations', 'cf7-google-sheets-connector' ); ?></li>
				<li><?php echo esc_html__( 'Recommended: Allow only Administrators and trusted Editors', 'cf7-google-sheets-connector' ); ?></li>
			</ul>
			</div>
		</div>
		<div class="select-info text-right mt-30">
			<input type="submit" class="btn btn-primary button-large" name="gscf7_fluentform_settings"
			value="<?php echo esc_html__( 'Save Settings', 'cf7-google-sheets-connector' ); ?>" />
		</div>
		</div>

	</div>
	</div>