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

  document.addEventListener("DOMContentLoaded", function () {
    initListFilters();
    initThumbnails();
  });
})();
