<?php if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

$gscf7_privacy_settings = get_option('gscf7_privacy_settings', array(
	'privacy'  => array('enabled' => 0, 'text' => '', 'page_id' => 0),
	'gdpr'     => array('enabled' => 0, 'text' => '', 'page_id' => 0),
	'branding' => array('enabled' => 0, 'display' => 'visible', 'nofollow' => 1),
));

$gscf7_privacy = wp_parse_args($gscf7_privacy_settings['privacy'] ?? array(), array(
	'enabled' => 0,
	'text'    => '',
	'page_id' => 0,
));

$gscf7_gdpr = wp_parse_args($gscf7_privacy_settings['gdpr'] ?? array(), array(
	'enabled' => 0,
	'text'    => '',
	'page_id' => 0,
));

$gscf7_branding = wp_parse_args(
	isset($gscf7_privacy_settings['branding']) && is_array($gscf7_privacy_settings['branding'])
		? $gscf7_privacy_settings['branding']
		: array(),
	array('enabled' => 0, 'display' => 'visible', 'nofollow' => 1)
);

$gscf7_google_link = '<a href="' . esc_url('https://policies.google.com/privacy') . '" target="_blank" rel="noopener noreferrer">'
	. esc_html__('Privacy Policy', 'cf7-google-sheets-connector') . '</a>';

$gscf7_gsc_link = '<a href="' . esc_url('https://www.gsheetconnector.com/privacy-policy') . '" target="_blank" rel="noopener noreferrer">'
	. esc_html__('GSheetConnector Privacy Policy', 'cf7-google-sheets-connector') . '</a>';

$gscf7_default_privacy_text  = '<p><strong>' . esc_html__('Contact Form Submissions and Google Sheets Integration', 'cf7-google-sheets-connector') . '</strong></p>';
$gscf7_default_privacy_text .= '<p>' . esc_html__('When you submit an inquiry through our website\'s contact forms, we collect the personal information you provide, such as your name, email address, phone number, and message details, for the purpose of processing your submission, responding to your inquiry, and providing relevant support.', 'cf7-google-sheets-connector') . '</p>';

$gscf7_default_privacy_text .= '<p><strong>' . esc_html__('Use of Third-Party Services', 'cf7-google-sheets-connector') . '</strong></p>';
$gscf7_default_privacy_text .= '<p>' . esc_html__('To manage and process form submissions, we may use the following third-party services:', 'cf7-google-sheets-connector') . '</p>';
$gscf7_default_privacy_text .= '<ul>';
$gscf7_default_privacy_text .= '<li><strong>' . esc_html__('Google Sheets (Google LLC):', 'cf7-google-sheets-connector') . '</strong> '
	. esc_html__('When a form is connected to Google Sheets, the submitted information may be transmitted to and stored in the connected Google spreadsheet. This allows us to organize submissions, manage inquiries, and follow up on requests. Google\'s handling of personal information is described in its', 'cf7-google-sheets-connector')
	. ' ' . $gscf7_google_link . '.</li>';
$gscf7_default_privacy_text .= '<li><strong>' . esc_html__('GSheetConnector:', 'cf7-google-sheets-connector') . '</strong> '
	. esc_html__('We use GSheetConnector to integrate supported website forms with Google Sheets. The integration facilitates the transfer of form submission data between the website and the connected Google services. For more information about GSheetConnector\'s data handling practices, please review the', 'cf7-google-sheets-connector')
	. ' ' . $gscf7_gsc_link . '.</li>';
$gscf7_default_privacy_text .= '</ul>';

$gscf7_default_privacy_text .= '<p><strong>' . esc_html__('Data Sharing and Retention', 'cf7-google-sheets-connector') . '</strong></p>';
$gscf7_default_privacy_text .= '<p>' . esc_html__('We do not sell personal information collected through our contact forms. Access to submitted information is limited to authorized personnel and service providers as necessary for the purposes described in this policy.', 'cf7-google-sheets-connector') . '</p>';
$gscf7_default_privacy_text .= '<p>' . esc_html__('We retain personal information only for as long as reasonably necessary to fulfill the purpose for which it was collected, provide support, maintain relevant records, or comply with applicable legal obligations. When the information is no longer required, we take appropriate steps to delete it, subject to applicable retention requirements.', 'cf7-google-sheets-connector') . '</p>';

$gscf7_default_privacy_text .= '<p><strong>' . esc_html__('Your Privacy Rights', 'cf7-google-sheets-connector') . '</strong></p>';
$gscf7_default_privacy_text .= '<p>' . esc_html__('Depending on your location and applicable data protection laws, you may have the right to request access to, correction of, or deletion of your personal information, or to object to or restrict certain processing activities. To exercise applicable rights or ask questions about how your information is handled, please contact us using the contact details provided on this website.', 'cf7-google-sheets-connector') . '</p>';

$gscf7_default_gdpr_text = esc_html__('Your form submission will be securely sent to and stored in Google Sheets to help us process your request and respond to your inquiry.', 'cf7-google-sheets-connector');



$gscf7_privacy_text = $gscf7_privacy['text'] ?: $gscf7_default_privacy_text;
$gscf7_gdpr_text    = $gscf7_gdpr['text'] ?: $gscf7_default_gdpr_text;

?>
<div class="wrap w-100 m-0 gs-form" id="opener">
	<div class="system-general_setting inner-wrap w-100 bg-white p-40" id="googlesheet">
		<div class="gs-form opacity-down">
			<div class="gsc-access-wrapper">
				<div>
					<div class="heading mt-0">
						<?php echo esc_html__('GDPR & Privacy Policy', 'cf7-google-sheets-connector'); ?>
					</div>
					<p><?php echo esc_html__('Manage the notices displayed on your connected forms and your website’s Privacy Policy page. Customize the messages to explain how submitted information may be used and stored.', 'cf7-google-sheets-connector'); ?></p>
					<div class="gsc-setting-text justify-between align-center pt-15 pb-15 mt-30 bg-white">
						<div class="d-flex justify-between">
							<div>
								<div class="systemifo fw-600 text-dark">
									<?php echo esc_html__('Enable GDPR Notice', 'cf7-google-sheets-connector'); ?>
								</div>
								<label for="gscf7_gdpr_enable" class="fw-400">
									<?php echo esc_html__('Display a notice below Contact Form 7 forms connected to Google Sheets through GSheetConnector.', 'cf7-google-sheets-connector'); ?>
								</label>
							</div>
							<div>
								<input type="hidden" name="gscf7_gdpr_enable" value="0">
								<div class="custom-check">
									<input type="checkbox"
										class="check-toggle"
										id="gscf7_gdpr_enable"
										name="gscf7_gdpr_enable"
										value="1"
										<?php checked((int) $gscf7_gdpr['enabled'], 1); ?>>
									<label for="gscf7_gdpr_enable" class="button-toggle"></label>
								</div>
							</div>

						</div>
						<div class="gdpr-section">

							<!-- GDPR Notice Text -->
							<div class="mb-30 mt-30">
								<?php
								wp_editor(
									$gscf7_gdpr_text,
									'gscf7gdprtext',
									array(
										'textarea_name' => 'gscf7_gdpr_text',
										'textarea_rows' => 6,
										'media_buttons' => false,
										'teeny'         => false,
										'quicktags'     => false,
										'tinymce'       => array(
											'toolbar1' => 'bold,italic,underline,link,unlink',
											'toolbar2' => '',
										),
									)
								);
								?>

								<p class="gsc-hint mt-10">
									<?php echo esc_html__('You can customize this message using the text editor below. The notice is displayed only when this setting is enabled.', 'cf7-google-sheets-connector'); ?>
								</p>
							</div>
						</div>

					</div>

					<div class="gsc-setting-text justify-between align-center pt-15 pb-15 mt-30 bg-white">
						<div class="d-flex justify-between">
							<div>
								<div class="systemifo fw-600 text-dark">
									<?php esc_html_e('Enable “Powered by GSheetConnector” credit', 'cf7-google-sheets-connector'); ?>
								</div>
								<label for="gscf7_branding_enable" class="fw-400">
									<?php esc_html_e('Show a small credit link below your Contact Form 7 forms.', 'cf7-google-sheets-connector'); ?>
								</label>
							</div>
							<div>
								<div class="custom-check">
									<input type="checkbox"
										class="check-toggle"
										id="gscf7_branding_enable"
										name="gscf7_branding_enable"
										value="1"
										<?php checked((int) $gscf7_branding['enabled'], 1); ?>>
									<label for="gscf7_branding_enable" class="button-toggle"></label>
								</div>
							</div>
						</div>

						<div class="gsc-branding-section <?php echo empty($gscf7_branding['enabled']) ? 'd-none' : ''; ?>">

							<!-- Preview -->
							<div class="mb-30 mt-30 msg-inner gs-info-box-blue text-dark fw-600 mt-10 mb-10">
								<div class="gsc-branding-preview">
									<?php esc_html_e('Powered by', 'cf7-google-sheets-connector'); ?>
									<a href="https://www.gsheetconnector.com/" target="_blank" rel="noopener noreferrer">GSheetConnector ↗</a>
								</div>
								<p class="gsc-hint mt-10 mb-0">
									<?php esc_html_e('Preview of the branding credit', 'cf7-google-sheets-connector'); ?>
								</p>
							</div>

							<!-- Display -->
							<div class="mb-30 mt-30">
								<div class="systemifo fw-600 text-dark mb-10">
									<?php esc_html_e('Display', 'cf7-google-sheets-connector'); ?>
								</div>
								<label class="d-block mb-10">
									<input type="radio" name="gscf7_branding_display" value="visible"
										<?php checked($gscf7_branding['display'], 'visible'); ?>>
									<?php esc_html_e('Visible beneath the form notice', 'cf7-google-sheets-connector'); ?>
								</label>
								<label class="d-block">
									<input type="radio" name="gscf7_branding_display" value="hidden"
										<?php checked($gscf7_branding['display'], 'hidden'); ?>>
									<?php esc_html_e('Hidden', 'cf7-google-sheets-connector'); ?>
								</label>
							</div>

							<!-- nofollow -->
							<div class="mb-30 mt-30">
								<label class="d-block">
									<input type="checkbox" name="gscf7_branding_nofollow" value="1"
										<?php checked((int) $gscf7_branding['nofollow'], 1); ?>>
									<?php esc_html_e('Add rel="nofollow" to the branding link', 'cf7-google-sheets-connector'); ?>
								</label>
							</div>

						</div>
					</div>

					<div class="gsc-setting-text justify-between align-center pt-15 pb-15 mt-30 bg-white">
						<div class="d-flex justify-between">
							<div>
								<div class="systemifo fw-600 text-dark">
									<?php echo esc_html__('Enable Privacy Policy Notice', 'cf7-google-sheets-connector'); ?>
								</div>
								<label for="gscf7_privacy_enable" class="fw-400">
									<?php echo esc_html__('Choose where to display a privacy notice on your website’s designated Privacy Policy page.', 'cf7-google-sheets-connector'); ?>
								</label>
							</div>
							<div>
								<div class="custom-check">
									<input type="checkbox"
										class="check-toggle"
										id="gscf7_privacy_enable"
										value="1"
										<?php checked((int) $gscf7_privacy['enabled'], 1); ?>>
									<label for="gscf7_privacy_enable" class="button-toggle"></label>
								</div>
							</div>
						</div>
						<div class="gdpr-privacy-section">

							<!-- Display On Page (automatic — no manual page picker) -->
							<div class="mb-30 mt-30">
								<div class="gsc-privacy-policy-display">
									<div class="systemifo fw-600 text-dark mb-10">
										<?php esc_html_e('Display On', 'cf7-google-sheets-connector'); ?>
									</div>
									<?php
									$gscf7_privacy_page_id  = (int) get_option('wp_page_for_privacy_policy');
									$gscf7_privacy_page_url = $gscf7_privacy_page_id
										? get_permalink($gscf7_privacy_page_id)
										: '';
									?>

									<?php if ($gscf7_privacy_page_url) : ?>
										<div class="d-flex justify-between">
											<p class="gsc-description">
												<?php esc_html_e(
													'Shown automatically on your site’s designated Privacy Policy page:',
													'cf7-google-sheets-connector'
												); ?>
											</p>

											<a
												href="<?php echo esc_url($gscf7_privacy_page_url); ?>"
												target="_blank"
												rel="noopener noreferrer"
												class="gsc-privacy-policy-link">
												<?php esc_html_e('View Privacy Policy Page', 'cf7-google-sheets-connector'); ?>
											</a>
										</div>
									<?php else : ?>
										<p class="gsc-description">
											<?php esc_html_e(
												'No Privacy Policy page has been selected. Please configure one in Settings → Privacy.',
												'cf7-google-sheets-connector'
											); ?>
										</p>
									<?php endif; ?>
								</div>
							</div>

							<!-- Privacy Notice Text -->
							<div class="mb-30 mt-30">
								<?php
								wp_editor(
									$gscf7_privacy_text,
									'gscf7privacytext',
									array(
										'textarea_name' => 'gscf7_privacy_text',
										'textarea_rows' => 6,
										'media_buttons' => false,
										'teeny'         => false,
										'quicktags'     => false,
										'tinymce'       => array(
											'toolbar1' => 'bold,italic,underline,link,unlink',
											'toolbar2' => '',
										),
									)
								);
								?>

								<p class="gsc-hint mt-10">
									<?php echo esc_html__('Customize this message to match your website`s data practices. The notice is displayed according to the setting selected above.', 'cf7-google-sheets-connector'); ?>
								</p>
							</div>
						</div>
					</div>
				</div>

				<div class="gsc-access-info">
					<div class='para-heading fw-700 mb-20'><?php esc_html_e('GDPR Notices', 'cf7-google-sheets-connector'); ?></div>
					<div class="gsc-preview-wrap">

						<div class="mb-10">

							<label class="mb-10">
								<?php esc_html_e('Form Preview', 'cf7-google-sheets-connector'); ?>
							</label>

							<select id="gscf7_preview_form" class="gsc-select">
								<?php
								$gscf7_forms = get_posts(
									array(
										'post_type'      => 'wpcf7_contact_form',
										'post_status'    => 'publish',
										'posts_per_page' => -1,
										'orderby'        => 'ID',
										'order'          => 'ASC',
									)
								);
								foreach ($gscf7_forms as $gscf7_index => $gscf7_form) :
								?>
									<option
										value="<?php echo esc_attr($gscf7_form->ID); ?>"
										<?php selected(0, $gscf7_index); ?>>
										<?php echo esc_html($gscf7_form->post_title); ?>
									</option>
								<?php
								endforeach;
								?>
							</select>
						</div>
						<div id="gscf7_form_preview">
							<?php
							if (! empty($gscf7_forms)) {
								// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is rendered by Contact Form 7.
								echo preg_replace(
									'/<h[1-6][^>]*>\s*Basic Fields\s*<\/h[1-6]>/i',
									'',
									do_shortcode(
										'[contact-form-7 id="' . absint($gscf7_forms[0]->ID) . '"]'
									)
								);
							} else {
							?>
								<p>
									<?php
									esc_html_e(
										'No Contact Form 7 forms found.',
										'cf7-google-sheets-connector'
									);
									?>
								</p>
							<?php
							}
							?>
						</div>
					</div>



				</div>
			</div>

			<div class="text-right mt-30">
				<span class="gscf7_privacy_loder"></span>
				<input type="button" class="btn btn-primary"
					name="gscf7_privacy_save" id="gscf7_privacy_save"
					value="<?php echo esc_html__('Save Settings', 'cf7-google-sheets-connector'); ?>" />
				<div id="gscf7-privacy-popup" class="gsc-msg gsc-success fw-400 text-dark text-center pt-10 pb-10 manual-margin d-none">
					<?php echo esc_html__('Save Settings sucessfull.', 'cf7-google-sheets-connector'); ?>
				</div>
			</div>

			<input type="hidden" name="gscf7-privacy-setting-ajax-nonce" id="gscf7-privacy-setting-ajax-nonce"
				value="<?php echo esc_attr(wp_create_nonce('gscf7-privacy-setting-ajax-nonce')); ?>" />
		</div>
	</div>
</div>