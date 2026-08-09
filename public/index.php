<?php
$current_page = '/index.php';
require 'includes/auth_check.php';
$page_title = 'Accueil';
require 'includes/header.php';
?>
<div class="flex h-screen overflow-hidden">
  <?php require 'includes/sidebar.php'; ?>
  <main class="flex-1 overflow-y-auto page-bg" id="main-content">
    <div class="px-4 py-4 sm:px-6 sm:py-6">
      <div class="mb-6">
        <h1 class="page-title">Bienvenue, <?= htmlspecialchars($current_user['prenom'] ?? $current_user['email']) ?></h1>
        <p class="text-sm text-gray-500 mt-0.5">
          Rôle : <span class="font-medium"><?= htmlspecialchars($current_user['role']) ?></span>
        </p>
      </div>

      <div class="card p-6 max-w-lg">
        <h2 class="text-sm font-semibold text-gray-900 mb-1">Applications</h2>
        <p class="text-sm text-gray-500 mb-3">
          Retrouvez les modules disponibles dans le menu à gauche.
        </p>
        <a href="/planning.php" class="text-sm font-medium hover:underline" style="color:var(--c-accent-dark)">
          Gestion → Planning
        </a>
      </div>
    </div>
  </main>
</div>
<?php require 'includes/footer.php'; ?>
