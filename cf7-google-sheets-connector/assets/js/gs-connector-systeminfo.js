/**
 * Copies formatted system information text from the .gscf7-info-container element to clipboard.
 * It extracts all <h3> headings and <td> values inside .info-content, formats them,
 * and shows a temporary success message upon successful copy.
 */
jQuery(document).ready(function ($) {
  jQuery(document).on("click", "#gscf7-copy-info", function (e) {
    e.preventDefault();
    copySystemInfo();
  });

  function copySystemInfo() {
    const container = document.querySelector(".info-container");
    if (!container) return;

    let output = "";

    //   Loop all sections
    const sections = container.querySelectorAll(".mb-20");

    sections.forEach((section) => {
      // =========================
      //  GET BUTTON TEXT (FIXED)
      // =========================
      const btn = section.querySelector(".info-button");

      let title = "";
      if (btn) {
        title = Array.from(btn.childNodes)
          .filter((node) => node.nodeType === 3) // only text nodes
          .map((node) => node.nodeValue.trim())
          .join(" ")
          .trim();
      }

      if (title) {
        output += title + "\n=========================================\n";
      }

      // =========================
      //  GET TABLE DATA
      // =========================
      const contentDiv = section.nextElementSibling;

      if (!contentDiv) return;

      const rows = contentDiv.querySelectorAll("tr");

      rows.forEach((tr) => {
        const cols = tr.querySelectorAll("td");

        if (cols.length >= 2) {
          const key = (cols[0].innerText || "").trim();
          const value = (cols[1].innerText || "").trim();

          if (key && value) {
            output += `${key}: ${value}\n`;
          }
        }
      });

      output += "\n";
    });

    // =========================
    // COPY TO CLIPBOARD
    // =========================
    const textarea = document.createElement("textarea");
    textarea.value = output.trim();
    document.body.appendChild(textarea);

    textarea.select();
    document.execCommand("copy");

    document.body.removeChild(textarea);

    // =========================
    // SUCCESS MESSAGE
    // =========================
    jQuery(".gsc-copy-msg").removeClass("d-none").hide().fadeIn();

    setTimeout(function () {
      jQuery(".gsc-copy-msg").fadeOut();
    }, 2000);
  }
  // First section always open on load
  $("#info-container").show();

  function toggleSection(target) {
    $(
      "#info-container, #cf7-free-wordpress-info-container, #Drop-info-container, #active-info-container, #netplug-info-container, #acplug-info-container, #server-info-container, #database-info-container, #wrcons-info-container, #ftps-info-container",
    )
      .not(target)
      .slideUp();

    $(target).slideToggle();
  }

  $("#cf7-free-show-info-button").click(function () {
    toggleSection("#info-container");
  });

  $("#cf7-free-show-wordpress-info-button").click(function () {
    toggleSection("#cf7-free-wordpress-info-container");
  });

  $("#show-Drop-info-button").click(function () {
    toggleSection("#Drop-info-container");
  });

  $("#show-active-info-button").click(function () {
    toggleSection("#active-info-container");
  });

  $("#show-netplug-info-button").click(function () {
    toggleSection("#netplug-info-container");
  });

  $("#show-acplug-info-button").click(function () {
    toggleSection("#acplug-info-container");
  });

  $("#show-server-info-button").click(function () {
    toggleSection("#server-info-container");
  });

  $("#show-database-info-button").click(function () {
    toggleSection("#database-info-container");
  });

  $("#show-wrcons-info-button").click(function () {
    toggleSection("#wrcons-info-container");
  });

  $("#show-ftps-info-button").click(function () {
    toggleSection("#ftps-info-container");
  });
  // JavaScript function to copy the error log to the clipboard
  function copyErrorLog() {
    var $wrapper = jQuery(".gscf7-free-error-log-table");
    var finalText = "";

    $wrapper.find("tbody tr").each(function () {
      var $cols = jQuery(this).find("td");
      if ($cols.length < 4) return;

      finalText += $cols.eq(0).text().trim() + "\n";
      finalText += $cols.eq(1).text().trim() + "\n";
      finalText += $cols.eq(2).text().trim() + "\n";
      finalText += $cols.eq(3).text().trim() + "\n";
      finalText += "----------------------------------------\n";
    });

    var textarea = document.createElement("textarea");
    textarea.value = finalText;
    textarea.style.position = "fixed";
    textarea.style.opacity = "0";

    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand("copy");
    document.body.removeChild(textarea);

    // Show message immediately
    var $msg = jQuery("#gscf7-free-error-copy").siblings(".gsc-copy-msg");
    $msg.removeClass("d-none");

    // Hide after 1 second
    setTimeout(function () {
      $msg.addClass("d-none");
    }, 1000);
  }

  jQuery("#gscf7-free-error-copy").on("click", function () {
    copyErrorLog();
  });
});
jQuery(document).ready(function ($) {
  $("#gscf7-free-csv-info").on("click", function (e) {
    e.preventDefault();

    var $table = $(".gscf7-free-error-log-table");

    if (!$table.length) {
      return;
    }

    var rows = $table.find("tr");
    var csvContent = "";

    if (!rows.length) {
      return;
    }

    rows.each(function () {
      var cols = $(this).find("th, td");
      var rowData = [];

      cols.each(function () {
        var text = $(this).text().trim();

        // Escape double quotes
        text = text.replace(/"/g, '""');

        rowData.push('"' + text + '"');
      });

      csvContent += rowData.join(",") + "\n";
    });

    // Create CSV file
    var blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });

    var link = document.createElement("a");
    var url = URL.createObjectURL(blob);

    link.setAttribute("href", url);
    link.setAttribute("download", "debug-log.csv");
    link.style.display = "none";

    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  });
});
