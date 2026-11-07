(function () {
  var busy = null;

  function money(value) {
    return new Intl.NumberFormat('vi-VN').format(Number(value) || 0) + '₫';
  }

  function bounds() {
    var box = document.querySelector('.sa-catalog .sa-filter__price');
    if (!box) return { min: 0, max: 0 };
    return { min: Number(box.dataset.boundMin) || 0, max: Number(box.dataset.boundMax) || 0 };
  }

  function buildUrl() {
    var url = new URL(window.location.href);
    var drop = [];
    url.searchParams.forEach(function (_, key) {
      if (key.indexOf('filter_') === 0 || key.indexOf('query_type_') === 0 || key === 'min_price' || key === 'max_price' || key === 'paged' || key === 'sa_home_filter') {
        drop.push(key);
      }
    });
    drop.forEach(function (key) { url.searchParams.delete(key); });

    var groups = {};
    document.querySelectorAll('#sa-filter-form input[type="checkbox"]:checked').forEach(function (input) {
      groups[input.name] = groups[input.name] || [];
      groups[input.name].push(input.value);
    });
    Object.keys(groups).forEach(function (name) {
      url.searchParams.set(name, groups[name].join(','));
      url.searchParams.set(name.replace('filter_', 'query_type_'), 'or');
    });

    var price = bounds();
    var minInput = document.querySelector('.sa-catalog [data-sa-min]');
    var maxInput = document.querySelector('.sa-catalog [data-sa-max]');
    if (minInput && Number(minInput.value) > price.min) url.searchParams.set('min_price', String(Math.round(Number(minInput.value))));
    if (maxInput && Number(maxInput.value) < price.max) url.searchParams.set('max_price', String(Math.round(Number(maxInput.value))));

    var sort = document.querySelector('.sa-catalog [data-sa-orderby]');
    if (sort) {
      if (sort.value) url.searchParams.set('orderby', sort.value);
      else url.searchParams.delete('orderby');
    }
    return url.pathname + url.search;
  }

  function setOpen(open) {
    document.body.classList.toggle('sa-filter-open', open);
    var backdrop = document.querySelector('.sa-catalog .sa-filter-backdrop');
    if (backdrop) backdrop.hidden = !open;
  }

  function applyCounts(counts) {
    if (!counts) return;
    Object.keys(counts).forEach(function (name) {
      Object.keys(counts[name]).forEach(function (slug) {
        var input = document.querySelector('#sa-filter-form input[name="' + name + '"][value="' + slug + '"]');
        if (!input) return;
        var count = input.closest('label').querySelector('.sa-filter__count');
        if (count) count.textContent = String(counts[name][slug]);
      });
    });
  }

  function load(url, push) {
    var results = document.getElementById('sa-home-products');
    if (results) results.classList.add('is-loading');
    if (busy) busy.abort();
    busy = new AbortController();
    var request = new URL(url, window.location.origin);
    request.searchParams.set('sa_home_filter', '1');
    fetch(request.toString(), { signal: busy.signal, headers: { 'X-Requested-With': 'sa-home-filter' } })
      .then(function (response) { return response.json(); })
      .then(function (payload) {
        if (!payload || !payload.success) return;
        var box = document.getElementById('sa-home-products');
        var chips = document.getElementById('sa-home-chips');
        if (box) box.outerHTML = payload.data.products;
        if (chips) chips.innerHTML = payload.data.chips || '';
        applyCounts(payload.data.counts);
        var opener = document.querySelector('.sa-catalog__open');
        if (opener) opener.textContent = 'Bộ lọc' + (payload.data.active ? ' (' + payload.data.active + ')' : '');
        document.querySelectorAll('.sa-catalog [data-sa-found]').forEach(function (node) {
          node.textContent = String(payload.data.found || 0);
        });
        if (push) history.pushState({ saHomeFilter: 1 }, '', url);
      })
      .catch(function (error) {
        if (error.name !== 'AbortError' && results) results.classList.remove('is-loading');
      });
  }

  document.addEventListener('change', function (event) {
    if (!event.target.closest('.sa-catalog')) return;
    if (event.target.matches('#sa-filter-form input[type="checkbox"], [data-sa-orderby]')) {
      load(buildUrl(), true);
    }
  });

  document.addEventListener('input', function (event) {
    if (!event.target.closest('.sa-catalog')) return;
    var target = event.target;
    if (target.matches('[data-sa-range], [data-sa-min], [data-sa-max]')) {
      var minInput = document.querySelector('.sa-catalog [data-sa-min]');
      var maxInput = document.querySelector('.sa-catalog [data-sa-max]');
      var minRange = document.querySelector('.sa-catalog [data-sa-range="min"]');
      var maxRange = document.querySelector('.sa-catalog [data-sa-range="max"]');
      if (!minInput || !maxInput) return;
      if (target.dataset.saRange === 'min') minInput.value = target.value;
      if (target.dataset.saRange === 'max') maxInput.value = target.value;
      if (Number(minInput.value) > Number(maxInput.value)) {
        if (target === minInput || target === minRange) maxInput.value = minInput.value;
        else minInput.value = maxInput.value;
      }
      if (minRange) minRange.value = minInput.value;
      if (maxRange) maxRange.value = maxInput.value;
      var readout = document.querySelector('.sa-catalog .sa-filter__price-readout');
      if (readout) readout.textContent = money(minInput.value) + ' – ' + money(maxInput.value);
      clearTimeout(target._saTimer);
      target._saTimer = setTimeout(function () { load(buildUrl(), true); }, 400);
    }
    if (target.matches('.sa-filter__search')) {
      var query = target.value.trim().toLowerCase();
      var list = target.parentNode.querySelectorAll('.sa-filter__list li');
      list.forEach(function (item) {
        var name = item.getAttribute('data-name') || '';
        var extra = item.hasAttribute('data-extra');
        var opened = target.parentNode.querySelector('.sa-filter__more') && target.parentNode.querySelector('.sa-filter__more').getAttribute('data-open') === '1';
        item.hidden = query ? name.indexOf(query) === -1 : (extra && !opened);
      });
    }
  });

  document.addEventListener('click', function (event) {
    if (!event.target.closest('.sa-catalog') && !event.target.closest('.sa-filter-backdrop')) return;
    var toggle = event.target.closest('.sa-filter__toggle');
    if (toggle) {
      var group = toggle.closest('.sa-filter__group');
      var open = !group.classList.contains('is-open');
      group.classList.toggle('is-open', open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    var more = event.target.closest('[data-sa-more]');
    if (more) {
      var panel = more.parentNode;
      var opened = more.getAttribute('data-open') !== '1';
      more.setAttribute('data-open', opened ? '1' : '0');
      more.textContent = opened ? 'Thu gọn' : 'Xem thêm';
      panel.querySelectorAll('[data-extra]').forEach(function (item) { item.hidden = !opened; });
    }
    if (event.target.closest('[data-sa-open]')) setOpen(true);
    if (event.target.closest('[data-sa-close], .sa-catalog .sa-filter-backdrop')) setOpen(false);
    if (event.target.closest('.sa-catalog [data-sa-clear]')) {
      document.querySelectorAll('#sa-filter-form input[type="checkbox"]').forEach(function (input) { input.checked = false; });
      var price = bounds();
      var minInput = document.querySelector('.sa-catalog [data-sa-min]');
      var maxInput = document.querySelector('.sa-catalog [data-sa-max]');
      var minRange = document.querySelector('.sa-catalog [data-sa-range="min"]');
      var maxRange = document.querySelector('.sa-catalog [data-sa-range="max"]');
      if (minInput) minInput.value = String(price.min);
      if (maxInput) maxInput.value = String(price.max);
      if (minRange) minRange.value = String(price.min);
      if (maxRange) maxRange.value = String(price.max);
      load(buildUrl(), true);
    }
    var chip = event.target.closest('#sa-home-chips [data-clear-filter]');
    if (chip) {
      var box = document.querySelector('#sa-filter-form input[name="filter_' + chip.dataset.clearFilter + '"][value="' + chip.dataset.clearValue + '"]');
      if (box) box.checked = false;
      load(buildUrl(), true);
    }
    if (event.target.closest('#sa-home-chips [data-clear-price]')) {
      var priceReset = bounds();
      var minField = document.querySelector('.sa-catalog [data-sa-min]');
      var maxField = document.querySelector('.sa-catalog [data-sa-max]');
      if (minField) minField.value = String(priceReset.min);
      if (maxField) maxField.value = String(priceReset.max);
      load(buildUrl(), true);
    }
  });

  window.addEventListener('popstate', function () {
    window.location.reload();
  });
}());
