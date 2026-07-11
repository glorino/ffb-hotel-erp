/**
 * DASHBOARD.JS — Luxury Hospitality ERP | Admin Dashboard
 * Handles all dashboard interactions and management features.
 */

'use strict';

const DashboardApp = (function () {
  // ── Config ──
  const CONFIG = {
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    baseUrl: document.querySelector('base')?.getAttribute('href') || '/',
    refreshInterval: 30000,
    flashDuration: 5000,
    sidebarBreakpoint: 992
  };

  // ── Cache DOM ──
  let els = {};

  function cacheElements () {
    els = {
      wrapper: document.querySelector('.dashboard-wrapper'),
      sidebar: document.querySelector('.sidebar'),
      sidebarOverlay: document.querySelector('.sidebar-overlay'),
      toggleBtn: document.querySelector('.header-toggle'),
      mainContent: document.querySelector('.main-content'),
      header: document.querySelector('.dashboard-header'),
      profileBtn: document.querySelector('.profile-btn'),
      profileDropdown: document.querySelector('.profile-dropdown'),
      notificationBtn: document.querySelector('.notification-btn'),
      notificationDropdown: document.querySelector('.notification-dropdown'),
      branchTrigger: document.querySelector('.branch-trigger'),
      branchDropdown: document.querySelector('.branch-dropdown'),
      branchOptions: document.querySelectorAll('.branch-option'),
      tableSearch: document.querySelector('[data-table-search]'),
      dataTables: document.querySelectorAll('.data-table'),
      sortableHeaders: document.querySelectorAll('.data-table th.sortable'),
      modals: document.querySelectorAll('.dashboard-modal-overlay'),
      modalTriggers: document.querySelectorAll('[data-modal-target]'),
      modalCloseBtns: document.querySelectorAll('.dashboard-modal-close, [data-modal-close]'),
      flashMessages: document.querySelectorAll('.dashboard-flash'),
      confirmTriggers: document.querySelectorAll('[data-confirm]'),
      tabs: document.querySelectorAll('.tab-item'),
      tabContents: document.querySelectorAll('.tab-content'),
      forms: document.querySelectorAll('.dashboard-form-validate'),
      refreshTriggers: document.querySelectorAll('[data-refresh]'),
      printTriggers: document.querySelectorAll('[data-print]'),
      calcFields: document.querySelectorAll('[data-calc]')
    };
  }

  // ── Init ──
  function init () {
    cacheElements();
    if (!els.sidebar) return;

    initSidebar();
    initHeaderDropdowns();
    initTableSearch();
    initTableSorting();
    initModals();
    initFlashMessages();
    initConfirmDialogs();
    initFormValidation();
    initTabs();
    initAutoRefresh();
    initPrint();
    initDynamicCalculations();
    initDropdowns();
    initOutsideClick();
    handleResize();
  }

  // ── Sidebar ──
  function initSidebar () {
    // Toggle collapse
    els.toggleBtn?.addEventListener('click', function () {
      if (window.innerWidth < CONFIG.sidebarBreakpoint) {
        els.sidebar.classList.toggle('open');
        els.sidebarOverlay.classList.toggle('active');
        document.body.classList.toggle('sidebar-open');
      } else {
        els.sidebar.classList.toggle('collapsed');
      }
    });

    // Overlay click to close on mobile
    els.sidebarOverlay?.addEventListener('click', function () {
      els.sidebar.classList.remove('open');
      els.sidebarOverlay.classList.remove('active');
      document.body.classList.remove('sidebar-open');
    });

    // Submenu toggle
    els.sidebar.querySelectorAll('.sidebar-nav-item.has-submenu').forEach(function (item) {
      item.addEventListener('click', function (e) {
        e.preventDefault();
        const submenu = this.nextElementSibling;
        if (submenu && submenu.classList.contains('sidebar-submenu')) {
          submenu.classList.toggle('open');
          const arrow = this.querySelector('.nav-arrow');
          if (arrow) arrow.classList.toggle('open');
        }
      });
    });
  }

  function handleResize () {
    let resizeTimer;
    window.addEventListener('resize', function () {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(function () {
        if (window.innerWidth >= CONFIG.sidebarBreakpoint) {
          els.sidebarOverlay?.classList.remove('active');
          document.body.classList.remove('sidebar-open');
          if (els.sidebar) {
            const hasCollapsed = els.sidebar.classList.contains('collapsed');
            els.sidebar.classList.remove('open');
            if (!hasCollapsed && window.innerWidth >= 1200) {
              // Keep collapsed state as is
            }
          }
        }
      }, 200);
    });
  }

  // ── Header Dropdowns ──
  function initHeaderDropdowns () {
    // Profile dropdown
    els.profileBtn?.addEventListener('click', function (e) {
      e.stopPropagation();
      closeAllDropdowns();
      els.profileDropdown?.classList.toggle('active');
    });

    // Notification dropdown
    els.notificationBtn?.addEventListener('click', function (e) {
      e.stopPropagation();
      closeAllDropdowns();
      els.notificationDropdown?.classList.toggle('active');
    });

    // Branch switcher
    els.branchTrigger?.addEventListener('click', function (e) {
      e.stopPropagation();
      closeAllDropdowns();
      els.branchDropdown?.classList.toggle('active');
    });

    // Branch selection
    els.branchOptions.forEach(function (opt) {
      opt.addEventListener('click', function () {
        const branchName = this.dataset.branch || this.textContent.trim();
        const triggerText = els.branchTrigger?.querySelector('span');
        if (triggerText) triggerText.textContent = branchName;
        els.branchOptions.forEach(function (o) { o.classList.remove('active'); });
        this.classList.add('active');
        els.branchDropdown?.classList.remove('active');
      });
    });
  }

  function closeAllDropdowns () {
    document.querySelectorAll('.profile-dropdown, .notification-dropdown, .branch-dropdown').forEach(function (el) {
      el.classList.remove('active');
    });
  }

  function initOutsideClick () {
    document.addEventListener('click', function () {
      closeAllDropdowns();
    });
  }

  // ── Table Search ──
  function initTableSearch () {
    if (!els.tableSearch) return;

    els.tableSearch.addEventListener('input', function () {
      const query = this.value.toLowerCase().trim();
      const tableId = this.dataset.tableSearch;
      const tables = tableId
        ? document.querySelectorAll('#' + tableId + ' .data-table')
        : els.dataTables;

      tables.forEach(function (table) {
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(function (row) {
          let found = false;
          row.querySelectorAll('td').forEach(function (cell) {
            if (cell.textContent.toLowerCase().includes(query)) {
              found = true;
            }
          });
          row.style.display = found ? '' : 'none';
        });
      });
    });
  }

  // ── Table Sorting ──
  function initTableSorting () {
    els.sortableHeaders.forEach(function (header) {
      header.addEventListener('click', function () {
        const table = this.closest('.data-table');
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const index = Array.from(this.parentElement.children).indexOf(this);
        const isAsc = this.classList.contains('sorted-asc');
        const type = this.dataset.sort || 'string';

        // Clear all sorted states
        table.querySelectorAll('th.sortable').forEach(function (th) {
          th.classList.remove('sorted', 'sorted-asc', 'sorted-desc');
        });

        this.classList.add('sorted', isAsc ? 'sorted-desc' : 'sorted-asc');

        rows.sort(function (a, b) {
          const aVal = a.children[index]?.textContent?.trim() || '';
          const bVal = b.children[index]?.textContent?.trim() || '';

          if (type === 'number') {
            const aNum = parseFloat(aVal.replace(/[^0-9.-]/g, '')) || 0;
            const bNum = parseFloat(bVal.replace(/[^0-9.-]/g, '')) || 0;
            return isAsc ? bNum - aNum : aNum - bNum;
          }

          if (type === 'date') {
            return isAsc
              ? new Date(bVal) - new Date(aVal)
              : new Date(aVal) - new Date(bVal);
          }

          return isAsc
            ? bVal.localeCompare(aVal)
            : aVal.localeCompare(bVal);
        });

        rows.forEach(function (row) { tbody.appendChild(row); });
      });
    });
  }

  // ── Modals ──
  function initModals () {
    // Open modals
    els.modalTriggers.forEach(function (trigger) {
      trigger.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.dataset.modalTarget);
        if (target) openModal(target);
      });
    });

    // Close modals
    els.modalCloseBtns.forEach(function (btn) {
      btn.addEventListener('click', function () {
        const modal = this.closest('.dashboard-modal-overlay');
        if (modal) closeModal(modal);
      });
    });

    // Close on overlay click
    els.modals.forEach(function (modal) {
      modal.addEventListener('click', function (e) {
        if (e.target === modal) closeModal(modal);
      });

      // Close on Escape
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
          closeModal(modal);
        }
      });
    });
  }

  function openModal (modal) {
    modal.classList.add('active');
    modal.style.display = 'flex';
    // Force reflow then fade in
    void modal.offsetHeight;
    modal.style.opacity = '1';
    document.body.style.overflow = 'hidden';
  }

  function closeModal (modal) {
    modal.style.opacity = '0';
    setTimeout(function () {
      modal.classList.remove('active');
      modal.style.display = '';
    }, 300);
    document.body.style.overflow = '';
  }

  // ── Flash Messages ──
  function initFlashMessages () {
    els.flashMessages.forEach(function (msg) {
      const closeBtn = msg.querySelector('.flash-close');
      if (closeBtn) {
        closeBtn.addEventListener('click', function () {
          dismissFlash(msg);
        });
      }
      // Auto-dismiss
      setTimeout(function () { dismissFlash(msg); }, CONFIG.flashDuration);
    });
  }

  function dismissFlash (el) {
    if (!el || el.classList.contains('dismissing')) return;
    el.classList.add('dismissing');
    el.style.opacity = '0';
    el.style.transform = 'translateX(40px)';
    setTimeout(function () { el.remove(); }, 300);
  }

  // ── Confirm Dialogs ──
  function initConfirmDialogs () {
    els.confirmTriggers.forEach(function (trigger) {
      trigger.addEventListener('click', function (e) {
        e.preventDefault();
        const message = this.dataset.confirm || 'Are you sure you want to proceed?';
        const confirmText = this.dataset.confirmText || 'Yes, proceed';
        const cancelText = this.dataset.cancelText || 'Cancel';
        const callback = this.dataset.confirmCallback || null;

        showConfirmDialog(message, confirmText, cancelText, function (confirmed) {
          if (confirmed) {
            if (callback) {
              // Execute callback function name
              if (typeof window[callback] === 'function') {
                window[callback](trigger);
              }
            }
            // If it's a link, follow it
            if (trigger.tagName === 'A') {
              window.location.href = trigger.href;
            }
            // If it's a form submit button
            if (trigger.type === 'submit') {
              trigger.closest('form')?.submit();
            }
          }
        });
      });
    });
  }

  function showConfirmDialog (message, confirmText, cancelText, callback) {
    const existing = document.querySelector('.confirm-dialog-overlay');
    if (existing) existing.remove();

    const overlay = document.createElement('div');
    overlay.className = 'dashboard-modal-overlay confirm-dialog-overlay active';
    overlay.style.display = 'flex';
    overlay.style.opacity = '1';

    overlay.innerHTML =
      '<div class="dashboard-modal dashboard-modal-sm">' +
        '<div class="dashboard-modal-body">' +
          '<div class="confirm-dialog">' +
            '<div class="confirm-icon warning">&#9888;</div>' +
            '<h4>Confirm</h4>' +
            '<p>' + escapeHtml(message) + '</p>' +
            '<div class="confirm-actions">' +
              '<button class="dashboard-btn dashboard-btn-outline confirm-cancel">' + escapeHtml(cancelText) + '</button>' +
              '<button class="dashboard-btn dashboard-btn-danger confirm-ok">' + escapeHtml(confirmText) + '</button>' +
            '</div>' +
          '</div>' +
        '</div>' +
      '</div>';

    document.body.appendChild(overlay);

    overlay.querySelector('.confirm-cancel').addEventListener('click', function () {
      overlay.remove();
      if (callback) callback(false);
    });

    overlay.querySelector('.confirm-ok').addEventListener('click', function () {
      overlay.remove();
      if (callback) callback(true);
    });

    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) {
        overlay.remove();
        if (callback) callback(false);
      }
    });
  }

  // ── Form Validation ──
  function initFormValidation () {
    els.forms.forEach(function (form) {
      form.addEventListener('submit', function (e) {
        let valid = true;

        this.querySelectorAll('[required]').forEach(function (field) {
          if (!field.value?.trim()) {
            showFieldError(field, 'This field is required');
            valid = false;
          } else {
            clearFieldError(field);
          }
        });

        this.querySelectorAll('[data-validate="email"]').forEach(function (field) {
          if (field.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(field.value)) {
            showFieldError(field, 'Enter a valid email');
            valid = false;
          }
        });

        this.querySelectorAll('[data-validate="phone"]').forEach(function (field) {
          if (field.value && !/^\+?[\d\s\-()]{7,20}$/.test(field.value)) {
            showFieldError(field, 'Enter a valid phone number');
            valid = false;
          }
        });

        if (!valid) e.preventDefault();
      });
    });
  }

  function showFieldError (field, message) {
    field.classList.add('error');
    const errorEl = field.parentElement.querySelector('.form-error');
    if (errorEl) {
      errorEl.textContent = message;
      errorEl.style.display = 'block';
    }
  }

  function clearFieldError (field) {
    field.classList.remove('error');
    const errorEl = field.parentElement.querySelector('.form-error');
    if (errorEl) {
      errorEl.textContent = '';
      errorEl.style.display = '';
    }
  }

  // ── Tabs ──
  function initTabs () {
    els.tabs.forEach(function (tab) {
      tab.addEventListener('click', function () {
        const parent = this.closest('.tabs');
        const target = this.dataset.tab;

        parent.querySelectorAll('.tab-item').forEach(function (t) { t.classList.remove('active'); });
        this.classList.add('active');

        const contentParent = parent.parentElement;
        contentParent.querySelectorAll('.tab-content').forEach(function (tc) { tc.classList.remove('active'); });
        const targetContent = contentParent.querySelector('.tab-content[data-tab="' + target + '"]');
        if (targetContent) targetContent.classList.add('active');
      });
    });
  }

  // ── Auto Refresh ──
  function initAutoRefresh () {
    els.refreshTriggers.forEach(function (trigger) {
      const interval = parseInt(trigger.dataset.refresh, 10) || CONFIG.refreshInterval;
      const url = trigger.dataset.url || window.location.href;

      setInterval(function () {
        const container = trigger.closest('[data-refresh-container]') || trigger.parentElement;
        container.classList.add('refreshing');

        fetch(url, {
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': CONFIG.csrfToken
          }
        })
          .then(function (res) { return res.text(); })
          .then(function (html) {
            container.innerHTML = html;
            container.classList.remove('refreshing');
          })
          .catch(function () {
            container.classList.remove('refreshing');
          });
      }, interval);
    });
  }

  // ── Print ──
  function initPrint () {
    els.printTriggers.forEach(function (trigger) {
      trigger.addEventListener('click', function (e) {
        e.preventDefault();
        const targetId = this.dataset.print || null;
        if (targetId) {
          const el = document.getElementById(targetId);
          if (el) {
            printElement(el);
          }
        } else {
          window.print();
        }
      });
    });
  }

  function printElement (el) {
    const originalContents = document.body.innerHTML;
    document.body.innerHTML = el.innerHTML;
    window.print();
    document.body.innerHTML = originalContents;
    // Re-init app after print
    init();
  }

  // ── Dynamic Calculations ──
  function initDynamicCalculations () {
    els.calcFields.forEach(function (field) {
      const formula = field.dataset.calc;
      const inputs = field.dataset.calcInputs ? field.dataset.calcInputs.split(',') : [];

      function recalc () {
        let result = formula;
        inputs.forEach(function (inputName) {
          const input = document.querySelector('[name="' + inputName.trim() + '"]');
          const val = input ? parseFloat(input.value) || 0 : 0;
          result = result.replace(new RegExp(inputName.trim(), 'g'), val);
        });
        try {
          const total = Function('"use strict"; return (' + result + ')')();
          field.value = isNaN(total) ? '' : total.toFixed(2);
        } catch (e) {
          // Silently fail
        }
      }

      inputs.forEach(function (inputName) {
        const input = document.querySelector('[name="' + inputName.trim() + '"]');
        input?.addEventListener('input', recalc);
        input?.addEventListener('change', recalc);
      });
    });
  }

  // ── Dropdown / Select Enhancements ──
  function initDropdowns () {
    // Currently handled by CSS, but we add active class toggle here
  }

  // ── Utility ──
  function escapeHtml (text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  // ── Public API ──
  return {
    init: init,
    openModal: openModal,
    closeModal: closeModal,
    showConfirm: showConfirmDialog,
    dismissFlash: dismissFlash,
    closeAllDropdowns: closeAllDropdowns
  };
})();

// ── Bootstrap ──
document.addEventListener('DOMContentLoaded', DashboardApp.init);

// ═══════════════════════════════════════════════════════════
// FRAMER-STYLE SCROLL REVEAL & ANIMATIONS
// ═══════════════════════════════════════════════════════════
(function() {
  'use strict';

  // Scroll reveal for .reveal elements
  function initScrollReveal() {
    var reveals = document.querySelectorAll('.reveal');
    if (!reveals.length) return;

    var observer = new IntersectionObserver(function(entries) {
      entries.forEach(function(entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

    reveals.forEach(function(el) { observer.observe(el); });
  }

  // Animate stat values (count-up effect)
  function initCountUp() {
    document.querySelectorAll('.stat-value').forEach(function(el) {
      var text = el.textContent.trim();
      var match = text.match(/^[\₦$£€]?([\d,.]+)/);
      if (!match) return;

      var prefix = text.substring(0, text.indexOf(match[1]));
      var numStr = match[1].replace(/,/g, '');
      var num = parseFloat(numStr);
      if (isNaN(num) || num === 0) return;

      var suffix = text.substring(text.indexOf(match[1]) + match[1].length);
      var hasDecimal = match[1].includes('.');
      var duration = 800;
      var start = performance.now();

      function tick(now) {
        var elapsed = now - start;
        var progress = Math.min(elapsed / duration, 1);
        var eased = 1 - Math.pow(1 - progress, 3);
        var current = num * eased;

        if (hasDecimal) {
          el.textContent = prefix + current.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + suffix;
        } else {
          el.textContent = prefix + Math.round(current).toLocaleString() + suffix;
        }

        if (progress < 1) requestAnimationFrame(tick);
      }

      el.textContent = prefix + '0' + suffix;
      requestAnimationFrame(tick);
    });
  }

  // Stagger animation for table rows
  function initTableStagger() {
    document.querySelectorAll('.table tbody tr, .data-table tbody tr').forEach(function(row, i) {
      row.style.animationDelay = (i * 0.04) + 's';
    });
  }

  // Init on load
  document.addEventListener('DOMContentLoaded', function() {
    initScrollReveal();
    initCountUp();
    initTableStagger();
  });
})();
