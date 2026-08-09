// ── Formulaire "mot de passe oublié" (demande de lien) ─────────────────────────
const fpForm = document.getElementById('fp-form');
if (fpForm) {
  fpForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const msgEl = document.getElementById('fp-msg');
    const email = document.getElementById('fp-email').value.trim();
    try {
      const data = await API.post('/auth/forgot-password', { email });
      msgEl.textContent = data?.message || 'Si cet email existe, un lien a été envoyé.';
      msgEl.className = 'text-xs px-3 py-2 rounded-lg badge-success';
    } catch (err) {
      msgEl.textContent = err.message || 'Erreur, veuillez réessayer.';
      msgEl.className = 'text-xs px-3 py-2 rounded-lg badge-error';
    }
  });
}

// ── Formulaire de réinitialisation (avec token) ─────────────────────────────────
const rpForm = document.getElementById('rp-form');
if (rpForm) {
  const pwdInput = document.getElementById('rp-password');
  const checks = {
    len:     (v) => v.length >= 12,
    upper:   (v) => /[A-Z]/.test(v),
    special: (v) => /[^a-zA-Z0-9]/.test(v),
  };

  pwdInput.addEventListener('input', () => {
    const v = pwdInput.value;
    for (const key of Object.keys(checks)) {
      const li = document.getElementById(`pol-${key}`);
      const ok = checks[key](v);
      li.classList.toggle('text-gray-400', !ok);
      li.classList.toggle('text-green-600', ok);
      li.querySelector('.pol-icon').textContent = ok ? '●' : '○';
    }
  });

  rpForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const errorEl  = document.getElementById('rp-error');
    errorEl.classList.add('hidden');

    const password = pwdInput.value;
    const confirm  = document.getElementById('rp-confirm').value;
    const token    = rpForm.dataset.token;

    if (password !== confirm) {
      errorEl.textContent = 'Les mots de passe ne correspondent pas';
      errorEl.classList.remove('hidden');
      return;
    }

    try {
      await API.post('/auth/reset-password', { token, password });
      window.location.href = '/login.php';
    } catch (err) {
      errorEl.textContent = err.message || 'Erreur, veuillez réessayer.';
      errorEl.classList.remove('hidden');
    }
  });
}
