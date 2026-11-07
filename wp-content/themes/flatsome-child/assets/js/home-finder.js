(function () {
  var form = document.querySelector('.sa-finder__form');
  if (!form) return;

  form.addEventListener('submit', function () {
    form.querySelectorAll('select').forEach(function (select) {
      if (!select.value) select.disabled = true;
    });
    form.querySelectorAll('[data-sa-finder-type]').forEach(function (input) {
      var select = form.querySelector('[name="' + input.getAttribute('data-sa-finder-type') + '"]');
      if (!select || !select.value) input.disabled = true;
    });
  });

  var clear = form.querySelector('[data-sa-finder-clear]');
  if (clear) {
    clear.addEventListener('click', function () {
      form.querySelectorAll('select').forEach(function (select) {
        if (!select.disabled) select.value = '';
      });
    });
  }
}());
