document.getElementById('login-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  const errorEl = document.getElementById('login-error');
  const btn     = document.getElementById('login-btn');
  errorEl.classList.add('hidden');

  const email    = document.getElementById('login-email').value.trim();
  const password = document.getElementById('login-password').value;
  const turnstileEl = document.querySelector('[name="cf-turnstile-response"]');

  btn.disabled = true;
  btn.textContent = 'Connexion...';

  try {
    const data = await API.post('/auth/login', {
      email,
      password,
      turnstile_token: turnstileEl ? turnstileEl.value : '',
    });
    Auth.setToken(data.token);
    const params = new URLSearchParams(window.location.search);
    window.location.href = params.get('next') || '/index.php';
  } catch (err) {
    errorEl.textContent = err.message || 'Erreur de connexion';
    errorEl.classList.remove('hidden');
  } finally {
    btn.disabled = false;
    btn.textContent = 'Se connecter';
  }
});
