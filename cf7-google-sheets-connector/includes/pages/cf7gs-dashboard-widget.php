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
 * Lists every Contact Form 7 feed connected to a Google Sheet. Guard here as
 * well so it cannot be rendered through another include path.
 */
if ( ! current_user_can( 'manage_options' ) ) {
	return;
}
?>
<div class="dashboard-content gscf7-dashboard-widget">
	<p class="gscf7-widget-intro"><?php esc_html_e( 'Contact Forms (CF7) connected with Google Sheets.', 'cf7-google-sheets-connector' ); ?></p>

	<div class="gscf7-widget-table-scroll">
		<?php
		// Same table the plugin's Dashboard tab renders (Form Name | Sheet URL).
		echo Gs_Connector_Service::instance()->gscf7_render_connected_feeds_table(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</div>
</div>
<style type="text/css">
	#gs_dashboard .postbox-header .hndle {
		justify-content: flex-start !important;
	}
	.gscf7-dashboard-widget .gscf7-widget-intro {
		margin: 0 0 12px;
	}
	.gscf7-dashboard-widget .gscf7-widget-table-scroll {
		/* ~4 feed rows + sticky header, then scroll. */
		max-height: 200px;
		overflow-y: auto;
		border: 1px solid #e5e7eb;
	}
	.gscf7-dashboard-widget .gscf7-feed-not-connected {
		color: #9ca3af;
		font-style: italic;
	}
	.gscf7-dashboard-widget .gscf7-connected-feeds-table {
		margin: 0;
		border: 0;
		box-shadow: none;
	}
	.gscf7-dashboard-widget .gscf7-connected-feeds-table thead th {
		position: sticky;
		top: 0;
		z-index: 1;
		background: #fff;
	}
	.gscf7-dashboard-widget .gscf7-connected-feeds-empty {
		padding: 16px 10px;
		text-align: center;
		color: #6b7280;
	}
</style>
