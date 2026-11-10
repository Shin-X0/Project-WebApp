/* ============================================================
   ART TERRE CREATIONS — admin.js
   Progressive enhancement for admin list screens:
   live search, filter chips, result counts and empty states.
   The pages stay fully usable without JavaScript.
   ============================================================ */
(function () {
  "use strict";

  function normalise(value) {
    return (value || "").toString().trim().toLowerCase();
  }

  function initListFilters() {
    var table = document.querySelector("[data-filter-table]");
    if (!table) return;

    var rows = Array.prototype.slice.call(table.querySelectorAll("tbody tr[data-row]"));
    if (!rows.length) return;

    var searchInput = document.querySelector("[data-filter-search]");
    var groups = Array.prototype.slice.call(document.querySelectorAll("[data-filter-group]"));
    var chips = Array.prototype.slice.call(document.querySelectorAll("[data-filter-group] [data-filter]"));
    var countTarget = document.querySelector("[data-filter-count]");
    var emptyState = document.querySelector("[data-filter-empty]");
    var tableWrap = table.closest(".admin-table-wrap");
    var state = {};

    function matchesSearch(row, query) {
      if (!query) return true;
      return normalise(row.getAttribute("data-search")).indexOf(query) !== -1;
    }

    function matchesGroups(row, skipGroup) {
      for (var group in state) {
        if (!Object.prototype.hasOwnProperty.call(state, group)) continue;
        if (group === skipGroup) continue;
        if (state[group] === "all") continue;
        if (row.getAttribute("data-" + group) !== state[group]) return false;
      }
      return true;
    }

    function collect(query, skipGroup) {
      return rows.filter(function (row) {
        return matchesSearch(row, query) && matchesGroups(row, skipGroup);
      });
    }

    function apply() {
      var query = normalise(searchInput && searchInput.value);

      rows.forEach(function (row) {
        row.hidden = !(matchesSearch(row, query) && matchesGroups(row, null));
      });

      var visible = collect(query, null).length;

      chips.forEach(function (chip) {
        var groupEl = chip.closest("[data-filter-group]");
        if (!groupEl) return;
        var group = groupEl.getAttribute("data-filter-group");
        var value = chip.getAttribute("data-filter");

        chip.classList.toggle("is-active", state[group] === value);

        var counter = chip.querySelector(".chip-count");
        if (!counter) return;
        counter.textContent = collect(query, group).filter(function (row) {
          return value === "all" || row.getAttribute("data-" + group) === value;
        }).length;
      });

      if (countTarget) {
        countTarget.textContent = visible === 1 ? "1 artwork" : visible + " artworks";
      }

      if (emptyState) emptyState.hidden = visible !== 0;
      if (tableWrap) tableWrap.hidden = visible === 0;
    }

    groups.forEach(function (groupEl) {
      var group = groupEl.getAttribute("data-filter-group");
      var active = groupEl.querySelector("[data-filter].is-active");
      state[group] = active ? active.getAttribute("data-filter") : "all";
    });

    if (searchInput) {
      searchInput.addEventListener("input", apply);
      searchInput.addEventListener("keydown", function (event) {
        if (event.key === "Escape") {
          searchInput.value = "";
          apply();
        }
      });
    }

    chips.forEach(function (chip) {
      chip.addEventListener("click", function () {
        var groupEl = chip.closest("[data-filter-group]");
        if (!groupEl) return;
        state[groupEl.getAttribute("data-filter-group")] = chip.getAttribute("data-filter");
        apply();
      });
    });

    /* "/" jumps to the search box, like most admin tools. */
    document.addEventListener("keydown", function (event) {
      if (!searchInput) return;
      if (event.key !== "/" || event.ctrlKey || event.metaKey || event.altKey) return;
      var tag = ((event.target && event.target.tagName) || "").toLowerCase();
      if (tag === "input" || tag === "textarea" || tag === "select") return;
      event.preventDefault();
      searchInput.focus();
    });

    apply();
  }

  /* Hide broken thumbnails so the gradient placeholder shows instead. */
  function initThumbnails() {
    var images = document.querySelectorAll(".artwork-thumb img");
    Array.prototype.forEach.call(images, function (image) {
      image.addEventListener("error", function () {
        image.remove();
      });
    });
  }

  /* Inline order status updates (progressive enhancement).
     Falls back to a normal POST (full page reload / flash) without JS. */
  function showStatusFeedback(el, message, state) {
    if (!el) return;
    el.textContent = message;
    el.setAttribute("data-state", state);
    el.classList.add("show");
    clearTimeout(el._hideTimer);
    el._hideTimer = setTimeout(function () {
      el.classList.remove("show");
    }, 2600);
  }

  function initStatusForms() {
    var forms = document.querySelectorAll("[data-status-form]");
    if (!forms.length) return;

    Array.prototype.forEach.call(forms, function (form) {
      form.addEventListener("submit", function (event) {
        event.preventDefault();

        var select = form.querySelector("[data-status-select]");
        var badge = form.querySelector("[data-status-badge]");
        var feedback = form.querySelector("[data-status-feedback]");
        var payload = new URLSearchParams(new FormData(form));

        form.classList.add("is-saving");

        fetch(form.action, {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
          },
          body: payload.toString(),
          credentials: "same-origin"
        })
          .then(function (response) {
            return response.json().catch(function () {
              return { success: false, message: "Unexpected server response." };
            });
          })
          .then(function (data) {
            form.classList.remove("is-saving");
            if (!data || !data.success) {
              if (select) {
                select.value = data && data.status ? data.status : select.getAttribute("data-original") || "";
              }
              showStatusFeedback(feedback, (data && data.message) || "Could not update status.", "error");
              return;
            }
            if (badge) {
              badge.textContent = data.status;
              badge.className = "admin-badge badge-" + data.status;
            }
            if (select) {
              select.setAttribute("data-original", data.status);
              select.value = data.status;
            }
            showStatusFeedback(feedback, data.message || "Status updated.", "success");
          })
          .catch(function () {
            form.classList.remove("is-saving");
            showStatusFeedback(feedback, "Network error — please try again.", "error");
          });
      });
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    initListFilters();
    initThumbnails();
    initStatusForms();
  });
})();
