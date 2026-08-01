<?php if ( ! defined( 'ABSPATH' ) ) {

	exit; // Exit if accessed directly

}

?>

<div class="api_service_setting_cf7 oauth-method row justify-between shadow-box mt-40 p-30" id="gs-cf7-service-setting">

	<div class="col-7">

		<div class="service-method mr-20">

			<div class="heading mt-0"><?php echo esc_html( __( 'Google Sheets Integration', 'cf7-google-sheets-connector' ) ); ?>

				<span class="badge"><?php echo esc_html( __( 'Service Account', 'cf7-google-sheets-connector' ) ); ?></span>

			</div>

			<div class="inside">

				<?php if ( ! $gscf7_service_valid ) : ?>

					<p><?php echo esc_html( __( 'Connect CF7 to Google Sheets using a secure service account. Upload your JSON credentials and share your spreadsheet with the service account email to enable syncing.', 'cf7-google-sheets-connector' ) ); ?>

					</p>

					<div class="heading mt-30"><?php echo esc_html( __( 'Upload or paste your JSON credentials', 'cf7-google-sheets-connector' ) ); ?></div>

					<div class="gs-service-json-box">

						<div class="gsc-json-wrapper">

							<label for="gs_cf7_service_json" class="gs-label gsc-json-label">

								<?php echo esc_html__( 'Service Account JSON File', 'cf7-google-sheets-connector' ); ?>

							</label>

							<textarea

								name="gs_cf7_service_json"

								id="gs_cf7_service_json"

								rows="10"

								class="gsc-service-json"

								placeholder="<?php echo esc_attr__( 'Paste your service account JSON credentials here...', 'cf7-google-sheets-connector' ); ?>"><?php echo esc_textarea( $gscf7_service_json ); ?></textarea>



							<div class="gsc-json-help">

								<?php echo esc_html__( 'This JSON file is used for authentication and is stored securely.', 'cf7-google-sheets-connector' ); ?>

							</div>

						</div>

						<div class="mt-30 mb-20">

							<div class="gsc-upload-wrapper">

								<input

									type="file"

									id="gs_cf7_upload_json"

									accept=".json" />

								<label for="gs_cf7_upload_json" class="gsc-upload-box">

									<span class="gsc-upload-icon">

										<svg viewBox="0 0 24 24" fill="none" width="22" height="22">

											<path d="M12 16V4" stroke="currentColor" stroke-width="2" stroke-linecap="round" />

											<path d="M8 8L12 4L16 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />

											<path d="M4 16V18C4 19.1046 4.89543 20 6 20H18C19.1046 20 20 19.1046 20 18V16"

												stroke="currentColor" stroke-width="2" stroke-linecap="round" />

										</svg>

									</span>



									<div class="gsc-upload-text">

										<strong><?php echo esc_html__( 'Upload JSON File', 'cf7-google-sheets-connector' ); ?></strong>

										<span><?php echo esc_html__( 'Click to select your .json file', 'cf7-google-sheets-connector' ); ?></span>

									</div>

								</label>



								<small class="gsc-upload-help">

									<?php echo esc_html__( 'Choose your service account JSON file. Its contents will be auto-filled above.', 'cf7-google-sheets-connector' ); ?>

								</small>

							</div>

						</div>

						<input type="button"

							class="btn btn-primary mt-20 common-disable"

							id="gs_cf7_save_service_json"

							value="<?php echo esc_attr__( 'Save', 'cf7-google-sheets-connector' ); ?>" />

						<span class="loading-sign-service-auth" aria-live="polite"></span>
						<div id="gs-validation-message-auth"></div>

					</div>





				<?php else : ?>

					<p><?php echo esc_html( __( 'To enable syncing, share your Google Sheet with the service account email connected to this plugin and grant Editor access.', 'cf7-google-sheets-connector' ) ); ?>

					</p>

					<div class="gsc-service-success">

						<div class="gsc-connected-box  d-flex justify-between flex-wrap gap-10 align-center mt-15">

							<div class="gsc-connected-left d-flex">

								<div class="gsc-connected-label">

									<?php echo esc_html( __( 'Service Account Email', 'cf7-google-sheets-connector' ) ); ?>

									<span class="tooltip" data-tooltip="<?php echo esc_html( __( 'Your Google Sheet must be shared with this service account email and granted Editor access to enable data synchronization.', 'cf7-google-sheets-connector' ) ); ?>" data-tooltip-pos="right" data-tooltip-length="medium">

										<i class="fa-solid fa-circle-question help-icon"></i>

									</span>

								</div>

								<div class="connected-account gsc-connected-email">

									<?php echo esc_html( $gscf7_service_email ); ?>

									<a href="javascript:void(0);" class="ml-5 mt-5" data-email="<?php echo esc_html( $gscf7_service_email ); ?>">

										<svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">

											<path d="M19.53 8L14 2.47C13.8595 2.32931 13.6688 2.25018 13.47 2.25H11C10.2707 2.25 9.57118 2.53973 9.05546 3.05546C8.53973 3.57118 8.25 4.27065 8.25 5V6.25H7C6.27065 6.25 5.57118 6.53973 5.05546 7.05546C4.53973 7.57118 4.25 8.27065 4.25 9V19C4.25 19.7293 4.53973 20.4288 5.05546 20.9445C5.57118 21.4603 6.27065 21.75 7 21.75H14C14.7293 21.75 15.4288 21.4603 15.9445 20.9445C16.4603 20.4288 16.75 19.7293 16.75 19V17.75H17C17.7293 17.75 18.4288 17.4603 18.9445 16.9445C19.4603 16.4288 19.75 15.7293 19.75 15V8.5C19.7421 8.3116 19.6636 8.13309 19.53 8ZM14.25 4.81L17.19 7.75H14.25V4.81ZM15.25 19C15.25 19.3315 15.1183 19.6495 14.8839 19.8839C14.6495 20.1183 14.3315 20.25 14 20.25H7C6.66848 20.25 6.35054 20.1183 6.11612 19.8839C5.8817 19.6495 5.75 19.3315 5.75 19V9C5.75 8.66848 5.8817 8.35054 6.11612 8.11612C6.35054 7.8817 6.66848 7.75 7 7.75H8.25V15C8.25 15.7293 8.53973 16.4288 9.05546 16.9445C9.57118 17.4603 10.2707 17.75 11 17.75H15.25V19ZM17 16.25H11C10.6685 16.25 10.3505 16.1183 10.1161 15.8839C9.8817 15.6495 9.75 15.3315 9.75 15V5C9.75 4.66848 9.8817 4.35054 10.1161 4.11612C10.3505 3.8817 10.6685 3.75 11 3.75H12.75V8.5C12.7526 8.69811 12.8324 8.88737 12.9725 9.02747C13.1126 9.16756 13.3019 9.24741 13.5 9.25H18.25V15C18.25 15.3315 18.1183 15.6495 17.8839 15.8839C17.6495 16.1183 17.3315 16.25 17 16.25Z" fill="#199436"></path>

										</svg>

									</a>

									<div class="gsc-copy-msg d-none"><?php echo esc_html__( 'Copied sucessfully', 'cf7-google-sheets-connector' ); ?></div>

								</div>

							</div>



							<div class="gsc-connected-status d-flex align-center">

								<span class="status-dot"></span>

								<?php echo esc_html__( 'Share sheet for sync', 'cf7-google-sheets-connector' ); ?>

							</div>

						</div>

						<input type="button"

							class="btn deactivate-btn mt-30"

							id="gs_cf7_deactivate_service_auth"

							value="<?php echo esc_attr__( 'Deactivate', 'cf7-google-sheets-connector' ); ?>">

						<span class="loading-sign-service-auth"></span>
						<div id="gs-validation-message-auth"></div>

					</div>

				<?php endif; ?>

			</div>

		</div>

	</div>

	<div id="gs-confirm-service-popup" class="gs-popup-overlay d-none">

		<div class="gs-popup text-center">



			<div class="gsc-modal-icon">

				<svg width="30px" height="30px" viewBox="-0.5 0 25 25" fill="none" xmlns="http://www.w3.org/2000/svg">

					<path d="M18.2202 21.25H5.78015C5.14217 21.2775 4.50834 21.1347 3.94373 20.8364C3.37911 20.5381 2.90402 20.095 2.56714 19.5526C2.23026 19.0101 2.04372 18.3877 2.02667 17.7494C2.00963 17.111 2.1627 16.4797 2.47015 15.92L8.69013 5.10999C9.03495 4.54078 9.52077 4.07013 10.1006 3.74347C10.6804 3.41681 11.3346 3.24518 12.0001 3.24518C12.6656 3.24518 13.3199 3.41681 13.8997 3.74347C14.4795 4.07013 14.9654 4.54078 15.3102 5.10999L21.5302 15.92C21.8376 16.4797 21.9907 17.111 21.9736 17.7494C21.9566 18.3877 21.7701 19.0101 21.4332 19.5526C21.0963 20.095 20.6211 20.5381 20.0565 20.8364C19.4919 21.1347 18.8581 21.2775 18.2202 21.25V21.25Z" stroke="#d97706" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>

					<path d="M10.8809 17.15C10.8809 17.0021 10.9102 16.8556 10.9671 16.7191C11.024 16.5825 11.1074 16.4586 11.2125 16.3545C11.3175 16.2504 11.4422 16.1681 11.5792 16.1124C11.7163 16.0567 11.8629 16.0287 12.0109 16.03C12.2291 16.034 12.4413 16.1021 12.621 16.226C12.8006 16.3499 12.9398 16.5241 13.0211 16.7266C13.1023 16.9292 13.122 17.1512 13.0778 17.3649C13.0335 17.5786 12.9272 17.7745 12.7722 17.9282C12.6172 18.0818 12.4203 18.1863 12.2062 18.2287C11.9921 18.2711 11.7703 18.2494 11.5685 18.1663C11.3666 18.0833 11.1938 17.9426 11.0715 17.7618C10.9492 17.5811 10.8829 17.3683 10.8809 17.15ZM11.2409 14.42L11.1009 9.20001C11.0876 9.07453 11.1008 8.94766 11.1398 8.82764C11.1787 8.70761 11.2424 8.5971 11.3268 8.5033C11.4112 8.40949 11.5144 8.33449 11.6296 8.28314C11.7449 8.2318 11.8697 8.20526 11.9959 8.20526C12.1221 8.20526 12.2469 8.2318 12.3621 8.28314C12.4774 8.33449 12.5805 8.40949 12.6649 8.5033C12.7493 8.5971 12.8131 8.70761 12.852 8.82764C12.8909 8.94766 12.9042 9.07453 12.8909 9.20001L12.7609 14.42C12.7609 14.6215 12.6808 14.8149 12.5383 14.9574C12.3957 15.0999 12.2024 15.18 12.0009 15.18C11.7993 15.18 11.606 15.0999 11.4635 14.9574C11.321 14.8149 11.2409 14.6215 11.2409 14.42Z" fill="#d97706"></path>

				</svg>

			</div>



			<div class="gsc-modal-title">

				<?php echo esc_html__( 'Deactivate Integration', 'cf7-google-sheets-connector' ); ?>

			</div>



			<p class="gsc-modal-text">

				<?php echo esc_html__( 'Are you sure you want to deactivate Google Sheets integration? This will stop syncing your form entries.', 'cf7-google-sheets-connector' ); ?>

			</p>



			<div class="popup-actions d-flex justify-center gap-10">



				<button type="button"

					class="btn deactivate-btn"

					id="gs-service-popup-cancel">

					<?php echo esc_html__( 'Cancel', 'cf7-google-sheets-connector' ); ?>

				</button>



				<button type="button"

					class="btn btn-primary"

					id="gs-service-popup-confirm">

					<?php echo esc_html__( 'Deactivate', 'cf7-google-sheets-connector' ); ?>

				</button>



			</div>



		</div>

	</div>

	<?php

	$gscf7_show_service_account_slider = false;

	$gscf7_auth_method = get_option( 'gs_cf7_auth_method' );

	if ( $gscf7_auth_method == 'cf7_service' ) {

		$gscf7_show_service_account_slider = true;
	}

	?>

	<?php if ( $gscf7_service_valid != true ) { ?>

		<div class="col-5">

			<div class="ml-20 service-account-method">

				<div class="step-guide-col">

					<div class="heading mt-0"> <?php echo esc_html( __( 'Connection Guide', 'cf7-google-sheets-connector' ) ); ?>

						<span class="badge"><?php echo esc_html( __( 'Step-by-Step', 'cf7-google-sheets-connector' ) ); ?></span>

					</div>

					<p><?php echo esc_html__( 'Follow these step-by-step instructions to create and configure your Google service account and securely connect it with CF7.', 'cf7-google-sheets-connector' ); ?></p>

				</div>



				<div class="gsc-slider-wrapper mt-30">

					<div class="gsc-slider">



						<div class="gsc-slide">



							<div class="gsc-slider-headers fw-600 mb-10 text-dark"><?php esc_html_e( 'Step-1 Open Google Cloud > Service Accounts', 'cf7-google-sheets-connector' ); ?>

								<a href="#" class="i-help" hover-tooltip="<?php echo esc_html__( 'Open your Google Cloud project and go to IAM & Admin > Service Accounts to manage service identities.', 'cf7-google-sheets-connector' ); ?>">

									<svg width="800px" height="800px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">

										<path fill-rule="evenodd" clip-rule="evenodd" d="M12 19.5C16.1421 19.5 19.5 16.1421 19.5 12C19.5 7.85786 16.1421 4.5 12 4.5C7.85786 4.5 4.5 7.85786 4.5 12C4.5 16.1421 7.85786 19.5 12 19.5ZM12 21C16.9706 21 21 16.9706 21 12C21 7.02944 16.9706 3 12 3C7.02944 3 3 7.02944 3 12C3 16.9706 7.02944 21 12 21ZM12.75 15V16.5H11.25V15H12.75ZM10.5 10.4318C10.5 9.66263 11.1497 9 12 9C12.8503 9 13.5 9.66263 13.5 10.4318C13.5 10.739 13.3151 11.1031 12.9076 11.5159C12.5126 11.9161 12.0104 12.2593 11.5928 12.5292L11.25 12.7509V14.25H12.75V13.5623C13.1312 13.303 13.5828 12.9671 13.9752 12.5696C14.4818 12.0564 15 11.3296 15 10.4318C15 8.79103 13.6349 7.5 12 7.5C10.3651 7.5 9 8.79103 9 10.4318H10.5Z" fill="#080341" />

									</svg>

								</a>

							</div>

							<a href="<?php echo esc_url( 'https://console.cloud.google.com/iam-admin/serviceaccounts' ); ?>"

								target="_blank"

								rel="noopener noreferrer"

								class="link">

								<?php echo esc_html__( 'Navigate to Service Accounts', 'cf7-google-sheets-connector' ); ?>

							</a>







							<img src="<?php echo esc_url( GS_CONNECTOR_URL ); ?>/assets/img/service-step1.png" alt="" />





						</div>



						<div class="gsc-slide">

							<div class="gsc-slider-headers fw-600 mb-10 text-dark"><?php esc_html_e( 'Step-2 Create a New Service Account', 'cf7-google-sheets-connector' ); ?> <a href="#" class="i-help" hover-tooltip="<?php echo esc_html__( 'Click Create Service Account and enter a meaningful name like ex.CF7 Forms GSheet.', 'cf7-google-sheets-connector' ); ?>">

									<svg width="800px" height="800px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">

										<path fill-rule="evenodd" clip-rule="evenodd" d="M12 19.5C16.1421 19.5 19.5 16.1421 19.5 12C19.5 7.85786 16.1421 4.5 12 4.5C7.85786 4.5 4.5 7.85786 4.5 12C4.5 16.1421 7.85786 19.5 12 19.5ZM12 21C16.9706 21 21 16.9706 21 12C21 7.02944 16.9706 3 12 3C7.02944 3 3 7.02944 3 12C3 16.9706 7.02944 21 12 21ZM12.75 15V16.5H11.25V15H12.75ZM10.5 10.4318C10.5 9.66263 11.1497 9 12 9C12.8503 9 13.5 9.66263 13.5 10.4318C13.5 10.739 13.3151 11.1031 12.9076 11.5159C12.5126 11.9161 12.0104 12.2593 11.5928 12.5292L11.25 12.7509V14.25H12.75V13.5623C13.1312 13.303 13.5828 12.9671 13.9752 12.5696C14.4818 12.0564 15 11.3296 15 10.4318C15 8.79103 13.6349 7.5 12 7.5C10.3651 7.5 9 8.79103 9 10.4318H10.5Z" fill="#080341" />

									</svg></a>

							</div>

							<a href="<?php echo esc_url( 'https://console.cloud.google.com/iam-admin/serviceaccounts/create' ); ?>"

								target="_blank"

								rel="noopener noreferrer"

								class="link">

								<?php echo esc_html__( 'Create a Service Account', 'cf7-google-sheets-connector' ); ?>

							</a>



							<img src="<?php echo esc_url( GS_CONNECTOR_URL ); ?>/assets/img/service-step2.png" alt="" />

						</div>



						<div class="gsc-slide">

							<div class="gsc-slider-headers fw-600 mb-10 text-dark"><?php esc_html_e( 'Step-3 Fill Service Account Details', 'cf7-google-sheets-connector' ); ?> <a href="#" class="i-help" hover-tooltip="<?php echo esc_html__( 'Add Service Account Name & ID', 'cf7-google-sheets-connector' ); ?>">

									<svg width="800px" height="800px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">

										<path fill-rule="evenodd" clip-rule="evenodd" d="M12 19.5C16.1421 19.5 19.5 16.1421 19.5 12C19.5 7.85786 16.1421 4.5 12 4.5C7.85786 4.5 4.5 7.85786 4.5 12C4.5 16.1421 7.85786 19.5 12 19.5ZM12 21C16.9706 21 21 16.9706 21 12C21 7.02944 16.9706 3 12 3C7.02944 3 3 7.02944 3 12C3 16.9706 7.02944 21 12 21ZM12.75 15V16.5H11.25V15H12.75ZM10.5 10.4318C10.5 9.66263 11.1497 9 12 9C12.8503 9 13.5 9.66263 13.5 10.4318C13.5 10.739 13.3151 11.1031 12.9076 11.5159C12.5126 11.9161 12.0104 12.2593 11.5928 12.5292L11.25 12.7509V14.25H12.75V13.5623C13.1312 13.303 13.5828 12.9671 13.9752 12.5696C14.4818 12.0564 15 11.3296 15 10.4318C15 8.79103 13.6349 7.5 12 7.5C10.3651 7.5 9 8.79103 9 10.4318H10.5Z" fill="#080341" />

									</svg></a>

							</div>

							<a href="<?php echo esc_url( 'https://console.cloud.google.com/iam-admin/serviceaccounts' ); ?>"

								target="_blank"

								rel="noopener noreferrer"

								class="link">

								<?php echo esc_html__( 'Google Console', 'cf7-google-sheets-connector' ); ?>

							</a>







							<img src="<?php echo esc_url( GS_CONNECTOR_URL ); ?>/assets/img/service-step3.png" alt="" />

						</div>



						<div class="gsc-slide">

							<div class="gsc-slider-headers fw-600 mb-10 text-dark"><?php esc_html_e( 'Step-4 Open Service Account Keys Section', 'cf7-google-sheets-connector' ); ?> <a href="#" class="i-help" hover-tooltip="<?php echo esc_html__( 'Open the created service account and switch to the Keys tab to generate credentials.', 'cf7-google-sheets-connector' ); ?>">

									<svg width="800px" height="800px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">

										<path fill-rule="evenodd" clip-rule="evenodd" d="M12 19.5C16.1421 19.5 19.5 16.1421 19.5 12C19.5 7.85786 16.1421 4.5 12 4.5C7.85786 4.5 4.5 7.85786 4.5 12C4.5 16.1421 7.85786 19.5 12 19.5ZM12 21C16.9706 21 21 16.9706 21 12C21 7.02944 16.9706 3 12 3C7.02944 3 3 7.02944 3 12C3 16.9706 7.02944 21 12 21ZM12.75 15V16.5H11.25V15H12.75ZM10.5 10.4318C10.5 9.66263 11.1497 9 12 9C12.8503 9 13.5 9.66263 13.5 10.4318C13.5 10.739 13.3151 11.1031 12.9076 11.5159C12.5126 11.9161 12.0104 12.2593 11.5928 12.5292L11.25 12.7509V14.25H12.75V13.5623C13.1312 13.303 13.5828 12.9671 13.9752 12.5696C14.4818 12.0564 15 11.3296 15 10.4318C15 8.79103 13.6349 7.5 12 7.5C10.3651 7.5 9 8.79103 9 10.4318H10.5Z" fill="#080341" />

									</svg></a>

							</div>

							<a href="<?php echo esc_url( 'https://console.cloud.google.com/iam-admin/serviceaccounts' ); ?>"

								target="_blank"

								rel="noopener noreferrer"

								class="link">

								<?php echo esc_html__( 'Go to Service Account → Keys', 'cf7-google-sheets-connector' ); ?>

							</a>





							<img src="<?php echo esc_url( GS_CONNECTOR_URL ); ?>/assets/img/service-step4.png" alt="" />

						</div>









						<div class="gsc-slide">

							<div class="gsc-slider-headers fw-600 mb-10 text-dark"><?php esc_html_e( 'Step-5 Download JSON Key File', 'cf7-google-sheets-connector' ); ?> <a href="#" class="i-help" hover-tooltip="<?php echo esc_html__( 'Select JSON format and download the key file securely to your computer.', 'cf7-google-sheets-connector' ); ?>">

									<svg width="800px" height="800px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">

										<path fill-rule="evenodd" clip-rule="evenodd" d="M12 19.5C16.1421 19.5 19.5 16.1421 19.5 12C19.5 7.85786 16.1421 4.5 12 4.5C7.85786 4.5 4.5 7.85786 4.5 12C4.5 16.1421 7.85786 19.5 12 19.5ZM12 21C16.9706 21 21 16.9706 21 12C21 7.02944 16.9706 3 12 3C7.02944 3 3 7.02944 3 12C3 16.9706 7.02944 21 12 21ZM12.75 15V16.5H11.25V15H12.75ZM10.5 10.4318C10.5 9.66263 11.1497 9 12 9C12.8503 9 13.5 9.66263 13.5 10.4318C13.5 10.739 13.3151 11.1031 12.9076 11.5159C12.5126 11.9161 12.0104 12.2593 11.5928 12.5292L11.25 12.7509V14.25H12.75V13.5623C13.1312 13.303 13.5828 12.9671 13.9752 12.5696C14.4818 12.0564 15 11.3296 15 10.4318C15 8.79103 13.6349 7.5 12 7.5C10.3651 7.5 9 8.79103 9 10.4318H10.5Z" fill="#080341" />

									</svg></a>

							</div>

							<a href="<?php echo esc_url( 'https://console.cloud.google.com/iam-admin/serviceaccounts' ); ?>"

								target="_blank"

								rel="noopener noreferrer"

								class="link">

								<?php echo esc_html__( 'Download JSON Key File', 'cf7-google-sheets-connector' ); ?>

							</a>





							<img src="<?php echo esc_url( GS_CONNECTOR_URL ); ?>/assets/img/service-step5.png" alt="" />



						</div>





						<div class="gsc-slide">

							<div class="gsc-slider-headers fw-600 mb-10 text-dark"><?php esc_html_e( 'Step-6 Upload JSON Key in CF7', 'cf7-google-sheets-connector' ); ?> <a href="#" class="i-help" hover-tooltip="<?php echo esc_html__( 'Select JSON format and download the key file securely to your computer.', 'cf7-google-sheets-connector' ); ?>">

									<svg width="800px" height="800px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">

										<path fill-rule="evenodd" clip-rule="evenodd" d="M12 19.5C16.1421 19.5 19.5 16.1421 19.5 12C19.5 7.85786 16.1421 4.5 12 4.5C7.85786 4.5 4.5 7.85786 4.5 12C4.5 16.1421 7.85786 19.5 12 19.5ZM12 21C16.9706 21 21 16.9706 21 12C21 7.02944 16.9706 3 12 3C7.02944 3 3 7.02944 3 12C3 16.9706 7.02944 21 12 21ZM12.75 15V16.5H11.25V15H12.75ZM10.5 10.4318C10.5 9.66263 11.1497 9 12 9C12.8503 9 13.5 9.66263 13.5 10.4318C13.5 10.739 13.3151 11.1031 12.9076 11.5159C12.5126 11.9161 12.0104 12.2593 11.5928 12.5292L11.25 12.7509V14.25H12.75V13.5623C13.1312 13.303 13.5828 12.9671 13.9752 12.5696C14.4818 12.0564 15 11.3296 15 10.4318C15 8.79103 13.6349 7.5 12 7.5C10.3651 7.5 9 8.79103 9 10.4318H10.5Z" fill="#080341" />

									</svg></a>

							</div>

							<a href="<?php echo esc_url( 'https://www.gsheetconnector.com/docs/cf7-gsheetconnector/service-account-setting-pro-version' ); ?>"

								target="_blank"

								rel="noopener noreferrer"

								class="link">

								<?php echo esc_html__( 'Check our detailed guideline', 'cf7-google-sheets-connector' ); ?>

							</a>

							<img src="<?php echo esc_url( GS_CONNECTOR_URL ); ?>/assets/img/service-step7.png" alt="" />

						</div>



					</div>



					<button class="gsc-nav prev">❮</button>

					<button class="gsc-nav next">❯</button>

				</div> <!-- slider #end -->



			</div> <!-- step guide col #end -->

		</div>

	<?php } else { ?>

		<div class="col-5">

			<div class="heading mt-0"> <?php echo esc_html( __( 'How to Connect Google Sheets', 'cf7-google-sheets-connector' ) ); ?>

			</div>

			<p>
			<?php
			echo esc_html( __( 'This guide shows how to share your Google Sheet with the connected service account and grant editor access for seamless synchronization.', 'cf7-google-sheets-connector' ) );

			?>

				<img src="<?php echo esc_url( GS_CONNECTOR_URL ); ?>assets/img/service-account-email-share.gif" class="mt-30" alt="" />

		</div>

	<?php } ?>

</div>
