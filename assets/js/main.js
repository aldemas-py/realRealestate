/**
 * FLEXISPACE - Main JavaScript
 * Policy-as-Code compliant frontend
 */

document.addEventListener("DOMContentLoaded", function () {
  // Mobile menu toggle
  const menuToggle = document.querySelector(".mobile-menu-toggle");
  const mainNav = document.querySelector(".main-nav");

  if (menuToggle && mainNav) {
    menuToggle.addEventListener("click", function (e) {
      e.stopPropagation();
      mainNav.classList.toggle("open");
    });

    document.addEventListener("click", function (e) {
      if (!mainNav.contains(e.target) && !menuToggle.contains(e.target)) {
        mainNav.classList.remove("open");
      }
    });
  }

  // Auto-dismiss alerts after 5 seconds
  document.querySelectorAll(".alert").forEach(function (alert) {
    setTimeout(function () {
      alert.style.opacity = "0";
      alert.style.transition = "opacity 0.5s ease";
      setTimeout(function () {
        alert.remove();
      }, 500);
    }, 5000);
  });

  // Profile tabs
  const tabs = document.querySelectorAll(".profile-tab");
  const contents = document.querySelectorAll(".tab-content");

  tabs.forEach(function (tab) {
    tab.addEventListener("click", function () {
      const target = this.dataset.tab;
      tabs.forEach(function (t) {
        t.classList.remove("active");
      });
      contents.forEach(function (c) {
        c.classList.remove("active");
      });
      this.classList.add("active");
      document.getElementById(target)?.classList.add("active");
    });
  });

  // Filter form auto-submit on change
  document
    .querySelectorAll(".filters-bar select, .filters-bar input")
    .forEach(function (el) {
      el.addEventListener("change", function () {
        this.closest("form")?.submit();
      });
    });

  // Confirm dialogs
  document.querySelectorAll("[data-confirm]").forEach(function (el) {
    el.addEventListener("click", function (e) {
      if (!confirm(this.dataset.confirm || "Are you sure?")) {
        e.preventDefault();
      }
    });
  });

  // Image gallery thumbnails
  const galleryThumbs = document.querySelectorAll(".space-gallery-thumbs img");
  const galleryMain = document.querySelector(".space-gallery-main img");

  galleryThumbs.forEach(function (thumb) {
    thumb.addEventListener("click", function () {
      if (galleryMain) {
        galleryMain.src = this.src;
        galleryMain.alt = this.alt;
      }
      galleryThumbs.forEach(function (t) {
        t.classList.remove("active");
      });
      this.classList.add("active");
    });
  });
});
