<?php

/*
 * Class for displaying Gsheet Connector PRO adds
 * @since 2.8
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
   exit;
}

/**
 * GS_Connector_Adds Class
 * @since 2.8
 */
class GS_Connector_Adds {
   public function __construct() {
      add_action( 'admin_init', array( $this, 'display_adds_block' ) );
      add_action( 'wp_ajax_set_adds_interval', array( $this, 'set_adds_interval' ) );
      add_action( 'wp_ajax_close_adds_interval', array( $this, 'close_adds_interval' ) );

      // notifiation when auth expired
      add_action( 'admin_init', array( $this, 'display_auth_expired_adds_block' ) );
      add_action( 'wp_ajax_set_auth_expired_adds_interval', array( $this, 'set_auth_expired_adds_interval' ) );
      add_action( 'wp_ajax_close_auth_expired_adds_interval', array( $this, 'close_auth_expired_adds_interval' ) );


}

/**
 * Display adds block.
 *
 * @return void
 */
public function display_adds_block() {

    $get_display_interval = get_option( 'gs_display_add_interval' );
    $close_add_interval   = get_option( 'gs_close_add_interval' );

    // Permanently dismissed.
    if ( 'off' === $close_add_interval ) {
        return;
    }

    $adds_interval_timestamp = 0;

    if ( ! empty( $get_display_interval ) ) {
        $date_object = DateTime::createFromFormat( 'Y-m-d', $get_display_interval );

        if ( $date_object instanceof DateTime ) {
            $adds_interval_timestamp = $date_object->getTimestamp();
        }
    }

    // Show notice if no delay OR delay expired.
    if (
        empty( $get_display_interval ) ||
        time() > $adds_interval_timestamp
    ) {
        add_action(
            'admin_notices',
            array( $this, 'show_gs_adds' )
        );
    }
}

   
   public function set_adds_interval() {
      // check nonce
      check_ajax_referer( 'gs_adds_ajax_nonce', 'security' );
   $time_interval = gmdate( 'Y-m-d', strtotime( '+30 days' ) );
      update_option( 'gs_display_add_interval', $time_interval );
      wp_send_json_success();
   }
   
   public function close_adds_interval() {
      // check nonce
      check_ajax_referer( 'gs_adds_ajax_nonce', 'security' );
      update_option( 'gs_close_add_interval', 'off' );
      wp_send_json_success();
   }
   
   public function show_gs_adds() {
      $ajax_nonce   = wp_create_nonce( "gs_adds_ajax_nonce" );
      $review_text  = '<div class="gs-adds-notice success" data-nonce="' . esc_attr( $ajax_nonce ) . '">';
      $review_text .= 'Upgrade to <a href="https://www.gsheetconnector.com/cf7-google-sheet-connector-pro" target="_blank" >GSheetConnector for CF7 PRO</a> with automated sheets to setup within few clicks. Grab the deal with discounted price.';
      $review_text .= '<ul class="review-rating-list">';
      $review_text .= '<li>
      <a href="#"
         class="set-adds-interval"
         title="Will be displayed again after 30 days">
         Nope, may be later.
      </a>
      </li>';

      $review_text .= '<li>
      <a href="#"
         class="close-adds-interval"
         title="This notice will be permanently dismissed">
         Dismiss
      </a>
      </li>';
      
      $review_text .= '</ul></div>';

      $rating_block = Gs_Connector_Free_Utility::instance()->admin_notice( array(
         'type'    => 'review',
         'message' => $review_text
      ) );
      echo wp_kses_post($rating_block);
   }



   public function display_auth_expired_adds_block() {

    $get_display_interval = get_option( 'gs_auth_expired_display_add_interval' );
    $close_add_interval   = get_option( 'gs_auth_expired_close_add_interval' );

    // Permanently dismissed
    if ( $close_add_interval === 'off' ) {
        return;
    }

    $adds_interval_timestamp = 0;

    if ( ! empty( $get_display_interval ) ) {
        $date_object = DateTime::createFromFormat( 'Y-m-d', $get_display_interval );

        if ( $date_object instanceof DateTime ) {
            $adds_interval_timestamp = $date_object->getTimestamp();
        }
    }

    $auth_expired = get_option( 'cf7gs_auth_expired_free' );

    if ( $auth_expired === 'true' ) {

        // Show notice if no delay OR delay expired
        if (
            empty( $get_display_interval ) ||
            current_time( 'timestamp' ) > $adds_interval_timestamp
        ) {
            add_action(
                'admin_notices',
                array( $this, 'show_gs_auth_expired_adds' )
            );
        }
    }
}


  public function set_auth_expired_adds_interval() {
      // check nonce
      check_ajax_referer( 'gs_auth_expired_adds_ajax_nonce', 'security' );
      $time_interval = gmdate( 'Y-m-d', strtotime( '+30 days' ) );
      update_option( 'gs_auth_expired_display_add_interval', $time_interval );
      wp_send_json_success();
   }

   public function close_auth_expired_adds_interval() {
      // check nonce
      check_ajax_referer( 'gs_auth_expired_adds_ajax_nonce', 'security' );
      update_option( 'gs_auth_expired_close_add_interval', 'off' );
      wp_send_json_success();
   }


   public function show_gs_auth_expired_adds() {
      $ajax_nonce   = wp_create_nonce( "gs_auth_expired_adds_ajax_nonce" );
      $review_text  = '<div class="gs-auth-expired-adds-notice" data-nonce="' . esc_attr( $ajax_nonce ) . '">';
      $review_text .= 'CF7 Google Sheet Connector FREE is installed but it is not connected to your Google account, so you are missing out the submission entries.
         <a href="admin.php?page=wpcf7-google-sheet-config&tab=integration" target="_blank">Connect now</a>. It only takes 30 seconds!. 
          ';
      $review_text .= '<ul class="review-rating-list">';
      $review_text .= '<li>
        <a href="#"
           class="set-auth-expired-adds-interval"
           title="This notice will be shown again after 30 days">
           Nope, may be later.
        </a>
    </li>';
    
    $review_text .= '<li>
        <a href="#"
           class="close-auth-expired-adds-interval"
           title="This notice will be permanently dismissed">
           Dismiss
        </a>
    </li>';
      $review_text .= '</ul>';
      $review_text .= '</div>';

      $rating_block = Gs_Connector_Free_Utility::instance()->admin_notice( array(
         'type'    => 'auth-expired-notice',
         'message' => $review_text
      ) );
      echo wp_kses_post($rating_block);
   }
   
}
// construct an instance so that the actions get loaded
$gs_connector_adds = new GS_Connector_Adds();