<?php
/*
 * CF7GS Dashboard Widget
 * @since 2.1
 * @package cf7-google-sheets-connector
 * Text Domain: cf7-google-sheets-connector
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
   exit();
}
?>
<?php
$gscf7_connector_service = new Gs_Connector_Service();
$gscf7_forms_list           = $gscf7_connector_service->get_forms_connected_to_sheet();

// GROUP BY FORM ID
$gscf7_grouped = [];

if (! empty($gscf7_forms_list)) {
   foreach ($gscf7_forms_list as $gscf7_row) {
      $gscf7_form_id = $gscf7_row->form_id;
      $gscf7_sheet   = $gscf7_row->sheet_name;
      $gscf7_tab     = $gscf7_row->tab_name;

      if (! $gscf7_form_id || ! $gscf7_sheet || ! $gscf7_tab) {
         continue;
      }

      $gscf7_grouped[$gscf7_form_id]['ID']            = $gscf7_row->ID;
      $gscf7_grouped[$gscf7_form_id]['post_title']    = $gscf7_row->post_title;
      $gscf7_grouped[$gscf7_form_id]['connections'][] = [
         'sheet_name' => $gscf7_sheet,
         'tab_name'   => $gscf7_tab,
         'sheet_id'   => $gscf7_row->sheet_id,
         'tab_id'     => $gscf7_row->tab_id,
      ];
   }
}
?>
<div class="dashboard-content">
   <h3><?php esc_html_e('Contact Forms (CF7) connected with Google Sheets', 'cf7-google-sheets-connector'); ?></h3>

   <table class="widefat striped">
      <thead>
         <tr>
            <th><?php esc_html_e('Form Name', 'cf7-google-sheets-connector'); ?></th>
            <th><?php esc_html_e('Sheet Connections', 'cf7-google-sheets-connector'); ?></th>
         </tr>
      </thead>
      <tbody>
         <?php if (! empty($gscf7_grouped)) : ?>
            <?php foreach ($gscf7_grouped as $gscf7_form_id => $gscf7_form) : ?>
               <tr>
                  <td>
                     <a href="<?php echo esc_url(admin_url('admin.php?page=wpcf7&post=' . intval($gscf7_form['ID']) . '&action=edit')); ?>">
                        <?php echo esc_html($gscf7_form['post_title']); ?>
                     </a>
                  </td>
                  <td>
                     <?php foreach ($gscf7_form['connections'] as $gscf7_conn) :
                        $gscf7_sheet_name = $gscf7_conn['sheet_name'];
                        $gscf7_tab_name   = $gscf7_conn['tab_name'];
                        $gscf7_sheet_id   = $gscf7_conn['sheet_id'];
                        $gscf7_tab_id     = $gscf7_conn['tab_id'];

                        if (empty($gscf7_sheet_id)) {
                           continue;
                        }
                     ?>

                        <div style="margin-bottom:5px;">
                           <a target="_blank"
                              href="<?php echo esc_url('https://docs.google.com/spreadsheets/d/' . $gscf7_sheet_id . '/edit#gid=' . $gscf7_tab_id); ?>">
                              <?php echo esc_html($gscf7_sheet_name . ' → ' . $gscf7_tab_name); ?>
                           </a>
                        </div>

                     <?php endforeach; ?>
                  </td>
               </tr>

            <?php endforeach; ?>

         <?php else : ?>

            <tr>
               <td colspan="2">
                  <?php esc_html_e('No Contact Forms connected with Google Sheets.', 'cf7-google-sheets-connector'); ?>
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