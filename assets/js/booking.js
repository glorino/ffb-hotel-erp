/**
 * BOOKING.JS — Luxury Hospitality ERP | Booking System
 * Handles room booking, availability checks, pricing, and form interactions.
 */

'use strict';

const BookingApp = (function () {
  // ── Config ──
  const CONFIG = {
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    baseUrl: document.querySelector('base')?.getAttribute('href') || '/',
    endpoints: {
      checkAvailability: 'ajax/check-availability.php',
      validateCoupon: 'ajax/validate-coupon.php',
      getRooms: 'ajax/get-rooms.php',
      submitBooking: 'ajax/submit-booking.php'
    },
    taxRate: 0.075, // 7.5%
    serviceCharge: 0.05 // 5%
  };

  // ── State ──
  let state = {
    roomTypeId: null,
    roomId: null,
    checkIn: null,
    checkOut: null,
    guests: 1,
    roomRate: 0,
    nights: 0,
    subtotal: 0,
    discount: 0,
    discountType: null, // 'percentage' or 'fixed'
    couponCode: null,
    tax: 0,
    serviceCharge: 0,
    total: 0,
    paymentMethod: 'paystack',
    currentStep: 1,
    totalSteps: 1
  };

  // ── Cache DOM ──
  let els = {};

  function cacheElements () {
    els = {
      form: document.getElementById('booking-form') || document.querySelector('[data-booking-form]'),
      roomTypeSelect: document.querySelector('[name="room_type_id"]'),
      roomSelect: document.querySelector('[name="room_id"]'),
      checkIn: document.querySelector('[name="check_in"]'),
      checkOut: document.querySelector('[name="check_out"]'),
      guests: document.querySelector('[name="guests"]'),
      nightsDisplay: document.querySelector('[data-nights]'),
      rateDisplay: document.querySelector('[data-rate-display]'),
      subtotalDisplay: document.querySelector('[data-subtotal]'),
      discountDisplay: document.querySelector('[data-discount]'),
      taxDisplay: document.querySelector('[data-tax]'),
      chargeDisplay: document.querySelector('[data-service-charge]'),
      totalDisplay: document.querySelector('[data-total]'),
      couponInput: document.querySelector('[name="coupon_code"]'),
      applyCouponBtn: document.querySelector('[data-apply-coupon]'),
      removeCouponBtn: document.querySelector('[data-remove-coupon]'),
      couponMessage: document.querySelector('[data-coupon-message]'),
      couponRow: document.querySelector('[data-coupon-row]'),
      paymentMethods: document.querySelectorAll('[name="payment_method"]'),
      paystackSection: document.querySelector('[data-paystack-section]'),
      submitBtn: document.querySelector('[type="submit"]'),
      stepIndicators: document.querySelectorAll('.step-indicator'),
      stepContents: document.querySelectorAll('.step-content'),
      prevBtn: document.querySelector('[data-prev-step]'),
      nextBtn: document.querySelector('[data-next-step]'),
      availabilityForm: document.querySelector('[data-availability-form]'),
      availabilityResults: document.querySelector('[data-availability-results]')
    };
  }

  // ── Init ──
  function init () {
    cacheElements();
    if (!els.form && !els.availabilityForm) return;

    // Determine steps
    if (els.stepContents.length) {
      state.totalSteps = els.stepContents.length;
    }

    initDateValidation();
    initRoomTypeDropdown();
    initPriceCalculation();
    initCouponHandlers();
    initPaymentToggle();
    initStepNavigation();
    initAvailabilityCheck();
    initFormSubmission();
    initGuestChange();
    setMinDates();
  }

  // ── Date Validation ──
  function initDateValidation () {
    function validateDates () {
      if (!els.checkIn?.value || !els.checkOut?.value) return;

      const inDate = new Date(els.checkIn.value);
      const outDate = new Date(els.checkOut.value);

      if (outDate <= inDate) {
        els.checkOut.setCustomValidity('Check-out must be after check-in');
        showError(els.checkOut, 'Check-out must be after check-in');
      } else {
        els.checkOut.setCustomValidity('');
        clearError(els.checkOut);
      }
    }

    els.checkIn?.addEventListener('change', function () {
      // Set min checkout date
      if (els.checkOut) {
        const minDate = new Date(this.value);
        minDate.setDate(minDate.getDate() + 1);
        els.checkOut.min = minDate.toISOString().split('T')[0];
      }
      validateDates();
      calculatePrice();
    });

    els.checkOut?.addEventListener('change', function () {
      validateDates();
      calculatePrice();
    });
  }

  function setMinDates () {
    const today = new Date().toISOString().split('T')[0];
    if (els.checkIn) els.checkIn.min = today;
    if (els.checkOut) els.checkOut.min = today;
  }

  // ── Room Type → Rooms Dropdown (AJAX) ──
  function initRoomTypeDropdown () {
    if (!els.roomTypeSelect) return;

    els.roomTypeSelect.addEventListener('change', function () {
      state.roomTypeId = this.value;
      state.roomId = null;

      if (els.roomSelect) {
        els.roomSelect.disabled = true;
        els.roomSelect.innerHTML = '<option value="">Loading...</option>';
      }

      if (!this.value) {
        if (els.roomSelect) {
          els.roomSelect.innerHTML = '<option value="">Select room type first</option>';
          els.roomSelect.disabled = true;
        }
        return;
      }

      fetch(CONFIG.baseUrl + CONFIG.endpoints.getRooms, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-Token': CONFIG.csrfToken
        },
        body: 'room_type_id=' + encodeURIComponent(this.value)
      })
        .then(function (res) {
          if (!res.ok) throw new Error('Network response was not ok');
          return res.json();
        })
        .then(function (data) {
          if (!els.roomSelect) return;
          els.roomSelect.innerHTML = '<option value="">Select a room</option>';

          if (data.rooms && data.rooms.length) {
            data.rooms.forEach(function (room) {
              var opt = document.createElement('option');
              opt.value = room.id;
              opt.textContent = room.room_number;
              if (room.price_per_night) {
                opt.dataset.price = room.price_per_night;
                opt.textContent += ' - ' + formatCurrency(room.price_per_night) + '/night';
              }
              els.roomSelect.appendChild(opt);
            });
            els.roomSelect.disabled = false;
          } else {
            els.roomSelect.innerHTML = '<option value="">No rooms available</option>';
          }
        })
        .catch(function () {
          if (els.roomSelect) {
            els.roomSelect.innerHTML = '<option value="">Error loading rooms</option>';
            els.roomSelect.disabled = false;
          }
        });
    });

    // Room select change → update rate
    els.roomSelect?.addEventListener('change', function () {
      const selected = this.options[this.selectedIndex];
      state.roomId = this.value || null;
      state.roomRate = parseFloat(selected?.dataset?.price) || 0;

      if (els.rateDisplay) {
        els.rateDisplay.textContent = state.roomRate
          ? formatCurrency(state.roomRate) + '/night'
          : '—';
      }
      calculatePrice();
    });
  }

  // ── Price Calculation ──
  function initPriceCalculation () {
    // Triggered by date/room/guest changes
  }

  function calculatePrice () {
    if (!els.checkIn?.value || !els.checkOut?.value || !state.roomRate) {
      resetPriceDisplay();
      return;
    }

    const inDate = new Date(els.checkIn.value);
    const outDate = new Date(els.checkOut.value);
    const diffTime = outDate - inDate;

    if (diffTime <= 0) {
      resetPriceDisplay();
      return;
    }

    state.nights = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    state.subtotal = state.nights * state.roomRate;

    // Apply discount
    let discountAmount = 0;
    if (state.discountType === 'percentage') {
      discountAmount = state.subtotal * (state.discount / 100);
    } else if (state.discountType === 'fixed') {
      discountAmount = Math.min(state.discount, state.subtotal);
    }
    state.discount = discountAmount;

    const afterDiscount = state.subtotal - discountAmount;
    state.tax = afterDiscount * CONFIG.taxRate;
    state.serviceCharge = afterDiscount * CONFIG.serviceCharge;
    state.total = afterDiscount + state.tax + state.serviceCharge;

    updatePriceDisplay();
  }

  function updatePriceDisplay () {
    if (els.nightsDisplay) els.nightsDisplay.textContent = state.nights + (state.nights > 1 ? ' nights' : ' night');
    if (els.subtotalDisplay) els.subtotalDisplay.textContent = formatCurrency(state.subtotal);

    if (els.discountDisplay && state.discount > 0) {
      els.discountDisplay.textContent = '-' + formatCurrency(state.discount);
      els.discountDisplay.closest('[data-coupon-row]')?.classList.remove('hidden');
    } else if (els.discountDisplay) {
      els.discountDisplay.textContent = formatCurrency(0);
      els.discountDisplay.closest('[data-coupon-row]')?.classList.add('hidden');
    }

    if (els.taxDisplay) els.taxDisplay.textContent = formatCurrency(state.tax);
    if (els.chargeDisplay) els.chargeDisplay.textContent = formatCurrency(state.serviceCharge);
    if (els.totalDisplay) els.totalDisplay.textContent = formatCurrency(state.total);
  }

  function resetPriceDisplay () {
    state.nights = 0;
    state.subtotal = 0;
    state.tax = 0;
    state.serviceCharge = 0;
    state.total = 0;

    if (els.nightsDisplay) els.nightsDisplay.textContent = '—';
    if (els.subtotalDisplay) els.subtotalDisplay.textContent = formatCurrency(0);
    if (els.discountDisplay) els.discountDisplay.textContent = formatCurrency(0);
    if (els.taxDisplay) els.taxDisplay.textContent = formatCurrency(0);
    if (els.chargeDisplay) els.chargeDisplay.textContent = formatCurrency(0);
    if (els.totalDisplay) els.totalDisplay.textContent = formatCurrency(0);
  }

  // ── Guest Change ──
  function initGuestChange () {
    els.guests?.addEventListener('change', function () {
      state.guests = parseInt(this.value, 10) || 1;
    });
  }

  // ── Coupon Handlers ──
  function initCouponHandlers () {
    if (!els.applyCouponBtn) return;

    els.applyCouponBtn.addEventListener('click', function () {
      const code = els.couponInput?.value?.trim();
      if (!code) {
        showCouponMessage('Please enter a coupon code', 'error');
        return;
      }

      this.disabled = true;
      this.textContent = 'Validating...';

      fetch(CONFIG.baseUrl + CONFIG.endpoints.validateCoupon, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-Token': CONFIG.csrfToken
        },
        body: 'coupon_code=' + encodeURIComponent(code)
      })
        .then(function (res) { return res.json(); })
        .then(function (data) {
          if (data.valid) {
            state.discount = data.discount_value;
            state.discountType = data.discount_type; // 'percentage' or 'fixed'
            state.couponCode = code;
            showCouponMessage(data.message || 'Coupon applied!', 'success');
            els.removeCouponBtn?.classList.remove('hidden');
            els.applyCouponBtn?.classList.add('hidden');
            if (els.couponInput) els.couponInput.disabled = true;
            calculatePrice();
          } else {
            showCouponMessage(data.message || 'Invalid coupon code', 'error');
            state.discount = 0;
            state.discountType = null;
            state.couponCode = null;
            calculatePrice();
          }
        })
        .catch(function () {
          showCouponMessage('Could not validate coupon. Please try again.', 'error');
        })
        .finally(function () {
          if (els.applyCouponBtn) {
            els.applyCouponBtn.disabled = false;
            els.applyCouponBtn.textContent = 'Apply';
          }
        });
    });

    els.removeCouponBtn?.addEventListener('click', function () {
      state.discount = 0;
      state.discountType = null;
      state.couponCode = null;
      if (els.couponInput) { els.couponInput.value = ''; els.couponInput.disabled = false; }
      this.classList.add('hidden');
      els.applyCouponBtn?.classList.remove('hidden');
      showCouponMessage('Coupon removed', 'info');
      calculatePrice();
    });
  }

  function showCouponMessage (msg, type) {
    if (!els.couponMessage) return;
    els.couponMessage.textContent = msg;
    els.couponMessage.className = 'coupon-message ' + (type || 'info');
    els.couponMessage.classList.remove('hidden');

    if (type !== 'error') {
      setTimeout(function () {
        els.couponMessage?.classList.add('hidden');
      }, 5000);
    }
  }

  // ── Payment Method Toggle ──
  function initPaymentToggle () {
    els.paymentMethods.forEach(function (method) {
      method.addEventListener('change', function () {
        state.paymentMethod = this.value;
        if (els.paystackSection) {
          els.paystackSection.style.display = state.paymentMethod === 'paystack' ? 'block' : 'none';
        }
      });
    });
  }

  // ── Step Navigation ──
  function initStepNavigation () {
    function goToStep (step) {
      if (step < 1 || step > state.totalSteps) return;
      state.currentStep = step;

      els.stepIndicators.forEach(function (indicator, i) {
        const num = i + 1;
        indicator.classList.remove('active', 'completed');
        if (num === step) indicator.classList.add('active');
        if (num < step) indicator.classList.add('completed');
      });

      els.stepContents.forEach(function (content, i) {
        content.classList.toggle('active', (i + 1) === step);
      });

      if (els.prevBtn) {
        els.prevBtn.style.display = step === 1 ? 'none' : 'inline-flex';
      }

      if (els.nextBtn) {
        if (step === state.totalSteps) {
          els.nextBtn.style.display = 'none';
          els.submitBtn?.classList.remove('hidden');
        } else {
          els.nextBtn.style.display = 'inline-flex';
          els.submitBtn?.classList.add('hidden');
        }
      }
    }

    els.nextBtn?.addEventListener('click', function () {
      // Validate current step
      const currentContent = els.stepContents[state.currentStep - 1];
      if (currentContent && !validateStep(currentContent)) return;
      goToStep(state.currentStep + 1);
    });

    els.prevBtn?.addEventListener('click', function () {
      goToStep(state.currentStep - 1);
    });

    if (els.stepIndicators.length) goToStep(1);
  }

  function validateStep (content) {
    let valid = true;
    content.querySelectorAll('[required]').forEach(function (field) {
      if (!field.value?.trim()) {
        showError(field, 'This field is required');
        valid = false;
      } else {
        clearError(field);
      }
    });
    return valid;
  }

  // ── Availability Check ──
  function initAvailabilityCheck () {
    if (!els.availabilityForm) return;

    els.availabilityForm.addEventListener('submit', function (e) {
      e.preventDefault();
      const formData = new FormData(this);

      els.availabilityResults.innerHTML = '<div class="text-center"><div class="spinner"></div><p>Checking availability...</p></div>';
      els.availabilityResults.classList.remove('hidden');

      fetch(CONFIG.baseUrl + CONFIG.endpoints.checkAvailability, {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-Token': CONFIG.csrfToken
        }
      })
        .then(function (res) { return res.json(); })
        .then(function (data) {
          if (data.available) {
            els.availabilityResults.innerHTML =
              '<div class="alert alert-success">Rooms are available! <a href="' +
              (data.booking_url || '#') + '" class="btn-gold btn-sm">Book Now</a></div>';
          } else {
            els.availabilityResults.innerHTML =
              '<div class="alert alert-warning">' +
              (data.message || 'No rooms available for the selected dates.') +
              '</div>';
          }
        })
        .catch(function () {
          els.availabilityResults.innerHTML =
            '<div class="alert alert-danger">Error checking availability. Please try again.</div>';
        });
    });
  }

  // ── Form Submission ──
  function initFormSubmission () {
    if (!els.form) return;

    els.form.addEventListener('submit', function (e) {
      // Store state in hidden fields
      const totalInput = this.querySelector('[name="total_amount"]');
      if (totalInput) totalInput.value = state.total.toFixed(2);

      const discountInput = this.querySelector('[name="discount_amount"]');
      if (discountInput) discountInput.value = state.discount.toFixed(2);

      const nightsInput = this.querySelector('[name="nights"]');
      if (nightsInput) nightsInput.value = state.nights;

      const couponInput = this.querySelector('[name="coupon_code"]');
      if (couponInput && state.couponCode) couponInput.value = state.couponCode;

      // Validate dates one more time
      if (els.checkIn?.value && els.checkOut?.value) {
        if (new Date(els.checkOut.value) <= new Date(els.checkIn.value)) {
          e.preventDefault();
          showError(els.checkOut, 'Check-out must be after check-in');
          return;
        }
      }

      if (els.submitBtn) {
        els.submitBtn.disabled = true;
        els.submitBtn.textContent = 'Processing...';
      }
    });
  }

  // ── Utility ──
  function formatCurrency (amount) {
    return '\u20A6' + Number(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function showError (el, message) {
    el?.classList.add('error');
    const errorEl = el?.parentElement?.querySelector('.form-error');
    if (errorEl) {
      errorEl.textContent = message;
      errorEl.classList.add('show');
    }
  }

  function clearError (el) {
    el?.classList.remove('error');
    const errorEl = el?.parentElement?.querySelector('.form-error');
    if (errorEl) {
      errorEl.textContent = '';
      errorEl.classList.remove('show');
    }
  }

  // ── Public API ──
  return {
    init: init,
    state: state,
    calculatePrice: calculatePrice,
    formatCurrency: formatCurrency
  };
})();

// ── Bootstrap ──
document.addEventListener('DOMContentLoaded', BookingApp.init);
