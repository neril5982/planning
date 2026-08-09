// ── Auth ──────────────────────────────────────────────────────────────────────
const TOKEN_KEY = 'plan_token';

const Auth = {
  getToken() {
    return localStorage.getItem(TOKEN_KEY)
        || (document.cookie.match(/(?:^|;\s*)plan_token=([^;]+)/) || [])[1]
        || null;
  },
  setToken(t) {
    localStorage.setItem(TOKEN_KEY, t);
    document.cookie = `${TOKEN_KEY}=${t}; path=/; max-age=86400; samesite=Lax`;
  },
  clear() {
    localStorage.removeItem(TOKEN_KEY);
    document.cookie = `${TOKEN_KEY}=; path=/; max-age=0`;
  },
  getUser() {
    const t = this.getToken();
    if (!t) return null;
    try {
      const b64 = t.split('.')[1];
      const pad = b64.length % 4 ? '='.repeat(4 - b64.length % 4) : '';
      return JSON.parse(atob(b64.replace(/-/g, '+').replace(/_/g, '/') + pad));
    } catch { return null; }
  },
  isAdmin() { return this.getUser()?.role === 'admin'; },
};

// ── API client ────────────────────────────────────────────────────────────────
const API = {
  async req(method, path, body) {
    const headers = { 'Content-Type': 'application/json' };
    const t = Auth.getToken();
    if (t) headers['Authorization'] = `Bearer ${t}`;
    const opts = { method, headers };
    if (body != null) opts.body = JSON.stringify(body);
    let res;
    try { res = await fetch('/api' + path, opts); }
    catch (e) { UI.toast('Erreur réseau', 'error'); throw e; }
    let json;
    try { json = await res.json(); }
    catch { json = { success: false, message: 'Réponse invalide' }; }
    if (!json.success) {
      const err = new Error(json.message || 'Erreur');
      err.status = res.status;
      err.errors = json.errors || {};
      throw err;
    }
    return json.data;
  },
  get(path)        { return this.req('GET', path); },
  post(path, body)  { return this.req('POST', path, body); },
};

// ── Escape helper ─────────────────────────────────────────────────────────────
function esc(s) {
  return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// ── Modal ─────────────────────────────────────────────────────────────────────
function openModal(html, { wide = false } = {}) {
  const el = document.getElementById('modal');
  el.innerHTML = `<div class="bg-white rounded-xl shadow-xl ${wide ? 'max-w-2xl' : 'max-w-md'} w-full p-6 overflow-y-auto" style="max-height:90vh">${html}</div>`;
  el.classList.remove('hidden');
  el.onclick = (e) => { if (e.target === el) closeModal(); };
}
function closeModal() { document.getElementById('modal')?.classList.add('hidden'); }

// ── Logout ────────────────────────────────────────────────────────────────────
async function logout() {
  try { await API.post('/auth/logout'); } catch {}
  Auth.clear();
  window.location.href = '/login.php';
}

// ── UI helpers ────────────────────────────────────────────────────────────────
const UI = {
  toast(message, type = 'info') {
    const container = document.getElementById('toast-container');
    if (!container) return;
    const el = document.createElement('div');
    el.className = `pointer-events-auto text-sm px-4 py-2.5 rounded-lg shadow ${
      type === 'error' ? 'badge-error' : type === 'success' ? 'badge-success' : 'bg-white border border-gray-200'
    }`;
    el.textContent = message;
    container.appendChild(el);
    setTimeout(() => el.remove(), 4000);
  },
};
