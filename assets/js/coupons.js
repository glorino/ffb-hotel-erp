/**
 * COUPONS.JS — Luxury Hospitality ERP | Coupon Management
 * Handles coupon validation, application, and dynamic form fields.
 */

'use strict';

const CouponApp = (function () {
  // ── Config ──
  const CONFIG = {
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    baseUrl: document.querySelector('base')?.getAttribute('href') || '/',
    endpoints: {
      validate: 'ajax/validate-coupon.php',
      apply: 'ajax/apply-coupon.php',
      remove: 'ajax/remove-coupon.php'
    }
  };

  // ── State ──
  let state = {
    appliedCoupon: null,
    discount: 0,
    discountType: null, // 'percentage' | 'fixed'
    isApplying: false
  };

  // ── Cache DOM ──
  let els = {};

  function cacheElements () {
    els = {
      couponInput: document.querySelector('[name="coupon_code"]'),
      applyBtn: document.querySelector('[data-apply-coupon]'),
      removeBtn: document.querySelector('[data-remove-coupon]'),
      couponMessage: document.querySelector('[data-coupon-message]'),
      discountDisplay: document.querySelector('[data-discount-display]'),
      discountRow: document.querySelector('[data-discount-row]'),
      totalDisplay: document.querySelector('[data-total]'),
      amountInput: document.querySelector('[name="amount"]'),
      copyBtns: document.querySelectorAll('[data-copy-coupon]'),
      // Create/edit form
      couponForm: document.querySelector('[data-coupon-form]'),
      discountTypeSelect: document.querySelector('[name="discount_type"]'),
      discountValueField: document.querySelector('[name="discount_value"]'),
      maxUsesField: document.querySelector('[name="max_uses"]'),
      minAmountField: document.querySelector('[name="min_amount"]')
    };
  }

  // ── Init ──
  function init () {
    cacheElements();
    if (!els.applyBtn && !els.couponForm && !els.copyBtns.length) return;

    initApplyButton();
    initRemoveButton();
    initCopyButtons();
    initCouponForm();
    initRealTimeValidation();
    initPreAppliedCoupon();
  }

  // ── Apply Coupon ──
  function initApplyButton () {
    if (!els.applyBtn) return;

    els.applyBtn.addEventListener('click', function () {
      const code = els.couponInput?.value?.trim();
      if (!code) {
        showMessage('Please enter a coupon code', 'error');
        return;
      }

      if (state.isApplying) return;
      state.isApplying = true;
      this.disabled = true;
      this.textContent = 'Validating...';

      const formData = new FormData();
      formData.append('coupon_code', code);
      formData.append('action', 'validate');

      const amount = els.amountInput?.value || '0';

      fetch(CONFIG.baseUrl + CONFIG.endpoints.validate + '?code=' + encodeURIComponent(code) + '&amount=' + encodeURIComponent(amount), {
        method: 'GET',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-Token': CONFIG.csrfToken
        }
      })
        .then(function (res) {
          if (!res.ok) throw new Error('Network error');
          return res.json();
        })
        .then(function (data) {
          if (data.valid) {
            applyCoupon(code, data);
          } else {
            showMessage(data.message || 'Invalid or expired coupon', 'error');
            removeCoupon();
          }
        })
        .catch(function () {
          showMessage('Could not validate coupon. Please try again.', 'error');
        })
        .finally(function () {
          state.isApplying = false;
          if (els.applyBtn) {
            els.applyBtn.disabled = false;
            els.applyBtn.textContent = 'Apply';
          }
        });
    });
  }

  function applyCoupon (code, data) {
    state.appliedCoupon = code;
    state.discount = data.discount_value;
    state.discountType = data.discount_type;

    if (els.couponInput) els.couponInput.disabled = true;
    els.applyBtn?.classList.add('hidden');
    els.removeBtn?.classList.remove('hidden');

    showMessage(data.message || 'Coupon applied!', 'success');

    // Update discount display
    if (els.discountDisplay) {
      const formatted = state.discountType === 'percentage'
        ? state.discount + '%'
        : formatCurrency(state.discount);
      els.discountDisplay.textContent = '-' + formatted;
    }
    els.discountRow?.classList.remove('hidden');

    // Trigger recalculation if available
    if (typeof BookingApp !== 'undefined' && BookingApp.calculatePrice) {
      BookingApp.state.discount = state.discount;
      BookingApp.state.discountType = state.discountType;
      BookingApp.state.couponCode = code;
      BookingApp.calculatePrice();
    }

    // Dispatch event for other modules
    document.dispatchEvent(new CustomEvent('coupon:applied', {
      detail: { code: code, discount: state.discount, type: state.discountType }
    }));
  }

  // ── Remove Coupon ──
  function initRemoveButton () {
    if (!els.removeBtn) return;

    els.removeBtn.addEventListener('click', function () {
      removeCoupon();

      // Notify backend
      if (state.appliedCoupon) {
        fetch(CONFIG.baseUrl + CONFIG.endpoints.remove, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': CONFIG.csrfToken
          },
          body: 'coupon_code=' + encodeURIComponent(state.appliedCoupon)
        }).catch(function () { /* silent */ });
      }
    });
  }

  function removeCoupon () {
    state.appliedCoupon = null;
    state.discount = 0;
    state.discountType = null;

    if (els.couponInput) { els.couponInput.value = ''; els.couponInput.disabled = false; }
    els.applyBtn?.classList.remove('hidden');
    els.removeBtn?.classList.add('hidden');
    els.discountRow?.classList.add('hidden');
    showMessage('Coupon removed', 'info');

    // Trigger recalculation
    if (typeof BookingApp !== 'undefined' && BookingApp.calculatePrice) {
      BookingApp.state.discount = 0;
      BookingApp.state.discountType = null;
      BookingApp.state.couponCode = null;
      BookingApp.calculatePrice();
    }

    document.dispatchEvent(new CustomEvent('coupon:removed'));
  }

  // ── Copy Coupon Code ──
  function initCopyButtons () {
    els.copyBtns.forEach(function (btn) {
      btn.addEventListener('click', function () {
        const code = this.dataset.couponCode || this.dataset.copy || '';
        if (!code) return;

        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(code).then(function () {
            showCopyFeedback(btn, 'Copied!');
          }).catch(function () {
            fallbackCopy(btn, code);
          });
        } else {
          fallbackCopy(btn, code);
        }
      });
    });
  }

  function fallbackCopy (btn, text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
    showCopyFeedback(btn, 'Copied!');
  }

  function showCopyFeedback (btn, message) {
    const original = btn.textContent;
    btn.textContent = message;
    btn.disabled = true;
    setTimeout(function () {
      btn.textContent = original;
      btn.disabled = false;
    }, 2000);
  }

  // ── Create/Edit Coupon Form ──
  function initCouponForm () {
    if (!els.couponForm) return;

    // Toggle fields based on discount type
    els.discountTypeSelect?.addEventListener('change', function () {
      const type = this.value;
      const valueLabel = els.discountValueField?.closest('.dashboard-form-group')?.querySelector('label');

      if (valueLabel) {
        valueLabel.textContent = type === 'percentage' ? 'Discount (%)' : 'Discount (Fixed Amount)';
      }

      if (els.discountValueField) {
        els.discountValueField.placeholder = type === 'percentage' ? 'e.g. 10' : 'e.g. 5000';
        els.discountValueField.max = type === 'percentage' ? '100' : '';
      }

      // Min amount might only apply to percentage
      if (els.minAmountField) {
        const minGroup = els.minAmountField.closest('.dashboard-form-group');
        if (minGroup) {
          minGroup.style.display = type === 'percentage' ? 'block' : 'none';
        }
      }
    });

    // Form validation
    els.couponForm.addEventListener('submit', function (e) {
      let valid = true;

      const code = this.querySelector('[name="code"]');
      const value = els.discountValueField;
      const type = els.discountTypeSelect;

      if (!code?.value?.trim()) {
        showFieldError(code, 'Coupon code is required');
        valid = false;
      } else if (code.value.trim().length < 3) {
        showFieldError(code, 'Code must be at least 3 characters');
        valid = false;
      }

      if (!value?.value || parseFloat(value.value) <= 0) {
        showFieldError(value, 'Discount value must be greater than 0');
        valid = false;
      }

      if (type?.value === 'percentage' && parseFloat(value?.value) > 100) {
        showFieldError(value, 'Percentage cannot exceed 100');
        valid = false;
      }

      if (!valid) e.preventDefault();
    });

    // Auto-generate code
    const generateBtn = els.couponForm.querySelector('[data-generate-code]');
    const codeInput = els.couponForm.querySelector('[name="code"]');

    generateBtn?.addEventListener('click', function () {
      if (!codeInput) return;
      const generated = generateCouponCode();
      codeInput.value = generated;
    });
  }

  function generateCouponCode () {
    const prefix = 'HOTEL';
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let result = prefix;
    for (let i = 0; i < 6; i++) {
      result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return result;
  }

  // ── Real-time Validation ──
  function initRealTimeValidation () {
    if (!els.couponInput) return;

    let debounceTimer;

    els.couponInput.addEventListener('input', function () {
      clearTimeout(debounceTimer);
      const code = this.value.trim();

      if (code.length < 3) {
        els.couponMessage?.classList.add('hidden');
        return;
      }

      debounceTimer = setTimeout(function () {
        fetch(CONFIG.baseUrl + CONFIG.endpoints.validate + '?code=' + encodeURIComponent(code), {
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': CONFIG.csrfToken
          }
        })
          .then(function (res) { return res.json(); })
          .then(function (data) {
            if (data.valid) {
              showMessage('Coupon is valid! Apply to use.', 'success');
            } else if (data.found === false) {
              // Coupon not found at all
              showMessage('', '');
              els.couponMessage?.classList.add('hidden');
            } else {
              showMessage(data.message || 'Coupon cannot be applied', 'error');
            }
          })
          .catch(function () { /* silent */ });
      }, 500);
    });
  }

  // ── Pre-applied Coupon (from URL or session) ──
  function initPreAppliedCoupon () {
    const urlParams = new URLSearchParams(window.location.search);
    const couponParam = urlParams.get('coupon');

    if (couponParam && els.applyBtn) {
      els.couponInput.value = couponParam;
      els.applyBtn.click();
    }
  }

  // ── Utility ──
  function showMessage (msg, type) {
    if (!els.couponMessage) return;
    els.couponMessage.textContent = msg;
    els.couponMessage.className = 'coupon-message';
    if (type) {
      els.couponMessage.classList.add('coupon-message--' + type);
    }
    els.couponMessage.classList.toggle('hidden', !msg);
  }

  function showFieldError (field, message) {
    if (!field) return;
    field.classList.add('error');
    const errorEl = field.parentElement.querySelector('.form-error');
    if (errorEl) {
      errorEl.textContent = message;
      errorEl.style.display = 'block';
    }
  }

  function formatCurrency (amount) {
    return '\u20A6' + Number(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  // ── Public API ──
  return {
    init: init,
    applyCoupon: applyCoupon,
    removeCoupon: removeCoupon,
    generateCouponCode: generateCouponCode,
    state: state
  };
})();

// ── Bootstrap ──
document.addEventListener('DOMContentLoaded', CouponApp.init);
