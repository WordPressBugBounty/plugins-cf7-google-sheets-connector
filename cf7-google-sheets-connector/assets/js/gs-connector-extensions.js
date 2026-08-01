/*
 * extension page
 */
jQuery(document).ready(function (jQuery) {
  jQuery(".gsheetconnector-addons-list").each(function () {
    if (jQuery(this).html().trim().length === 0) {
      jQuery(this).addClass("blank_div");
      jQuery(this).prev("div").hide();
    }
  });
  jQuery(".gscf7-free-install-plugin-btn").on("click", function () {
    var button = jQuery(this);
    var pluginSlug = button.data("plugin");
    var downloadUrl = button.data("download");
    var nonce = jQuery("#gs-ajax-nonce").val();
    var loaderSpan = button
      .closest(".button-bar")
      .find(".loading-sign-install");
    loaderSpan.addClass("loading");
    button.prop("disabled", true);

    jQuery.ajax({
      url: ajaxurl,
      type: "POST",
      dataType: "json",
      data: {
        action: "gscf7_install_plugin",
        plugin_slug: pluginSlug,
        download_url: downloadUrl,
        security: nonce,
      },

      success: function (response) {
        loaderSpan.removeClass("loading");

        if (response.success) {
          button.hide();

          button
            .closest(".button-bar")
            .find(".gscf7-free-activate-plugin-btn")
            .show();
          location.reload();
        } else {
          jQuery(".popup-actions-active-msg-free").text(response.data.message);

          jQuery("#gscf7-confirm-active-popup-free").removeClass("d-none");

          button.prop("disabled", false);
          location.reload();
        }
      },

      error: function () {
        loaderSpan.removeClass("loading");

        jQuery(".popup-actions-active-msg-free").text(
          "Something went wrong. Please try again.",
        );

        jQuery("#gscf7-confirm-active-popup-free").removeClass("d-none");

        button.prop("disabled", false);
      },
    });
  });

  /**
   * Handle plugin activation button click via AJAX.
   *
   * - Shows loading spinner
   * - Sends plugin slug to server for activation
   * - On success, updates button to "Activated" and reloads page
   * - On error or failure, resets button and removes loading state
   */

  jQuery(document).on("click", ".gscf7-free-activate-plugin-btn", function () {
    var button = jQuery(this);
    var pluginSlug = button.data("plugin");
    var loaderSpan = button.siblings(".loading-sign-active");

    loaderSpan.addClass("loading");
    button.prop("disabled", true);

    jQuery.ajax({
      url: ajaxurl,
      type: "POST",
      dataType: "json",
      data: {
        action: "gscf7_activate_plugin",
        plugin_slug: pluginSlug,
        security: jQuery("#gs-ajax-nonce").val(),
      },

      success: function (response) {
        loaderSpan.removeClass("loading");

        if (response.success) {
          //  Success → reload only
          location.reload();
        } else {
          //  Permission denied → open popup
          jQuery(".popup-actions-active-msg-free").text(
            response.data.message ||
              "You do not have permission to activate this plugin.",
          );

          jQuery("#gscf7-confirm-active-popup-free").removeClass("d-none");

          button.prop("disabled", false);
        }
      },

      error: function () {
        loaderSpan.removeClass("loading");

        jQuery(".popup-actions-active-msg-free").text(
          "Something went wrong. Please try again.",
        );

        jQuery("#gscf7-confirm-active-popup-free").removeClass("d-none");

        button.prop("disabled", false);
      },
    });
  });

  /**
   * Handle plugin deactivation button click via AJAX.
   *
   * - Sends plugin slug to server for deactivation
   * - On success, shows alert and reloads the page
   * - On error, shows AJAX error alert
   */

  let selectedPluginSlug = "";
  // Open popup on deactivate click
  jQuery(".gscf7-free-deactivate-plugin").on("click", function (e) {
    selectedPluginSlug = jQuery(this).data("plugin");
    jQuery("#gscf7-confirm-dective-popup-free").removeClass("d-none");
  });
  // Cancel button
  jQuery("#gscf7-dective-popup-cancel-free").on("click", function () {
    jQuery("#gscf7-confirm-dective-popup-free").addClass("d-none");
    selectedPluginSlug = "";
  });
  // Confirm deactivate
  jQuery("#gscf7-deactive-popup-confirm-free").on("click", function () {
    if (!selectedPluginSlug) return;
    jQuery.ajax({
      url: ajaxurl,
      type: "POST",
      dataType: "json",
      data: {
        action: "gscf7_deactivate_plugin",
        plugin_slug: selectedPluginSlug,
        security: jQuery("#gs-ajax-nonce").val(),
      },
      success: function (response) {
        if (response.success) {
          jQuery("#gscf7-confirm-dective-popup-free").addClass("d-none");
          location.reload();
        }
      },
    });
  });
  // filter extations tab
  document.querySelectorAll(".market-tab").forEach((tab) => {
    tab.addEventListener("click", function () {
      let filter = this.dataset.filter;

      document
        .querySelectorAll(".market-tab")
        .forEach((t) => t.classList.remove("active"));

      this.classList.add("active");

      document.querySelectorAll(".gsc-market-item").forEach((card) => {
        if (filter === "all") {
          card.style.display = "block";
        } else {
          card.style.display = card.classList.contains(filter)
            ? "block"
            : "none";
        }
      });
    });
  });
});
