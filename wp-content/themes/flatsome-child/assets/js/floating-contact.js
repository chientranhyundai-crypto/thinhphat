(function () {
  function boot() {
    var root = document.querySelector('[data-sa-float]');
    var zone = document.querySelector('[data-sa-contact-zone]');
    var sheet = document.querySelector('[data-sa-contact-sheet]');
    var tab = root ? root.querySelector('[data-sa-open-contact]') : null;
    if (!root || !zone || !sheet || !tab) {
      return;
    }

    var bot = root.querySelector('[data-sa-chatbot]');
    var panel = document.getElementById(bot ? bot.getAttribute('aria-controls') : '');
    var isOpen = false;
    var isDirty = false;
    var suppressed = false;
    var lastPointer = 'mouse';
    var desktopQuery = window.matchMedia('(min-width: 1025px)');

    function fields() {
      return sheet.querySelectorAll('[name="your-name"], [name="your-email"], [name="your-phone"], [name="your-message"]');
    }

    function syncDirty() {
      isDirty = Array.prototype.some.call(fields(), function (field) {
        return (field.value || '').trim() !== '';
      });
      root.classList.toggle('is-dirty', isDirty);
    }

    function placeSheet() {
      if (!desktopQuery.matches) {
        sheet.style.top = '';
        sheet.style.maxHeight = '';
        return;
      }
      sheet.style.top = '0px';
      if (!isOpen) {
        return;
      }
      var tabRect = tab.getBoundingClientRect();
      var limit = Math.max(220, window.innerHeight - 16);
      sheet.style.maxHeight = limit + 'px';
      var height = sheet.getBoundingClientRect().height;
      var shift = tabRect.top + height - (window.innerHeight - 8);
      if (shift < 0) {
        shift = 0;
      }
      if (tabRect.top - shift < 8) {
        shift = Math.max(0, tabRect.top - 8);
      }
      sheet.style.top = (-shift) + 'px';
    }

    function setOpen(open) {
      isOpen = !!open;
      root.classList.toggle('is-form-open', isOpen);
      tab.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      sheet.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
      placeSheet();
    }

    function allowHover() {
      return desktopQuery.matches && lastPointer !== 'touch';
    }

    function rememberPointer(event) {
      if (event && event.pointerType) {
        lastPointer = event.pointerType;
      }
    }

    document.addEventListener('pointerdown', function (event) {
      rememberPointer(event);
      if (!isOpen || isDirty || zone.contains(event.target)) {
        return;
      }
      setOpen(false);
    }, true);

    function onEnter(event) {
      rememberPointer(event);
      if (!allowHover() || event.pointerType === 'touch' || suppressed) {
        return;
      }
      setOpen(true);
    }

    function onLeave() {
      if (suppressed) {
        suppressed = false;
      }
      if (!allowHover() || isDirty) {
        return;
      }
      setOpen(false);
    }

    var hoverBound = false;
    function syncHover() {
      var enable = desktopQuery.matches;
      if (enable && !hoverBound) {
        zone.addEventListener('pointerenter', onEnter);
        zone.addEventListener('pointerleave', onLeave);
        hoverBound = true;
      } else if (!enable && hoverBound) {
        zone.removeEventListener('pointerenter', onEnter);
        zone.removeEventListener('pointerleave', onLeave);
        hoverBound = false;
      }
    }
    syncHover();
    if (desktopQuery.addEventListener) {
      desktopQuery.addEventListener('change', syncHover);
    }

    tab.addEventListener('click', function () {
      if (allowHover()) {
        suppressed = false;
        setOpen(true);
        return;
      }
      if (isOpen && isDirty) {
        return;
      }
      setOpen(!isOpen);
    });

    sheet.addEventListener('click', function (event) {
      if (!event.target.closest('[data-sa-close-contact]')) {
        return;
      }
      suppressed = true;
      setOpen(false);
    });

    sheet.addEventListener('input', syncDirty);
    sheet.addEventListener('change', syncDirty);

    zone.addEventListener('focusout', function (event) {
      if (isDirty || zone.contains(event.relatedTarget)) {
        return;
      }
      if (!allowHover()) {
        return;
      }
      setOpen(false);
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && isOpen && !isDirty) {
        setOpen(false);
      }
    });

    window.addEventListener('resize', function () {
      if (isOpen) {
        placeSheet();
      }
    });

    document.addEventListener('wpcf7mailsent', function (event) {
      if (!sheet.contains(event.target)) {
        return;
      }
      isDirty = false;
      root.classList.remove('is-dirty');
      setOpen(true);
    });

    if (bot && panel) {
      bot.addEventListener('click', function () {
        var open = panel.hidden;
        panel.hidden = !open;
        bot.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open && !panel.dataset.ready) {
          var src = panel.getAttribute('data-src');
          if (panel.getAttribute('data-enabled') === '1' && src) {
            var script = document.createElement('script');
            script.src = src;
            script.async = true;
            panel.appendChild(script);
          } else {
            panel.textContent = 'Khung chat sẵn sàng để kết nối chatbot từ seoauto.vn.';
          }
          panel.dataset.ready = '1';
        }
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
}());
