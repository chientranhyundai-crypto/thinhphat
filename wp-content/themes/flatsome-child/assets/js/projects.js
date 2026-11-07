(function () {
  var root = document.querySelector('.sa-da-board') || document.querySelector('.sa-da');
  if (!root) return;
  var buttons = root.querySelectorAll('[data-sa-da-filter], .sa-da-filter');
  var items = root.querySelectorAll('[data-sa-da-item], .sa-da-item');
  var empty = root.querySelector('[data-sa-da-empty], .sa-da-empty');

  function filterKey(button) {
    var data = button.getAttribute('data-sa-da-filter');
    if (data) return data;
    var match = (button.className || '').match(/sa-da-filter--([a-z0-9-]+)/);
    return match ? match[1] : 'all';
  }

  function itemTerms(item) {
    var data = item.getAttribute('data-sa-da-terms');
    if (data) return data.split(/\s+/).filter(Boolean);
    var terms = [];
    (item.className || '').split(/\s+/).forEach(function (name) {
      if (name.indexOf('sa-da-term-') === 0) terms.push(name.slice(11));
    });
    return terms;
  }

  function apply(key) {
    var visible = 0;
    items.forEach(function (item) {
      var terms = itemTerms(item);
      var show = key === 'all' || terms.indexOf(key) !== -1;
      item.hidden = !show;
      if (show) visible += 1;
    });
    if (empty) {
      empty.hidden = visible !== 0;
      empty.classList.toggle('is-on', visible === 0);
    }
    buttons.forEach(function (button) {
      var on = filterKey(button) === key;
      button.classList.toggle('is-active', on);
      button.setAttribute('aria-pressed', on ? 'true' : 'false');
    });
  }

  buttons.forEach(function (button) {
    button.addEventListener('click', function (event) {
      if (button.classList.contains('sa-da-filter')) event.preventDefault();
      apply(filterKey(button));
    });
  });
})();
