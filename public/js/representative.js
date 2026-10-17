(function () {
  document.addEventListener('click', function (e) {
    var button = e.target.closest('[data-select-all]');
    if (!button) { return; }

    var form = button.closest('form');
    if (!form) { return; }

    var boxes = form.querySelectorAll('input[name="rooms[]"]');
    var allOn = boxes.length > 0 && Array.prototype.every.call(boxes, function (box) { return box.checked; });
    boxes.forEach(function (box) { box.checked = !allOn; });
  });
})();
