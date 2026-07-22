<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Class GSCF7_FormList_Table
 */
class GSCF7_FormList_Table extends WP_List_Table
{

    /**
     * Prepare table items.
     *
     * @return void
     */
    public function prepare_items()
    {

        $columns = $this->get_columns();
        $hidden  = $this->get_hidden_columns();

        $per_page     = 10;
        $current_page = $this->get_pagenum();

        $data = $this->table_data($per_page, $current_page);

        $count_forms = wp_count_posts('wpcf7_contact_form');

        $total_items = isset($count_forms->publish)
            ? (int) $count_forms->publish
            : 0;

        $this->set_pagination_args(
            array(
                'total_items' => $total_items,
                'per_page'    => $per_page,
                'total_pages' => ceil($total_items / $per_page),
            )
        );

        $this->_column_headers = array(
            $columns,
            $hidden,
            array(),
            'name',
        );

        $this->items = $data;
    }

    /**
     * Table columns.
     *
     * @return array
     */
    public function get_columns()
    {

        return array(
            'name'  => esc_html__('Name', 'cf7-google-sheets-connector'),
            'count' => esc_html__('Count', 'cf7-google-sheets-connector'),
        );
    }

    /**
     * Hidden columns.
     *
     * @return array
     */
    public function get_hidden_columns()
    {

        return array();
    }

    /**
     * Get table data.
     *
     * @param int $per_page     Items per page.
     * @param int $current_page Current page.
     *
     * @return array
     */
    private function table_data($per_page = 10, $current_page = 1)
    {

        global $wpdb;

        $cfdb       = apply_filters('cfdb7_database', $wpdb);
        $data       = array();
        $table_name = $cfdb->prefix . 'cf7db_gsheet_forms';

        /*
		 * Check table exists.
		 */
        $table_exists = $cfdb->get_var(
            $cfdb->prepare(
                'SHOW TABLES LIKE %s',
                $table_name
            )
        );

        /*
		 * Create table if not exists.
		 */
        if ($table_exists !== $table_name) {

            $charset_collate = $cfdb->get_charset_collate();

            $sql = "CREATE TABLE {$table_name} (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				form_id bigint(20) NOT NULL,
				value longtext NOT NULL,
				date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
				PRIMARY KEY (id)
			) {$charset_collate};";

            require_once ABSPATH . 'wp-admin/includes/upgrade.php';

            dbDelta($sql);

            /*
			 * Return empty array instead of boolean.
			 */
            return array();
        }

        $offset = ($current_page - 1) * $per_page;

        $args = array(
            'post_type'      => 'wpcf7_contact_form',
            'posts_per_page' => $per_page,
            'offset'         => $offset,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'post_status'    => 'publish',
        );

        $the_query = new WP_Query($args);

        if ($the_query->have_posts()) {

            while ($the_query->have_posts()) {

                $the_query->the_post();

                $form_id = get_the_ID();

                $total_count = $cfdb->get_var(
                    $cfdb->prepare(
                        "SELECT COUNT(*) FROM {$table_name} WHERE form_id = %d",
                        $form_id
                    )
                );

                $form_title = get_the_title();

                $url = add_query_arg(
                    array(
                        'page'   => 'wpcf7-google-sheet-config',
                        'tab'    => 'cf7_db',
                        'formId' => $form_id,
                    ),
                    admin_url('admin.php')
                );

                $link = sprintf(
                    '<a class="row-title" href="%1$s">%2$s</a>',
                    esc_url($url),
                    esc_html($form_title)
                );

                $data[] = array(
                    'name'  => $link,
                    'count' => (int) $total_count,
                );
            }
        }

        wp_reset_postdata();

        return $data;
    }

    /**
     * Default column output.
     *
     * @param array  $item        Row item.
     * @param string $column_name Column name.
     *
     * @return string
     */
    public function column_default($item, $column_name)
    {

        return isset($item[$column_name])
            ? $item[$column_name]
            : '';
    }
}
