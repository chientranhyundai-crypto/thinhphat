(function () {
  var desktop = 1100;

  function rootEl() {
    return document.querySelector('.sa-home .sa-hero-ux');
  }

  function clearHeights(root) {
    var viewport = root.querySelector('.flickity-viewport');
    if (viewport) {
      viewport.style.removeProperty('height');
    }
    root.querySelectorAll('.sa-hero-slide').forEach(function (slide) {
      slide.style.removeProperty('height');
    });
  }

  function fit() {
    var root = rootEl();
    if (!root) {
      return;
    }
    if (window.innerWidth >= desktop) {
      clearHeights(root);
      return;
    }
    var viewport = root.querySelector('.flickity-viewport');
    var selected = root.querySelector('.sa-hero-slide.is-selected') || root.querySelector('.sa-hero-slide');
    if (!viewport || !selected) {
      return;
    }
    selected.style.setProperty('height', 'auto', 'important');
    var height = Math.ceil(selected.scrollHeight);
    if (!height) {
      return;
    }
    selected.style.setProperty('height', height + 'px', 'important');
    viewport.style.setProperty('height', height + 'px', 'important');
  }

  function bind() {
    fit();
    var slider = document.querySelector('.sa-home .sa-hero-ux .slider');
    if (window.jQuery && slider && !slider.dataset.saHeroFit) {
      slider.dataset.saHeroFit = '1';
      window.jQuery(slider).on('settle.flickity change.flickity', fit);
    }
  }

  window.addEventListener('resize', fit);
  window.addEventListener('load', function () {
    bind();
    window.setTimeout(fit, 200);
  });
})();
