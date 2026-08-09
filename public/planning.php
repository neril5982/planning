<?php
$current_page = '/planning.php';
require 'includes/auth_check.php';
$page_title    = 'Planning';
$extra_scripts = ['/assets/js/planning.js'];
require 'includes/header.php';
?>
<div class="flex h-screen overflow-hidden">
  <?php require 'includes/sidebar.php'; ?>
  <main class="flex-1 overflow-y-auto page-bg" id="main-content">
    <div class="px-4 py-4 sm:px-6 sm:py-6">
      <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
        <div>
          <h1 class="page-title">Planning</h1>
          <p class="text-sm text-gray-500 mt-0.5">Planning des chantiers</p>
        </div>
        <div class="flex items-center gap-2">
          <button id="gantt-today-btn" type="button"
            class="text-sm font-medium px-3 py-2 rounded-lg border border-gray-300 bg-white hover:bg-gray-50">
            Aujourd'hui
          </button>
          <button id="gantt-add-btn" type="button" class="text-sm font-semibold px-4 py-2 rounded-lg btn-primary">
            + Nouveau chantier
          </button>
        </div>
      </div>

      <div class="card overflow-hidden">
        <div id="gantt-wrap" style="overflow:auto;max-width:100%">
          <div id="gantt-grid"></div>
        </div>
      </div>
    </div>
  </main>
</div>
<?php require 'includes/footer.php'; ?>
