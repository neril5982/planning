const ROLE_LABELS = { admin: 'Admin', direction: 'Direction', user: 'Utilisateur' };
const ROLE_BADGE_CLASS = {
  admin: 'bg-purple-100 text-purple-700',
  direction: 'bg-blue-100 text-blue-700',
  user: 'badge-default',
};

function formatDate(iso) {
  if (!iso) return '';
  const d = new Date(iso.replace(' ', 'T'));
  return d.toLocaleDateString('fr-FR');
}

function formatDateTime(iso) {
  if (!iso) return 'Jamais';
  const d = new Date(iso.replace(' ', 'T'));
  return `${d.toLocaleDateString('fr-FR')} à ${d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}`;
}

let usersCache = [];

async function loadUsers() {
  const users = await API.get('/admin/users');
  usersCache = users;
  document.getElementById('users-table').innerHTML = `
    <table class="w-full text-sm">
      <thead><tr class="text-xs text-gray-500 bg-gray-50" style="border-bottom:1px solid var(--c-card-border)">
        <th class="px-4 py-3 text-left font-medium">Nom</th>
        <th class="px-4 py-3 text-left font-medium">Email</th>
        <th class="px-4 py-3 text-left font-medium">Rôle</th>
        <th class="px-4 py-3 text-left font-medium">Dernière connexion</th>
        <th class="px-4 py-3 text-left font-medium">Créé le</th>
        <th class="px-4 py-3 text-right font-medium">Actions</th>
      </tr></thead>
      <tbody>
        ${users.map(u => `
          <tr class="hover:bg-gray-50" style="border-bottom:1px solid var(--c-card-border)">
            <td class="px-4 py-3 font-medium">${esc((u.prenom || '') + ' ' + (u.nom || ''))}</td>
            <td class="px-4 py-3 text-gray-500">${esc(u.email)}</td>
            <td class="px-4 py-3">
              <span class="px-2 py-0.5 rounded-full text-xs font-medium ${ROLE_BADGE_CLASS[u.role] || 'badge-default'}">
                ${ROLE_LABELS[u.role] || u.role}
              </span>
            </td>
            <td class="px-4 py-3 text-gray-400 text-xs">${formatDateTime(u.last_login_at)}</td>
            <td class="px-4 py-3 text-gray-400 text-xs">${formatDate(u.created_at)}</td>
            <td class="px-4 py-3 text-right">
              <button onclick="showUserModal(${u.id})" class="text-xs hover:underline mr-3" style="color:var(--c-accent-dark)">Modifier</button>
              <button onclick="deleteUser(${u.id})" class="text-xs hover:underline" style="color:var(--c-error)">Supprimer</button>
            </td>
          </tr>`).join('') || `<tr><td colspan="6" class="px-4 py-6 text-center text-gray-400">Aucun utilisateur</td></tr>`}
      </tbody>
    </table>`;
}

function showUserModal(id) {
  const u = id != null ? usersCache.find(x => x.id === id) : null;
  const isEdit = !!u;
  openModal(`
    <h3 class="font-bold text-gray-900 mb-4">${isEdit ? 'Modifier' : 'Ajouter'} un utilisateur</h3>
    <form id="user-form" class="flex flex-col gap-3">
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="text-xs font-medium text-gray-700 block mb-1">Prénom</label>
          <input id="u-prenom" value="${esc(u?.prenom || '')}" class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-accent">
        </div>
        <div>
          <label class="text-xs font-medium text-gray-700 block mb-1">Nom</label>
          <input id="u-nom" value="${esc(u?.nom || '')}" class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-accent">
        </div>
      </div>
      <div>
        <label class="text-xs font-medium text-gray-700 block mb-1">Email</label>
        <input id="u-email" type="email" required value="${esc(u?.email || '')}" class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-accent">
      </div>
      ${isEdit ? `
      <div>
        <label class="text-xs font-medium text-gray-700 block mb-1">Mot de passe (laisser vide pour ne pas changer)</label>
        <input id="u-pwd" type="password" placeholder="••••••••" class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-accent">
      </div>` : `
      <div class="text-xs text-gray-500 px-3 py-2 rounded-lg bg-blue-50 text-blue-700">
        Un email d'activation sera envoyé à cette adresse pour que l'utilisateur choisisse son mot de passe.
      </div>`}
      <div>
        <label class="text-xs font-medium text-gray-700 block mb-1">Rôle</label>
        <select id="u-role" class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-accent bg-white">
          <option value="user"      ${u?.role === 'user'      ? 'selected' : ''}>Utilisateur</option>
          <option value="direction" ${u?.role === 'direction' ? 'selected' : ''}>Direction</option>
          <option value="admin"     ${u?.role === 'admin'     ? 'selected' : ''}>Admin</option>
        </select>
      </div>
      <div id="u-err" class="hidden text-xs px-3 py-2 rounded-lg badge-error"></div>
      <div class="flex gap-3 justify-end pt-1">
        <button type="button" onclick="closeModal()" class="text-sm px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Annuler</button>
        <button type="submit" class="text-sm px-4 py-2 rounded-lg font-medium btn-primary">${isEdit ? 'Enregistrer' : 'Créer'}</button>
      </div>
    </form>
  `);

  document.getElementById('user-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const errEl = document.getElementById('u-err');
    errEl.classList.add('hidden');

    const body = {
      email: document.getElementById('u-email').value.trim(),
      nom: document.getElementById('u-nom').value.trim(),
      prenom: document.getElementById('u-prenom').value.trim(),
      role: document.getElementById('u-role').value,
    };
    if (isEdit) {
      const pwd = document.getElementById('u-pwd')?.value;
      if (pwd) body.password = pwd;
    }

    try {
      if (isEdit) {
        await API.req('PUT', `/admin/users/${u.id}`, body);
      } else {
        await API.post('/admin/users', body);
      }
      closeModal();
      UI.toast(isEdit ? 'Utilisateur mis à jour' : 'Utilisateur créé', 'success');
      await loadUsers();
    } catch (err) {
      errEl.textContent = err.message || 'Erreur';
      errEl.classList.remove('hidden');
    }
  });
}

function deleteUser(id) {
  const u = usersCache.find(x => x.id === id);
  openModal(`
    <h3 class="font-bold text-gray-900 mb-2">Supprimer ${esc(u?.email || '')} ?</h3>
    <p class="text-sm text-gray-600 mb-4">Cette action est irréversible.</p>
    <div class="flex gap-3 justify-end">
      <button onclick="closeModal()" class="text-sm px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Annuler</button>
      <button id="confirm-delete-btn" class="text-sm px-4 py-2 rounded-lg font-medium btn-danger">Supprimer</button>
    </div>
  `);
  document.getElementById('confirm-delete-btn').addEventListener('click', async () => {
    try {
      await API.req('DELETE', `/admin/users/${id}`);
      closeModal();
      UI.toast('Utilisateur supprimé', 'success');
      await loadUsers();
    } catch (err) {
      UI.toast(err.message || 'Erreur', 'error');
    }
  });
}

document.getElementById('btn-add-user').addEventListener('click', () => showUserModal(null));

loadUsers();
