<?php
require_once __DIR__ . '/../config.php';
$page_title    = 'Connexion';
$extra_scripts = ['/assets/js/login.js'];
require 'includes/header.php';
?>
<?php if (TURNSTILE_SITE_KEY !== ''): ?>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<?php endif; ?>
<div class="min-h-screen tile-bg flex items-center justify-center p-4">
  <div class="w-full max-w-sm">
    <div class="text-center mb-8">
      <h1 class="text-xl font-bold text-gray-900">Gestion SARL Moncomble</h1>
      <p class="text-sm text-gray-500 mt-1">Connectez-vous à votre portail</p>
    </div>
    <div class="card shadow-sm p-6">
      <form id="login-form" class="flex flex-col gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
          <input id="login-email" type="email" autocomplete="email" required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
            placeholder="vous@domaine.fr">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Mot de passe</label>
          <input id="login-password" type="password" autocomplete="current-password" required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
            placeholder="••••••••">
        </div>
        <?php if (TURNSTILE_SITE_KEY !== ''): ?>
        <div class="cf-turnstile" data-sitekey="<?= htmlspecialchars(TURNSTILE_SITE_KEY, ENT_QUOTES) ?>"></div>
        <?php endif; ?>
        <div id="login-error" class="hidden text-xs px-3 py-2 rounded-lg badge-error"></div>
        <button type="submit" id="login-btn"
          class="w-full text-sm font-semibold py-2.5 rounded-lg btn-primary">
          Se connecter
        </button>
      </form>
      <div class="mt-4 text-center">
        <a href="/reset_password.php" class="text-xs hover:underline" style="color:var(--c-accent)">
          Mot de passe oublié ?
        </a>
      </div>
    </div>
  </div>
</div>
<?php require 'includes/footer.php'; ?>
