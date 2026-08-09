<?php
$current_page = '/changelog.php';
require 'includes/auth_check.php';
$page_title = 'Changelog';
require 'includes/header.php';
?>
<div class="flex h-screen overflow-hidden">
  <?php require 'includes/sidebar.php'; ?>
  <main class="flex-1 overflow-y-auto page-bg" id="main-content">
    <div class="px-4 py-4 sm:px-6 sm:py-6 max-w-2xl">
      <h1 class="page-title mb-1">Changelog</h1>
      <p class="text-sm text-gray-500 mb-6">Historique des évolutions du portail.</p>

      <div class="card p-6">
        <div class="flex items-center gap-2 mb-3">
          <span class="text-sm font-bold text-gray-900">v1.0.0</span>
          <span class="text-xs text-gray-400">09/08/2026</span>
        </div>

        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Ajouté</h3>
        <ul class="text-sm text-gray-700 list-disc pl-5 space-y-1.5 mb-4">
          <li><strong>Portail</strong> : connexion, déconnexion, mot de passe oublié / réinitialisation, gestion des rôles (admin / direction / user).</li>
          <li><strong>Gestion → Planning</strong> : calendrier des chantiers en vue Gantt — créneaux de dates multiples par chantier, jours fériés français calculés automatiquement, week-ends/fériés non recouverts par les barres, entreprise associée (Moncomble / RVM) avec logo, palette de couleurs personnalisable.</li>
          <li><strong>Administration → Paramètres</strong> (admin uniquement) : gestion des utilisateurs (créer, modifier, supprimer), date de dernière connexion.</li>
          <li>Numéro de version affiché dans le portail, avec ce changelog consultable directement dans l'application.</li>
        </ul>

        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Corrigé</h3>
        <ul class="text-sm text-gray-700 list-disc pl-5 space-y-1.5">
          <li>Suppression d'un utilisateur : une erreur JavaScript empêchait la suppression lorsque le nom contenait certains caractères spéciaux (ex. apostrophe).</li>
          <li>Tri du planning : les chantiers sont de nouveau triés par date de début (et non plus par date de création).</li>
        </ul>
      </div>
    </div>
  </main>
</div>
<?php require 'includes/footer.php'; ?>
