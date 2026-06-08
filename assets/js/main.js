/**
 * MAIN.JS — Premium Luxury Interactions - FFB Hotel
 */

'use strict';

const HotelApp = (function () {

  const CONFIG = {
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    baseUrl: document.querySelector('base')?.getAttribute('href') || '/',
    scrollOffset: 100,
    flashDuration: 5000,
    animThreshold: 0.12,
    breakpoints: { mobile: 576, tablet: 768, desktop: 992, wide: 1200 }
  };

  let els = {};
  let ticking = false;
  let testimonialTimer = null;

  function cacheElements() {
    els = {
      header: document.querySelector('.site-header'),
      hamburger: document.querySelector('.hamburger'),
      mobileOverlay: document.querySelector('.mobile-overlay'),
      mobileBackdrop: document.querySelector('.mobile-overlay-backdrop'),
      backToTop: document.querySelector('.back-to-top'),
      announcementBar: document.querySelector('.announcement-bar'),
      announcementClose: document.querySelector('#announcementClose'),
      heroParticles: document.querySelector('.hero-particles'),
      testiCards: document.querySelectorAll('.testimonial-card'),
      testiDots: document.querySelectorAll('.testimonial-dot'),
      statCounters: document.querySelectorAll('.stat-number, .counter-number'),
      newsForm: document.querySelector('#footerNewsletterForm'),
      contactForm: document.querySelector('#contact-form'),
      lightbox: document.querySelector('.lightbox-overlay'),
      lightboxImg: document.querySelector('.lightbox-img'),
      lightboxCaption: document.querySelector('.lightbox-caption'),
      lightboxClose: document.querySelector('.lightbox-close'),
      galleryItems: document.querySelectorAll('.gallery-item'),
      flashMessages: document.querySelectorAll('.flash-message'),
      animateEls: document.querySelectorAll('[data-animate]'),
      lazyImgs: document.querySelectorAll('img[data-src]'),
      heroCheckIn: document.querySelector('#heroCheckIn'),
      heroCheckOut: document.querySelector('#heroCheckOut'),
      bookingForm: document.querySelector('#booking-form'),
    };
  }

  function init() {
    cacheElements();
    if (!els.header) return;

    initPreloader();
    initHeaderScroll();
    initMobileMenu();
    initSmoothScroll();
    initBackToTop();
    initAnnouncementBar();
    initHeroParticles();
    initCounterAnimation();
    initScrollAnimations();
    initLazyLoading();
    initGalleryLightbox();
    initTestimonialCarousel();
    initFlashMessages();
    initNewsletterForm();
    initContactForm();
    initDatePickers();
    initImageFallbacks();
    initBookingForm();
    initParallax();
  }

  function initPreloader() {
    var preloader = document.querySelector('.preloader');
    if (!preloader) return;
    window.addEventListener('load', function () {
      setTimeout(function () {
        preloader.classList.add('hidden');
      }, 800);
    });
    setTimeout(function () {
      if (!preloader.classList.contains('hidden')) {
        preloader.classList.add('hidden');
      }
    }, 4000);
  }

  function initHeaderScroll() {
    var ticking = false;
    window.addEventListener('scroll', function () {
      if (!ticking) {
        requestAnimationFrame(function () {
          var scrollY = window.pageYOffset || document.documentElement.scrollTop;
          els.header.classList.toggle('scrolled', scrollY > 80);
          if (els.backToTop) {
            els.backToTop.classList.toggle('visible', scrollY > 500);
          }
          ticking = false;
        });
        ticking = true;
      }
    }, { passive: true });
  }

  function initMobileMenu() {
    if (!els.hamburger) return;

    function openMenu() {
      els.hamburger.classList.add('active');
      els.mobileOverlay.classList.add('open');
      els.mobileBackdrop.classList.add('active');
      document.body.classList.add('menu-open');
    }

    function closeMenu() {
      els.hamburger.classList.remove('active');
      els.mobileOverlay.classList.remove('open');
      els.mobileBackdrop.classList.remove('active');
      document.body.classList.remove('menu-open');
    }

    els.hamburger.addEventListener('click', function () {
      if (els.mobileOverlay.classList.contains('open')) {
        closeMenu();
      } else {
        openMenu();
      }
    });

    if (els.mobileBackdrop) {
      els.mobileBackdrop.addEventListener('click', closeMenu);
    }

    document.querySelectorAll('.mobile-nav-link').forEach(function (link) {
      link.addEventListener('click', closeMenu);
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && els.mobileOverlay.classList.contains('open')) {
        closeMenu();
      }
    });
  }

  function initSmoothScroll() {
    document.addEventListener('click', function (e) {
      var link = e.target.closest('a[href^="#"]');
      if (!link) return;
      var targetId = link.getAttribute('href');
      if (targetId === '#' || !targetId) return;
      var target = document.querySelector(targetId);
      if (!target) return;
      e.preventDefault();
      var headerH = els.header ? els.header.offsetHeight : 0;
      var targetPos = target.getBoundingClientRect().top + window.pageYOffset - headerH - 20;
      window.scrollTo({ top: targetPos, behavior: 'smooth' });
    });
  }

  function initBackToTop() {
    if (!els.backToTop) return;
    els.backToTop.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  function initAnnouncementBar() {
    if (!els.announcementClose || !els.announcementBar) return;
    els.announcementClose.addEventListener('click', function () {
      var bar = els.announcementBar;
      bar.style.transition = 'height 0.35s ease, padding 0.35s ease, opacity 0.35s ease';
      bar.style.height = '0';
      bar.style.padding = '0 48px';
      bar.style.opacity = '0';
      document.body.classList.remove('has-announcement');
      var h = document.querySelector('.site-header');
      if (h) h.style.top = '0';
      setTimeout(function () { bar.style.display = 'none'; }, 400);
    });
  }

  function initHeroParticles() {
    var container = els.heroParticles;
    if (!container) return;
    var colors = ['#c9a84c', '#dfc26b', '#a8882e', '#f5edd6'];
    for (var i = 0; i < 20; i++) {
      var p = document.createElement('div');
      p.className = 'hero-particle';
      p.style.left = (5 + Math.random() * 90) + '%';
      p.style.top = (5 + Math.random() * 90) + '%';
      p.style.animationDelay = (Math.random() * 8) + 's';
      p.style.animationDuration = (5 + Math.random() * 5) + 's';
      var size = 2 + Math.random() * 4;
      p.style.width = size + 'px';
      p.style.height = size + 'px';
      p.style.background = colors[Math.floor(Math.random() * colors.length)];
      p.style.boxShadow = '0 0 ' + (size * 2) + 'px ' + p.style.background;
      container.appendChild(p);
    }
  }

  function initCounterAnimation() {
    if (!els.statCounters.length) return;

    if (!('IntersectionObserver' in window)) {
      els.statCounters.forEach(function (el) {
        el.textContent = el.dataset.target || el.textContent;
      });
      return;
    }

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          animateCounter(entry.target);
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.5 });

    els.statCounters.forEach(function (el) { observer.observe(el); });

    function animateCounter(el) {
      var target = parseInt(el.dataset.target || el.textContent.replace(/[^0-9]/g, ''), 10);
      if (!target) return;
      var suffix = el.dataset.suffix || '';
      var prefix = el.dataset.prefix || '';
      var duration = parseInt(el.dataset.duration, 10) || 2000;
      var start = performance.now();

      function update(now) {
        var elapsed = now - start;
        var progress = Math.min(elapsed / duration, 1);
        var eased = 1 - Math.pow(1 - progress, 3);
        var current = Math.round(target * eased);
        el.textContent = prefix + current.toLocaleString() + suffix;
        if (progress < 1) {
          requestAnimationFrame(update);
        } else {
          el.textContent = prefix + target.toLocaleString() + suffix;
        }
      }
      requestAnimationFrame(update);
    }
  }

  function initScrollAnimations() {
    if (!('IntersectionObserver' in window) || !els.animateEls.length) return;

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          var el = entry.target;
          var delay = parseInt(el.dataset.delay, 10) || 0;
          if (delay) {
            setTimeout(function () { el.classList.add('animated'); }, delay);
          } else {
            el.classList.add('animated');
          }
          observer.unobserve(el);
        }
      });
    }, { threshold: CONFIG.animThreshold, rootMargin: '0px 0px -60px 0px' });

    els.animateEls.forEach(function (el) { observer.observe(el); });
  }

  function initLazyLoading() {
    if (!('IntersectionObserver' in window) || !els.lazyImgs.length) {
      els.lazyImgs.forEach(function (img) {
        if (img.dataset.src) img.src = img.dataset.src;
        if (img.dataset.srcset) img.srcset = img.dataset.srcset;
      });
      return;
    }

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          var img = entry.target;
          if (img.dataset.src) img.src = img.dataset.src;
          if (img.dataset.srcset) img.srcset = img.dataset.srcset;
          img.classList.add('loaded');
          observer.unobserve(img);
        }
      });
    }, { rootMargin: '200px 0px', threshold: 0.01 });

    els.lazyImgs.forEach(function (img) { observer.observe(img); });
  }

  function initGalleryLightbox() {
    if (!els.lightbox || !els.galleryItems.length) return;

    var currentIndex = 0;
    var galleryImgs = [];

    els.galleryItems.forEach(function (item, index) {
      var img = item.querySelector('img');
      if (img) {
        galleryImgs.push({ src: img.src, alt: img.alt || '' });
      } else {
        galleryImgs.push(null);
      }

      item.addEventListener('click', function () {
        if (!galleryImgs[index]) return;
        currentIndex = index;
        openLightbox(galleryImgs[index].src, galleryImgs[index].alt);
      });
    });

    if (els.lightboxClose) {
      els.lightboxClose.addEventListener('click', closeLightbox);
    }

    els.lightbox.addEventListener('click', function (e) {
      if (e.target === els.lightbox) closeLightbox();
    });

    document.addEventListener('keydown', function (e) {
      if (!els.lightbox.classList.contains('active')) return;
      if (e.key === 'Escape') closeLightbox();
      if (e.key === 'ArrowLeft') navigateLightbox(-1);
      if (e.key === 'ArrowRight') navigateLightbox(1);
    });

    function openLightbox(src, alt) {
      els.lightboxImg.src = src;
      els.lightboxImg.alt = alt || '';
      if (els.lightboxCaption) els.lightboxCaption.textContent = alt || '';
      els.lightbox.classList.add('active');
      document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
      els.lightbox.classList.remove('active');
      document.body.style.overflow = '';
    }

    function navigateLightbox(dir) {
      if (!galleryImgs.length) return;
      currentIndex = (currentIndex + dir + galleryImgs.length) % galleryImgs.length;
      var item = galleryImgs[currentIndex];
      if (item) {
        els.lightboxImg.src = item.src;
        els.lightboxImg.alt = item.alt || '';
        if (els.lightboxCaption) els.lightboxCaption.textContent = item.alt || '';
      }
    }
  }

  function initTestimonialCarousel() {
    if (!els.testiCards.length) return;
    if (els.testiCards.length <= 1) return;

    var current = 0;
    var total = els.testiCards.length;
    var delay = 5000;

    function showSlide(index) {
      els.testiCards.forEach(function (card, i) {
        card.style.opacity = i === index ? '1' : '0';
        card.style.visibility = i === index ? 'visible' : 'hidden';
        card.style.position = i === index ? 'relative' : 'absolute';
        card.style.transition = 'opacity 0.6s ease';
      });
      els.testiDots.forEach(function (dot, i) {
        dot.classList.toggle('active', i === index);
      });
      current = index;
    }

    function nextSlide() {
      showSlide((current + 1) % total);
    }

    function startAutoPlay() {
      stopAutoPlay();
      testimonialTimer = setInterval(nextSlide, delay);
    }

    function stopAutoPlay() {
      clearInterval(testimonialTimer);
    }

    els.testiDots.forEach(function (dot) {
      dot.addEventListener('click', function () {
        var idx = parseInt(this.dataset.index, 10);
        if (!isNaN(idx)) {
          showSlide(idx);
          startAutoPlay();
        }
      });
    });

    showSlide(0);
    startAutoPlay();

    var container = els.testiCards[0].closest('.testimonials-section');
    if (container) {
      container.addEventListener('mouseenter', stopAutoPlay);
      container.addEventListener('mouseleave', startAutoPlay);
    }
  }

  function initFlashMessages() {
    els.flashMessages.forEach(function (msg) {
      var closeBtn = msg.querySelector('.close-flash');
      if (closeBtn) {
        closeBtn.addEventListener('click', function () {
          dismissFlash(msg);
        });
      }
      setTimeout(function () { dismissFlash(msg); }, CONFIG.flashDuration);
    });
  }

  function dismissFlash(el) {
    if (!el || el.classList.contains('dismissing')) return;
    el.classList.add('dismissing');
    el.style.opacity = '0';
    el.style.transform = 'translateX(40px)';
    setTimeout(function () { if (el.parentNode) el.parentNode.removeChild(el); }, 300);
  }

  function initNewsletterForm() {
    if (!els.newsForm) return;
    els.newsForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var email = this.querySelector('input[type="email"]');
      if (!email || !email.value || !isValidEmail(email.value)) {
        showFormError(email, 'Please enter a valid email');
        return;
      }
      var btn = this.querySelector('button');
      var orig = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = '...';

      var fd = new FormData(this);
      fd.append('action', 'newsletter_subscribe');

      fetch(CONFIG.baseUrl + 'ajax/newsletter.php', {
        method: 'POST',
        body: fd,
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-Token': CONFIG.csrfToken
        }
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (data.success) {
            email.value = '';
            btn.innerHTML = '<i class="bi bi-check"></i>';
          } else {
            showFormError(email, data.message || 'Subscription failed');
          }
        })
        .catch(function () {
          showFormError(email, 'Connection error. Try again.');
        })
        .finally(function () {
          btn.disabled = false;
          setTimeout(function () { btn.innerHTML = orig; }, 2500);
        });
    });
  }

  function initContactForm() {
    if (!els.contactForm) return;
    els.contactForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var name = this.querySelector('[name="name"]');
      var email = this.querySelector('[name="email"]');
      var message = this.querySelector('[name="message"]');
      var valid = true;

      clearFormErrors(this);

      if (!name || !name.value || !name.value.trim()) {
        showFormError(name, 'Name is required');
        valid = false;
      }
      if (!email || !email.value || !isValidEmail(email.value)) {
        showFormError(email, 'Valid email is required');
        valid = false;
      }
      if (!message || !message.value || !message.value.trim()) {
        showFormError(message, 'Message is required');
        valid = false;
      }
      if (!valid) return;

      var btn = this.querySelector('button[type="submit"]');
      var orig = btn.textContent;
      btn.disabled = true;
      btn.textContent = 'Sending...';

      var fd = new FormData(this);
      fd.append('action', 'contact_send');

      fetch(CONFIG.baseUrl + 'ajax/contact.php', {
        method: 'POST',
        body: fd,
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-Token': CONFIG.csrfToken
        }
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (data.success) {
            els.contactForm.reset();
            btn.textContent = 'Sent!';
            btn.classList.add('success');
          } else {
            showFormError(null, data.message || 'Failed to send');
            btn.textContent = orig;
          }
        })
        .catch(function () {
          showFormError(null, 'Connection error');
          btn.textContent = orig;
        })
        .finally(function () {
          btn.disabled = false;
          setTimeout(function () {
            btn.textContent = orig;
            btn.classList.remove('success');
          }, 3000);
        });
    });
  }

  function initDatePickers() {
    if (els.heroCheckIn && els.heroCheckOut) {
      els.heroCheckIn.addEventListener('change', function () {
        var minOut = new Date(this.value);
        minOut.setDate(minOut.getDate() + 1);
        els.heroCheckOut.min = minOut.toISOString().split('T')[0];
        if (els.heroCheckOut.value && els.heroCheckOut.value <= this.value) {
          els.heroCheckOut.value = '';
        }
      });
    }
  }

  function initBookingForm() {
    if (!els.bookingForm) return;
    var checkIn = els.bookingForm.querySelector('[name="check_in"]');
    var checkOut = els.bookingForm.querySelector('[name="check_out"]');
    if (checkIn && checkOut) {
      checkIn.addEventListener('change', function () {
        var minDate = new Date(this.value);
        minDate.setDate(minDate.getDate() + 1);
        checkOut.min = minDate.toISOString().split('T')[0];
      });
    }
  }

  function initImageFallbacks() {
    document.querySelectorAll('img').forEach(function (img) {
      img.addEventListener('error', function () {
        if (this.dataset.fallback) {
          this.src = this.dataset.fallback;
        }
      });
    });
  }

  function initParallax() {
    var hero = document.querySelector('.hero');
    if (!hero) return;
    var video = hero.querySelector('.hero-video');
    var content = hero.querySelector('.hero-content');
    var ticking = false;
    window.addEventListener('scroll', function () {
      if (!ticking) {
        requestAnimationFrame(function () {
          var scrollY = window.pageYOffset;
          if (scrollY < window.innerHeight * 1.5) {
            if (video) {
              video.style.transform = 'translateY(' + (scrollY * 0.4) + 'px)';
            }
            if (content) {
              content.style.transform = 'translateY(' + (scrollY * 0.2) + 'px)';
              content.style.opacity = Math.max(0, 1 - (scrollY / (window.innerHeight * 0.7)));
            }
          }
          ticking = false;
        });
        ticking = true;
      }
    }, { passive: true });
  }

  function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }

  function showFormError(el, message) {
    if (el) {
      el.classList.add('error');
      var errorEl = el.parentElement.querySelector('.form-error');
      if (errorEl) {
        errorEl.textContent = message;
        errorEl.classList.add('show');
      } else {
        var err = document.createElement('div');
        err.className = 'form-error show';
        err.textContent = message;
        el.parentElement.appendChild(err);
      }
    } else {
      var container = document.querySelector('.form-errors');
      if (container) {
        container.textContent = message;
        container.classList.add('show');
      } else {
        alert(message);
      }
    }
  }

  function clearFormErrors(form) {
    form.querySelectorAll('.form-error').forEach(function (el) {
      el.classList.remove('show');
      el.textContent = '';
    });
    form.querySelectorAll('.error').forEach(function (el) {
      el.classList.remove('error');
    });
    var container = form.querySelector('.form-errors');
    if (container) {
      container.classList.remove('show');
      container.textContent = '';
    }
  }

  return { init: init };
})();

document.addEventListener('DOMContentLoaded', HotelApp.init);
