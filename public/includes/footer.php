<div id="modal" class="hidden fixed inset-0 bg-black/40 z-40 flex items-center justify-center p-4"></div>
<div id="toast-container" class="fixed top-4 right-4 z-50 flex flex-col gap-2 pointer-events-none"></div>
<footer id="app-footer">
  <span>© <?= date('Y') ?> Gestion SARL Moncomble</span>
  <span style="color:rgba(255,255,255,.45)">·</span>
  <span>Développé par <a href="https://www.dubois-digital.com" target="_blank" rel="noopener">Dubois Digital</a></span>
  <?php if (!empty($current_user)): ?>
  <span style="color:rgba(255,255,255,.45)">·</span>
  <a href="/changelog.php" title="Voir le changelog" style="font-family:monospace">v<?= defined('APP_VERSION') ? APP_VERSION : '' ?></a>
  <?php endif; ?>
</footer>
<script src="/assets/js/app.js"></script>
<?php foreach ($extra_scripts ?? [] as $src): ?>
<script src="<?= htmlspecialchars($src) ?>"></script>
<?php endforeach; ?>
<script>
(function () {
  var sidebar  = document.getElementById('sidebar');
  var overlay  = document.getElementById('sidebar-overlay');
  var burger   = document.getElementById('burger-btn');
  var btnClose = document.getElementById('sidebar-close');
  if (!sidebar) return;
  function isDesktop() { return window.innerWidth >= 1024; }
  function openNav()  { sidebar.classList.add('sidebar-open');    overlay.classList.add('open');    document.body.style.overflow = 'hidden'; }
  function closeNav() { sidebar.classList.remove('sidebar-open'); overlay.classList.remove('open'); document.body.style.overflow = ''; }

  // ── Sidebar repliée (desktop) : icônes seules, dépliage au clic ────────────
  function setCollapsed(collapsed) {
    sidebar.classList.toggle('collapsed', collapsed);
    document.body.classList.toggle('sidebar-collapsed', collapsed);
    try { localStorage.setItem('sidebar_collapsed', collapsed ? '1' : '0'); } catch (e) {}
  }
  if (isDesktop() && (function () { try { return localStorage.getItem('sidebar_collapsed') === '1'; } catch (e) { return false; } })()) {
    setCollapsed(true);
  }

  if (burger)   burger.addEventListener('click', openNav);
  if (btnClose) {
    btnClose.addEventListener('click', function () {
      if (isDesktop()) {
        setCollapsed(!sidebar.classList.contains('collapsed'));
      } else {
        closeNav();
      }
    });
  }
  if (overlay)  overlay.addEventListener('click', closeNav);
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeNav(); });

  document.querySelectorAll('a.sidebar-link').forEach(function (l) {
    l.addEventListener('click', function () { if (!isDesktop()) closeNav(); });
  });

  document.querySelectorAll('.sidebar-link, .sidebar-logout').forEach(function (l) {
    l.addEventListener('click', function () {
      if (isDesktop() && sidebar.classList.contains('collapsed')) setCollapsed(false);
    }, true);
  });

  // ── Accordéon générique des sections de navigation ──────────────────────
  document.querySelectorAll('[data-nav-toggle]').forEach(function (toggle) {
    var key = toggle.getAttribute('data-nav-toggle');
    var sub = document.getElementById('nav-' + key + '-sub');
    var arrow = document.getElementById('nav-' + key + '-arrow');
    if (!sub) return;
    var storageKey = 'sidebar_' + key + '_open';

    function open(animate) {
      if (!animate) sub.style.transition = 'none';
      sub.style.maxHeight = sub.scrollHeight + 'px';
      if (arrow) arrow.style.transform = 'rotate(90deg)';
      if (!animate) setTimeout(function () { sub.style.transition = 'max-height .2s ease'; }, 0);
      sessionStorage.setItem(storageKey, '1');
    }
    function close() {
      sub.style.maxHeight = '0';
      if (arrow) arrow.style.transform = '';
      sessionStorage.setItem(storageKey, '0');
    }
    function isOpen() {
      return sub.style.maxHeight !== '0' && sub.style.maxHeight !== '0px' && sub.style.maxHeight !== '';
    }

    if (sub.dataset.navOpen === '1' || sessionStorage.getItem(storageKey) === '1') {
      open(false);
    }

    toggle.addEventListener('click', function () {
      if (isOpen()) { close(); } else { open(true); }
    });
  });
})();
</script>
</body>
</html>
