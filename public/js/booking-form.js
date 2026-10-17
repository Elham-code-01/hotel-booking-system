(function () {
  var form = document.getElementById('booking-form');
  if (!form) { return; }

  var nights = parseFloat(form.getAttribute('data-nights')) || 0;
  var rate = parseFloat(form.getAttribute('data-rate')) || 1;
  var price = form.querySelector('[name="price_sar"]');

  function boxes() { return form.querySelectorAll('input[name="rooms[]"]'); }
  function fmt(x) { return x.toLocaleString('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
  function set(key, value) {
    var el = form.querySelector('[data-sum="' + key + '"]');
    if (el) { el.textContent = value; }
  }

  function update() {
    var count = 0;
    boxes().forEach(function (box) { if (box.checked) { count++; } });
    var sar = count * nights * (parseFloat(price.value) || 0);
    set('rooms', count);
    set('sar', fmt(sar));
    set('usd', fmt(sar / rate));
  }

  form.addEventListener('change', update);
  form.addEventListener('input', update);

  form.addEventListener('click', function (e) {
    var tool = e.target.closest('[data-floor-toggle],[data-select-all],[data-clear]');
    if (!tool) { return; }

    if (tool.hasAttribute('data-floor-toggle')) {
      var group = form.querySelector('[data-floor="' + tool.getAttribute('data-floor-toggle') + '"]');
      var list = group ? group.querySelectorAll('input') : [];
      var allOn = list.length > 0 && Array.prototype.every.call(list, function (i) { return i.checked; });
      list.forEach(function (i) { i.checked = !allOn; });
    } else {
      var on = tool.hasAttribute('data-select-all');
      boxes().forEach(function (i) { i.checked = on; });
    }
    update();
  });

  update();
})();
