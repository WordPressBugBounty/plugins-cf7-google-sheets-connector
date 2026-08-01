<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<?php
if ( ! function_exists( 'gscf7_remove_footer_admin' ) ) {
	function gscf7_remove_footer_admin() {
		return '';
	}
}

add_filter( 'admin_footer_text', 'gscf7_remove_footer_admin' );
?>
<!---Start Footer Section--->
<div class="gscf7-free">
	<!--NEW FOOTER ONE--->
	<div class="gsc-admin-footer-bar">
	<!-- Left -->
	<div class="gsc-footer-left">
		<?php echo esc_html__( 'Please rate', 'cf7-google-sheets-connector' ); ?>
		<strong>
		<?php echo esc_html__( 'CF7 Google Sheet Connector', 'cf7-google-sheets-connector' ); ?>
		</strong>
		<span class="gsc-stars">★★★★★</span>
		<?php echo esc_html__( 'on', 'cf7-google-sheets-connector' ); ?>
		<a href="https://wordpress.org/support/plugin/cf7-google-sheets-connector/reviews/" target="_blank">
		<?php echo esc_html__( 'WordPress.org', 'cf7-google-sheets-connector' ); ?>
		</a>
		<?php echo esc_html__( 'to help us spread the word.', 'cf7-google-sheets-connector' ); ?>
	</div>
	<!-- Right -->
	<div class="gsc-footer-right">
		<div class="gsc-footer-social">
		<a href="https://www.facebook.com/gsheetconnectorofficial" target="_blank" aria-label="Facebook">
			<svg viewBox="0 0 24 24">
			<path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"></path>
			</svg>
		</a>
		<a href="https://www.instagram.com/gsheetconnector" target="_blank" aria-label="Instagram">
			<svg viewBox="0 0 24 24" fill="currentColor"
			xmlns="http://www.w3.org/2000/svg">
			<rect x="2" y="2" width="20" height="20" rx="5" />
			<circle cx="12" cy="12" r="4" fill="#fff" />
			<circle cx="17.5" cy="6.5" r="1.2" fill="#fff" />
			</svg>
		</a>
		<a href="https://www.linkedin.com/company/gsheetconnector/" target="_blank" aria-label="Linkdin">
			<svg viewBox="0 0 24 24" fill="currentColor"
			xmlns="http://www.w3.org/2000/svg">
			<rect x="2" y="2" width="20" height="20" rx="3" />
			<circle cx="7" cy="7" r="1.2" fill="#fff" />
			<rect x="6" y="10" width="2.5" height="7" fill="#fff" />
			<path d="M11 10h2v1.2c.5-.8 1.5-1.4 2.8-1.4
						2 0 3.2 1.2 3.2 3.6V17h-2.5v-3.2
						c0-1.2-.4-2-1.6-2s-1.9.8-1.9 2V17H11z"
				fill="#fff" />
			</svg>
		</a>
		<a href="https://twitter.com/gsheetconnector?lang=en" target="_blank" aria-label="Twitter">
			<svg viewBox="0 0 24 24">
			<path d="M23 3a10.9 10.9 0 01-3.14 1.53
					4.48 4.48 0 00-7.86 3v1
					A10.66 10.66 0 013 4s-4 9
					5 13a11.64 11.64 0 01-7 2
					c9 5 20 0 20-11.5
					a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"></path>
			</svg>
		</a>
		<a href="https://www.youtube.com/@GSheetConnector" target="_blank" aria-label="YouTube">
			<svg viewBox="0 0 24 24">
			<path d="M23 7s-.2-1.4-.8-2
					c-.8-.8-1.7-.8-2.1-.9
					C16.9 4 12 4 12 4s-4.9 0-8.1.1
					c-.4.1-1.3.1-2.1.9
					-.6.6-.8 2-.8 2S1 8.6 1 10.2v1.6
					C1 13.4 1.2 15 1.2 15s.2 1.4.8 2
					c.8.8 1.9.8 2.4.9
					C6.1 18 12 18 12 18s4.9 0 8.1-.1
					c.4-.1 1.3-.1 2.1-.9
					.6-.6.8-2 .8-2s.2-1.6.2-3.2v-1.6
					C23.2 8.6 23 7 23 7zM9.7 14.5V8.5l5.2 3-5.2 3z"></path>
			</svg>
		</a>
		</div>
		<div class="gsc-footer-version">
		<?php
		echo esc_html__( 'Version:', 'cf7-google-sheets-connector' ) . ' ' .
			esc_html( GS_CONNECTOR_VERSION );
		?>
		</div>
	</div>
	</div>
	<!-- Floating Icon -->
	<div class="assistant-widget">
	<!-- Bear Icon -->
	<div class="assistant-icon" id="gscf7-free-assistantBtn">
		<img src="<?php echo esc_url( GS_CONNECTOR_URL ); ?>/assets/img/gsheet-logo.svg" alt="GSheetConnectorSupport">
	</div>
	<!-- Popup Menu -->
	<div class="assistant-menu" id="gscf7-free-assistantMenu">
		<a href="https://gsheetconnector.com/docs/cf7-gsheetconnector" target="_blank" class="menu-item d-flex align-center gap-10 fw-500">
		<span class="menu-icon">
			<!-- Docs Icon -->
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
			<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
			<polyline points="14 2 14 8 20 8" />
			</svg>
		</span> <?php echo esc_html__( 'Docs', 'cf7-google-sheets-connector' ); ?>
		</a>
		<a href="https://www.gsheetconnector.com/plugins" target="_blank" class="menu-item d-flex align-center gap-10 fw-500">
		<span class="menu-icon">
			<!-- Plugin Icon -->
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
			<path d="M21 16V8a2 2 0 0 0-2-2h-4" />
			<path d="M3 8v8a2 2 0 0 0 2 2h4" />
			<rect x="7" y="2" width="10" height="20" rx="2" />
			</svg>
		</span><?php echo esc_html__( 'Plugins', 'cf7-google-sheets-connector' ); ?>
		</a>
		<a href="https://wordpress.org/support/plugin/cf7-google-sheets-connector" target="_blank" class="menu-item d-flex align-center gap-10 fw-500">
		<span class="menu-icon">
			<!-- Support Icon -->
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
			<path d="M22 12a10 10 0 1 0-20 0" />
			<path d="M2 12v4a2 2 0 0 0 2 2h4" />
			<path d="M22 12v4a2 2 0 0 1-2 2h-4" />
			</svg>
		</span> <?php echo esc_html__( 'Support', 'cf7-google-sheets-connector' ); ?>
		</a>
	</div>
	</div>
</div>
