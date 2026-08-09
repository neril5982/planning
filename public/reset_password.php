<?php
require_once __DIR__ . '/../config.php';
$page_title    = 'Réinitialisation';
$extra_scripts = ['/assets/js/reset_password.js'];
$token         = htmlspecialchars($_GET['token'] ?? '');
require 'includes/header.php';
?>
<div class="min-h-screen tile-bg flex items-center justify-center p-4">
  <div class="w-full max-w-sm">
    <div class="text-center mb-8">
      <div class="w-12 h-12 flex items-center justify-center mx-auto mb-3"
           style="background:#1E2B3A;border-radius:16px">
        <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect x="0" y="0" width="12" height="12" rx="2" fill="#378ADD"/>
          <rect x="16" y="0" width="12" height="12" rx="2" fill="#185FA5"/>
          <rect x="0" y="16" width="12" height="12" rx="2" fill="#185FA5"/>
          <rect x="16" y="16" width="12" height="12" rx="2" fill="#378ADD"/>
        </svg>
      </div>
      <h1 class="text-xl font-bold text-gray-900">
        <?= $token ? 'Nouveau mot de passe' : 'Mot de passe oublié' ?>
      </h1>
    </div>
    <div class="card shadow-sm p-6">
      <?php if ($token): ?>
      <form id="rp-form" class="flex flex-col gap-4" data-token="<?= $token ?>">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Nouveau mot de passe</label>
          <input id="rp-password" type="password" required autocomplete="new-password"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-accent"
            placeholder="Min. 12 caractères">
        </div>
        <ul id="rp-policy" class="text-xs space-y-1 text-gray-400 pl-1">
          <li id="pol-len"     class="flex items-center gap-1.5"><span class="pol-icon">○</span> 12 caractères minimum</li>
          <li id="pol-upper"   class="flex items-center gap-1.5"><span class="pol-icon">○</span> Au moins une majuscule</li>
          <li id="pol-special" class="flex items-center gap-1.5"><span class="pol-icon">○</span> Au moins un caractère spécial</li>
        </ul>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Confirmer le mot de passe</label>
          <input id="rp-confirm" type="password" required autocomplete="new-password"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-accent"
            placeholder="Répétez le mot de passe">
        </div>
        <div id="rp-error" class="hidden text-xs px-3 py-2 rounded-lg badge-error"></div>
        <button type="submit" class="w-full text-sm font-semibold py-2.5 rounded-lg btn-primary">
          Enregistrer
        </button>
      </form>
      <?php else: ?>
      <form id="fp-form" class="flex flex-col gap-4">
        <p class="text-sm text-gray-600">Entrez votre email pour recevoir un lien de réinitialisation.</p>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
          <input id="fp-email" type="email" required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-accent">
        </div>
        <div id="fp-msg" class="hidden text-xs px-3 py-2 rounded-lg"></div>
        <button type="submit" class="w-full text-sm font-semibold py-2.5 rounded-lg btn-primary">
          Envoyer le lien
        </button>
      </form>
      <?php endif; ?>
      <div class="mt-4 text-center">
        <a href="/login.php" class="text-xs hover:underline" style="color:var(--c-accent)">← Retour à la connexion</a>
      </div>
    </div>
  </div>
</div>
<?php require 'includes/footer.php'; ?>
