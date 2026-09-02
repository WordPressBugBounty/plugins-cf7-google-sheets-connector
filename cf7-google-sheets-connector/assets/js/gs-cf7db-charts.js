(function () {
  "use strict";

  var lineChart = null;
  var pieChart = null;
  var chartI18n = {};

  // Shared loader next to the "Form" dropdown. Both the chart/stat refresh
  // (initDashboardFormFilter) and the entries table refresh (initDashboardEntries)
  // fire in parallel off the same dropdown change, so this is reference-counted:
  // it only hides once every in-flight request it was shown for has finished.
  var pendingRequests = 0;

  function showLoader() {
    pendingRequests += 1;
    var loader = document.getElementById("gscf7-entries-loader");
    if (loader) {
      loader.classList.remove("d-none");
    }
  }

  function hideLoader() {
    pendingRequests = Math.max(0, pendingRequests - 1);
    if (pendingRequests === 0) {
      var loader = document.getElementById("gscf7-entries-loader");
      if (loader) {
        loader.classList.add("d-none");
      }
    }
  }

  document.addEventListener("DOMContentLoaded", function () {
    initCharts();
    initSortableTable();
    initDashboardFormFilter();
  });

  /**
   * Read the JSON payload the PHP template embedded and render the two charts.
   * Chart.js is enqueued only on this admin screen (see add_css_files()/add_js_files()).
   */
  function initCharts() {
    var dataEl = document.getElementById("gscf7-dashboard-chart-data");
    if (!dataEl || typeof Chart === "undefined") {
      return;
    }

    var data;
    try {
      data = JSON.parse(dataEl.textContent);
    } catch (e) {
      return;
    }

    chartI18n = data.i18n || {};
    renderCharts(data);
  }

  /**
   * (Re)draw the line + pie charts from a {labels, values, read, unread} payload.
   * Reused both for the initial page-load data and for AJAX refreshes triggered
   * by the "Form" filter dropdown.
   */
  function renderCharts(data) {
    var lineCanvas = document.getElementById("gscf7-entries-line-chart");
    if (lineCanvas) {
      if (lineChart) {
        lineChart.data.labels = data.labels;
        lineChart.data.datasets[0].data = data.values;
        lineChart.update();
      } else {
        lineChart = new Chart(lineCanvas.getContext("2d"), {
          type: "line",
          data: {
            labels: data.labels,
            datasets: [
              {
                label: chartI18n.entries || "Entries",
                data: data.values,
                borderColor: "#6d28d9",
                backgroundColor: "rgba(109, 40, 217, 0.12)",
                fill: true,
                tension: 0.35,
                pointRadius: 2,
              },
            ],
          },
          options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
              y: { beginAtZero: true, ticks: { precision: 0 } },
            },
          },
        });
      }
    }

    var pieCanvas = document.getElementById("gscf7-entries-pie-chart");
    if (pieCanvas) {
      if (pieChart) {
        pieChart.data.datasets[0].data = [data.read, data.unread];
        pieChart.update();
      } else {
        pieChart = new Chart(pieCanvas.getContext("2d"), {
          type: "doughnut",
          data: {
            labels: [chartI18n.read || "Read", chartI18n.unread || "Unread"],
            datasets: [
              {
                data: [data.read, data.unread],
                backgroundColor: ["#10b981", "#f59e0b"],
                borderWidth: 0,
              },
            ],
          },
          options: {
            responsive: true,
            plugins: { legend: { position: "bottom" } },
          },
        });
      }
    }
  }

  /**
   * On the Dashboard tab, selecting a form from the "Form" filter re-fetches
   * stat counts + chart data scoped to that form and refreshes the stat cards
   * and both charts in place (no page reload).
   */
  function initDashboardFormFilter() {
    var filterForm = document.getElementById("gscf7-entries-filter-form");
    var nonceField = document.getElementById("gscf7-dashboard-stats-nonce");

    if (!filterForm || !nonceField) {
      return;
    }

    var statEls = {
      total: document.getElementById("gscf7-stat-total"),
      unread: document.getElementById("gscf7-stat-unread"),
      read: document.getElementById("gscf7-stat-read"),
      new_today: document.getElementById("gscf7-stat-new-today"),
    };

    filterForm.addEventListener("change", function () {
      showLoader();

      var formData = new FormData();
      formData.append("action", "gscf7_dashboard_stats_query");
      formData.append("security", nonceField.value);
      formData.append("form_id", parseInt(filterForm.value, 10) || 0);

      fetch(window.ajaxurl || "admin-ajax.php", {
        method: "POST",
        body: formData,
        credentials: "same-origin",
      })
        .then(function (res) {
          return res.json();
        })
        .then(function (json) {
          if (!json || !json.success) {
            return;
          }

          var data = json.data;

          Object.keys(statEls).forEach(function (key) {
            if (statEls[key]) {
              statEls[key].textContent = data[key].toLocaleString();
            }
          });

          // Status-pill counts (All/Unread/Read) are rendered once at page
          // load for whichever form was selected then -- refresh them here
          // too, since they otherwise go stale the moment the Form dropdown
          // changes.
          var countMap = {
            all: data.total,
            unread: data.unread,
            read: data.read,
          };
          Object.keys(countMap).forEach(function (status) {
            var countEl = document.querySelector(
              '.gscf7-status-count[data-status-count="' + status + '"]',
            );
            if (countEl) {
              countEl.textContent = "(" + countMap[status].toLocaleString() + ")";
            }
          });

          renderCharts({
            labels: data.labels,
            values: data.values,
            read: data.read,
            unread: data.unread,
          });
        })
        .catch(function () {
          // Table refresh (initDashboardEntries) reports its own error state;
          // this handler only needs to make sure the loader still clears.
        })
        .then(function () {
          hideLoader();
        });
    });
  }

  /**
   * Click-to-sort on the Recent Entries table. Sorts by each column's
   * data-value attribute (numbers sort numerically, everything else as text).
   */
  function initSortableTable() {
    var table = document.getElementById("gscf7-entries-table");
    if (!table) {
      return;
    }

    var headers = table.querySelectorAll("th.gscf7-sortable");
    var tbody = table.querySelector("tbody");

    headers.forEach(function (th, colIndex) {
      th.addEventListener("click", function () {
        var type = th.getAttribute("data-sort");
        var currentlyAsc = th.classList.contains("gscf7-sort-asc");

        headers.forEach(function (h) {
          h.classList.remove("gscf7-sort-asc", "gscf7-sort-desc");
        });

        var nextAsc = !currentlyAsc;
        th.classList.add(nextAsc ? "gscf7-sort-asc" : "gscf7-sort-desc");

        var rows = Array.prototype.slice.call(tbody.querySelectorAll("tr"));

        rows.sort(function (rowA, rowB) {
          var cellA = rowA.children[colIndex].getAttribute("data-value") || "";
          var cellB = rowB.children[colIndex].getAttribute("data-value") || "";

          var result;
          if (type === "number" || type === "date") {
            result = parseFloat(cellA) - parseFloat(cellB);
          } else {
            result = cellA.localeCompare(cellB);
          }

          return nextAsc ? result : -result;
        });

        rows.forEach(function (row) {
          tbody.appendChild(row);
        });
      });
    });
  }

  /**
   * Drives the entries table + pagination shared by the Dashboard tab and the
   * CF7 Database screen. Two backends, picked per request by whether a
   * specific form is selected:
   *  - form_id === 0 ("All Forms"): the existing gscf7_dashboard_entries_query
   *    endpoint -- fixed ID/Date/Form/Status/Actions columns, no bulk actions.
   *  - form_id > 0: gscf7_cf7db_table_query -- wraps the existing
   *    GSCF7_FormEntry_Table, so columns/sorting/bulk-actions are whatever
   *    that class has always produced for that form.
   */
  function initDashboardEntries() {
    var table = document.getElementById("gscf7-entries-table");
    if (!table) {
      return;
    }

    var i18n = window.gscf7CF7DBi18n || {};
    var loadingText = i18n.loading || "Loading…";
    var errorText = i18n.error || "Unable to load entries.";
    var genericHeadHtml =
      "<tr>" +
      '<th scope="col" class="manage-column column-cb check-column">' +
      '<label class="screen-reader-text" for="cb-select-all-1">' +
      (i18n.selectAll || "Select All") +
      "</label>" +
      '<input id="cb-select-all-1" type="checkbox">' +
      "</th>" +
      '<th class="column-entry_id gscf7-sortable" data-orderby="id">' +
      (i18n.id || "ID") +
      '<span class="gscf7-sort-arrow"></span>' +
      "</th>" +
      "<th>" +
      (i18n.status || "Status") +
      "</th>" +
      "<th>" +
      (i18n.form || "Form Name") +
      "</th>" +
      '<th class="gscf7-sortable" data-orderby="date">' +
      (i18n.date || "Date") +
      '<span class="gscf7-sort-arrow"></span>' +
      "</th>" +
      '<th class="column-actions">' +
      (i18n.actions || "Actions") +
      "</th>" +
      "</tr>";

    var filterForm = document.getElementById("gscf7-entries-filter-form");
    var statusButtons = Array.prototype.slice.call(
      document.querySelectorAll(".gscf7-status-filter-btn"),
    );
    var perPageSelect = document.getElementById("gscf7-entries-per-page");
    var prevButton = document.getElementById("gscf7-pg-prev");
    var nextButton = document.getElementById("gscf7-pg-next");
    var currentPage = document.getElementById("gscf7-pg-current");
    var gotoInput = document.getElementById("gscf7-pg-goto");
    var totalCount = document.getElementById("gscf7-pg-total-count");
    var genericNonceField = document.getElementById(
      "gscf7-dashboard-entries-nonce",
    );
    var tableNonceField = document.getElementById("gscf7-cf7db-table-nonce");
    var tbody = document.getElementById("gscf7-entries-table-body");
    var thead = document.getElementById("gscf7-entries-thead");
    var toolbar = document.getElementById("gscf7-entries-toolbar");
    var tableWrap = document.getElementById("gscf7-entries-table-wrap");
    var bulkForm = document.getElementById("gscf7-entries-bulk-form");

    if (!tbody || !currentPage || !totalCount) {
      return;
    }

    function getActiveStatus() {
      var active = statusButtons.filter(function (button) {
        return button.classList.contains("is-active");
      })[0];
      return active ? active.getAttribute("data-status") || "all" : "all";
    }

    var state = {
      form_id: filterForm ? parseInt(filterForm.value, 10) || 0 : 0,
      status: getActiveStatus(),
      per_page: perPageSelect ? parseInt(perPageSelect.value, 10) || 10 : 10,
      paged: 1,
      orderby: "",
      order: "desc",
    };

    function setLoading() {
      if (tableWrap) {
        tableWrap.classList.add("is-loading");
      }
      tbody.innerHTML = "<tr><td>" + loadingText + "</td></tr>";
    }

    function setHead(html) {
      if (!thead) {
        return;
      }
      thead.innerHTML = html;

      if (!state.orderby) {
        return;
      }
      var activeTh = thead.querySelector(
        'th[data-orderby="' + state.orderby + '"]',
      );
      if (activeTh) {
        activeTh.classList.add(
          "asc" === state.order ? "gscf7-sort-asc" : "gscf7-sort-desc",
        );
      }
    }

    function setToolbar(html) {
      if (toolbar) {
        toolbar.innerHTML = html;
      }
    }

    function setActiveStatus(status) {
      statusButtons.forEach(function (button) {
        button.classList.toggle(
          "is-active",
          button.getAttribute("data-status") === status,
        );
      });
    }

    function updateFormIdInUrl(formId) {
      if (!window.history || !window.history.replaceState || !window.URL) {
        return;
      }
      try {
        var url = new URL(window.location.href);
        url.searchParams.set("formId", formId);
        window.history.replaceState(null, "", url.toString());
      } catch (e) {
        // Unsupported/blocked URL API -- the table still works, only the
        // address bar and bulk-action form target stay on the prior formId.
      }
    }

    function updateNavigation(data) {
      currentPage.textContent = data.paged;
      totalCount.textContent = data.total;
      if (gotoInput) {
        gotoInput.value = data.paged;
        gotoInput.max = data.total_pages;
      }
      if (prevButton) {
        prevButton.disabled = data.paged <= 1;
      }
      if (nextButton) {
        nextButton.disabled = data.paged >= data.total_pages;
      }
    }

    function loadEntries(showSpinner) {
      // The shared spinner sits next to the Form dropdown, so showing it for
      // a status-pill click (All/Unread/Read) reads as if the form itself
      // were reloading. Callers that trigger it from that dropdown (or
      // pagination/sorting) pass true; the status buttons pass false so
      // those switches feel instant.
      showSpinner = false !== showSpinner;

      setLoading();
      if (showSpinner) {
        showLoader();
      }

      var isGeneric = state.form_id === 0;
      var formData = new FormData();

      if (isGeneric) {
        formData.append("action", "gscf7_dashboard_entries_query");
        formData.append(
          "security",
          genericNonceField ? genericNonceField.value : "",
        );
      } else {
        formData.append("action", "gscf7_cf7db_table_query");
        formData.append(
          "security",
          tableNonceField ? tableNonceField.value : "",
        );
      }

      formData.append("form_id", state.form_id);
      formData.append("status", state.status);
      formData.append("per_page", state.per_page);
      formData.append("paged", state.paged);
      formData.append("orderby", state.orderby);
      formData.append("order", state.order);

      fetch(window.ajaxurl || "admin-ajax.php", {
        method: "POST",
        body: formData,
        credentials: "same-origin",
      })
        // Read as text first (not res.json()) so a non-JSON response (a PHP
        // warning/fatal printed ahead of the JSON, a redirect to a login
        // page, etc.) can be surfaced instead of just a generic parse-error
        // message with no clue what actually went wrong.
        .then(function (res) {
          return res.text().then(function (text) {
            return { status: res.status, text: text };
          });
        })
        .then(function (res) {
          if (tableWrap) {
            tableWrap.classList.remove("is-loading");
          }
          if (showSpinner) {
            hideLoader();
          }

          var json;
          try {
            json = JSON.parse(res.text);
          } catch (parseError) {
            // eslint-disable-next-line no-console
            console.error(
              "gscf7: non-JSON response from " +
                (isGeneric
                  ? "gscf7_dashboard_entries_query"
                  : "gscf7_cf7db_table_query") +
                " (HTTP " +
                res.status +
                "):",
              res.text,
            );
            tbody.innerHTML =
              "<tr><td>" +
              errorText +
              ' <span style="color:#9ca3af;font-size:12px;">(HTTP ' +
              res.status +
              " -- see browser console for the raw response)</span></td></tr>";
            return;
          }

          if (json && json.success) {
            setHead(isGeneric ? genericHeadHtml : json.data.head_html || "");
            setToolbar(json.data.toolbar_html || "");
            tbody.innerHTML = json.data.rows_html;
            updateNavigation(json.data);
          } else {
            var message =
              json && json.data && json.data.message
                ? json.data.message
                : errorText;
            tbody.innerHTML = "<tr><td>" + message + "</td></tr>";
          }
        })
        .catch(function (err) {
          if (tableWrap) {
            tableWrap.classList.remove("is-loading");
          }
          if (showSpinner) {
            hideLoader();
          }
          // eslint-disable-next-line no-console
          console.error("gscf7: entries request failed:", err);
          tbody.innerHTML = "<tr><td>" + errorText + "</td></tr>";
        });
    }

    if (filterForm) {
      filterForm.addEventListener("change", function () {
        state.form_id = parseInt(this.value, 10) || 0;
        state.paged = 1;
        state.orderby = "";
        state.order = "desc";
        updateFormIdInUrl(state.form_id);
        loadEntries();
      });
    }

    statusButtons.forEach(function (button) {
      button.addEventListener("click", function () {
        var status = this.getAttribute("data-status") || "all";
        state.status = status;
        state.paged = 1;
        setActiveStatus(status);
        loadEntries(false);
      });
    });

    if (perPageSelect) {
      perPageSelect.addEventListener("change", function () {
        state.per_page = parseInt(this.value, 10) || 10;
        state.paged = 1;
        loadEntries();
      });
    }

    if (prevButton) {
      prevButton.addEventListener("click", function () {
        if (state.paged > 1) {
          state.paged -= 1;
          loadEntries();
        }
      });
    }

    if (nextButton) {
      nextButton.addEventListener("click", function () {
        state.paged += 1;
        loadEntries();
      });
    }

    if (gotoInput) {
      gotoInput.addEventListener("change", function () {
        var page = parseInt(this.value, 10) || 1;
        if (page < 1) {
          page = 1;
        }
        state.paged = page;
        loadEntries();
      });
    }

    // Delegated because the thead is fully replaced by every AJAX response
    // (both the generic "All Forms" head and the per-form dynamic head are
    // sortable by ID/Date).
    if (thead) {
      thead.addEventListener("click", function (event) {
        var th =
          event.target && event.target.closest
            ? event.target.closest("th.gscf7-sortable")
            : null;

        if (!th) {
          return;
        }

        var column = th.getAttribute("data-orderby");
        if (state.orderby === column) {
          state.order = "asc" === state.order ? "desc" : "asc";
        } else {
          state.orderby = column;
          state.order = "asc";
        }
        state.paged = 1;
        loadEntries();
      });
    }

    /**
     * Refresh the status-pill counts + chart after a bulk action changes
     * read/unread/delete state -- a small, separate fetch (rather than
     * reusing initDashboardFormFilter()'s closure, which isn't reachable
     * from here) to gscf7_dashboard_stats_query, the same endpoint the Form
     * dropdown already uses.
     */
    function refreshStatsAndChart() {
      var statsNonce = document.getElementById("gscf7-dashboard-stats-nonce");
      if (!statsNonce) {
        return;
      }

      var formData = new FormData();
      formData.append("action", "gscf7_dashboard_stats_query");
      formData.append("security", statsNonce.value);
      formData.append("form_id", state.form_id);

      fetch(window.ajaxurl || "admin-ajax.php", {
        method: "POST",
        body: formData,
        credentials: "same-origin",
      })
        .then(function (res) {
          return res.json();
        })
        .then(function (json) {
          if (!json || !json.success) {
            return;
          }

          var data = json.data;
          var countMap = {
            all: data.total,
            unread: data.unread,
            read: data.read,
          };
          Object.keys(countMap).forEach(function (status) {
            var countEl = document.querySelector(
              '.gscf7-status-count[data-status-count="' + status + '"]',
            );
            if (countEl) {
              countEl.textContent = "(" + countMap[status].toLocaleString() + ")";
            }
          });

          renderCharts({
            labels: data.labels,
            values: data.values,
            read: data.read,
            unread: data.unread,
          });
        })
        .catch(function () {
          // Non-critical refresh -- the bulk action itself already succeeded.
        });
    }

    // Delete/Read/Unread submit via AJAX instead of a full page reload.
    // ("Spread Sheet" is intercepted separately in gs-connector.js into the
    // existing upsell popup and calls preventDefault() before this runs, so
    // it never reaches the "not one of these three" check below.)
    if (bulkForm) {
      bulkForm.addEventListener("submit", function (event) {
        var select = bulkForm.querySelector(
          'select[name="action"], select[name="action2"]',
        );
        var action = select ? select.value : "-1";

        if (
          "delete" !== action &&
          "read" !== action &&
          "unread" !== action
        ) {
          return;
        }

        // Prevent the full-page submit unconditionally for these three
        // actions from here on -- both remaining exits below (no rows
        // checked, delete canceled) must NOT fall through to a real submit.
        event.preventDefault();

        var checked = Array.prototype.slice.call(
          bulkForm.querySelectorAll('input[name="contact_form[]"]:checked'),
        );

        if (checked.length === 0) {
          window.alert(i18n.noEntriesSelected);
          return;
        }

        if ("delete" === action && !window.confirm(i18n.confirmDelete)) {
          return;
        }

        var nonceField = bulkForm.querySelector('input[name="_wpnonce"]');
        var formData = new FormData();
        formData.append("action", "gscf7_cf7db_bulk_action");
        formData.append("bulk_action", action);
        formData.append("form_id", state.form_id);
        formData.append("_wpnonce", nonceField ? nonceField.value : "");
        checked.forEach(function (checkbox) {
          formData.append("entry_ids[]", checkbox.value);
        });

        fetch(window.ajaxurl || "admin-ajax.php", {
          method: "POST",
          body: formData,
          credentials: "same-origin",
        })
          .then(function (res) {
            return res.json();
          })
          .then(function (json) {
            if (json && json.success) {
              loadEntries();
              refreshStatsAndChart();
            } else {
              var message =
                json && json.data && json.data.message
                  ? json.data.message
                  : errorText;
              window.alert(message);
            }
          })
          .catch(function () {
            window.alert(errorText);
          });
      });
    }

    setActiveStatus(state.status);
    loadEntries();
  }

  initDashboardEntries();
})();
