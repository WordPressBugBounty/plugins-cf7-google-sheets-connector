<?php
/*
 * CF7GS Dashboard Widget
 * @since 2.1
 * @package cf7-google-sheets-connector
 * Text Domain: cf7-google-sheets-connector
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/*
 * This template lists every Google Spreadsheet the site is connected to.
 * Guard it here as well so it cannot be rendered through another include path.
 */
if ( ! current_user_can( 'manage_options' ) ) {
	return;
}
?>
<?php
$gscf7_connector_service = Gs_Connector_Service::instance();
$gscf7_forms_list        = $gscf7_connector_service->get_forms_connected_to_sheet();

// GROUP BY FEED ID (each feed is its own row, even if multiple feeds share the same form)
$gscf7_grouped = array();

if ( ! empty( $gscf7_forms_list ) ) {
	foreach ( $gscf7_forms_list as $gscf7_row ) {
		$gscf7_feed_id = $gscf7_row->feed_id;
		$gscf7_sheet   = $gscf7_row->sheet_name;
		$gscf7_tab     = $gscf7_row->tab_name;

		if ( ! $gscf7_feed_id || ! $gscf7_sheet || ! $gscf7_tab ) {
			continue;
		}

		$gscf7_grouped[ $gscf7_feed_id ]['ID']            = $gscf7_row->ID;
		$gscf7_grouped[ $gscf7_feed_id ]['post_title']    = $gscf7_row->post_title;
		$gscf7_grouped[ $gscf7_feed_id ]['feed_name']     = $gscf7_row->feed_name;
		$gscf7_grouped[ $gscf7_feed_id ]['connections'][] = array(
			'sheet_name' => $gscf7_sheet,
			'tab_name'   => $gscf7_tab,
			'sheet_id'   => $gscf7_row->sheet_id,
			'tab_id'     => $gscf7_row->tab_id,
		);
	}
}
?>
<div class="dashboard-content">
	<h3><?php esc_html_e( 'Contact Forms (CF7) connected with Google Sheets', 'cf7-google-sheets-connector' ); ?></h3>

	<table class="widefat striped">
		<thead>
		<tr>
			<th><?php esc_html_e( 'Form Name', 'cf7-google-sheets-connector' ); ?></th>
			<th><?php esc_html_e( 'Sheet Name', 'cf7-google-sheets-connector' ); ?></th>
		</tr>
		</thead>
		<tbody>
		<?php if ( ! empty( $gscf7_grouped ) ) : ?>
				<?php foreach ( $gscf7_grouped as $gscf7_feed_id => $gscf7_feed ) : ?>
				<tr>
					<td>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcf7&post=' . intval( $gscf7_feed['ID'] ) . '&action=edit' ) ); ?>">
						<?php echo esc_html( $gscf7_feed['post_title'] ); ?>
					</a>
					</td>

					<td>
					<?php
					foreach ( $gscf7_feed['connections'] as $gscf7_conn ) :
						$gscf7_sheet_name = $gscf7_conn['sheet_name'];
						$gscf7_tab_name   = $gscf7_conn['tab_name'];
						$gscf7_sheet_id   = $gscf7_conn['sheet_id'];
						$gscf7_tab_id     = $gscf7_conn['tab_id'];

						if ( empty( $gscf7_sheet_id ) ) {
							continue;
						}
						?>

						<div style="margin-bottom:5px;">
							<a target="_blank"
								href="<?php echo esc_url( 'https://docs.google.com/spreadsheets/d/' . $gscf7_sheet_id . '/edit#gid=' . $gscf7_tab_id ); ?>">
							<?php echo esc_html( $gscf7_sheet_name ); ?>
							</a>
						</div>

						<?php endforeach; ?>
					</td>
				</tr>

			<?php endforeach; ?>

		<?php else : ?>

			<tr>
				<td colspan="2">
					<?php esc_html_e( 'No Contact Forms connected with Google Sheets.', 'cf7-google-sheets-connector' ); ?>
				</td>
			</tr>

		<?php endif; ?>

		</tbody>
	</table>
</div>
<style type="text/css">
	.postbox-header .hndle {
		justify-content: flex-start !important;
	}
</style>
