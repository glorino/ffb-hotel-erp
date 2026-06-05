/**
 * PAYMENTS.JS — Luxury Hospitality ERP | Payment Processing
 * Handles Paystack integration, payment form validation, and receipts.
 */

'use strict';

const PaymentApp = (function () {
  // ── Config ──
  const CONFIG = {
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    baseUrl: document.querySelector('base')?.getAttribute('href') || '/',
    paystackPublicKey: document.querySelector('meta[name="paystack-key"]')?.getAttribute('content') || '',
    endpoints: {
      verifyTransaction: 'ajax/verify-transaction.php',
      checkStatus: 'ajax/check-payment-status.php'
    }
  };

  // ── State ──
  let state = {
    reference: null,
    amount: 0,
    email: '',
    metadata: {},
    isProcessing: false
  };

  // ── Cache DOM ──
  let els = {};

  function cacheElements () {
    els = {
      paymentForm: document.getElementById('payment-form') || document.querySelector('[data-payment-form]'),
      payBtn: document.querySelector('[data-pay-now]'),
      amountDisplay: document.querySelector('[data-payment-amount]'),
      emailInput: document.querySelector('[name="email"]'),
      referenceInput: document.querySelector('[name="transaction_reference"]'),
      statusDisplay: document.querySelector('[data-payment-status]'),
      receiptContainer: document.querySelector('[data-receipt]'),
      printReceiptBtn: document.querySelector('[data-print-receipt]'),
      paymentMethods: document.querySelectorAll('[name="payment_method"]'),
      paystackSection: document.querySelector('[data-paystack-section]'),
      splitPaymentInputs: document.querySelectorAll('[data-split-payment]'),
      splitTotalDisplay: document.querySelector('[data-split-total]')
    };
  }

  // ── Init ──
  function init () {
    cacheElements();
    if (!els.paymentForm && !els.payBtn) return;

    initPaystackButton();
    initPaymentForm();
    initPaymentMethodToggle();
    initPrintReceipt();
    initSplitPayment();
    initStatusCheck();
  }

  // ── Paystack Inline Popup ──
  function initPaystackButton () {
    if (!els.payBtn) return;

    els.payBtn.addEventListener('click', function (e) {
      e.preventDefault();

      if (state.isProcessing) return;

      const amount = parseFloat(this.dataset.amount || els.amountDisplay?.dataset.amount || 0);
      const email = els.emailInput?.value?.trim() || this.dataset.email || '';

      if (!email) {
        showError('Please provide a valid email address');
        return;
      }

      if (amount <= 0) {
        showError('Invalid payment amount');
        return;
      }

      if (!CONFIG.paystackPublicKey) {
        showError('Payment system not configured. Please contact support.');
        return;
      }

      state.amount = amount;
      state.email = email;
      state.reference = 'REF-' + Date.now() + '-' + Math.random().toString(36).substring(2, 8).toUpperCase();

      // Set reference in form
      if (els.referenceInput) els.referenceInput.value = state.reference;

      state.isProcessing = true;
      this.disabled = true;
      this.textContent = 'Processing...';

      const handler = PaystackPop.setup({
        key: CONFIG.paystackPublicKey,
        email: email,
        amount: Math.round(amount * 100), // Convert to kobo
        ref: state.reference,
        currency: 'NGN',
        metadata: state.metadata,
        callback: function (response) {
          state.isProcessing = false;
          if (els.payBtn) {
            els.payBtn.disabled = false;
            els.payBtn.textContent = 'Pay Now';
          }
          // Verify transaction
          verifyTransaction(response.reference);
        },
        onClose: function () {
          state.isProcessing = false;
          if (els.payBtn) {
            els.payBtn.disabled = false;
            els.payBtn.textContent = 'Pay Now';
          }
          showError('Payment cancelled');
        }
      });

      handler.openIframe();
    });
  }

  // ── Verify Transaction ──
  function verifyTransaction (reference) {
    if (els.statusDisplay) {
      els.statusDisplay.textContent = 'Verifying payment...';
      els.statusDisplay.className = 'payment-status info';
    }

    fetch(CONFIG.baseUrl + CONFIG.endpoints.verifyTransaction, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-Token': CONFIG.csrfToken
      },
      body: 'reference=' + encodeURIComponent(reference)
    })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (data.success) {
          if (els.statusDisplay) {
            els.statusDisplay.textContent = 'Payment successful!';
            els.statusDisplay.className = 'payment-status success';
          }
          // Redirect to success page or show receipt
          if (data.redirect_url) {
            window.location.href = data.redirect_url;
          } else if (els.receiptContainer) {
            showReceipt(data);
          } else {
            // Trigger form submission
            const form = els.paymentForm;
            if (form) {
              const refInput = form.querySelector('[name="transaction_reference"]');
              if (refInput) refInput.value = reference;
              form.submit();
            }
          }
        } else {
          if (els.statusDisplay) {
            els.statusDisplay.textContent = data.message || 'Verification failed';
            els.statusDisplay.className = 'payment-status error';
          }
        }
      })
      .catch(function () {
        if (els.statusDisplay) {
          els.statusDisplay.textContent = 'Could not verify payment. Please contact support.';
          els.statusDisplay.className = 'payment-status error';
        }
      });
  }

  // ── Payment Form ──
  function initPaymentForm () {
    if (!els.paymentForm) return;

    els.paymentForm.addEventListener('submit', function (e) {
      // Make sure reference is set if Paystack was used
      if (!els.referenceInput?.value && els.payBtn) {
        e.preventDefault();
        els.payBtn.click();
      }
    });
  }

  // ── Payment Method Toggle ──
  function initPaymentMethodToggle () {
    els.paymentMethods.forEach(function (method) {
      method.addEventListener('change', function () {
        if (els.paystackSection) {
          els.paystackSection.style.display = this.value === 'paystack' ? 'block' : 'none';
        }
        if (els.payBtn) {
          els.payBtn.style.display = this.value === 'paystack' ? 'inline-flex' : 'none';
        }
      });
    });
  }

  // ── Print Receipt ──
  function initPrintReceipt () {
    if (!els.printReceiptBtn) return;

    els.printReceiptBtn.addEventListener('click', function () {
      const receipt = els.receiptContainer;
      if (!receipt) return;

      const originalContents = document.body.innerHTML;
      document.body.innerHTML = receipt.outerHTML;
      window.print();
      document.body.innerHTML = originalContents;
      init(); // Re-init after print
    });
  }

  // ── Split Payment ──
  function initSplitPayment () {
    if (!els.splitPaymentInputs.length) return;

    function calculateSplit () {
      let total = 0;
      els.splitPaymentInputs.forEach(function (input) {
        total += parseFloat(input.value) || 0;
      });
      if (els.splitTotalDisplay) {
        els.splitTotalDisplay.textContent = formatCurrency(total);
      }
    }

    els.splitPaymentInputs.forEach(function (input) {
      input.addEventListener('input', calculateSplit);
      input.addEventListener('change', calculateSplit);
    });
  }

  // ── Status Check Polling ──
  function initStatusCheck () {
    const statusContainer = document.querySelector('[data-payment-status-poll]');
    if (!statusContainer) return;

    const reference = statusContainer.dataset.reference;
    const checkUrl = statusContainer.dataset.checkUrl || CONFIG.baseUrl + CONFIG.endpoints.checkStatus;

    if (!reference) return;

    function poll () {
      fetch(checkUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-Token': CONFIG.csrfToken
        },
        body: 'reference=' + encodeURIComponent(reference)
      })
        .then(function (res) { return res.json(); })
        .then(function (data) {
          if (data.status === 'success') {
            statusContainer.innerHTML =
              '<div class="payment-status success">Payment confirmed!</div>';
            if (data.redirect_url) {
              setTimeout(function () { window.location.href = data.redirect_url; }, 1500);
            }
          } else if (data.status === 'failed') {
            statusContainer.innerHTML =
              '<div class="payment-status error">Payment failed. Please try again.</div>';
          } else {
            // Still pending, poll again
            setTimeout(poll, 5000);
          }
        })
        .catch(function () {
          setTimeout(poll, 5000);
        });
    }

    setTimeout(poll, 2000);
  }

  // ── Receipt Display ──
  function showReceipt (data) {
    if (!els.receiptContainer) return;

    const receipt = data.receipt || data;
    els.receiptContainer.innerHTML =
      '<div class="receipt">' +
        '<div class="receipt-header">' +
          '<h3>Payment Receipt</h3>' +
          '<p>Reference: ' + (receipt.reference || state.reference) + '</p>' +
        '</div>' +
        '<div class="receipt-body">' +
          '<div class="receipt-row"><span>Amount</span><span>' + formatCurrency(receipt.amount || state.amount) + '</span></div>' +
          '<div class="receipt-row"><span>Status</span><span class="status-badge completed">Paid</span></div>' +
          '<div class="receipt-row"><span>Date</span><span>' + (receipt.paid_at ? new Date(receipt.paid_at).toLocaleString() : new Date().toLocaleString()) + '</span></div>' +
          (receipt.channel ? '<div class="receipt-row"><span>Method</span><span>' + receipt.channel + '</span></div>' : '') +
        '</div>' +
        '<div class="receipt-footer">' +
          '<button class="dashboard-btn dashboard-btn-outline" data-print-receipt>Print Receipt</button>' +
        '</div>' +
      '</div>';

    // Re-bind print button
    const newPrintBtn = els.receiptContainer.querySelector('[data-print-receipt]');
    if (newPrintBtn) {
      newPrintBtn.addEventListener('click', function () {
        window.print();
      });
    }
  }

  // ── Utility ──
  function formatCurrency (amount) {
    return '\u20A6' + Number(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function showError (message) {
    const container = document.querySelector('.payment-errors') || els.statusDisplay;
    if (container) {
      container.textContent = message;
      container.className = 'payment-status error';
    } else {
      alert(message);
    }
  }

  // ── Public API ──
  return {
    init: init,
    verifyTransaction: verifyTransaction,
    formatCurrency: formatCurrency,
    state: state
  };
})();

// ── Bootstrap ──
document.addEventListener('DOMContentLoaded', PaymentApp.init);
