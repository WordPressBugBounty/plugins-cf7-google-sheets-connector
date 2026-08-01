<?php
/*
 * Google Sheet configuration and settings page
 * @since 1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

$gscf7_active_tab = 'dashboard';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from Google.
if ( isset( $_GET['tab'] ) ) {
	$gscf7_active_tab = sanitize_text_field(
		wp_unslash( $_GET['tab'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from Google.
	);
}
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from Google.
if ( isset( $_GET['code'] ) ) {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback from Google.
	if ( is_string( $_GET['code'] ) ) {
		$gscf7_active_tab = 'integration';
	}
}
$gscf7_active_tab_name = '';
if ( $gscf7_active_tab == 'dashboard' ) {
	$gscf7_active_tab_name = 'Dashboard';
} elseif ( $gscf7_active_tab == 'integration' ) {
	$gscf7_active_tab_name = 'Integration';
} elseif ( $gscf7_active_tab == 'settings' ) {
	$gscf7_active_tab_name = 'Settings';
} elseif ( $gscf7_active_tab == 'cf7_db' ) {
	$gscf7_active_tab_name = 'CF7 Database';
} elseif ( $gscf7_active_tab == 'gs-integrate-info' ) {
	$gscf7_active_tab_name = 'System Status';
} elseif ( $gscf7_active_tab == 'extension' ) {
	$gscf7_active_tab_name = 'Extension';
}
// Check plugin version and subscription plan
$gscf7_plugin_version = defined( 'GS_CONNECTOR_VERSION' ) ? GS_CONNECTOR_VERSION : 'N/A';
?>
<div class="gscf7-free">
	<!--Start NOTICE BAR-->
	<div id="pro-notice-bar" class="pro-header-notice">
		<span class="pro-notice-bar-message"><?php echo esc_html__( 'You`re using CF7 Google Sheet Connector Lite. To unlock more features consider ', 'cf7-google-sheets-connector' ); ?><a href="https://www.gsheetconnector.com/cf7-google-sheet-connector-pro" class="link-hover-white" target="_blank" rel="noopener"><?php echo esc_html__( 'upgrading to Pro', 'cf7-google-sheets-connector' ); ?></a></span>
		<button type="button" id="pro-dismiss-header-notice" title="Dismiss this message" data-page="overview" class="pro-dismiss">
			<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M15.8327 5.34175L14.6577 4.16675L9.99935 8.82508L5.34102 4.16675L4.16602 5.34175L8.82435 10.0001L4.16602 14.6584L5.34102 15.8334L9.99935 11.1751L14.6577 15.8334L15.8327 14.6584L11.1744 10.0001L15.8327 5.34175Z" fill="white"></path>
			</svg>
		</button>
	</div>
	<!--End NOTICE BAR-->
	<!-- START NOTICE SLIDER -->
	<?php
	$gscf7_is_authenticated     = false;
	$gscf7_selected_method      = '';
	$gscf7_authenticated        = get_option( 'gs_token' );
	$gscf7_manual               = get_option( 'cf7_manual' );
	$gscf7_authenticatedService = get_option( 'gs_cf7_service_account_json' );
	$gscf7_auth_method          = get_option( 'gs_cf7_auth_method' );
	$gscf7_per                  = get_option( 'gs_verify' );
	$gscf7_email_account        = '';

	// Check if the user is authenticated when saving existing API method
	if ( ( ! empty( $gscf7_authenticated ) && $gscf7_per == 'valid' && $gscf7_auth_method === 'cf7_existing' ) ) {
		$gscf7_google_sheet  = new CF7GSC_googlesheet();
		$gscf7_email_account = $gscf7_google_sheet->gsheet_print_google_account_email();
		if ( $gscf7_email_account ) {
			$gscf7_is_authenticated = true;
			$gscf7_selected_method  = esc_html( __( 'Existing', 'cf7-google-sheets-connector' ) );
		}
	} elseif ( ( ! empty( $gscf7_authenticatedService ) && $gscf7_auth_method === 'cf7_service' ) ) {
		$gscf7_is_authenticated = true;
		$gscf7_decoded_json     = json_decode( $gscf7_authenticatedService, true );

		if ( json_last_error() === JSON_ERROR_NONE && isset( $gscf7_decoded_json['client_email'] ) ) {

			$gscf7_selected_method = esc_html( __( 'Service', 'cf7-google-sheets-connector' ) );
		}
	} else {
		$gscf7_selected_method = esc_html( __( 'Auth Required', 'cf7-google-sheets-connector' ) );
	}

	$gscf7_show_auth_notice = ! $gscf7_is_authenticated;

	$gscf7_install_time     = strtotime( get_option( 'gscf7_free_install_time' ) );
	$gscf7_install_time_raw = get_option( 'gscf7_free_install_time' );

	$gscf7_install_time = $gscf7_install_time_raw ? strtotime( $gscf7_install_time_raw ) : 0;

	$gscf7_time_passed = $gscf7_install_time && ( time() - $gscf7_install_time >= 2 * DAY_IN_SECONDS );

	$gscf7_is_dismissed = gscf7_is_dismissed( 'review' );
	$gscf7_is_snoozed   = gscf7_is_snoozed( 'review' );

	$gscf7_show_review_notice =
		$gscf7_time_passed &&
		! $gscf7_is_dismissed &&
		! $gscf7_is_snoozed;
	$gscf7_show_addon_notice  =
		! gscf7_is_dismissed( 'addons' ) &&
		! gscf7_is_snoozed( 'addons' );

	$gscf7_show_pro_upsell_notice = ! gscf7_is_dismissed( 'pro_upsell' ) &&
		! gscf7_is_snoozed( 'pro_upsell' );
	function gscf7_is_dismissed( $key ) {
		return get_option( 'gscf7_free_notice_' . $key ) === 'dismissed';
	}

	function gscf7_is_snoozed( $key ) {
		$time = get_option( 'gscf7_free_notice_' . $key . '_time' );
		return $time && ( time() - $time < 15 * DAY_IN_SECONDS );
	}
	// ==============================
	// CHECK IF ANY NOTICE EXISTS
	// ==============================
	$gscf7_has_notice =
		$gscf7_show_auth_notice ||
		( $gscf7_show_review_notice && $gscf7_is_authenticated ) || ( $gscf7_show_pro_upsell_notice && $gscf7_is_authenticated ) ||
		( $gscf7_is_authenticated && $gscf7_show_addon_notice );

	?>
	<!-- ============================== -->
	<!--  SLIDER START -->
	<!-- ============================== -->

	<div class="notification-gsc-notice-slider">
		<div class="notification-gsc-slider-track">

			<!-- ========================= -->
			<!-- 2. Auth -->
			<!-- ========================= -->
			<?php if ( $gscf7_show_auth_notice ) : ?>

				<div class="notification-gsc-slide">
					<div class="gsc-activate-banner">
						<div class="gsc-activate-content">

							<div class="gsc-activate-content-header">
								<?php esc_html_e( 'Authenticate with Your Google Account', 'cf7-google-sheets-connector' ); ?>
							</div>

							<p>
								<?php esc_html_e( 'Your connection has expired or hasn’t been set up yet.', 'cf7-google-sheets-connector' ); ?>
							</p>

							<p>
								<?php esc_html_e( 'Your Google connection is missing or expired. Please authenticate to continue syncing data without interruptions.', 'cf7-google-sheets-connector' ); ?>
							</p>

							<div class="gsc-activate-actions">

								<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcf7-google-sheet-config&tab=integration' ) ); ?>"
									class="gsc-btn-activate link-hover-white">
									<?php esc_html_e( 'Authenticate Now', 'cf7-google-sheets-connector' ); ?>
								</a>

								<a href="<?php echo esc_url( 'https://www.gsheetconnector.com/docs/cf7-gsheetconnector' ); ?>"
									target="_blank"
									rel="noopener noreferrer"
									class="gsc-btn-secondary">
									<?php esc_html_e( 'Learn How', 'cf7-google-sheets-connector' ); ?>
								</a>

							</div>

						</div>
					</div>
				</div>
			<?php endif; ?>

			<!-- ========================= -->
			<!-- 2. REVIEW -->
			<!-- ========================= -->

			<?php if ( $gscf7_is_authenticated && $gscf7_show_review_notice ) : ?>
				<div class="notification-gsc-slide">
					<div class="gsc-upgrade-banner">

						<div class="gsc-upgrade-content">
							<div class="gsc-upgrade-heading">
								<?php esc_html_e( 'Enjoying the Plugin?', 'cf7-google-sheets-connector' ); ?>
							</div>

							<p>
								<?php esc_html_e( 'If you are enjoying the plugin, please consider leaving a 5-star review. Your support helps us improve and grow.', 'cf7-google-sheets-connector' ); ?>
							</p>

							<div class="gsc-upgrade-actions">


								<a href="<?php echo esc_url( 'https://wordpress.org/support/plugin/cf7-google-sheets-connector/reviews' ); ?>"
									target="_blank"
									rel="noopener noreferrer"
									class="gsc-btn-upgrade gsc-review-btn link-hover-white">
									<?php esc_html_e( 'Ok, you deserve it!', 'cf7-google-sheets-connector' ); ?>
								</a>

								<button class="gsc-dismiss-btn gsc-no-thanks-btn" data-key="review">
									<?php esc_html_e( 'I already did', 'cf7-google-sheets-connector' ); ?>
								</button>


								<a href="<?php echo esc_url( 'https://www.gsheetconnector.com/support' ); ?>"
									target="_blank"
									rel="noopener noreferrer"
									class="gsc-btn-secondary">
									<?php esc_html_e( 'I need help', 'cf7-google-sheets-connector' ); ?>
								</a>


								<button class="gsc-btn-later" data-key="review">
									<?php esc_html_e( 'Maybe Later', 'cf7-google-sheets-connector' ); ?>
								</button>

							</div>
						</div>


						<button class="gsc-upgrade-close" data-key="review">✕</button>

					</div>
				</div>
			<?php endif; ?>


			<!-- ========================= -->
			<!-- 3. ADDONS -->
			<!-- ========================= -->
			<?php if ( $gscf7_is_authenticated && $gscf7_show_addon_notice ) : ?>
				<div class="notification-gsc-slide">
					<div class="gsc-ad-banner">
						<div class="gsc-ad-content">

							<div class="gsc-ad-content-header">
								<?php esc_html_e( 'Enhance Your Setup', 'cf7-google-sheets-connector' ); ?>
							</div>

							<p>
								<?php esc_html_e( 'Extend your workflow with our add-ons.', 'cf7-google-sheets-connector' ); ?>
							</p>

							<p>
								<?php esc_html_e( 'Discover tools that integrate seamlessly and help you get more done.', 'cf7-google-sheets-connector' ); ?>
							</p>

							<div class="gsc-ad-actions">

								<a href="https://www.gsheetconnector.com/plugins"
									target="_blank"
									class="gsc-btn-ad link-hover-white">
									<?php esc_html_e( 'Explore Add-ons', 'cf7-google-sheets-connector' ); ?>
								</a>

								<a href="<?php echo esc_url( 'https://www.gsheetconnector.com/docs' ); ?>"
									target="_blank"
									class="gsc-btn-ad link-hover-white">
									<?php esc_html_e( 'View Details', 'cf7-google-sheets-connector' ); ?>
								</a>

								<button class="gsc-btn-later" data-key="addons">
									<?php esc_html_e( 'Maybe Later', 'cf7-google-sheets-connector' ); ?>
								</button>

							</div>

						</div>

						<button class="gsc-upgrade-close" data-key="addons">✕</button>
					</div>
				</div>
			<?php endif; ?>


			<!-- ========================= -->
			<!-- 4. Show PRO Benefits -->
			<!-- ========================= -->

			<?php if ( $gscf7_is_authenticated && $gscf7_show_pro_upsell_notice ) : ?>

				<div class="notification-gsc-slide">
					<div class="gsc-upgrade-banner">

						<div class="gsc-upgrade-content">
							<div class="gsc-upgrade-heading">
								<?php esc_html_e( 'Unlock Advance Features of CF7 Google Sheet Connector Pro', 'cf7-google-sheets-connector' ); ?>
							</div>

							<p>
								<?php esc_html_e( 'Use advanced features like Manual Authentication and automatic field mapping, no need to create columns in Google Sheets manually. Choose only the fields you need with simple toggles, use advanced tags, sync past form entries, and get priority support.', 'cf7-google-sheets-connector' ); ?>
							</p>

							<div class="gsc-upgrade-actions">

								<!-- View License -->
								<a href="<?php echo esc_url( 'https://www.gsheetconnector.com/cf7-google-sheet-connector-pro' ); ?>"
									target="_blank"
									rel="noopener noreferrer"
									class="gsc-btn-upgrade  link-hover-white">
									<?php esc_html_e( 'View License Types', 'cf7-google-sheets-connector' ); ?>
								</a>

								<!-- Compare -->
								<a href="<?php echo esc_url( 'https://www.gsheetconnector.com/cf7-google-sheet-connector-pro#compare' ); ?>"
									target="_blank"
									rel="noopener noreferrer"
									class="gsc-btn-secondary">
									<?php esc_html_e( 'Compare Features', 'cf7-google-sheets-connector' ); ?>
								</a>

								<!-- Maybe Later (15 days logic handled in JS) -->
								<button class="gsc-btn-later" data-key="pro_upsell">
									<?php esc_html_e( 'Maybe Later', 'cf7-google-sheets-connector' ); ?>
								</button>

							</div>
						</div>

						<!-- Close permanently -->
						<button class="gsc-upgrade-close" data-key="pro_upsell">✕</button>

					</div>
				</div>

			<?php endif; ?>

		</div>
		<?php if ( $gscf7_has_notice ) : ?>
			<!-- ARROWS -->
			<div class="notification-gsc-slider-arrows">
				<button class="notification-gsc-slider-btn prev">❮</button>
				<button class="notification-gsc-slider-btn next">❯</button>
			</div>
		<?php endif; ?>
	</div>
	<?php wp_nonce_field( 'gs-ajax-nonce', 'gs-ajax-nonce' ); ?>
	<!-- END NOTICE SLIDER -->
	<!--Start Gsheet-Header Section-->
	<div class="gsheet-header-wrapper pt-10 pb-10 justify-between bg-white">
		<div class="container">
			<div class="row justify-between align-center">
				<div class="left-gsheet-header d-flex align-center">
					<div class="gsheet-header-logo">
						<a href="https://gsheetconnector.com/docs/cf7-gsheetconnector" target="_blank"><i class="d-block"></i></a>
					</div>
					<div class="gsheet-header-logo-text">
						<a href="https://gsheetconnector.com/docs/cf7-gsheetconnector" class="text-decoration-none" target="_blank">
							<div class="line-height-zero m-0">
								<span class="title fw-600"><?php echo esc_html( __( 'CF7 Google Sheet Connector', 'cf7-google-sheets-connector' ) ); ?></span>
							</div>
						</a>
						<small class="p-0"><?php echo esc_html( __( 'v', 'cf7-google-sheets-connector' ) ); ?> <?php echo esc_html( $gscf7_plugin_version, 'cf7-google-sheets-connector' ); ?> </small>
					</div>
				</div>
				<div class="right-gsheet-header">
					<ul class="d-flex gap-10">
						<li>
							<a href="https://gsheetconnector.com/docs/cf7-gsheetconnector" class="d-flex justify-center align-center bg-white" title="Document" target="_blank">
								<svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M18 6.00002V6.75002H18.75V6.00002H18ZM15.7172 2.32614L15.6111 1.58368L15.7172 2.32614ZM4.91959 3.86865L4.81353 3.12619H4.81353L4.91959 3.86865ZM5.07107 6.75002H18V5.25002H5.07107V6.75002ZM18.75 6.00002V4.30604H17.25V6.00002H18.75ZM15.6111 1.58368L4.81353 3.12619L5.02566 4.61111L15.8232 3.0686L15.6111 1.58368ZM4.81353 3.12619C3.91638 3.25435 3.25 4.0227 3.25 4.92895H4.75C4.75 4.76917 4.86749 4.63371 5.02566 4.61111L4.81353 3.12619ZM18.75 4.30604C18.75 2.63253 17.2678 1.34701 15.6111 1.58368L15.8232 3.0686C16.5763 2.96103 17.25 3.54535 17.25 4.30604H18.75ZM5.07107 5.25002C4.89375 5.25002 4.75 5.10627 4.75 4.92895H3.25C3.25 5.9347 4.06532 6.75002 5.07107 6.75002V5.25002Z" fill="#666"></path>
									<path d="M8 12H16" stroke="#666" stroke-width="1.5" stroke-linecap="round"></path>
									<path d="M8 15.5H13.5" stroke="#666" stroke-width="1.5" stroke-linecap="round"></path>
									<path d="M4 6V19C4 20.6569 5.34315 22 7 22H17C18.6569 22 20 20.6569 20 19V14M4 6V5M4 6H17C18.6569 6 20 7.34315 20 9V10" stroke="#666" stroke-width="1.5" stroke-linecap="round"></path>
								</svg>
							</a>
						</li>
						<li>
							<a href="https://wordpress.org/support/plugin/cf7-google-sheets-connector" class="d-flex justify-center align-center bg-white" title="Support" target="_blank">
								<svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M18 6L14.8284 9.17157M14.8284 9.17157C14.1046 8.44772 13.1046 8 12 8C10.8954 8 9.89543 8.44772 9.17157 9.17157M14.8284 9.17157C15.5523 9.89543 16 10.8954 16 12C16 13.1046 15.5523 14.1046 14.8284 14.8284M18 18L14.8284 14.8284M14.8284 14.8284C14.1046 15.5523 13.1046 16 12 16C10.8954 16 9.89543 15.5523 9.17157 14.8284M6 18L9.17157 14.8284M9.17157 14.8284C8.44772 14.1046 8 13.1046 8 12C8 10.8954 8.44772 9.89543 9.17157 9.17157M6 6L9.17157 9.17157M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="#666" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
								</svg>
							</a>
						</li>
						<li>
							<a href="https://wordpress.org/plugins/cf7-google-sheets-connector/#developers" class="d-flex justify-center align-center bg-white" title="Changelog" target="_blank">
								<svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path fill-rule="evenodd" clip-rule="evenodd" d="M19.4423 2.60315C19.7838 2.77155 20 3.11926 20 3.50001V7.46482L20.8906 8.05856C22.2084 8.93711 23 10.4162 23 12C23 13.5838 22.2084 15.0629 20.8906 15.9415L20 16.5352V19.5C20 19.8774 19.7876 20.2226 19.4507 20.3927C19.1139 20.5627 18.7101 20.5287 18.4064 20.3048C18.4064 20.3048 18.4064 20.3048 18.4064 20.3048C18.4064 20.3048 18.4064 20.3047 18.4063 20.3047L18.4063 20.3047L18.4054 20.3041L18.4012 20.301L18.3831 20.2876L18.3098 20.2344C18.2453 20.1876 18.1506 20.1194 18.0313 20.0349C17.7926 19.8657 17.4571 19.6319 17.0712 19.3747C16.2873 18.8523 15.3391 18.2625 14.5765 17.9059C13.1878 17.2566 11.7408 16.7733 10.6322 16.4513C10.1547 16.3125 9.74373 16.2048 9.43209 16.1275C8.63487 17.4199 8.92926 19.1226 10.1451 20.0682C11.3765 21.026 10.6993 23 9.13919 23H6C5.59997 23 5.23843 22.7616 5.08085 22.3939L4.69925 21.5035C3.87957 19.5909 3.83735 17.4342 4.58156 15.491L4.62696 15.3725C2.51738 14.8594 1 12.9633 1 10.7539C1 8.12839 3.12838 6.00001 5.75387 6.00001H9C9.02628 6.00001 9.05256 6.00104 9.07876 6.00311C9.07893 6.00313 9.0791 6.00314 9.07927 6.00315C9.07943 6.00317 9.07959 6.00318 9.07974 6.00319C9.07975 6.00319 9.07975 6.00319 9.07976 6.00319L9.08164 6.00333L9.10038 6.00461C9.1185 6.00579 9.14773 6.00754 9.18726 6.00945C9.26636 6.01329 9.38647 6.01774 9.54125 6.01952C9.85127 6.02309 10.2977 6.01586 10.8305 5.97193C11.9038 5.8834 13.2878 5.64894 14.6043 5.08164C15.3591 4.75639 16.2945 4.1762 17.0738 3.64858C17.456 3.38981 17.7874 3.15279 18.023 2.98068C18.1406 2.89473 18.2339 2.82527 18.2972 2.77773L18.369 2.72362L18.3866 2.71022L18.3906 2.70716L18.3913 2.70662L18.3913 2.70658L18.3914 2.70655C18.6934 2.47485 19.1009 2.43476 19.4423 2.60315ZM8 8.00001H5.75387C4.23295 8.00001 3 9.23295 3 10.7539C3 12.1213 4.00336 13.2816 5.35646 13.4789L6.14107 13.5933L8 13.8515V8.00001ZM10 14.2079C10.3214 14.2886 10.7267 14.396 11.1901 14.5306C12.3557 14.8692 13.9087 15.3859 15.4235 16.0941C16.2629 16.4866 17.2274 17.082 18 17.5909V16V8.00001V5.43572C17.2289 5.9496 16.2582 6.54673 15.3957 6.91837C13.8127 7.6005 12.1967 7.86604 10.9949 7.96516C10.6233 7.9958 10.2876 8.01083 10 8.0169V14.2079ZM7.36806 15.7829L6.64962 15.6832L6.44927 16.2063C5.89112 17.6637 5.92278 19.2812 6.53754 20.7157L6.6594 21H8.22938C6.9697 19.5684 6.63958 17.5343 7.36806 15.7829ZM20 14.1152C20.6294 13.5985 21 12.8238 21 12C21 11.1762 20.6294 10.4015 20 9.88478V14.1152Z" fill="#666" />
								</svg>
							</a>
						</li>
					</ul>
				</div>
			</div>
		</div>
	</div>
	<!--End Gsheet-Header Section-->
	<!--Start Breadcrumb Section-->
	<div class="breadcrumb-wrapper pt-13 pb-13 text-uppercase fw-500 text-gray">
		<div class="container">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcf7-google-sheet-config' ) ); ?>" class="text-primary text-decoration-none">
				<?php echo esc_html__( 'Google Sheet', 'cf7-google-sheets-connector' ); ?>
			</a>
			<span>/</span>
			<span><?php echo esc_html( $gscf7_active_tab_name ); ?></span>
		</div>
	</div>
	<!--End Breadcrumb Section-->
	<!--Start Tab Panel Section-->
	<?php
	$tabs = array(
		'dashboard'         => esc_html__( 'Dashboard', 'cf7-google-sheets-connector' ),
		'integration'       => esc_html__( 'Integration', 'cf7-google-sheets-connector' ),
		'settings'          => esc_html__( 'Settings', 'cf7-google-sheets-connector' ),
		'cf7_db'            => esc_html__( 'CF7 Database', 'cf7-google-sheets-connector' ),
		'gs-integrate-info' => esc_html__( 'System Status', 'cf7-google-sheets-connector' ),
		'extension'         => esc_html__( 'Extensions', 'cf7-google-sheets-connector' ),
	);

	echo '<div class="d-none">
        <div class="gscf7-free-selected-method"
        data-value="' . esc_attr( $gscf7_selected_method ) . '">'
		. esc_html( $gscf7_selected_method ) .
		'</div>
        </div>';
	echo '<div class="nav-tab-wrapper d-flex justify-flex-start w-100 m-0">';
	foreach ( $tabs as $tab => $gscf7_name ) {
		$gscf7_class = ( $tab == $gscf7_active_tab ) ? ' nav-tab-active' : '';
		echo '<a class="nav-tab text-decoration-none fw-500 text-center' . esc_attr( $gscf7_class ) . '" href="' .
			esc_url(
				add_query_arg(
					array(
						'page' => 'wpcf7-google-sheet-config',
						'tab'  => $tab,
					),
					admin_url( 'admin.php' )
				)
			) . '">' .
			esc_html( $gscf7_name ) .
			'</a>';
	}
	echo '</div><div class="wrap-gsc">';
	switch ( $gscf7_active_tab ) {
		case 'integration':
			echo '<div class="wrap w-100 m-0"><div class="inner-wrap  w-100 bg-white p-40">';
			$gscf7_intigrate = new Gs_Connector_Free_Init();
			$gscf7_intigrate->google_sheet_config();
			break;
			echo '</div></div>';
		case 'cf7_db':
			$gscf7_cf7db = new GS_CF7DB();
			$gscf7_cf7db->show_enable_disable_set();
			break;
		case 'dashboard':
			include GS_CONNECTOR_PATH . 'includes/pages/dashboard.php';
			break;
		case 'settings':
			include GS_CONNECTOR_PATH . 'includes/pages/gs-cf7-sheet-setting.php';
			break;
		case 'gs-integrate-info':
			include GS_CONNECTOR_PATH . 'includes/pages/gs-integrate-info.php';
			break;
		case 'extension':
			include GS_CONNECTOR_PATH . 'includes/pages/extensions/extensions.php';
			break;
	}
	?>
</div>
</div>
<!--End Tab Panel Section-->

<!--Start Common Pro Feature-->
<?php if ( $gscf7_active_tab != 'dashboard' ) { ?>
	<div class="gscf7-free">
		<div class="common-section-gsc-promo-wrapper">
			<!-- Left Image Area -->
			<div class="d-flex flex-wrap gap-50 align-center">
				<div class="cf7-to-gsheet">
					<img src="<?php echo esc_url( GS_CONNECTOR_URL ); ?>/assets/img/pro-cf7-gsc.webp">
				</div>
				<!-- <div class="common-section-gsc-promo-left">
				<div class="common-section-gsc-card gsc-card-1">
					<img src="< ?php echo esc_url(GS_CONNECTOR_URL); ?>/assets/img/edd-razpay.webp">
				</div>
				<div class="common-section-gsc-card gsc-card-2">
					<svg width="54" height="54" viewBox="0 0 54 54" fill="none" xmlns="http://www.w3.org/2000/svg">
						<circle cx="27" cy="27" r="27" fill="#223139" />
						<g clip-path="url(#clip0_68_20011)">
							<path d="M5.58618 26.6897C5.58618 35.2954 10.5592 42.6942 17.8355 46.2099L7.47069 17.8216C6.2667 20.5503 5.58618 23.5413 5.58618 26.6897ZM41.9676 25.5878C41.9676 22.9116 41.0253 21.0225 40.1878 19.6058C39.0885 17.8216 38.0415 16.2999 38.0415 14.5158C38.0415 12.5218 39.5596 10.6852 41.7058 10.6852C41.8105 10.6852 41.9152 10.6852 41.9676 10.6852C38.1462 7.117 32.9638 4.96558 27.3103 4.96558C19.72 4.96558 13.1242 8.37637 9.25049 14.2534C10.7686 14.2534 16.3697 14.2534 16.3697 14.2534C17.5214 14.201 17.6784 16.1425 16.5268 16.2474C16.5268 16.2474 15.3751 16.4049 14.0664 16.4573L21.9709 39.9656L26.7345 25.6927L23.2796 16.5098C22.1279 16.4573 20.9763 16.2999 20.9763 16.2999C19.8247 16.2474 19.9293 14.201 21.1333 14.2534H32.6497C33.8014 14.201 33.9584 16.1425 32.8068 16.2999C32.8068 16.2999 31.6551 16.4573 30.2941 16.5098L38.1462 39.8606L40.2925 32.6192C41.4441 29.7857 41.9676 27.4243 41.9676 25.5878ZM27.7291 28.5788L21.1857 47.5218C23.1225 48.099 25.2164 48.4138 27.3103 48.4138C29.823 48.4138 32.2833 47.9941 34.5343 47.1545C34.4819 47.0495 34.4296 46.9446 34.3772 46.8396L27.7291 28.5788ZM46.4171 16.2474C46.5218 16.9296 46.5741 17.6642 46.5741 18.5038C46.5741 20.7077 46.1554 23.174 44.899 26.2699L38.2509 45.4753C44.6896 41.6972 49.0345 34.7182 49.0345 26.6897C49.0345 22.9116 48.0922 19.3434 46.4171 16.2474Z" fill="white" />
						</g>
						<defs>
							<clipPath id="clip0_68_20011">
								<rect width="43.4483" height="43.4483" fill="white" transform="translate(5.58618 4.96558)" />
							</clipPath>
						</defs>
					</svg>
				</div>
				<div class="common-section-gsc-card gsc-card-3">
					<img src="< ?php echo esc_url(GS_CONNECTOR_URL); ?>/assets/img/woocommerce.jpg">
				</div>
				<div class="common-section-gsc-card gsc-card-4">
					<svg width="50" height="50" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M19.9013 3.58832C15.9467 4.60444 12.3687 6.6743 9.54398 9.5345C6.45561 12.6205 4.76077 15.6312 3.70619 19.7709C2.76461 23.459 2.76461 26.545 3.70619 30.2332C4.76077 34.373 6.45561 37.3836 9.54398 40.4696C16.2857 47.2814 26.191 48.8996 34.929 44.6846C38.394 42.9912 43.1018 38.2868 44.6838 34.9374C47.772 28.4644 47.772 21.4644 44.6838 15.0667C43.0266 11.6796 38.3186 6.97538 34.929 5.31948C30.2588 3.06144 24.421 2.38404 19.9013 3.58832ZM30.1834 5.05604C33.7238 5.95926 36.8122 7.7657 39.5238 10.4753C43.5914 14.5398 45.5124 19.1688 45.5124 24.8516C45.5124 28.0504 44.872 31.9644 44.3824 31.9644C43.3656 31.9644 37.5278 27.072 32.4432 21.916C26.7184 16.1581 26.2288 15.7441 24.7222 15.6312C22.7638 15.443 21.9728 15.8194 21.1818 17.3624C20.165 19.2817 13.5739 25.83 9.61931 28.803C7.58551 30.346 5.77767 31.588 5.58934 31.588C4.49713 31.588 4.12049 23.3086 5.02441 19.7333C6.79456 12.9592 12.8207 6.90012 19.5624 5.09368C22.2364 4.37864 27.4716 4.341 30.1834 5.05604ZM30.4846 21.9538C33.2342 24.8138 34.7784 26.6956 34.2886 26.545C33.2718 26.2064 33.2718 26.3568 34.251 28.2386C34.6654 29.1042 34.929 29.8568 34.8536 29.9698C34.6276 30.158 32.4808 28.088 30.1834 25.4536C27.773 22.744 27.434 22.9698 27.434 27.1848C27.434 30.2332 27.321 30.7976 26.6808 31.3998C25.9652 32.0396 25.8898 32.0396 24.308 30.3084C22.4624 28.3138 21.9728 28.3138 20.843 30.346C19.7884 32.303 18.9598 32.4912 19.2987 30.6848C19.6 29.0288 18.9974 28.8784 17.0013 30.1202C16.2857 30.5718 15.6077 30.8354 15.4948 30.6848C15.3818 30.5718 15.8337 29.2924 16.474 27.8246C17.1519 26.3568 17.6416 24.9268 17.5286 24.6634C17.4156 24.4 18.2065 23.158 19.2987 21.8784C20.391 20.6366 21.6716 19.0559 22.0858 18.3408C22.8014 17.2118 23.065 17.0613 24.3832 17.0613C25.7768 17.0989 26.1158 17.3624 30.4846 21.9538Z" fill="url(#paint0_linear_68_17590)" />
						<defs>
							<linearGradient id="paint0_linear_68_17590" x1="25" y1="47" x2="25" y2="3" gradientUnits="userSpaceOnUse">
								<stop stop-color="#2D93FC" />
								<stop offset="1" stop-color="#5147FB" />
							</linearGradient>
						</defs>
					</svg>
				</div>
			</div> -->
				<!-- Right Content -->
				<div class="common-section-gsc-promo-content">
					<div class="common-section-heading"><?php echo esc_html( __( 'Advanced Tools for Easy Spreadsheet Control', 'cf7-google-sheets-connector' ) ); ?></div>
					<p class="mb-0"><?php echo esc_html( __( 'Improve your sheet management with smart automation and flexible customization features.', 'cf7-google-sheets-connector' ) ); ?></p>
					<div class="d-flex gap-40">
						<ul>
							<li><?php echo esc_html__( 'Google Sheets API v4', 'cf7-google-sheets-connector' ); ?></li>
							<li><?php echo esc_html__( 'One-Click Authentication', 'cf7-google-sheets-connector' ); ?></li>
							<li><?php echo esc_html__( 'Authenticated Email Display', 'cf7-google-sheets-connector' ); ?></li>
							<li><?php echo esc_html__( 'Click & Fetch Automation', 'cf7-google-sheets-connector' ); ?></li>
							<li><?php echo esc_html__( 'Create New Spreadsheet', 'cf7-google-sheets-connector' ); ?></li>
							<li><?php echo esc_html__( 'Manual Sheet / Tab Name', 'cf7-google-sheets-connector' ); ?></li>

						</ul>

						<ul>
							<li><?php echo esc_html__( 'Automated Sheet & Tab', 'cf7-google-sheets-connector' ); ?></li>
							<li><?php echo esc_html__( 'Multiple Feeds to Sheets', 'cf7-google-sheets-connector' ); ?></li>
							<li><?php echo esc_html__( 'Drag-and-Drop Column Order', 'cf7-google-sheets-connector' ); ?></li>
							<li><?php echo esc_html__( 'Headers On / Off + Rename', 'cf7-google-sheets-connector' ); ?></li>
							<li><?php echo esc_html__( 'Image / PDF Attachment Link', 'cf7-google-sheets-connector' ); ?></li>
							<li><?php echo esc_html__( 'Freeze & Color Headers', 'cf7-google-sheets-connector' ); ?></li>
						</ul>

						<ul>
							<li><?php echo esc_html__( 'Sync Past Entries', 'cf7-google-sheets-connector' ); ?></li>
							<li><?php echo esc_html__( 'Role Management', 'cf7-google-sheets-connector' ); ?></li>
							<li><?php echo esc_html__( 'Quick Configuration', 'cf7-google-sheets-connector' ); ?></li>
							<li><?php echo esc_html__( 'Multi-Language Support', 'cf7-google-sheets-connector' ); ?></li>
							<li><?php echo esc_html__( 'Multi-Site Support', 'cf7-google-sheets-connector' ); ?></li>
							<li><?php echo esc_html__( 'Latest WP & PHP Support', 'cf7-google-sheets-connector' ); ?></li>
						</ul>
					</div>
					<div class="mt-30 d-flex align-center gap-20">
						<a href="https://www.gsheetconnector.com/docs/cf7-gsheetconnector" target="_blank" class="btn btn-primary link-hover-white text-decoration-none">View Docs</a>
						<a class="text-decoration-none free-pro-btn" href="https://www.gsheetconnector.com/cf7-google-sheet-connector-pro#compare" target="_blank">Free vs Pro</a>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php } ?>
<!--End Common Pro Feature-->
<!--Message Slider-->
<?php require GS_CONNECTOR_PATH . 'includes/pages/admin-footer.php'; ?>
