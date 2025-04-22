
// single and multisheet connection tab show and hide function
function sigle_multi_tab(tab) {
    jQuery('.cf7-sub-tab').hide();
    jQuery('.' + tab).show();

    if (tab == 'cf7-sub-tab-multi') {
        jQuery('.cf7-sub-tab-single-li').removeClass("cf7-sub-tab-active");
        jQuery('.cf7-sub-tab-multi-li').addClass("cf7-sub-tab-active");
    } else {
        jQuery('.cf7-sub-tab-single-li').addClass("cf7-sub-tab-active");
        jQuery('.cf7-sub-tab-multi-li').removeClass("cf7-sub-tab-active");
    }
}

jQuery(document).ready(function () {
 /**
     * verify the api code
     * @since 1.0
     */
    jQuery(document).on('click', '#save-gs-code', function () {
        jQuery(".loading-sign").addClass("loading");
        var data = {
            action: 'verify_gs_integation',
            code: jQuery('#gs-code').val(),
            security: jQuery('#gs-ajax-nonce').val()
        };
        jQuery.post(ajaxurl, data, function (response) {
            if (!response.success) {
                jQuery(".loading-sign").removeClass("loading");
                jQuery("#gs-validation-message").empty();
                jQuery("<span class='error-message'>Access code Can't be blank</span>").appendTo('#gs-validation-message');
            } else {
                jQuery(".loading-sign").removeClass("loading");
                jQuery("#gs-validation-message").empty();
                jQuery("<span class='gs-valid-message'>Your Google Access Code is Authorized and Saved.</span>").appendTo('#gs-validation-message');
                setTimeout(function () {
                    window.location.href = jQuery("#redirect_auth").val();
                }, 1000);
            }
        });

    });

    /**
     * deactivate the api code
     * @since 4.2
     */
    jQuery(document).on('click', '#deactivate-log', function () {
        jQuery(".loading-sign-deactive").addClass("loading");
        var txt;
        var r = confirm("Are You sure you want to deactivate Google Integration ?");
        if (r == true) {
            var data = {
                action: 'deactivate_gs_integation',
                security: jQuery('#gs-ajax-nonce').val()
            };
            jQuery.post(ajaxurl, data, function (response) {
                if (response == -1) {
                    return false; // Invalid nonce
                }

                if (!response.success) {
                    alert('Error while deactivation');
                    jQuery(".loading-sign-deactive").removeClass("loading");
                    jQuery("#deactivate-message").empty();

                } else {
                    jQuery(".loading-sign-deactive").removeClass("loading");
                    jQuery("#deactivate-message").empty();
                    jQuery("<span class='gs-valid-message'>Your account is removed. Reauthenticate again to integrate Contact Form with Google Sheet.</span>").appendTo('#deactivate-message');
                    setTimeout(function () {
                        location.reload();
                    }, 1000);
                }
            });
        } else {
            jQuery(".loading-sign-deactive").removeClass("loading");
        }



    });

    /**
     * Clear debug
     */
    jQuery(document).on('click', '.debug-clear', function () {
        jQuery(".clear-loading-sign").addClass("loading");
        var data = {
            action: 'gs_clear_log',
            security: jQuery('#gs-ajax-nonce').val()
        };
        jQuery.post(ajaxurl, data, function (response) {
            var clear_msg = response.data;
            if (response.success) {
                jQuery(".clear-loading-sign").removeClass("loading");
                jQuery("#gs-validation-message").empty();
                jQuery("<span class='gs-valid-message'>"+clear_msg+"</span>").appendTo('#gs-validation-message');
                setTimeout(function () {
                        location.reload();
                    }, 1000);

            }
        });
    });
/**
    * Clear debug for system status tab
    */
   jQuery(document).on('click', '.clear-content-logs-cf7', function () {

      jQuery(".clear-loading-sign-logs-cf7").addClass("loading");
      var data = {
         action: 'cf7_clear_debug_log',
         security: jQuery('#gs-ajax-nonce').val()
      };
      jQuery.post(ajaxurl, data, function ( response ) {
         if (response == -1) {
            return false; // Invalid nonce
         }
         
         if (response.success) {
            jQuery(".clear-loading-sign-logs-cf7").removeClass("loading");
            jQuery('.clear-content-logs-msg-cf7').html('Logs are cleared.');
            setTimeout(function () {
                        location.reload();
                    }, 1000);
         }
      });
   });
   /**
     * Display Error logs
     */
    jQuery(document).on('click', '.closeView', function () {
    jQuery('.closeView').text("View").removeClass('closeView');
    jQuery('button').addClass('gsc-cf7free-logs');
    jQuery('.system-error-cf7free-logs').hide(); // Corrected from toggle to hide
});

jQuery(document).on('click', '.gsc-cf7free-logs', function () {
    jQuery('.gsc-cf7free-logs').text("Close").addClass('closeView'); // Changed from .gsc-cf7pro-logs to .gsc-cf7free-logs
    jQuery('button').removeClass('gsc-cf7free-logs');
    jQuery('.system-error-cf7free-logs').show(); // Corrected from toggle to show
});

jQuery(document).ready(function ($) {
    // Hide .system-error-cf7free-logs initially
    $('.system-error-cf7free-logs').hide();

    // Prevent system-error-cf7free-logs from toggling when clicking the div itself
    $('.system-error-cf7free-logs').on('click', function (e) {
        e.stopPropagation(); // Prevents the click event from propagating further
    });
});




    // Check if the message has already been hidden by looking in localStorage
    if (localStorage.getItem('googleDriveMsgHidden') === 'true') {
        jQuery('#google-drive-msg').hide(); // Hide the message if it's already hidden
    }

    // On button click, hide the #google-drive-msg div and store the hidden state in localStorage
    jQuery('.button_cf7formgsc').on('click', function() {
        jQuery('#google-drive-msg').hide(); // Hide the message
        localStorage.setItem('googleDriveMsgHidden', 'true'); // Save the hidden state in localStorage
    });

    // On #deactivate-log click, show the #google-drive-msg div and clear localStorage
    jQuery('#deactivate-log').on('click', function() {
        jQuery('#google-drive-msg').show(); // Show the message
        localStorage.removeItem('googleDriveMsgHidden'); // Remove the hidden state from localStorage
    });
});
