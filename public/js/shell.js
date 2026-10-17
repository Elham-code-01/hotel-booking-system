(function () {
  var root = document.documentElement;
  var sidebar = document.getElementById('sidebar');
  var backdrop = document.getElementById('backdrop');
  var panel = document.getElementById('notify-panel');
  var bell = document.querySelector('[data-toggle="notify-panel"]');

  function openNav() { if (sidebar) { sidebar.classList.add('open'); } if (backdrop) { backdrop.classList.add('open'); } }
  function closeNav() { if (sidebar) { sidebar.classList.remove('open'); } if (backdrop) { backdrop.classList.remove('open'); } }
  function closeNotify() { if (panel) { panel.hidden = true; } if (bell) { bell.setAttribute('aria-expanded', 'false'); } }

  document.addEventListener('click', function (e) {
    var action = e.target.closest('[data-action]');
    if (action) {
      var name = action.getAttribute('data-action');
      if (name === 'open-nav') { openNav(); }
      if (name === 'close-nav') { closeNav(); }
      if (name === 'print') { window.print(); }
      if (name === 'show-password') {
        var input = document.getElementById(action.getAttribute('data-target'));
        if (input) {
          input.type = input.type === 'password' ? 'text' : 'password';
          action.textContent = input.type === 'password' ? 'Show' : 'Hide';
          action.setAttribute('aria-pressed', String(input.type === 'text'));
        }
      }
      if (name === 'theme') {
        root.classList.toggle('dark');
        try { localStorage.setItem('theme', root.classList.contains('dark') ? 'dark' : 'light'); } catch (err) {}
      }
      return;
    }
    var toggle = e.target.closest('[data-toggle="notify-panel"]');
    if (toggle && panel) {
      var willOpen = panel.hidden;
      panel.hidden = !willOpen;
      toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
      return;
    }
    if (panel && !panel.hidden && !e.target.closest('.notify')) { closeNotify(); }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { closeNav(); closeNotify(); }
  });

  document.querySelectorAll('.flash').forEach(function (el) {
    setTimeout(function () { el.remove(); }, 5000);
  });
})();
