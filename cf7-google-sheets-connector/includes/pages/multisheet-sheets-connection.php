<?php if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<div class="cf7-sub-tab-multi cf7-sub-tab cf7gs-multi-tab gscf7-pro" style="display: block;">



	<div class="gs-fields shadow-box mt-30 p-30">



		<div class="feed-error-message gsc-msg gsc-error d-block fw-400 text-dark text-center pt-10 pb-10 margin-manual d-none"></div>

		<div class="feed-success-message gsc-msg gsc-success d-block fw-400 text-dark text-center pt-10 pb-10 margin-manual d-none"></div>



		<!-- FIXED ID -->

		<div id="gs-multi-error-message" class="gsc-msg gsc-success d-block fw-400 text-dark text-center pt-10 pb-10 margin-manual d-none"></div>



		<input type="hidden" id="formId" value="6">



		<div class="heading mt-0">

		<?php echo esc_html__( 'Sheet Sync Feeds', 'cf7-google-sheets-connector' ); ?>

		<span class="pro-ver">

			<?php esc_html_e( 'Pro', 'cf7-google-sheets-connector' ); ?>

		</span>

		</div>



		<p>

		<?php echo esc_html__( 'Create, connect, manage, and monitor how your form submissions are automatically synced to Google Sheets in real time.', 'cf7-google-sheets-connector' ); ?>

		</p>



		<div class="gs-row">



		<div>

			<a class="btn btn-primary mt-20 text-decoration-none link-hover-white" id="cf7-add">

				<?php echo esc_html__( '+ Add Form Feed', 'cf7-google-sheets-connector' ); ?>

			</a>



			<a class="btn btn-primary mt-20 text-decoration-none d-none link-hover-white" id="cf7-close">

				<?php echo esc_html__( '- Close Form Feed', 'cf7-google-sheets-connector' ); ?>

			</a>

		</div>



		<div class="shadow-box mt-50 p-30 feed-box d-none">



			<div class="heading mt-0">

				<?php echo esc_html__( 'Create Sheet Sync Feeds', 'cf7-google-sheets-connector' ); ?>

			</div>



			<p>

				<?php echo esc_html__( 'Enter feed name to start syncing form submissions with Google Sheets in real time.', 'cf7-google-sheets-connector' ); ?>

			</p>



			<div class="add-feed-form">

				<div class="row">



					<div class="col-4">

					<label for="cf7-feed-name">

						<?php echo esc_html__( 'Feed Name', 'cf7-google-sheets-connector' ); ?>

					</label>



					<input type="text" id="cf7-feed-name" class="form-control mt-5" name="cf7-feed-name">



					<div class="input-msg d-none">

						<?php echo esc_html__( 'Please fill out this field', 'cf7-google-sheets-connector' ); ?>

					</div>

					</div>



					<div class="col-4 mt-25">

					<input type="button" id="cf7-add-feed" class="btn btn-primary"

						value="<?php echo esc_attr__( 'Submit', 'cf7-google-sheets-connector' ); ?>">

					</div>



				</div>

			</div>

		</div>



		<div class="gscf-feeds-list form-feed-table-common mt-30">



			<table class="wp-list-table full-table">

				<thead>

					<tr class="pt-30">



					<th class="pt-15 pb-15 pl-20 pr-20 text-left bg-white text-dark fw-600">

						<?php esc_html_e( 'Form ID', 'cf7-google-sheets-connector' ); ?>

					</th>



					<th class="pt-15 pb-15 pl-20 pr-20 text-left bg-white text-dark fw-600">

						<?php esc_html_e( 'Feed Name', 'cf7-google-sheets-connector' ); ?>

					</th>



					<th class="pt-15 pb-15 pl-20 pr-20 text-left bg-white text-dark fw-600">

						<?php esc_html_e( 'Form Name', 'cf7-google-sheets-connector' ); ?>

					</th>



					<th class="pt-15 pb-15 pl-20 pr-20 text-left bg-white text-dark fw-600">

						<?php esc_html_e( 'Timestamp', 'cf7-google-sheets-connector' ); ?>

					</th>



					<th class="pt-15 pb-15 pl-20 pr-20 text-left bg-white text-dark fw-600">

						<?php esc_html_e( 'Actions', 'cf7-google-sheets-connector' ); ?>

					</th>



					<th class="pt-15 pb-15 pl-20 pr-20 text-left bg-white text-dark fw-600">

						<?php esc_html_e( 'Connected Sheet', 'cf7-google-sheets-connector' ); ?>

					</th>



					<th class="pt-15 pb-15 pl-20 pr-20 text-left bg-white text-dark fw-600">

						<?php esc_html_e( 'Status', 'cf7-google-sheets-connector' ); ?>

					</th>



					</tr>

				</thead>



				<tbody>

					<tr id="feed-9" class="">



					<td class="pt-15 pb-15 pl-20 pr-20 text-left">

						7

					</td>



					<td class="pt-15 pb-15 pl-20 pr-20 text-left">

						<?php echo esc_html__( 'cf7 test', 'cf7-google-sheets-connector' ); ?>

					</td>



					<td class="pt-15 pb-15 pl-20 pr-20 text-left">

						<?php echo esc_html__( 'contact 4', 'cf7-google-sheets-connector' ); ?>

					</td>



					<td class="pt-15 pb-15 pl-20 pr-20 text-left">

						<?php echo esc_html__( 'May 9, 2026 7:38 pm (Asia/Kolkata)', 'cf7-google-sheets-connector' ); ?>

					</td>



					<td class="pt-15 pb-15 pl-20 pr-20 text-left">





						<i class="fa-regular fa-pen-to-square"></i>







						<i class="fa-regular fa-trash-can"></i>





					</td>



					<td class="pt-15 pb-15 pl-20 pr-20 text-left">

						<?php esc_html_e( 'No spreadsheet connected', 'cf7-google-sheets-connector' ); ?>

					</td>



					<td class="pt-15 pb-15 pl-20 pr-20 text-left status-col">



						<div class="custom-check">



							<input type="checkbox"

								class="check-toggle"

								id="gscf7_status_9"

								data-id="9"

								name="gscf7_status[9]"

								value="1"

								checked="checked" disabled>



							<label for="gscf7_status_9" class="button-toggle"></label>



						</div>



					</td>



					</tr>

				</tbody>

			</table>



		</div>



		</div>



	</div>






</div>

<div class="feed-informtion-inner shadow-box mt-40 p-30">

	<!-- Free setting End -->



	<div class="system-debug-logs" id="opener">

		<div class="cf7-feeds-list">

		<div class="feed-informtion-inner shadow-box p-30">



			<div class="heading mt-0">

				<?php esc_html_e( 'Feed Details', 'cf7-google-sheets-connector' ); ?>

				<span class="pro-ver">

					<?php esc_html_e( 'Pro', 'cf7-google-sheets-connector' ); ?>

				</span>

			</div>



			<p>

				<?php
				esc_html_e(
					'Displays the form and feed information used for Google Sheets synchronization.',
					'cf7-google-sheets-connector'
				);
				?>

			</p>



			<div class="gsc-info-grid mt-20">



				<div>



					<div class="feed-info-heading mt-20">

					<?php esc_html_e( 'Form ID', 'cf7-google-sheets-connector' ); ?>

					</div>



					<div class="feed-info-sub-head fw-700">

					7

					</div>



				</div>



				<div>



					<div class="feed-info-heading mt-20">

					<?php esc_html_e( 'Feed Name', 'cf7-google-sheets-connector' ); ?>

					</div>



					<div class="feed-info-sub-head fw-700">

					<?php echo esc_html__( 'cf7 test', 'cf7-google-sheets-connector' ); ?>

					</div>



				</div>



				<div>



					<div class="feed-info-heading mt-20">

					<?php esc_html_e( 'Form Name', 'cf7-google-sheets-connector' ); ?>

					</div>



					<div class="feed-info-sub-head fw-700">

					<?php echo esc_html__( 'contact 4', 'cf7-google-sheets-connector' ); ?>

					</div>



				</div>



				<div>



					<div class="feed-info-heading mt-20">

					<?php esc_html_e( 'Timestamp', 'cf7-google-sheets-connector' ); ?>

					</div>



					<div class="feed-info-sub-head fw-700">

					<?php echo esc_html__( 'May 9, 2026 7:38 pm (Asia/Kolkata)', 'cf7-google-sheets-connector' ); ?>

					</div>



				</div>



			</div>



		</div>



		</div>

		<div class="feed_details feed_details_79">



		<div class="custom-check manually-method-add d-flex align-center mt-40">

			<div class="d-flex align-center flex-wrap justify-between w-100 gap-10">



				<div>



					<div class="heading mt-0">

					<?php esc_html_e( 'Enable Manual Google Sheets Configuration', 'cf7-google-sheets-connector' ); ?>

					<span class="pro-ver">

						<?php esc_html_e( 'Pro', 'cf7-google-sheets-connector' ); ?>

					</span>

					</div>



					<p class="note mb-0">

					<?php
						esc_html_e(
							'If you prefer to connect using your existing Google Sheet details, enable this option and enter the required sheet details to configure, Refer step-by-step in documentation',
							'cf7-google-sheets-connector'
						);
						?>



					<a href="https://www.gsheetconnector.com/docs/cf7-gsheetconnector/plugin-settings-pro-version" target="_blank">

						<?php esc_html_e( 'click here.', 'cf7-google-sheets-connector' ); ?>

					</a>

					</p>



				</div>



				<input type="checkbox"

					name="cf7-gs79[manual-field]"

					id="manual-name-multi-79"

					class="check-toggle-cf7 manual-name-multi check-toggle"

					data-id="79" disabled>



				<label for="manual-name-multi-79" class="button-woo-toggle-cf7 button-toggle"></label>



			</div>

		</div>



		<div>



			<input type="hidden" value="cf7 test" id="feed_name_edit_79" name="cf7-gs79[feed_name_edit]">



			<!-- ▼ Dropdown Input Fields -->

			<div class="sheet-details-79 display_div">



				<div class="heading mt-30">

					<?php esc_html_e( 'Automatic Google Sheets Configuration', 'cf7-google-sheets-connector' ); ?>

					<span class="pro-ver">

					<?php esc_html_e( 'Pro', 'cf7-google-sheets-connector' ); ?>

					</span>

				</div>



				<p>

					<?php
					esc_html_e(
						'Automatic configure your Google Sheets and start syncing form submissions in real time.',
						'cf7-google-sheets-connector'
					);
					?>

				</p>



				<div class="" id="sheet-details-79">



					<div class="row">



					<div class="col-4">

						<div class="mr-10 form-group">



							<label>

								<?php esc_html_e( 'Sheet Name', 'cf7-google-sheets-connector' ); ?>

							</label>



							<select name="cf7-gs79[sheet-name] auto-select-display w-100"

								id="gs-sheet-name-feed"

								data-id="79">



								<option value="">

								<?php esc_html_e( 'Select', 'cf7-google-sheets-connector' ); ?>

								</option>



							</select>



							<div class="input-msg d-none">

								<?php esc_html_e( 'Please fill out this field', 'cf7-google-sheets-connector' ); ?>

							</div>



						</div>

					</div>



					<div class="col-4 res-top-20">



						<div class="mr-10 form-group">



							<label>

								<?php esc_html_e( 'Sheet Tab Name', 'cf7-google-sheets-connector' ); ?>

							</label>



							<select name="cf7-gs79[sheet-tab-name]" id="gs-sheet-tab-name-79">

							</select>



						</div>



						<div class="input-msg d-none">

							<?php esc_html_e( 'Please fill out this field', 'cf7-google-sheets-connector' ); ?>

						</div>



					</div>



					<div id="gs-sheet-create-new-multi-79" class="col-4 gs-sheet-create-new-multi-hidden res-top-20">



						<div class="mr-10 form-group">



							<label>

								<?php esc_html_e( 'Create Spreadsheet', 'cf7-google-sheets-connector' ); ?>

							</label>



							<input type="text"

								id="gs-sheet-create-new-79"

								name="cf7-gs79[gs-sheet-create-new]"

								value="">



						</div>



					</div>



					<div class="col-4 sheet-url mt-20">

						<div class="sheet-url" id="sheet-url"></div>

					</div>

					</div>

					<div class="gsc-sheet-actions gs-sync-row flex-wrap gap-10 gsc-sheet-card-status">



					<div>



						<div class="heading mt-0">

							<?php esc_html_e( 'Fetch Sheets', 'cf7-google-sheets-connector' ); ?>

						</div>



						<div class="gs-sync-row">



							<a href="#"

								class="gsc-fetch-link"

								id="gs-sync-multi"

								data-id="79"

								data-init="yes">



								<?php esc_html_e( 'Click here', 'cf7-google-sheets-connector' ); ?>

							</a>



							<?php
							esc_html_e(
								'to retrieve all accessible Google Sheets and update the dropdown in Form Feed settings. Use this after creating a new sheet or renaming a spreadsheet or tab.',
								'cf7-google-sheets-connector'
							);
							?>



							<span class="loading-sign-79"></span>



						</div>



					</div>



					</div>



				</div>



			</div>



		</div>



		<!-- ▼ Manual Text Fields -->

		<div class="manual-fields-79 mt-30 hide" id="manual-fields-79" data-id="79">



			<div class="row">



				<div class="col-6">



					<div class="form-group mr-10">



					<label>

						<?php esc_html_e( 'Sheet Name', 'cf7-google-sheets-connector' ); ?>



						<span class="tooltip"

							data-tooltip="<?php esc_attr_e( 'Enter the exact name of your Google Spreadsheet (as shown in Google Sheets).', 'cf7-google-sheets-connector' ); ?>"

							data-tooltip-pos="right"

							data-tooltip-length="medium">



							<i class="fa-solid fa-circle-question help-icon"></i>



						</span>



					</label>



					<input type="text"

						class="form-control"

						name="cf7-gs79[sheet-name-custom]"

						value="">



					</div>



				</div>



				<div class="col-6 res-top-20">



					<div class="form-group mr-10">



					<label>

						<?php esc_html_e( 'Sheet ID', 'cf7-google-sheets-connector' ); ?>



						<span class="tooltip"

							data-tooltip="<?php esc_attr_e( 'Paste the Spreadsheet ID from your Google Sheet URL. (Example: https://docs.google.com/spreadsheets/d/**SPREADSHEET_ID**/edit)', 'cf7-google-sheets-connector' ); ?>"

							data-tooltip-pos="right"

							data-tooltip-length="medium">



							<i class="fa-solid fa-circle-question help-icon"></i>



						</span>



					</label>



					<input type="text"

						class="form-control"

						name="cf7-gs79[sheet-id-custom]"

						value="">



					</div>



				</div>



				<div class="col-6 mt-20">



					<div class="form-group mr-10">



					<label>

						<?php esc_html_e( 'Sheet Tab Name', 'cf7-google-sheets-connector' ); ?>



						<span class="tooltip"

							data-tooltip="<?php esc_attr_e( 'Enter the exact sheet tab name (e.g., Sheet1) from the bottom of your Google Spreadsheet.', 'cf7-google-sheets-connector' ); ?>"

							data-tooltip-pos="right"

							data-tooltip-length="medium">



							<i class="fa-solid fa-circle-question help-icon"></i>



						</span>



					</label>



					<input type="text"

						class="form-control"

						name="cf7-gs79[sheet-tab-name-custom]"

						value="">



					</div>



				</div>



				<div class="col-6 mt-20">



					<div class="form-group mr-10">



					<label>

						<?php esc_html_e( 'Tab ID', 'cf7-google-sheets-connector' ); ?>



						<span class="tooltip"

							data-tooltip="<?php esc_attr_e( 'Get the Tab ID from your sheet URL after gid=.', 'cf7-google-sheets-connector' ); ?>"

							data-tooltip-pos="right"

							data-tooltip-length="medium">



							<i class="fa-solid fa-circle-question help-icon"></i>



						</span>



					</label>



					<input type="text"

						class="form-control"

						name="cf7-gs79[tab-id-custom]"

						value="">



					</div>



				</div>



			</div>



			<div class="sheet-url" id="sheet-url"></div>



		</div>



		</div>

		<div class="feed-setting-pro-wrapper">

		<div class="form-fields-list gscf7-list-set shadow-box mt-40 p-30" id="field-mapping">



			<div class="gscf7-color-code">



				<div class="color-ffgs">



					<div class="heading">

					<?php esc_html_e( 'Select Fields to Sync', 'cf7-google-sheets-connector' ); ?>

					<span class="pro-ver">

						<?php esc_html_e( 'Pro', 'cf7-google-sheets-connector' ); ?>

					</span>

					</div>



					<p class="gsc-pro-desc">

					<?php

						esc_html_e(
							'Enable the fields you want to send to Google Sheets and rename columns if needed.',
							'cf7-google-sheets-connector'
						);

						?>

					</p>



				</div>



				<div class="gscf7-color-code flex-wrap d-flex align-center gap-20 pt-10">



					<div class="color-ffgs field-type-form">



					<span class="field-list-pill align-center fw-700">

						<?php esc_html_e( 'Field List', 'cf7-google-sheets-connector' ); ?>

					</span>



					</div>



					<div class="color-ffgs field-type-system">



					<span class="align-center fw-700">

						<?php esc_html_e( 'Submission Info', 'cf7-google-sheets-connector' ); ?>

					</span>



					</div>



					<div class="color-ffgs custom-mail-tags">



					<span class="align-center fw-700">

						<?php esc_html_e( 'Custom Mail Tags', 'cf7-google-sheets-connector' ); ?>

					</span>



					</div>



				</div>



			</div>

			<div class="toggle-button select-all-toggle gsc-pro-content">

				<div class="mt-40 select-all-field mb-20">

					<label class="switch">

					<input type="checkbox" id="select-all-checkbox" checked disabled>

					<span class="slider round button-toggle"></span>

					</label>

					<span class="label-text">

					<?php esc_html_e( 'Select All Fields', 'cf7-google-sheets-connector' ); ?>

					</span>

				</div>

				<div id="gscf7-sortable" class="ui-sortable connected-sortable">

					<?php $this->display_form_fields( $form_id, $post ); ?>

				</div>



			</div>

		</div>



		<div class="settings-card shadow-box mt-40 p-30" id="conditional-logic">

			<div class="mt-0 heading">

				<?php esc_html_e( 'Conditional Logic', 'cf7-google-sheets-connector' ); ?>

				<span class="pro-ver">

					<?php esc_html_e( 'Pro', 'cf7-google-sheets-connector' ); ?>

				</span>

			</div>

			<p class="mb-30">

				<?php

				esc_html_e(
					'Control how and when your form data is sent to Google Sheets using powerful conditional rules. Automatic filter, route, and manage submissions based on user input.',
					'cf7-google-sheets-connector'
				);

				?>

			</p>



			<div class="gscfrmnt-cards gscfrmnt-card setting-row">

				<div class="misc-conditional-inner30 misc-options-inner-color-multi-cf7gs-hidden">

					<input

					type="hidden"

					id="getfieldList"

					value="">

					<div>

					<?php esc_html_e( 'Process this feed if', 'cf7-google-sheets-connector' ); ?>



					<select

						name="cf7-gs30[enable_conditional_logic_type_feed]"

						class="enableConditionalLogic conditional-logic">



						<option value="all">

							<?php esc_html_e( 'All', 'cf7-google-sheets-connector' ); ?>

						</option>



						<option value="any" disabled>

							<?php esc_html_e( 'Any', 'cf7-google-sheets-connector' ); ?>

						</option>



					</select>



					<?php esc_html_e( 'of the following match:', 'cf7-google-sheets-connector' ); ?>



					</div>



					<div class="mt-30 d-flex flex-wrap gap-10">



					<select

						name="cf7-gs30[conditional_feed][0][enable_conditional_logic_field_name_feed]"

						class="enableConditionalLogic">



						<option value="your-name">

							<?php esc_html_e( 'your-name', 'cf7-google-sheets-connector' ); ?>

						</option>



						<option value="your-email" disabled>

							<?php esc_html_e( 'your-email', 'cf7-google-sheets-connector' ); ?>

						</option>



						<option value="your-subject" disabled>

							<?php esc_html_e( 'your-subject', 'cf7-google-sheets-connector' ); ?>

						</option>



						<option value="your-message" disabled>

							<?php esc_html_e( 'your-message', 'cf7-google-sheets-connector' ); ?>

						</option>



					</select>



					<select

						name="cf7-gs30[conditional_feed][0][enable_conditional_logic_rule_select_feed]"

						class="enableConditionalLogic">

						<option value="is">

							<?php esc_html_e( 'is', 'cf7-google-sheets-connector' ); ?>

						</option>

						<option value="isnot" disabled>

							<?php esc_html_e( 'is not', 'cf7-google-sheets-connector' ); ?>

						</option>

						<option value="greaterthan" disabled>

							<?php esc_html_e( 'greater than', 'cf7-google-sheets-connector' ); ?>

						</option>

						<option value="lessthan" disabled>

							<?php esc_html_e( 'less than', 'cf7-google-sheets-connector' ); ?>

						</option>

						<option value="contains" disabled>

							<?php esc_html_e( 'contains', 'cf7-google-sheets-connector' ); ?>

						</option>

						<option value="starts_with" disabled>

							<?php esc_html_e( 'starts with', 'cf7-google-sheets-connector' ); ?>

						</option>

						<option value="ends_with" disabled>

							<?php esc_html_e( 'ends with', 'cf7-google-sheets-connector' ); ?>

						</option>

					</select>

					<input

						type="text"

						name="cf7-gs30[conditional_feed][0][enable_conditional_logic_rule_value_feed]"

						value=""

						placeholder="<?php esc_attr_e( 'Enter a value', 'cf7-google-sheets-connector' ); ?>"

						class="enableConditionalLogic form-control conditional-form-control"

						disabled>

					<button

						type="button"

						class="add_field_choice-multi circle-plus"

						data-id="30">

						+

					</button>

					</div>

					<div class="conditional-logic-container-multi30"></div>

					<table class="alt-color-fields gsheet-table three-cols">

					<input

						type="hidden"

						name="selected_field_lists_num_multi"

						class="selected_field_lists_num_multi"

						value="1">

					</table>

				</div>

			</div>

		</div>

		<div class="freez_order_sort form-fields-list gscf7-list-set shadow-box mt-40 p-30">

			<div id="header-settings-sheet-sorting" name="header-settings-sheet-sorting">

				<div class="heading mt-0"><?php echo esc_html( __( 'Header Settings', 'cf7-google-sheets-connector' ) ); ?><span class="pro-ver"><?php echo esc_html( __( 'Pro', 'cf7-google-sheets-connector' ) ); ?></span></div>

				<p><?php echo esc_html( __( 'Customize the appearance and behavior of your sheet headers and rows for better readability and organization.', 'cf7-google-sheets-connector' ) ); ?></p>

				<!-- SECTION 1: Header Behavior -->

				<div class="header-styling-sheet d-flex gap-20">

					<div class="settings-card mb-20 w-100 bg-white">

					<div class="mt-0 header-settings-ineer-size fw-600 mb-20"><?php echo esc_html( __( 'Header Behavior', 'cf7-google-sheets-connector' ) ); ?></div>

					<div class="gscfrmnt-cards gscfrmnt-card setting-row">

						<div class="toggle-button freeze-header-toggle d-flex align-items-center justify-between mb-15">

							<span class="label-text fw-400"><?php echo esc_html( __( 'Freeze Header', 'cf7-google-sheets-connector' ) ); ?></span>

							<label class="switch">

								<input type="checkbox" id="freeze-header-checkbox" name="gscf-ff[freeze_header]" value="true" checked="" disabled>

								<span class="slider round button-toggle"></span>

							</label>

						</div>

					</div>

					<div class="sheet_sorting sheet_formatting mt-30 mb-30">

						<div class="gscfrmnt-cards">

							<div class="toggle-button sheet-sorting-toggle d-flex align-items-center justify-between">

								<span class="label-text fw-400"><?php echo esc_html( __( 'Sheet Sorting', 'cf7-google-sheets-connector' ) ); ?></span>

								<label class="switch" for="sheet-sorting-checkbox">

								<input type="checkbox" id="sheet-sorting-checkbox" name="gscf-ff[sheet_sorting]" value="1" checked="" disabled>

								<span class="slider round button-toggle"></span>

								</label>

							</div>

						</div>

						<div class="sheet-sorting-settings" id="sheet-sorting-settings">

							<div class="settings-grid">

								<div class="gscfrmnt-row form-group">

								<label for="sort-column-name">

									<?php echo esc_html__( 'Sort Column', 'cf7-google-sheets-connector' ); ?>

								</label>

								<select id="sort-column-name" name="gscf-ff[sort_column]">

								</select>

								</div>

								<div class="gscfrmnt-row form-group">

								<label for="sort-order"><?php echo esc_html__( 'Sort Order', 'cf7-google-sheets-connector' ); ?></label>

								<select id="sort-order" name="gscf-ff[sort_order]">

									<option value="ASCENDING"><?php echo esc_html__( 'Ascending', 'cf7-google-sheets-connector' ); ?></option>

									<option value="DESCENDING" disabled><?php echo esc_html__( 'Descending', 'cf7-google-sheets-connector' ); ?></option>

								</select>

								</div>

							</div>

						</div>

					</div>



					<div class="mt-0 header-settings-ineer-size fw-600 mb-20"><?php echo esc_html__( 'Header Style', 'cf7-google-sheets-connector' ); ?></div>

					<div class="sheet_formatting setting-row">

						<div class="gscfrmnt-sheet_formatting gscfrmnt-sheet_formatting">

							<div class="toggle-button sheet_formatting-header-toggle d-flex align-items-center justify-between mb-15">

								<span class="label-texts  fw-400"><?php echo esc_html( __( 'Header Appearance', 'cf7-google-sheets-connector' ) ); ?> </span>

								<label class="switch" for="sheet_formatting-header-checkbox">

								<input type="checkbox" id="sheet_formatting-header-checkbox" name="gscf-ff[sheet_formatting_header]" value="1" checked="" disabled>

								<span class="slider round button-toggle"></span>

								</label>

							</div>

						</div>

						<div class="font-styling-settings" id="font-styling-settings">

							<div class="settings-grid">

								<div class="font-style row-format form-group">

								<label for="font-size"><?php echo esc_html__( 'Font Style', 'cf7-google-sheets-connector' ); ?></label>

								<div class="d-flex gap-5">

									<label class="style-btn active"><input type="checkbox" name="font_styles[]" class="toggle-input active" value="normal">

										<?php echo esc_html__( 'Normal', 'cf7-google-sheets-connector' ); ?></label>

									<label class="style-btn"><input type="checkbox" name="font_styles[]" value="italic" disabled>

										<?php echo esc_html__( 'Italic', 'cf7-google-sheets-connector' ); ?></label>

									<label class="style-btn"><input type="checkbox" name="font_styles[]" value="bold" disabled><?php echo esc_html__( 'Bold', 'cf7-google-sheets-connector' ); ?></label>

								</div>

								</div>



								<div class="font-size row-format form-group">

								<label for="font-size"><?php echo esc_html__( 'Font Size', 'cf7-google-sheets-connector' ); ?></label>

								<div class="gs-font-size">

									<div class="gs-select-box">

										<select id="font-size" name="gscf-ff[font_size]" disabled>

											<option value="" selected><?php echo esc_html__( 'Select size', 'cf7-google-sheets-connector' ); ?></option>

											<option><?php echo esc_html__( '11', 'cf7-google-sheets-connector' ); ?></option>

											<option><?php echo esc_html__( '12', 'cf7-google-sheets-connector' ); ?></option>

											<option><?php echo esc_html__( '13', 'cf7-google-sheets-connector' ); ?></option>

											<option><?php echo esc_html__( '14', 'cf7-google-sheets-connector' ); ?></option>

											<option><?php echo esc_html__( '15', 'cf7-google-sheets-connector' ); ?></option>

										</select>

									</div>

								</div>

								</div>

								<div class="font-color row-format form-group">

								<label for="font-color"><?php echo esc_html__( 'Font Color', 'cf7-google-sheets-connector' ); ?></label>

								<input type="color" id="font-color" name="gscf-ff[font_color]" class="bg-color-set-input" value="#000000" disabled>

								</div>



							</div>

						</div>

					</div>

					</div>



					<!-- SECTION 2: Header Appearance -->

					<div class="settings-card  mb-20 w-100 bg-white">

					<div class="sheet_formatting">

						<div class="mt-0 header-settings-ineer-size fw-600 mb-20"><?php echo esc_html__( 'Row Style', 'cf7-google-sheets-connector' ); ?></div>

						<div class="gscfrmnt-sheet_formatting_row gscfrmnt-sheet_formatting_row">

							<div class="toggle-button sheet_formatting-row-toggle d-flex align-items-center justify-between">

								<span class="label-texts  fw-400"><?php echo esc_html__( 'Color Appearance', 'cf7-google-sheets-connector' ); ?> </span>

								<label class="switch" for="sheet_formatting-row-checkbox">

								<input type="checkbox" id="sheet_formatting-row-checkbox" name="gscf-ff[sheet_formatting_row]" value="1" checked="" disabled>

								<span class="slider round button-toggle"></span>

								</label>

							</div>

						</div>

						<div class="font-styling-settings-row" id="font-styling-settings-row">

							<div class="settings-grid">



								<div class="gscfrmnt-cards-sbg gscfrmnt-cards-sbg form-group">



								<label for="gscfrmnt-header-color" class="button-gscfrmnt-toggle-color"></label>

								<label>

									<?php echo esc_html__( 'Header Background', 'cf7-google-sheets-connector' ); ?> </label>

								<input type="color" id="header-color" name="gscf-ff[header-color]" class="bg-color-set-input" value="#000000" disabled>

								</div>

								<div class="settings-grid">

								<!-- ODD ROW COLOR -->

								<div class="form-group">

									<label for="odd-color"><?php echo esc_html__( 'Odd Row Color', 'cf7-google-sheets-connector' ); ?></label>

									<input type="color"

										id="odd-color"

										name="gscf-ff[odd_color]"

										class="bg-color-set-input"

										value="#ffffff" disabled>

								</div>



								<!-- EVEN ROW COLOR -->

								<div class="form-group">

									<label for="even-color"><?php echo esc_html__( 'Even Row Color', 'cf7-google-sheets-connector' ); ?></label>

									<input type="color"

										id="even-color"

										name="gscf-ff[even_color]"

										class="bg-color-set-input"

										value="#f5f5f5" disabled>

								</div>

								</div>

							</div>

						</div>

					</div>

					<!-- SECTION 3: Row Appearance -->

					<div class="sheet_formatting">



						<div class="gscfrmnt-sheet_formatting_row gscfrmnt-sheet_formatting_row">

							<div class="toggle-button sheet_formatting-row-toggle d-flex align-items-center justify-between">

								<span class="label-texts  fw-400"><?php echo esc_html__( 'Row Appearance', 'cf7-google-sheets-connector' ); ?> </span>

								<label class="switch" for="sheet_formatting-row-checkbox">

								<input type="checkbox" id="sheet_formatting-row-checkbox" name="gscf-ff[sheet_formatting_row]" value="1" checked="" disabled>

								<span class="slider round button-toggle"></span>

								</label>

							</div>

						</div>

						<div class="font-styling-settings-row" id="font-styling-settings-row">

							<div class="settings-grid">



								<!-- FONT STYLE -->

								<div class="font-style row-format form-group">

								<label><?php echo esc_html__( 'Font Style', 'cf7-google-sheets-connector' ); ?></label>

								<div class="d-flex gap-5">

									<label class="style-btn active">

										<input type="checkbox" name="font_styles[]" value="normal" checked> <?php echo esc_html__( 'Normal', 'cf7-google-sheets-connector' ); ?>

									</label>

									<label class="style-btn">

										<input type="checkbox" name="font_styles[]" value="italic" disabled> <?php echo esc_html__( 'Italic', 'cf7-google-sheets-connector' ); ?>

									</label>

									<label class="style-btn">

										<input type="checkbox" name="font_styles[]" value="bold" disabled> <?php echo esc_html__( 'Bold', 'cf7-google-sheets-connector' ); ?>

									</label>

								</div>

								</div>



								<!-- FONT SIZE -->

								<div class="font-size row-format form-group">

								<label for="font-size-row"><?php echo esc_html__( 'Font Size', 'cf7-google-sheets-connector' ); ?></label>

								<div class="gs-font-size">

									<div class="gs-select-box">

										<select id="font-size-row" name="gscf-ff[font_size_row]" disabled>

											<option value="14" selected><?php echo esc_html__( 'Select size', 'cf7-google-sheets-connector' ); ?></option>

											<option><?php echo esc_html__( '11', 'cf7-google-sheets-connector' ); ?></option>

											<option><?php echo esc_html__( '12', 'cf7-google-sheets-connector' ); ?></option>

											<option><?php echo esc_html__( '13', 'cf7-google-sheets-connector' ); ?></option>

											<option><?php echo esc_html__( '14', 'cf7-google-sheets-connector' ); ?></option>

											<option><?php echo esc_html__( '15', 'cf7-google-sheets-connector' ); ?></option>

										</select>

									</div>

								</div>

								</div>



								<!-- FONT COLOR -->

								<div class="font-color row-format form-group">

								<label for="font-color-row"><?php echo esc_html__( 'Font Color', 'cf7-google-sheets-connector' ); ?></label>

								<input type="color"

									id="font-color-row"

									name="gscf-ff[font_color]"

									class="bg-color-set-input"

									value="#000000" disabled>

								</div>

							</div>

						</div>

					</div>



					<!-- SECTION 4: Spreadsheet Download -->

					<div id="spreadsheet-download-sync" name="spreadsheet-download-sync">

						<div class="settings-card mt-30 w-100 bg-white">

							<div class="toggle-button sheet-sorting-toggle d-flex align-items-center justify-between" id="downloads-toggle-checkbox">

								<span class="label-text  fw-400"><?php echo esc_html( __( 'Spreadsheet Download', 'cf7-google-sheets-connector' ) ); ?></span>

								<label class="switch" for="download-toggle-checkbox">

								<input type="checkbox" id="download-toggle-checkbox" name="gscf-ff[download_spreadsheet]" value="1" checked="" disabled>

								<span class="slider round button-toggle"></span>

								</label>

							</div>

							<div id="download-button-wrapper" class="mt-15">

								<!-- style="display:none;"> -->

								<a class="sheet-url-fluentform common-sheet-url text-dark fw-700 download-spreadsheet" hover-tooltip="Spreadsheet Download">

								<i class="fa-regular fa-file-zipper text-dark fw-500 mr-5"></i><?php echo esc_html( __( 'Download', 'cf7-google-sheets-connector' ) ); ?> </a>

							</div>

						</div>

					</div>

					</div>

				</div>

			</div>



		</div>

		</div>



	</div>



</div>
