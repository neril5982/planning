<?php
$_role     = $current_user['role'] ?? 'user';
$_is_admin = $_role === 'admin';

// Sections de navigation repliables. Chaque future application vient s'ajouter
// ici comme un item dans "subs" (ou comme une nouvelle section).
$_nav_sections = [
  'gestion' => [
    'label' => 'Gestion',
    'icon'  => 'M9 3v2m6-2v2M4 8h16M5 6h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V7a1 1 0 011-1z',
    'subs'  => [
      ['path' => '/planning.php', 'label' => 'Planning'],
    ],
  ],
];

foreach ($_nav_sections as $_key => &$_section) {
    $_section['active'] = in_array($current_page ?? '', array_column($_section['subs'], 'path'), true);
}
unset($_section);

$_nav_admin = [];
if ($_is_admin) {
    $_nav_admin[] = [
        'path'  => '/parametres.php',
        'label' => 'Paramètres',
        'icon'  => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
    ];
}

$_prenom0 = mb_substr($current_user['prenom'] ?? '', 0, 1);
$_nom0    = mb_substr($current_user['nom']    ?? '', 0, 1);
?>
<div id="sidebar-overlay"></div>

<aside id="sidebar" class="sidebar flex flex-col">

  <div id="sidebar-header" class="px-4 py-5" style="border-bottom:1px solid var(--c-sidebar-border);position:relative">
    <div id="sidebar-header-brand-row" class="flex items-center gap-2.5" style="padding-right:26px">
      <div id="sidebar-brand-text">
        <div class="font-semibold text-sm text-gray-900">Gestion SARL Moncomble</div>
        <div class="text-xs text-gray-500">Portail</div>
      </div>
    </div>
    <button id="sidebar-close" aria-label="Replier le menu" type="button"
            class="p-1.5 rounded-lg transition-colors hover:bg-gray-100 text-gray-500"
            style="position:absolute;top:14px;right:10px">
      <svg id="sidebar-toggle-icon-collapse" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
      </svg>
      <svg id="sidebar-toggle-icon-expand" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
      </svg>
    </button>
  </div>

  <nav class="flex-1 overflow-y-auto px-3 py-4 flex flex-col gap-0.5">

    <a href="/index.php"
      class="sidebar-link <?= ($current_page ?? '') === '/index.php' ? 'active' : '' ?> flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors">
      <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round"
          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
      </svg>
      <span>Accueil</span>
    </a>

    <?php foreach ($_nav_sections as $_key => $_section): ?>
    <button id="nav-<?= $_key ?>-toggle" type="button" data-nav-toggle="<?= $_key ?>"
      class="sidebar-link w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors">
      <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="<?= htmlspecialchars($_section['icon']) ?>"/>
      </svg>
      <span class="flex-1 text-left"><?= htmlspecialchars($_section['label']) ?></span>
      <svg id="nav-<?= $_key ?>-arrow" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"
           style="transition:transform .2s ease">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
      </svg>
    </button>
    <div id="nav-<?= $_key ?>-sub" data-nav-open="<?= $_section['active'] ? '1' : '0' ?>"
         style="overflow:hidden;max-height:0;transition:max-height .2s ease">
      <?php foreach ($_section['subs'] as $_sub):
        $_active = ($current_page ?? '') === $_sub['path'] ? 'active' : '';
      ?>
      <a href="<?= htmlspecialchars($_sub['path']) ?>"
         class="sidebar-link <?= $_active ?> flex items-center gap-2 pl-9 pr-3 py-2 rounded-lg text-sm transition-colors">
        <span class="shrink-0" style="color:#9CA3AF;font-size:10px">—</span>
        <?= htmlspecialchars($_sub['label']) ?>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endforeach; ?>

    <?php if ($_nav_admin): ?>
    <div class="sidebar-section-label px-3 pt-3 pb-1">Administration</div>
    <?php foreach ($_nav_admin as $_item):
      $_active = ($current_page ?? '') === $_item['path'] ? 'active' : '';
    ?>
    <a href="<?= htmlspecialchars($_item['path']) ?>"
       class="sidebar-link <?= $_active ?> flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors">
      <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="<?= htmlspecialchars($_item['icon']) ?>"/>
      </svg>
      <span><?= htmlspecialchars($_item['label']) ?></span>
    </a>
    <?php endforeach; ?>
    <?php endif; ?>

  </nav>

  <div class="px-4 py-3" style="border-top:1px solid var(--c-sidebar-border)">
    <div class="flex items-center gap-2.5 mb-2">
      <div class="w-8 h-8 rounded-full flex items-center justify-center font-semibold text-xs shrink-0"
           style="background:#EFF6FF;color:#185FA5">
        <?= htmlspecialchars($_prenom0 . $_nom0) ?: '?' ?>
      </div>
      <div id="sidebar-user-text" class="overflow-hidden">
        <div class="text-sm font-medium text-gray-900 truncate">
          <?= htmlspecialchars(($current_user['prenom'] ?? '') . ' ' . ($current_user['nom'] ?? '')) ?>
        </div>
        <div class="text-xs text-gray-500 truncate">
          <?= htmlspecialchars($current_user['email'] ?? '') ?>
        </div>
      </div>
    </div>
    <button onclick="logout()"
      class="sidebar-logout w-full text-left text-xs flex items-center gap-1.5 transition-colors py-1">
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
      </svg>
      <span>Déconnexion</span>
    </button>
  </div>

</aside>

<div id="mobile-bar">
  <button id="burger-btn" aria-label="Ouvrir le menu">
    <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
    </svg>
  </button>
  <span class="text-gray-900 text-sm font-semibold">Gestion SARL Moncomble</span>
</div>
