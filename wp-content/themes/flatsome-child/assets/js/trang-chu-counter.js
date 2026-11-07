(function () {
  'use strict';

  var counters = document.querySelectorAll('.page-id-489 .tp-counter');
  if (!counters.length) {
    return;
  }

  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var duration = 1800;

  function formatValue(value, target, suffix) {
    var rounded = Math.round(value);
    var text = String(rounded);
    if (target >= 1000) {
      text = text.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
    return text + suffix;
  }

  function readCounter(el) {
    var target = parseFloat(el.getAttribute('data-target'));
    var suffix = el.getAttribute('data-suffix') || '';
    if (!isFinite(target)) {
      return null;
    }
    return { target: target, suffix: suffix };
  }

  function paint(el, value) {
    var data = readCounter(el);
    if (!data) {
      return;
    }
    el.textContent = formatValue(value, data.target, data.suffix);
  }

  function finish(el) {
    var data = readCounter(el);
    if (!data) {
      return;
    }
    el.textContent = formatValue(data.target, data.target, data.suffix);
    el.setAttribute('data-counted', '1');
  }

  if (reduceMotion) {
    Array.prototype.forEach.call(counters, finish);
    return;
  }

  Array.prototype.forEach.call(counters, function (el) {
    paint(el, 0);
  });

  function animate(el) {
    if (el.getAttribute('data-counted') === '1') {
      return;
    }
    el.setAttribute('data-counted', '1');
    var data = readCounter(el);
    if (!data) {
      return;
    }
    var start = null;

    function frame(timestamp) {
      if (start === null) {
        start = timestamp;
      }
      var progress = Math.min(1, (timestamp - start) / duration);
      var eased = 1 - Math.pow(1 - progress, 3);
      if (progress < 1) {
        paint(el, data.target * eased);
        window.requestAnimationFrame(frame);
        return;
      }
      el.textContent = formatValue(data.target, data.target, data.suffix);
    }

    window.requestAnimationFrame(frame);
  }

  if (!('IntersectionObserver' in window)) {
    Array.prototype.forEach.call(counters, animate);
    return;
  }

  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (!entry.isIntersecting) {
        return;
      }
      observer.unobserve(entry.target);
      animate(entry.target);
    });
  }, { threshold: 0.6 });

  Array.prototype.forEach.call(counters, function (el) {
    observer.observe(el);
  });
})();
