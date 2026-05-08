<script>
window.__gurztacApplyTheme = window.__gurztacApplyTheme || function(t) {
  try { localStorage.setItem('theme', t); } catch (e) {}
  var dark = (t === 'dark') || (t === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
  if (dark) document.documentElement.classList.add('dark');
  else document.documentElement.classList.remove('dark');
};

if (!window.__gurztacThemeBound) {
  window.__gurztacThemeBound = true;

  document.addEventListener('click', function (e) {
    var btn = e.target && e.target.closest ? e.target.closest('button') : null;
    if (!btn) return;
    var h = btn.getAttribute('x-on:click') || btn.getAttribute('@click') || '';
    if (h.indexOf('theme') === -1) return;
    var mode = null;
    if (h.indexOf("'light'") !== -1 || h.indexOf('"light"') !== -1) mode = 'light';
    else if (h.indexOf("'dark'") !== -1 || h.indexOf('"dark"') !== -1) mode = 'dark';
    else if (h.indexOf("'system'") !== -1 || h.indexOf('"system"') !== -1) mode = 'system';
    if (mode) window.__gurztacApplyTheme(mode);
  }, true);

  window.addEventListener('theme-changed', function (e) { window.__gurztacApplyTheme(e.detail); });

  if (window.matchMedia) {
    var mq = window.matchMedia('(prefers-color-scheme: dark)');
    if (mq.addEventListener) {
      mq.addEventListener('change', function () {
        if ((localStorage.getItem('theme') || 'system') === 'system') window.__gurztacApplyTheme('system');
      });
    }
  }

  document.addEventListener('livewire:navigated', function () {
    window.__gurztacApplyTheme(localStorage.getItem('theme') || 'system');
  });
}
</script>
