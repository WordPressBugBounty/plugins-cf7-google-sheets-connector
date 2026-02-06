jQuery(document).ready(function () {

 // if check gs-display-note class exists or not
    if (jQuery(".gs-display-note")[0]){
      jQuery(".submit").css("display", "none");
    }

   jQuery(document).on('click', '.set-adds-interval', function (e) {
    e.preventDefault();
    var notice = jQuery(this).closest('.gs-adds-notice');
    var nonce  = notice.data('nonce');
      var data = {
         action: 'set_adds_interval',
         security: nonce
      };

      jQuery.post(ajaxurl, data, function (response) {
         if (response.success) {
            jQuery('.gs-adds').slideUp('slow');
         }
      });
   });
   
   jQuery(document).on('click', '.close-adds-interval', function (e) {
    e.preventDefault();

    var notice = jQuery(this).closest('.gs-adds-notice');
    var nonce  = notice.data('nonce');

      var data = {
         action: 'close_adds_interval',
         security: nonce
      };

      jQuery.post(ajaxurl, data, function (response) {
         if (response.success) {
            jQuery('.gs-adds').slideUp('slow');
         }
      });
   });
   


   jQuery(document).on('click', '.set-auth-expired-adds-interval', function (e) {
    e.preventDefault();

    var notice = jQuery(this).closest('.gs-auth-expired-adds-notice');
    var nonce  = notice.data('nonce');

      var data = {
         action: 'set_auth_expired_adds_interval',
         security: nonce
      };

      jQuery.post(ajaxurl, data, function (response) {
         if (response.success) {
            jQuery('.gs-auth-expired-adds').slideUp('slow');
         }
      });
   });

 jQuery(document).on('click', '.close-auth-expired-adds-interval', function (e) {
    e.preventDefault();  

    var notice = jQuery(this).closest('.gs-auth-expired-adds-notice');
    var nonce  = notice.data('nonce');

      var data = {
         action: 'close_auth_expired_adds_interval',
         security: nonce
      };

      jQuery.post(ajaxurl, data, function (response) {
         if (response.success) {
            jQuery('.gs-auth-expired-adds').slideUp('slow');
         }
      });
   });


   // Upgrade notification scripts
   jQuery('.cf7gsc_upgrade_later').click(function () {
      var data = {
         action: 'set_upgrade_notification_interval',
         security: jQuery('#gs_upgrade_ajax_nonce').val()
      };

      jQuery.post(ajaxurl, data, function (response) {
         if (response.success) {
            jQuery('.gs-upgrade').slideUp('slow');
         }
      });
   });
   
   jQuery('.cf7gsc_upgrade').click(function () {
      var data = {
         action: 'close_upgrade_notification_interval',
         security: jQuery('#gs_upgrade_ajax_nonce').val()
      };

      jQuery.post(ajaxurl, data, function (response) {
         if (response.success) {
            jQuery('.gs-upgrade').slideUp('slow');
         }
      });
   });
   
});