/**
 * ZAHARA CO-WORKING SPACE - Main JavaScript
 * Policy-as-Code compliant frontend
 *
 * SESSION SECURITY:
 * - Proactive inactivity detection (server-defined timeout, default 5 minutes)
 * - Auto-redirects to login when session expires
 * - Logs out when browser/tab is closed (server-side: cookie_lifetime = 0)
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

  /**
   * ============================================
   * SESSION INACTIVITY TIMEOUT (default 5 min)
   * Proactive client-side enforcement
   * Only runs when a user is logged in
   * ============================================
   */
  const body = document.body;
  const isLoggedIn = body.dataset.userLoggedIn === "true";

  // Read server-defined timeout (defaults to 300s / 5 min)
  const SESSION_TIMEOUT_SECONDS =
    parseInt(body.dataset.sessionTimeout, 10) || 300;
  const SESSION_TIMEOUT_MS = SESSION_TIMEOUT_SECONDS * 1000;
  const WARNING_BEFORE_MS = 30 * 1000; // Warn 30 seconds before expiry

  // Only run inactivity detection for logged-in users
  if (isLoggedIn) {
    let idleTimer;
    let warningTimer;
    let isExpiring = false;

    // Reset timers on user activity
    function resetIdleTimer() {
      clearTimeout(idleTimer);
      clearTimeout(warningTimer);

      // Show warning modal 30 seconds before timeout
      warningTimer = setTimeout(
        showSessionWarning,
        SESSION_TIMEOUT_MS - WARNING_BEFORE_MS,
      );

      // Auto-redirect when session actually expires
      idleTimer = setTimeout(expireSession, SESSION_TIMEOUT_MS);
    }

    // Show a warning to the user that their session is about to expire
    function showSessionWarning() {
      if (document.getElementById("session-warning-modal")) return;

      const secondsLeft = Math.round(WARNING_BEFORE_MS / 1000);
      const modal = document.createElement("div");
      modal.id = "session-warning-modal";
      modal.style.cssText =
        "position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 9999; display: flex; align-items: center; justify-content: center;";
      modal.innerHTML = `
        <div style="background: white; border-radius: 12px; padding: 32px; max-width: 400px; width: 90%; text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
          <div style="font-size: 2.5rem; margin-bottom: 12px;">&#9203;</div>
          <h3 style="margin-bottom: 8px; color: #1565C0;">Session Expiring Soon</h3>
          <p style="color: #555; margin-bottom: 20px; font-size: 0.9rem;">
            Your session will expire in <strong>${secondsLeft} seconds</strong> due to inactivity. Please continue browsing to stay logged in.
          </p>
          <button id="stay-logged-in" style="background: #1565C0; color: white; border: none; padding: 12px 24px; border-radius: 8px; font-size: 1rem; cursor: pointer; font-weight: 600; width: 100%;">
            I'm still here — Continue Session
          </button>
        </div>
      `;

      document.body.appendChild(modal);

      // Continue button resets the timer
      document
        .getElementById("stay-logged-in")
        .addEventListener("click", function () {
          modal.remove();
          resetIdleTimer();
        });
    }

    // Redirect to logout when session expires
    function expireSession() {
      if (isExpiring) return;
      isExpiring = true;
      clearTimeout(idleTimer);
      clearTimeout(warningTimer);

      // Use ?expired=1 to distinguish session timeout from manual logout
      const logoutUrl =
        "/work_folder/realRealestate/public/logout.php?expired=1";
      window.location.href = logoutUrl;
    }

    // Track all user activity events
    const activityEvents = [
      "click",
      "keydown",
      "mousemove",
      "scroll",
      "touchstart",
    ];
    activityEvents.forEach(function (event) {
      document.addEventListener(event, resetIdleTimer, { passive: true });
    });

    // Start the idle timer
    resetIdleTimer();
  }

  /**
   * ============================================
   * BROWSER CLOSE DETECTION (best-effort)
   * Session cookie is session-based (cookie_lifetime=0),
   * so it is automatically destroyed when the browser closes.
   * This sends a final logout ping where possible.
   * ============================================
   */
  let isNavigatingAway = false;

  // Set flag when clicking any link or submitting a form
  document.addEventListener("click", function (e) {
    if (e.target.closest("a")) isNavigatingAway = true;
  });
  document.addEventListener("submit", function () {
    isNavigatingAway = true;
  });

  window.addEventListener("pagehide", function (event) {
    // Only fire beacon for true browser/tab close (not internal navigation).
    // The session cookie (lifetime=0) already handles browser-close expiry;
    // this beacon is a best-effort server-side cleanup.
    if (isLoggedIn && event.persisted === false && !isNavigatingAway) {
      const logoutUrl = "/work_folder/realRealestate/public/logout.php";
      if (navigator.sendBeacon) {
        navigator.sendBeacon(logoutUrl);
      }
    }
  });

  // Reset the navigation flag on page load (after navigation completes)
  window.addEventListener("pageshow", function () {
    isNavigatingAway = false;
  });
});
