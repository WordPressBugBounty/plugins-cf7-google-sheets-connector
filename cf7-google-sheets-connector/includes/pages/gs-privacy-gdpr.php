<?php if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

$gscf7_saved_settings = get_option('gscf7_privacy_settings');
$gscf7_saved_settings = is_array($gscf7_saved_settings) ? $gscf7_saved_settings : array();

$gscf7_stored_gdpr = (isset($gscf7_saved_settings['gdpr']) && is_array($gscf7_saved_settings['gdpr']))
	? $gscf7_saved_settings['gdpr']
	: array();
$gscf7_configured  = ! empty($gscf7_stored_gdpr['configured']);

$gscf7_gdpr = wp_parse_args(
	$gscf7_stored_gdpr,
	array(
		'enabled'    => 1,
		'text'       => '',
		'page_id'    => 0,
	)
);

$gscf7_gdpr['enabled'] = 1;

$gscf7_privacy = wp_parse_args(
	(isset($gscf7_saved_settings['privacy']) && is_array($gscf7_saved_settings['privacy'])) ? $gscf7_saved_settings['privacy'] : array(),
	array(
		'enabled'    => 0,
		'text'       => '',
		'page_id'    => 0,
	)
);

$gscf7_privacy_page_id  = (int) get_option('wp_page_for_privacy_policy');
$gscf7_privacy_page_url = $gscf7_privacy_page_id ? get_permalink($gscf7_privacy_page_id) : '';

$gscf7_default_privacy_text = esc_html__('Information submitted through this form may be stored in Google Sheets and processed by the website owner in accordance with applicable privacy laws.', 'cf7-google-sheets-connector');

$gscf7_privacy_text = trim(wp_strip_all_tags($gscf7_privacy['text'])) !== '' ? $gscf7_privacy['text'] : $gscf7_default_privacy_text;

$gscf7_default_gdpr_text =
	'<span>'
	. esc_html__('Your information will be securely sent to and stored in Google Sheets for the purpose of processing your form submission.', 'cf7-google-sheets-connector')
	. '</span>';
$gscf7_gdpr_text = $gscf7_configured ? $gscf7_gdpr['text'] : $gscf7_default_gdpr_text;
?>
<div class="wrap w-100 m-0 gs-form" id="opener">
	<div class="system-general_setting inner-wrap w-100 bg-white p-40" id="googlesheet">
		<div class="gs-form opacity-down">
			<div class="gsc-access-wrapper">
				<div>
					<div class="heading mt-0">
						<?php echo esc_html__('GDPR & Privacy Policy', 'cf7-google-sheets-connector'); ?>
					</div>
					<p><?php echo esc_html__('The GDPR notice is shown below every Contact Form 7 form connected to a Google Sheet; clear the editor and save to hide it. The Privacy Policy notice is a Pro feature shown here for reference only.', 'cf7-google-sheets-connector'); ?></p>
					<div class="gsc-setting-text justify-between align-center pt-15 pb-15 mt-30 bg-white">
						<div class="d-flex">
							<div>
								<div class="systemifo fw-600 text-dark">
									<?php echo esc_html__('Enable GDPR Notice', 'cf7-google-sheets-connector'); ?>
								</div>
								<label for="gscf7_gdpr_enable" class="fw-400">
									<?php echo esc_html__('When enabled, the GDPR notice will be shown below all Contact Form 7 forms connected with Google Sheets using GSheetConnector.', 'cf7-google-sheets-connector'); ?>
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
										<?php checked((int) $gscf7_gdpr['enabled'], 1); ?> disabled>
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
									<?php echo esc_html__('You can use HTML links in the notice text. This notice is shown below every Contact Form 7 form connected to a Google Sheet. Leave the editor empty and save to hide the notice.', 'cf7-google-sheets-connector'); ?>
								</p>
							</div>
						</div>

					</div>

					<div class="gsc-setting-text justify-between align-center pt-15 pb-15 mt-30 bg-white blur-pro-feature">
						<div class="d-flex justify-between">
							<div>
								<div class="systemifo fw-600 text-dark">
									<?php echo esc_html__('Enable Privacy Policy Notice', 'cf7-google-sheets-connector'); ?>
									<span class="gsc-pro-badge"><?php echo esc_html__('PRO', 'cf7-google-sheets-connector'); ?></span>
								</div>
								<label for="gscf7_privacy_enable" class="fw-400">
									<?php echo esc_html__('Choose where to show the Privacy Policy notice using the "Display On" option below, or turn it off.', 'cf7-google-sheets-connector'); ?>
								</label>
							</div>
							<div>
								<div class="custom-check">
									<input type="checkbox"
										class="check-toggle"
										id="gscf7_privacy_enable"
										value="1"
										<?php checked((int) $gscf7_privacy['enabled'], 1); ?> disabled>
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
									<?php echo esc_html__('You can use HTML links in the notice text. This text appears only when the Privacy Policy Notice is not set to "Disabled" above.', 'cf7-google-sheets-connector'); ?>
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
							<?php if (empty($gscf7_forms)) : ?>
								<p>
									<?php esc_html_e('No Contact Form 7 forms found.', 'cf7-google-sheets-connector'); ?>
								</p>
							<?php else : ?>
								<span class="loading"></span>
							<?php endif; ?>
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