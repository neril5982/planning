<?php
$current_page = '/parametres.php';
require 'includes/auth_check.php';
if (($current_user['role'] ?? '') !== 'admin') {
    header('Location: /index.php');
    exit;
}
$page_title    = 'Paramètres';
$extra_scripts = ['/assets/js/parametres.js'];
require 'includes/header.php';
?>
<div class="flex h-screen overflow-hidden">
  <?php require 'includes/sidebar.php'; ?>
  <main class="flex-1 overflow-y-auto page-bg" id="main-content">
    <div class="px-4 py-4 sm:px-6 sm:py-6 max-w-4xl">
      <h1 class="page-title mb-6">Paramètres</h1>

      <section class="card">
        <div class="px-5 py-4 flex items-center justify-between" style="border-bottom:1px solid var(--c-card-border)">
          <h2 class="font-semibold text-gray-900">Utilisateurs</h2>
          <button id="btn-add-user" class="text-xs px-3 py-1.5 rounded-lg font-medium btn-primary">
            + Ajouter
          </button>
        </div>
        <div id="users-table">
          <div class="flex justify-center py-10">
            <div class="w-6 h-6 border-4 rounded-full animate-spin spinner"></div>
          </div>
        </div>
      </section>
    </div>
  </main>
</div>
<?php require 'includes/footer.php'; ?>
