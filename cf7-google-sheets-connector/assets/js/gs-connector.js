jQuery(document).ready(function ($) {
  $(".cf7gs-tab-toggle").on("click", function () {
    var tabClass = $(this).data("tab");

    $(".cf7-sub-tab").hide();

    $("." + tabClass).show();

    if (tabClass === "cf7-sub-tab-multi") {
      $(".cf7-sub-tab-single-li").removeClass("cf7-sub-tab-active");

      $(".cf7-sub-tab-multi-li").addClass("cf7-sub-tab-active");
    } else {
      $(".cf7-sub-tab-multi-li").removeClass("cf7-sub-tab-active");

      $(".cf7-sub-tab-single-li").addClass("cf7-sub-tab-active");
    }
  });

  $(".cf7-sub-tab").hide();

  $(".cf7-sub-tab-single").show();

  $(document).on("click", "#save-gs-code", function () {
    $(".loading-sign").addClass("loading");

    var data = {
      action: "verify_gs_integation",

      code: $("#gs-code").val(),

      security: $("#gs-ajax-nonce").val(),
    };

    $.post(ajaxurl, data, function (response) {
      $(".loading-sign").removeClass("loading");

      $("#gs-validation-message").empty();

      if (!response.success) {
        $(
          "<div class='gsc-msg gsc-error fw-400 text-dark text-center pt-10 pb-10 manual-margin'>Access code Can't be blank</div>",
          ).appendTo("#gs-validation-message");
      } else {
        $(
          "<div class='gsc-msg gsc-success fw-400 text-dark text-center pt-10 pb-10 manual-margin'>Your Google Access Code is Authorized and Saved.</div>",
          ).appendTo("#gs-validation-message");

        setTimeout(function () {
          window.location.href = $("#redirect_auth").val();
        }, 2000);
      }
    });
  });

  $(document).on("click", "#gscf7-free-deactivate-log", function (e) {
    e.preventDefault();

    $("#gscf7-confirm-deactive-popup-free").removeClass("d-none");
  });

  $(document).on("click", "#gscf7-deactive-popup-free-cancel", function () {
    $("#gscf7-confirm-deactive-popup-free").addClass("d-none");
  });

  $(document).on("click", "#gscf7-deactive-popup-free-confirm", function () {
    $(".loading-sign-deactive").addClass("loading");

    var data = {
      action: "deactivate_gs_integation",

      security: $("#gs-ajax-nonce").val(),
    };

    $.post(ajaxurl, data, function (response) {
      $(".loading-sign-deactive").removeClass("loading");

      // $("#gscf7-confirm-deactive-popup-free").addClass("d-none");

      $("#deactivate-message").empty();
      if (!response.success) {
       $("#deactivate-message").html(
        "<div class='gsc-msg gsc-error fw-400 text-dark text-center pt-10 pb-10 manual-margin'>Error while deactivation.</div>",
        );
     } else {
       $("#deactivate-message").html(
        "<div class='gsc-msg gsc-success fw-400 text-dark text-center pt-10 pb-10 manual-margin'>Your account is removed. Reauthenticate again to integrate Contact Form with Google Sheet.</div>",
        );
       setTimeout(function () {
        location.reload();
      }, 1000);
     }
   });
     // close popup
    $("#gscf7-confirm-deactive-popup-free").addClass("d-none");

  });

  $(document).on("click", ".gscf7-free-clear-content-logs", function () {
    $.post(
      ajaxurl,

      {
        action: "cf7_clear_debug_log",

        security: $("#gs-ajax-nonce").val(),
      },

      function (response) {
        if (response.success) {
          setTimeout(() => location.reload(), 1000);
        }
      },
      );
  });

  $(".system-error-cf7free-logs")
  .hide()

  .on("click", function (e) {
    e.stopPropagation();
  });

const faqTrigger = $(".cd-faq-trigger");

  faqTrigger.on("click", function (event) {
    event.preventDefault();

    const dataid = $(this).attr("data-id");

    for (let i = 1; i <= 5; i++) {
      if (i != dataid && i != 5) {
        $(".cd-faq-content" + i).hide(200);
      }
    }

    $(this)
    .next(".cd-faq-content" + dataid)

    .slideToggle(200)

    .end()

    .parent("li")

    .toggleClass("content-visible");
  });

  const opener = document.getElementById("opener");

  const opener2 = document.getElementById("opener2");

  const popup = document.getElementById("popup-gs");

  const popup2 = document.getElementById("popup-gs2");

  const closeButton = document.getElementById("closeButton");

  const closeButton2 = document.getElementById("closeButton2");

  const popupOuter = document.getElementById("popup-outer-gs");

  const popupOuter2 = document.getElementById("popup-outer-gs2");

  if (opener) opener.addEventListener("click", () => fadeIn(popup));

  if (opener2) opener2.addEventListener("click", () => fadeIn(popup2));

  if (closeButton) closeButton.addEventListener("click", () => fadeOut(popup));

  if (closeButton2)
    closeButton2.addEventListener("click", () => fadeOut(popup2));

  if (popupOuter)
    popupOuter.addEventListener("click", (e) => {
      if (e.target === popupOuter) fadeOut(popup);
    });

  if (popupOuter2)
    popupOuter2.addEventListener("click", (e) => {
      if (e.target === popupOuter2) fadeOut(popup2);
    });

  function fadeIn(element) {
    if (!element) return;

    let opacity = 0;

    element.style.opacity = opacity;

    element.style.display = "block";

    const fadeInInterval = setInterval(() => {
      if (opacity < 1) {
        opacity += 0.1;

        element.style.opacity = opacity;
      } else {
        clearInterval(fadeInInterval);
      }
    }, 50);
  }

  function fadeOut(element) {
    if (!element) return;

    let opacity = 1;

    const fadeOutInterval = setInterval(() => {
      if (opacity > 0) {
        opacity -= 0.1;

        element.style.opacity = opacity;
      } else {
        clearInterval(fadeOutInterval);

        element.style.display = "none";
      }
    }, 50);
  }
});

document.addEventListener("DOMContentLoaded", function () {
  document.querySelectorAll("select.gsc-select").forEach((select) => {
    if (select.classList.contains("auto-processed")) return;

    select.classList.add("auto-processed");

    select.style.display = "none";

    const wrapper = document.createElement("div");

    wrapper.className = "auto-select";

    const display = document.createElement("div");

    display.className = "auto-select-display";

    display.innerText = select.options[select.selectedIndex]?.text || "Select";

    const optionsBox = document.createElement("div");

    optionsBox.className = "auto-select-options";

    [...select.options].forEach((opt, index) => {
      const item = document.createElement("div");

      item.className = "auto-select-option";

      item.innerText = opt.text;

      item.addEventListener("click", () => {
        select.selectedIndex = index;

        display.innerText = opt.text;

        optionsBox.style.display = "none";

        select.dispatchEvent(new Event("change", { bubbles: true }));
      });

      optionsBox.appendChild(item);
    });

    display.addEventListener("click", (e) => {
      e.stopPropagation();

      optionsBox.style.display =
      optionsBox.style.display === "block" ? "none" : "block";
    });

    wrapper.appendChild(display);

    wrapper.appendChild(optionsBox);

    select.parentNode.insertBefore(wrapper, select);

    wrapper.appendChild(select);
  });

  document.addEventListener("click", function (e) {
    document.querySelectorAll(".auto-select-options").forEach((box) => {
      if (!box.parentElement.contains(e.target)) {
        box.style.display = "none";
      }
    });
  });
});

document.addEventListener("DOMContentLoaded", function () {
  document.querySelectorAll(".gsc-slider-wrapper").forEach(function (wrapper) {
    const slider = wrapper.querySelector(".gsc-slider");

    const slides = wrapper.querySelectorAll(".gsc-slide");

    const prevBtn = wrapper.querySelector(".gsc-nav.prev");

    const nextBtn = wrapper.querySelector(".gsc-nav.next");

    let current = 0;

    function updateSlider() {
      slider.style.transform = "translateX(" + -current * 100 + "%)";
    }

    nextBtn.addEventListener("click", function () {
      current = (current + 1) % slides.length;

      updateSlider();
    });

    prevBtn.addEventListener("click", function () {
      current = (current - 1 + slides.length) % slides.length;

      updateSlider();
    });
  });
});

jQuery(document).ready(function ($) {
  function checkTableEmpty() {
    var rowCount = $("#gsc-fluentformformtable tbody tr").length;

    if (rowCount === 0) {
      $(".gscf7-fluentform-feeds-list").removeClass("mt-30");
    }
  }

  checkTableEmpty();
});

jQuery(document).ready(function ($) {
  $("#cf7gs_dro_option").on("change", function () {
    const value = $(this).val();

    if (value === "cf7_manual") {
      $("#cf7_manual_popup").removeClass("d-none");
    }
  });

  $(document).on("click", ".gscf7-free-popup-close", function () {
    $("#cf7_manual_popup").addClass("d-none");

    $("#cf7gs_dro_option").val("cf7_existing");
  });

  $(document).on("click", "#cf7_manual_popup", function (e) {
    if ($(e.target).is(this)) {
      $(this).addClass("d-none");

      $("#cf7gs_dro_option").val("cf7_existing");
    }
  });
});

jQuery(document).ready(function ($) {
  function disableButton() {
    $(".uninstall-settings-save-free")
    .prop("disabled", true)

    .addClass("common-disable");
  }

  function enableButton() {
    $(".uninstall-settings-save-free")
    .prop("disabled", false)

    .removeClass("common-disable");
  }

  disableButton();

  $(document).on("change", "#gscf7_uninstall_settings_free", function () {
    if ($(this).is(":checked")) {
      $("#cf7gs_uninstall-free").removeClass("d-none");
    }

    enableButton();
  });

  $(document).on("click", ".uninstall-cancel, .gsc-pro-close", function () {
    $("#cf7gs_uninstall-free").addClass("d-none");

    $("#gscf7_uninstall_settings_free").prop("checked", false);

    disableButton();
  });

  $(document).on("click", ".uninstall-confirm", function (e) {
    e.preventDefault();

    $("#cf7gs_uninstall-free").addClass("d-none");
  });

  $(document).on("click", ".uninstall-settings-save-free", function (e) {
    e.preventDefault();

    let settingValue = $("#gscf7_uninstall_settings_free").is(":checked")
    ? "Yes"
    : "No";

    saveUninstallSetting(settingValue);
  });

  function saveUninstallSetting(settingValue) {
    $(".loading-uninstall-free").addClass("loading");

    $("#gscf7-uninstall-msg-free").removeClass("d-none");

    $.ajax({
      url: ajaxurl,

      type: "POST",

      dataType: "json",

      data: {
        action: "gscf7_save_uninstall_settings",

        uninstall_setting: settingValue,

        security: $("#gs-ajax-nonce").val(),
      },

      success: function (response) {
        $(".loading-uninstall-free").removeClass("loading");

        if (response.success) {
          $("#gscf7-uninstall-msg-free").removeClass("d-none");

          setTimeout(function () {
            $("#gscf7-uninstall-msg-free").fadeOut();
          }, 2000);

          disableButton();
        }
      },
    });
  }
});

document.addEventListener("DOMContentLoaded", function () {
  var el = document.querySelector(".gscf7-free-selected-method");

  if (el && el.dataset.value && el.dataset.value.trim() !== "") {
    var badgeText = el.dataset.value.trim();

    var getvalue = el.getAttribute("data-value");

    document

    .querySelectorAll(".nav-tab-wrapper .nav-tab")

    .forEach(function (tab) {
      var href = tab.getAttribute("href") || "";

      if (href.indexOf("tab=integration") !== -1) {
        tab.style.position = "relative";

        if (
          tab.querySelector(".gscf7-free-selected-badge") ||
          tab.querySelector(".gscf7-free-selected-authrequired-badge")
          ) {
          return;
      }

      var badge = document.createElement("div");

      if (getvalue == "Auth Required") {
        badge.className = "gscf7-free-selected-authrequired-badge";
      } else {
        badge.className = "gscf7-free-selected-badge";
      }

      badge.textContent = badgeText;

      tab.appendChild(badge);
    }
  });
  }
});

jQuery(document).ready(function ($) {
  var $btn = $("#gs-cf7db-setting-btn");

  var $toggle = $("#gs_cf7db_setting");

  var $loader = $("#gscf7-db-loader");

  $btn.prop("disabled", true).addClass("common-disable");

  $toggle.on("change", function () {
    $btn.prop("disabled", false).removeClass("common-disable");
  });

  $btn.on("click", function (e) {
    var isChecked = $toggle.is(":checked") ? 1 : 0;

    var nonce = $("#gs-ajax-nonce").val();

    $btn.prop("disabled", true).addClass("common-disable");

    $loader.addClass("loading");

    $.ajax({
      url: ajaxurl,

      type: "POST",

      dataType: "json",

      data: {
        action: "save_gs_cf7db_setting",

        gs_cf7db_setting: isChecked,

        security: nonce,
      },

      success: function (response) {
        if (response.success) {
          $("#gscf7-db-msg").removeClass("d-none");

          $btn.prop("disabled", true).addClass("common-disable");
        } else {
        }

        setTimeout(function () {
          $("#gscf7-db-msg").addClass("d-none");
        }, 1000);
      },

      complete: function () {
        $loader.removeClass("loading");

        location.reload();
      },
    });
  });
});

jQuery(document).ready(function ($) {
  const noticeKey = "gscf7_notice_hidden_until";

  const oneDay = 24 * 60 * 60 * 1000;

  $(".gscf7-free #pro-dismiss-header-notice").on("click", function () {
    const now = new Date().getTime();

    $(".gscf7-free #pro-notice-bar").slideUp(200);

    localStorage.setItem(noticeKey, now + oneDay);
  });

  const hideUntil = localStorage.getItem(noticeKey);

  const now = new Date().getTime();

  if (hideUntil && now < hideUntil) {
    $(".gscf7-free #pro-notice-bar").hide();
  }
});

document.addEventListener("DOMContentLoaded", function () {
  const copyBtn = document.getElementById("gsc-copy-logs-free");

  const msgDiv = document.querySelector(".gsc-copy-msg");

  if (!copyBtn || !msgDiv) return;

  copyBtn.addEventListener("click", function (e) {
    e.preventDefault();

    const tbody = document.querySelector(".error-log-table tbody");

    if (!tbody) return;

    const rows = tbody.querySelectorAll("tr");

    if (!rows.length) {
      showMessage("No logs found to copy.", "error");

      return;
    }

    let output = "";

    rows.forEach((tr) => {
      const cols = tr.querySelectorAll("td");

      if (cols.length < 5) return;

      const date = (cols[0].innerText || "").trim();

      const errorId = (cols[1].innerText || "").trim();

      const code = (cols[2].innerText || "").trim();

      const message = (cols[3].innerText || "").trim();

      const detailsCell = cols[4];

      const details = detailsCell.querySelector("pre")
      ? detailsCell.querySelector("pre").innerText.trim()
      : (detailsCell.innerText || "").trim();

      output += `Date: ${date}\n`;

      output += `Error ID: ${errorId}\n`;

      output += `Code: ${code}\n`;

      output += `Message: ${message}\n`;

      output += `Details: ${details}\n`;

      output += `\n==========================\n\n`;
    });

    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard

      .writeText(output)

      .then(function () {
        showMessage("Copied successfully", "success");
      })

      .catch(function () {
        fallbackCopy(output);
      });
    } else {
      fallbackCopy(output);
    }

    function fallbackCopy(text) {
      const ta = document.createElement("textarea");

      ta.value = text;

      document.body.appendChild(ta);

      ta.select();

      try {
        document.execCommand("copy");

        showMessage("Copied successfully", "success");
      } catch (err) {
        showMessage("Copy failed. Please copy manually.", "error");
      }

      document.body.removeChild(ta);
    }

    function showMessage(text, type) {
      msgDiv.innerText = text;

      msgDiv.classList.remove("d-none");

      setTimeout(function () {
        msgDiv.classList.add("d-none");
      }, 2000);
    }
  });
});

jQuery(document).ready(function ($) {


  const isNewPage = window.location.href.includes("page=wpcf7-new");

  /* ---------------------------

     NEW FORM PAGE

  ----------------------------*/

  if (isNewPage) {
    sessionStorage.setItem("cf7_main_tab", "form-panel-tab");

    sessionStorage.removeItem("cf7_active_tab");

    return false;
  }

  /* ---------------------------

     Restore Main CF7 Tab

  ----------------------------*/

  var savedMainTab = sessionStorage.getItem("cf7_main_tab");

  if (savedMainTab) {
    $("#" + savedMainTab).trigger("click");
  }

  /* ---------------------------

     Restore Google Sheet Sub Tab

  ----------------------------*/

  var savedSubTab = sessionStorage.getItem("cf7_active_tab");

  if (savedSubTab && savedMainTab === "google_sheets-tab") {
    $(".cf7-sub-tab").hide();

    $("." + savedSubTab).show();

    $("#contact-form-editor-tabs li").removeClass("cf7-sub-tab-active");

    $('.cf7gs-tab-toggle[data-tab="' + savedSubTab + '"]')
    .parent("li")

    .addClass("cf7-sub-tab-active");
  }

  /* ---------------------------

     Main Tab Click

  ----------------------------*/

  $("#contact-form-editor-tabs button[role='tab']").on("click", function () {
    var mainTabId = $(this).attr("id");

    sessionStorage.setItem("cf7_main_tab", mainTabId);
  });

  /* ---------------------------

     Google Sheet Sub Tab Click

  ----------------------------*/

  $(".cf7gs-tab-toggle").on("click", function (e) {
    e.preventDefault();

    var tabClass = $(this).data("tab");

    $(".cf7-sub-tab").hide();

    $("." + tabClass).show();

    $("#contact-form-editor-tabs li").removeClass("cf7-sub-tab-active");

    $(this).parent("li").addClass("cf7-sub-tab-active");

    sessionStorage.setItem("cf7_active_tab", tabClass);
  });
});

jQuery(document).ready(function ($) {
  const btn = $("#gscf7-free-assistantBtn");

  const menu = $("#gscf7-free-assistantMenu");

  if (btn.length && menu.length) {
    btn.on("click", function (e) {
      e.stopPropagation();

      menu.toggleClass("active");
    });

    $(document).on("click", function (e) {
      if (
        !btn.is(e.target) &&
        btn.has(e.target).length === 0 &&
        !menu.is(e.target) &&
        menu.has(e.target).length === 0
        ) {
        menu.removeClass("active");
    }
  });
  }
});

jQuery(document).ready(function ($) {
  $(document).on("click", "#cf7gs-free-csv", function (e) {
    e.preventDefault();

    $("#cf7gs-free-pro-csv").removeClass("d-none");
  });

  $(document).on("click", ".gsc-pro-close", function () {
    $("#cf7gs-free-pro-csv").addClass("d-none");
  });

  $(document).on("click", "#cf7gs-free-pro-csv", function (e) {
    if ($(e.target).is("#cf7gs-free-pro-csv")) {
      $(this).addClass("d-none");
    }
  });

  $(document).on("click", ".sent_sheet", function (e) {
    e.preventDefault();

    $("#cf7gs-free-pro").removeClass("d-none");
  });

  $(document).on("click", ".gsc-pro-close", function () {
    $("#cf7gs-free-pro").addClass("d-none");
  });

  $(document).on("click", "#cf7gs-free-pro", function (e) {
    if ($(e.target).is("#cf7gs-free-pro")) {
      $(this).addClass("d-none");
    }
  });
});

jQuery(document).ready(function ($) {
  const urlParams = new URLSearchParams(window.location.search);

  if (urlParams.has("code")) {
    const btn = $("#save-gs-code");

    if (btn.length) {
      $("html, body").animate(
      {
        scrollTop: btn.offset().top - 100,
      },

      500,
      );

      btn.focus();
    }
  }

  $(document).on("click", "#save-method-api-cf7", function (e) {
    e.preventDefault();

    $(".loading-sign-method-api-cf7").addClass("loading");

    const method_api_cf7 = $("#cf7gs_dro_option").val();

    const data = {
      action: "save_method_api_cf7",

      method_api_cf7,

      security: $("#gs-ajax-nonce").val(),
    };

    $.post(ajaxurl, data, function (response) {
      setTimeout(() => location.reload(), 1000);
    });
  });

  /**
   * Show a message in the service account validation area.
   *
   * @param {string}  message Text to display.
   * @param {boolean} success Render as success instead of error.
   */
  function cf7gsServiceNotify(message, success) {
    var cls = success ? "gsc-success" : "gsc-error";

    jQuery("#gs-validation-message-auth")
      .empty()
      .append(
        jQuery("<div/>", {
          class:
            "gsc-msg " +
            cls +
            " fw-400 text-dark text-center pt-10 pb-10 manual-margin",
          text: message,
        }),
      );
  }

  /**
   * Validate a parsed Google service account key.
   *
   * Mirrored server-side in Gs_Connector_Service::gscf7_validate_service_account_json().
   *
   * @param {Object} json Parsed JSON object.
   * @return {string} Empty string when valid, otherwise the error message.
   */
  function cf7gsServiceValidate(json) {
    var required = [
      "client_email",
      "private_key",
      "type",
      "project_id",
      "token_uri",
    ];

    var missing = required.some(function (key) {
      return !json[key] || String(json[key]).trim() === "";
    });

    if (missing) {
      return "Your uploaded JSON key is invalid.";
    }

    if (
      !/^[^\s@]+@[^\s@]+\.iam\.gserviceaccount\.com$/.test(
        String(json.client_email).trim(),
      ) ||
      String(json.private_key).indexOf("BEGIN PRIVATE KEY") === -1
    ) {
      return "Your uploaded JSON key is invalid.";
    }

    if (String(json.type).trim() !== "service_account") {
      return "Invalid JSON: file is not a service_account type.";
    }

    return "";
  }

  jQuery(document).ready(function ($) {
    $("#gs_cf7_save_service_json").on("click", function () {
      var jsonRaw = $("#gs_cf7_service_json").val();

      $("#gs-validation-message-auth").empty();

      if (!jsonRaw || $.trim(jsonRaw) === "") {
        cf7gsServiceNotify(
          "Please upload or paste a service account JSON file.",
        );
        return;
      }

      var json;

      try {
        json = JSON.parse(jsonRaw);
      } catch (e) {
        cf7gsServiceNotify("Your uploaded JSON key is invalid.");
        return;
      }

      var error = cf7gsServiceValidate(json);

      if (error) {
        cf7gsServiceNotify(error);
        return;
      }

      var data = {
        action: "save_service_account_json_cf7",

        json: jsonRaw,

        security: $("#gs-ajax-nonce").val(),
      };

      $(".loading-sign-service-auth").addClass("loading");

      $.post(ajaxurl, data, function (res) {
        $(".loading-sign-service-auth").removeClass("loading");

        if (res.success) {
          cf7gsServiceNotify(
            "Your Service Account has been saved.",
            true,
          );
          location.reload();
        } else {
          cf7gsServiceNotify(
            (res.data && res.data.error) || "Error saving JSON",
          );
        }
      });
    });
  });

  $("#gs_cf7_upload_json").on("change", function (e) {
    const file = e.target.files[0];

    if (!file) return;

    const reader = new FileReader();

    reader.onload = function (event) {
      try {
        const content = JSON.parse(event.target.result);

        $("#gs_cf7_service_json").val(JSON.stringify(content, null, 2));

        // Validate immediately so the file is rejected at upload time rather
        // than only when Save is pressed.
        const uploadError = cf7gsServiceValidate(content);

        if (uploadError) {
          cf7gsServiceNotify(uploadError);
        } else {
          $("#gs-validation-message-auth").empty();
        }

        $("#gs_cf7_service_json").trigger("input");
      } catch (err) {
        // Previously failed silently, leaving the user with no feedback.
        cf7gsServiceNotify("Your uploaded JSON key is invalid.");
      }
    };

    reader.readAsText(file);
  });

  jQuery(document).ready(function ($) {
    const $textarea = $("#gs_cf7_service_json");

    const $saveBtn = $("#gs_cf7_save_service_json");

    const $fileInput = $("#gs_cf7_upload_json");

    function toggleSaveButton() {
      if ($.trim($textarea.val()) === "") {
        $saveBtn.prop("disabled", true).addClass("common-disable");
      } else {
        $saveBtn.prop("disabled", false).removeClass("common-disable");
      }
    }

    toggleSaveButton();

    $textarea.on("input", function () {
      toggleSaveButton();
    });

    $fileInput.on("change", function (e) {
      const file = e.target.files[0];

      if (!file) return;

      const reader = new FileReader();

      reader.onload = function (event) {
        $textarea.val(event.target.result);

        $textarea.trigger("input");
      };

      reader.readAsText(file);
    });
  });
});

document.addEventListener("DOMContentLoaded", function () {
  const slides = document.querySelectorAll(".notification-gsc-slide");

  const prevBtn = document.querySelector(".notification-gsc-slider-btn.prev");

  const nextBtn = document.querySelector(".notification-gsc-slider-btn.next");

  let index = 0;

  function updateSlider() {
    slides.forEach((slide) => slide.classList.remove("active"));

    slides[index].classList.add("active");
  }

  if (nextBtn) {
    nextBtn.onclick = function () {
      index = (index + 1) % slides.length;

      updateSlider();
    };
  }

  if (prevBtn) {
    prevBtn.onclick = function () {
      index = (index - 1 + slides.length) % slides.length;

      updateSlider();
    };
  }

  updateSlider();
});

jQuery(document).ready(function ($) {
  const track = $(".notification-gsc-slider-track");

  if (!track.children().length || $.trim(track.html()) === "") {
    $(".notification-gsc-notice-slider").addClass("d-none");
  }

  let totalSlides = $(
    ".notification-gsc-slider-track .notification-gsc-slide",
    ).length;

  if (totalSlides <= 1) {
    $(".notification-gsc-slider-arrows").hide();
  }

  function showNextSlide(currentSlide) {
    var track = currentSlide.closest(".notification-gsc-slider-track");

    var slides = track.find(".notification-gsc-slide");

    var currentIndex = slides.index(currentSlide);

    var nextIndex = currentIndex + 1;

    currentSlide.remove();

    slides = track.find(".notification-gsc-slide");

    if (slides.length > 0) {
      if (nextIndex >= slides.length) {
        nextIndex = 0;
      }

      slides.hide().eq(nextIndex).show();
    }

    location.reload();
  }

  $(document).on("click", ".gsc-upgrade-close, .gsc-dismiss-btn", function () {
    var key = $(this).data("key");

    var currentSlide = $(this).closest(".notification-gsc-slide");

    $.post(
      ajaxurl,

      {
        action: "gscf7_dismiss_notice",

        key: key,

        security: $("#gs-ajax-nonce").val(),
      },

      function () {
        showNextSlide(currentSlide);
      },
      );
  });

  $(document).on("click", ".gsc-btn-later", function () {
    var key = $(this).data("key");

    var currentSlide = $(this).closest(".notification-gsc-slide");

    $.post(
      ajaxurl,

      {
        action: "gscf7_snooze_notice",

        key: key,

        security: $("#gs-ajax-nonce").val(),
      },

      function () {
        showNextSlide(currentSlide);
      },
      );
  });
});

/***new slider for without permission for existing method */

document.addEventListener("DOMContentLoaded", function () {
  document.querySelectorAll(".gsc-slider-wrapper").forEach(function (wrapper) {
    const slider = wrapper.querySelector(".gsc-slider");

    const slides = wrapper.querySelectorAll(".gsc-slide");

    const prevBtn = wrapper.querySelector(".gsc-nav.prev");

    const nextBtn = wrapper.querySelector(".gsc-nav.next");

    let current = 0;

    function updateSlider() {
      slider.style.transform = "translateX(" + -current * 100 + "%)";
    }

    nextBtn?.addEventListener("click", function () {
      current = (current + 1) % slides.length;

      updateSlider();
    });

    prevBtn?.addEventListener("click", function () {
      current = (current - 1 + slides.length) % slides.length;

      updateSlider();
    });

    wrapper.goToSlide = function (index) {
      current = index;

      updateSlider();
    };
  });

  /* =========================

     AUTO MOVE TO STEP 4

  ========================== */

  setTimeout(function () {
    const errorBox = document.querySelector(".cf7-free-gsc-permission-error");

    const target = document.querySelector(".cf7-free-connection-guide-slider");

    if (!errorBox) {
      return;
    }

    if (!target) {
      return;
    }

    if (target.goToSlide) {
      target.goToSlide(3);

      target.scrollIntoView({
        behavior: "smooth",

        block: "center",
      });
    }
  }, 800);

  jQuery(document).ready(function ($) {
    const $popup = $("#gs-confirm-service-popup");

    const $loader = $(".loading-sign-service-auth");
    const $msg = $("#gs-validation-message-auth");

    $("#gs_cf7_deactivate_service_auth").on("click", function (e) {
      e.preventDefault();

      $popup.removeClass("d-none");
    });

    $("#gs-service-popup-cancel").on("click", function () {
      $popup.addClass("d-none");
    });

    $("#gs-service-popup-confirm").on("click", function () {
      $popup.addClass("d-none");

      const data = {
        action: "deactivate_service_account_cf7",

        security: $("#gs-ajax-nonce").val(),
      };

      $loader.addClass("active-loading").addClass("loading");
      $("#gs-validation-message-auth").append(
        "<div class='gsc-msg gsc-error fw-400 text-dark text-center pt-10 pb-10 manual-margin'>Your account is removed. Reauthenticate again to integrate Contact Form with Google Sheet</div>",
        );
      $.post(ajaxurl, data, function () {
        location.reload();
      }).fail(function () {
        $loader.removeClass("active-loading").removeAttr("loading");
      });
    });
  });
});

jQuery(document).ready(function ($) {
  let hideTimer;

  $(document).on("click", ".gsc-connected-email a", function (e) {
    e.preventDefault();

    var $this = $(this);

    var email = $this.data("email");

    var $msg = $this.closest(".gsc-connected-email").find(".gsc-copy-msg");

    if (!email) return;

    var tempInput = $("<textarea>");

    $("body").append(tempInput);

    tempInput.val(email).select();

    document.execCommand("copy");

    tempInput.remove();

    clearTimeout(hideTimer);

    $msg.removeClass("d-none");

    hideTimer = setTimeout(function () {
      $msg.addClass("d-none");
    }, 1000);
  });

  jQuery(document).on("click", "#copy-service-email", function (e) {
    e.preventDefault();

    var email = jQuery(this).data("email");

    var msg = jQuery(this).next(".gsc-copy-msg");

    var tempInput = jQuery("<input>");

    jQuery("body").append(tempInput);

    tempInput.val(email).select();

    document.execCommand("copy");

    tempInput.remove();

    msg.removeClass("d-none").fadeIn();

    setTimeout(function () {
      msg.fadeOut(function () {
        jQuery(this).addClass("d-none");
      });
    }, 2000);
  });
});

jQuery(document).ready(function ($) {
  // Find sidebar container

  let container = $("#postbox-container-1");

  // Find your div

  let metabox = $("#gscf7-free-metabox");

  // Check exists

  if (container.length && metabox.length) {
    // Move as second section

    container.find(".postbox").eq(0).after(metabox);

    // Show div

    metabox.show();
  }
});

/****Extenxion Counter****/

jQuery(document).ready(function ($) {
  $(".gscf7-free-counter").each(function () {
    let $this = $(this);

    let countTo = parseFloat($this.attr("data-count"));

    $({ countNum: 0 }).animate(
    {
      countNum: countTo,
    },

    {
      duration: 2500,

      easing: "swing",

      step: function () {
        if (countTo % 1 !== 0) {
          $this.text(this.countNum.toFixed(1));
        } else {
          $this.text(Math.floor(this.countNum));
        }
      },

      complete: function () {
        if (countTo % 1 !== 0) {
          $this.text(countTo.toFixed(1));
        } else {
          $this.text(countTo);
        }
      },
    },
    );
  });
function gscf7LoadFeedPage(page) {
    $.post(
        ajaxurl,
        {
            action: "gscf7_paginate_feed_list",
            paged: page,
            security: $("#gscf7-pagination-nonce").val()
        },
        function (res) {
            if (res.success) {
                let rows = res.data.rows_html;
                let pagination = res.data.pagination_html;

                // Inject table rows
                $("#gscf7-feed-table-body").html(rows);

                // Inject pagination links
                $("#gscf7-pagination-wrap").html(pagination);

                // Toggle headers and pagination visibility based on feed existence
                if (!res.data.has_feeds) {
                    $("#gscf7-feed-table thead").hide();
                    $("#gscf7-pagination-wrap").hide();
                } else {
                    $("#gscf7-feed-table thead").show();
                    $("#gscf7-pagination-wrap").show();
                }

                $("#gscf7-feed-table").attr("data-page", page);
            }
        }
    );
}

// Initial load
$(document).ready(function () {
    gscf7LoadFeedPage(1);
});

// Event delegation for pagination buttons
$(document).on("click", ".gscf7-page-link", function (e) {
    e.preventDefault();
    let page = $(this).data("page");
    if (page) {
        gscf7LoadFeedPage(page);
    }
});
});
jQuery(function ($) {
    $('#gsc-clear-logs').on('click', function (e) {
        e.preventDefault();

        $.ajax({
            url: ajaxurl, 
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'gscf7_clear_logs',
                nonce: $('#gs-ajax-nonce').val()
            },
            success: function (response) {
              
                if (response.success) {
                   
                    location.reload();
                } 
            }
        });

    });

});