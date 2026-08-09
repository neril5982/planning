// ── Date helpers ─────────────────────────────────────────────────────────────
function pDate(iso) { return new Date(iso + 'T00:00:00'); }
function pISO(d) { return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`; }
function pAddDays(d, n) { const r = new Date(d); r.setDate(r.getDate() + n); return r; }
function pDayDiff(a, b) { return Math.round((b - a) / 86400000); }

const DAY_LETTERS = ['D', 'L', 'M', 'M', 'J', 'V', 'S']; // getDay(): 0=dimanche
const MONTH_NAMES = ['JANVIER','FÉVRIER','MARS','AVRIL','MAI','JUIN','JUILLET','AOÛT','SEPTEMBRE','OCTOBRE','NOVEMBRE','DÉCEMBRE'];

function pISOWeek(d) {
  const date = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()));
  const day = (date.getUTCDay() + 6) % 7;
  date.setUTCDate(date.getUTCDate() - day + 3);
  const firstThursday = new Date(Date.UTC(date.getUTCFullYear(), 0, 4));
  return 1 + Math.round(((date - firstThursday) / 86400000 - 3 + ((firstThursday.getUTCDay() + 6) % 7)) / 7);
}

// ── Jours fériés français (fixes + mobiles calculés via Pâques) ────────────────
function easterDate(year) {
  const a = year % 19, b = Math.floor(year / 100), c = year % 100;
  const d = Math.floor(b / 4), e = b % 4, f = Math.floor((b + 8) / 25);
  const g = Math.floor((b - f + 1) / 3), h = (19 * a + b - d - g + 15) % 30;
  const i = Math.floor(c / 4), k = c % 4, l = (32 + 2 * e + 2 * i - h - k) % 7;
  const m = Math.floor((a + 11 * h + 22 * l) / 451);
  const month = Math.floor((h + l - 7 * m + 114) / 31);
  const day = ((h + l - 7 * m + 114) % 31) + 1;
  return new Date(year, month - 1, day);
}

function frenchHolidays(year) {
  const easter = easterDate(year);
  return [
    { date: new Date(year, 0, 1),  label: "Jour de l'an" },
    { date: pAddDays(easter, 1),   label: 'Lundi de Pâques' },
    { date: new Date(year, 4, 1),  label: 'Fête du travail' },
    { date: new Date(year, 4, 8),  label: 'Victoire 1945' },
    { date: pAddDays(easter, 39),  label: 'Ascension' },
    { date: pAddDays(easter, 50),  label: 'Lundi de Pentecôte' },
    { date: new Date(year, 6, 14), label: 'Fête nationale' },
    { date: new Date(year, 7, 15), label: 'Assomption' },
    { date: new Date(year, 10, 1), label: 'Toussaint' },
    { date: new Date(year, 10, 11),label: 'Armistice' },
    { date: new Date(year, 11, 25),label: 'Noël' },
  ];
}

// ── Entreprises ──────────────────────────────────────────────────────────────
const ENTREPRISES = ['Moncomble', 'RVM'];
const ENTREPRISE_LOGO = {
  Moncomble: '/assets/img/logoMoncomble.webp',
  RVM:       '/assets/img/logoRVM.webp',
};
const ENTREPRISE_MINI_LOGO = {
  Moncomble: '/assets/img/mini-Moncomble.png',
  RVM:       '/assets/img/mini-RVM.png',
};

// ── Palette de couleurs par défaut (modifiable via le sélecteur personnalisé) ──
const COLOR_PALETTE = [
  '#ADBF5E', '#53727F', '#FFBC7D', '#3C6DF0', '#D93025',
  '#5BA672', '#136AAF', '#F5A623', '#8E5FBF', '#7A7A7A',
];

// ── State ────────────────────────────────────────────────────────────────────
let chantiers = [];

async function loadChantiers() {
  chantiers = await API.get('/chantiers');
  renderGantt();
}

function allCreneaux() {
  return chantiers.flatMap(c => c.creneaux.map(cr => ({ ...cr, chantier: c })));
}

// ── Rendu du Gantt ───────────────────────────────────────────────────────────
const DAY_W = 30;
const LABEL_W = 220;

function renderGantt() {
  const grid = document.getElementById('gantt-grid');
  const today = new Date(); today.setHours(0, 0, 0, 0);

  const creneaux = allCreneaux();
  let rangeStart, rangeEnd;
  if (creneaux.length) {
    rangeStart = pAddDays(pDate(creneaux.reduce((a, c) => c.date_debut < a ? c.date_debut : a, creneaux[0].date_debut)), -7);
    rangeEnd   = pAddDays(pDate(creneaux.reduce((a, c) => c.date_fin   > a ? c.date_fin   : a, creneaux[0].date_fin)), 14);
  } else {
    rangeStart = pAddDays(today, -7);
    rangeEnd   = pAddDays(today, 90);
  }
  if (rangeStart > today) rangeStart = pAddDays(today, -7);
  if (rangeEnd < today) rangeEnd = pAddDays(today, 30);

  const days = [];
  for (let d = new Date(rangeStart); d <= rangeEnd; d = pAddDays(d, 1)) days.push(new Date(d));
  const numDays = days.length;

  const years = [...new Set(days.map(d => d.getFullYear()))];
  const holidays = new Map();
  years.forEach(y => frenchHolidays(y).forEach(h => holidays.set(pISO(h.date), h.label)));

  const bodyRowCount = Math.max(chantiers.length, 1);
  const totalRows = 3 + bodyRowCount;

  grid.style.gridTemplateColumns = `${LABEL_W}px repeat(${numDays}, ${DAY_W}px)`;
  grid.style.gridTemplateRows = `26px 20px 40px repeat(${bodyRowCount}, 46px)`;
  grid.innerHTML = '';
  const frag = document.createDocumentFragment();

  function cell(html, cls, col, row, extraStyle) {
    const el = document.createElement('div');
    el.className = cls;
    el.style.gridColumn = col;
    el.style.gridRow = row;
    if (extraStyle) Object.assign(el.style, extraStyle);
    el.innerHTML = html;
    frag.appendChild(el);
    return el;
  }

  // ── Labels d'en-tête (colonne fixe) ──
  cell('', 'gantt-label gantt-label-header', '1', '1 / 4');

  // ── Ligne mois ──
  let i = 0;
  while (i < numDays) {
    const m = days[i].getMonth(), y = days[i].getFullYear();
    let j = i;
    while (j < numDays && days[j].getMonth() === m && days[j].getFullYear() === y) j++;
    const tint = (m % 2 === 0) ? '#EAF1E1' : '#E7F0FA';
    cell(`${MONTH_NAMES[m]} ${y}`, 'gantt-month', `${i + 2} / ${j + 2}`, '1', { background: tint });
    i = j;
  }

  // ── Ligne semaines ISO ──
  i = 0;
  while (i < numDays) {
    const w = pISOWeek(days[i]);
    let j = i;
    while (j < numDays && pISOWeek(days[j]) === w) j++;
    cell(String(w), 'gantt-month', `${i + 2} / ${j + 2}`, '2', { fontSize: '10px', background: '#fff' });
    i = j;
  }

  // ── Ligne jours ──
  days.forEach((d, idx) => {
    const iso = pISO(d);
    const isWeekend = d.getDay() === 0 || d.getDay() === 6;
    const isHoliday = holidays.has(iso);
    const cls = 'gantt-day' + (isWeekend ? ' weekend' : '') + (isHoliday ? ' holiday' : '');
    const el = cell(`<span class="gd-num">${d.getDate()}</span><span>${DAY_LETTERS[d.getDay()]}</span>`, cls, `${idx + 2}`, '3');
    if (iso === pISO(today)) el.style.boxShadow = 'inset 0 0 0 2px var(--c-error)';
  });

  // ── Fond des lignes chantiers (weekend / jours fériés) ──
  for (let r = 0; r < bodyRowCount; r++) {
    days.forEach((d, idx) => {
      const iso = pISO(d);
      const isWeekend = d.getDay() === 0 || d.getDay() === 6;
      const isHoliday = holidays.has(iso);
      const cls = 'gantt-cell' + (isWeekend ? ' weekend' : '') + (isHoliday ? ' holiday' : '');
      cell('', cls, `${idx + 2}`, `${4 + r}`);
    });
  }

  // ── Overlays verticaux jours fériés (label) ──
  days.forEach((d, idx) => {
    const iso = pISO(d);
    if (!holidays.has(iso)) return;
    cell(`<span>${esc(holidays.get(iso))}</span>`, 'gantt-cell holiday-label', `${idx + 2}`, `4 / span ${bodyRowCount}`);
  });

  // ── Labels + barres des chantiers (une barre par créneau) ──
  if (!chantiers.length) {
    cell(`<div class="flex items-center h-full text-xs text-gray-400">Aucun chantier — cliquez sur "+ Nouveau chantier"</div>`, 'gantt-label', '1', '4');
  }
  chantiers.forEach((c, idx) => {
    const row = 4 + idx;
    const label = cell(
      `<div class="flex items-center gap-2 h-full">
         <div class="shrink-0 flex items-center justify-center" style="width:28px;height:100%;max-height:38px">
           <img src="${ENTREPRISE_MINI_LOGO[c.entreprise] || ''}" alt="${esc(c.entreprise)}" title="${esc(c.entreprise)}"
                style="max-width:100%;max-height:100%;object-fit:contain">
         </div>
         <div class="min-w-0">
           <div class="text-sm font-semibold text-gray-900 truncate">${esc(c.nom)}</div>
           ${c.ville ? `<div class="text-xs text-gray-500 truncate">${esc(c.ville)}</div>` : ''}
         </div>
       </div>`,
      'gantt-label', '1', row
    );
    label.style.cursor = 'pointer';
    label.addEventListener('click', () => openChantierModal(c));

    c.creneaux.forEach(cr => {
      const startIdx = Math.max(0, pDayDiff(rangeStart, pDate(cr.date_debut)));
      const endIdx   = Math.min(numDays - 1, pDayDiff(rangeStart, pDate(cr.date_fin)));
      if (endIdx < 0 || startIdx >= numDays) return;

      // Découpe la barre en segments de jours ouvrés consécutifs : les week-ends et
      // jours fériés ne doivent pas être recouverts par la couleur du chantier.
      let segStart = null;
      for (let d = startIdx; d <= endIdx + 1; d++) {
        const day = d <= endIdx ? days[d] : null;
        const isNonWorking = day && (day.getDay() === 0 || day.getDay() === 6 || holidays.has(pISO(day)));
        if (day && !isNonWorking) {
          if (segStart === null) segStart = d;
        } else if (segStart !== null) {
          const bar = cell(esc(c.nom), 'gantt-bar', `${segStart + 2} / ${d + 2}`, row, { background: c.couleur });
          bar.addEventListener('click', () => openChantierModal(c));
          segStart = null;
        }
      }
    });
  });

  grid.appendChild(frag);

  grid.dataset.todayCol = pDayDiff(rangeStart, today) + 2;
  scrollToToday(false);
}

function scrollToToday(smooth) {
  const wrap = document.getElementById('gantt-wrap');
  const grid = document.getElementById('gantt-grid');
  const col = Number(grid.dataset.todayCol || 0);
  if (!col) return;
  const left = LABEL_W + (col - 2) * DAY_W - wrap.clientWidth / 2 + DAY_W;
  wrap.scrollTo({ left: Math.max(0, left), behavior: smooth ? 'smooth' : 'auto' });
}

// ── Modal ajout / édition ────────────────────────────────────────────────────
function creneauRowHtml(cr) {
  return `
    <div class="ch-creneau-row flex items-center gap-2">
      <input type="date" class="ch-creneau-debut flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-accent" required value="${cr?.date_debut || ''}">
      <span class="text-gray-400 text-sm">→</span>
      <input type="date" class="ch-creneau-fin flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-accent" required value="${cr?.date_fin || ''}">
      <button type="button" class="ch-creneau-remove text-gray-400 hover:text-danger text-lg leading-none px-1" title="Retirer ce créneau">&times;</button>
    </div>
  `;
}

function openChantierModal(c) {
  const isEdit = !!c;
  const defaultColor = c?.couleur || COLOR_PALETTE[chantiers.length % COLOR_PALETTE.length];
  const initialCreneaux = c?.creneaux?.length ? c.creneaux : [null];
  const swatches = COLOR_PALETTE.map(hex => `
    <button type="button" class="ch-swatch" data-color="${hex}"
      style="width:28px;height:28px;border-radius:6px;background:${hex};border:2px solid transparent;cursor:pointer"></button>
  `).join('');
  openModal(`
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-lg font-semibold text-gray-900">${isEdit ? 'Modifier le chantier' : 'Nouveau chantier'}</h2>
      <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
    </div>
    <form id="chantier-form" class="flex flex-col gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Nom du chantier</label>
        <input id="ch-nom" type="text" required value="${esc(c?.nom || '')}"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-accent">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Ville</label>
        <input id="ch-ville" type="text" value="${esc(c?.ville || '')}"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-accent">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Entreprise</label>
        <div class="flex gap-2">
          ${ENTREPRISES.map(ent => `
            <label class="ch-entreprise-option flex-1 flex items-center justify-center px-3 py-3 border-2 rounded-lg cursor-pointer transition-colors"
                   data-entreprise="${ent}" style="border-color:#D1D5DB">
              <input type="radio" name="ch-entreprise" value="${ent}" class="hidden" ${(c?.entreprise || 'Moncomble') === ent ? 'checked' : ''}>
              <img src="${ENTREPRISE_LOGO[ent]}" alt="${ent}" style="height:28px;width:auto;object-fit:contain">
            </label>
          `).join('')}
        </div>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Créneaux de dates</label>
        <div id="ch-creneaux" class="flex flex-col gap-2">
          ${initialCreneaux.map(creneauRowHtml).join('')}
        </div>
        <button type="button" id="ch-add-creneau" class="mt-2 text-xs font-semibold hover:underline" style="color:var(--c-accent-dark)">
          + Ajouter un créneau
        </button>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Couleur</label>
        <div class="flex items-center gap-2 flex-wrap">
          ${swatches}
          <label class="relative" style="width:28px;height:28px" title="Couleur personnalisée">
            <input id="ch-couleur" type="color" value="${defaultColor}"
              class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
            <span id="ch-couleur-swatch" class="block w-full h-full rounded-md border border-gray-300"
              style="background:conic-gradient(red,yellow,lime,aqua,blue,magenta,red)"></span>
          </label>
        </div>
      </div>
      <div id="ch-error" class="hidden text-xs px-3 py-2 rounded-lg badge-error"></div>
      <div class="flex items-center justify-between gap-2 pt-2">
        ${isEdit ? `<button type="button" id="ch-delete" class="text-sm font-semibold px-4 py-2 rounded-lg btn-danger">Supprimer</button>` : '<span></span>'}
        <div class="flex gap-2">
          <button type="button" onclick="closeModal()" class="text-sm font-medium px-4 py-2 rounded-lg border border-gray-300">Annuler</button>
          <button type="submit" class="text-sm font-semibold px-4 py-2 rounded-lg btn-primary">Enregistrer</button>
        </div>
      </div>
    </form>
  `, { wide: true });

  const entrepriseOptions = document.querySelectorAll('.ch-entreprise-option');
  function highlightEntreprise() {
    entrepriseOptions.forEach(opt => {
      const checked = opt.querySelector('input').checked;
      opt.style.borderColor = checked ? 'var(--c-accent-dark)' : '#D1D5DB';
      opt.style.background = checked ? 'var(--c-accent-light, #EEF2DE)' : '';
      opt.style.fontWeight = checked ? '600' : '400';
    });
  }
  entrepriseOptions.forEach(opt => {
    opt.addEventListener('click', () => {
      opt.querySelector('input').checked = true;
      highlightEntreprise();
    });
  });
  highlightEntreprise();

  const creneauxWrap = document.getElementById('ch-creneaux');

  function wireRemoveButtons() {
    creneauxWrap.querySelectorAll('.ch-creneau-remove').forEach(btn => {
      btn.onclick = () => {
        if (creneauxWrap.children.length > 1) btn.closest('.ch-creneau-row').remove();
      };
    });
  }
  wireRemoveButtons();

  document.getElementById('ch-add-creneau').addEventListener('click', () => {
    creneauxWrap.insertAdjacentHTML('beforeend', creneauRowHtml(null));
    wireRemoveButtons();
  });

  const colorInput = document.getElementById('ch-couleur');
  function highlightSwatch(hex) {
    document.querySelectorAll('.ch-swatch').forEach(s => {
      s.style.borderColor = s.dataset.color.toLowerCase() === hex.toLowerCase() ? '#111827' : 'transparent';
    });
  }
  document.querySelectorAll('.ch-swatch').forEach(s => {
    s.addEventListener('click', () => {
      colorInput.value = s.dataset.color;
      highlightSwatch(s.dataset.color);
    });
  });
  colorInput.addEventListener('input', () => highlightSwatch(colorInput.value));
  highlightSwatch(defaultColor);

  document.getElementById('chantier-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const errorEl = document.getElementById('ch-error');
    errorEl.classList.add('hidden');

    const creneaux = [...creneauxWrap.querySelectorAll('.ch-creneau-row')].map(row => ({
      date_debut: row.querySelector('.ch-creneau-debut').value,
      date_fin: row.querySelector('.ch-creneau-fin').value,
    }));

    const body = {
      nom: document.getElementById('ch-nom').value.trim(),
      ville: document.getElementById('ch-ville').value.trim(),
      entreprise: document.querySelector('input[name="ch-entreprise"]:checked')?.value || 'Moncomble',
      couleur: colorInput.value,
      creneaux,
    };
    try {
      if (isEdit) {
        await API.req('PUT', `/chantiers/${c.id}`, body);
      } else {
        await API.post('/chantiers', body);
      }
      closeModal();
      UI.toast(isEdit ? 'Chantier mis à jour' : 'Chantier créé', 'success');
      await loadChantiers();
    } catch (err) {
      errorEl.textContent = err.message || 'Erreur, veuillez réessayer.';
      errorEl.classList.remove('hidden');
    }
  });

  if (isEdit) {
    document.getElementById('ch-delete').addEventListener('click', async () => {
      if (!confirm(`Supprimer le chantier "${c.nom}" ?`)) return;
      try {
        await API.req('DELETE', `/chantiers/${c.id}`);
        closeModal();
        UI.toast('Chantier supprimé', 'success');
        await loadChantiers();
      } catch (err) {
        UI.toast(err.message || 'Erreur lors de la suppression', 'error');
      }
    });
  }
}

// ── Impression (calendrier mensuel classique, une page par mois) ───────────────
const DAY_NAMES_LONG = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];

function openPrintModal() {
  const today = new Date();
  const defaultMonth = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}`;

  openModal(`
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-lg font-semibold text-gray-900">Imprimer le planning</h2>
      <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
    </div>
    <form id="print-form" class="flex flex-col gap-4">
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Du mois</label>
          <input id="print-mois-debut" type="month" required value="${defaultMonth}"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-accent">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Au mois</label>
          <input id="print-mois-fin" type="month" required value="${defaultMonth}"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-accent">
        </div>
      </div>
      <p class="text-xs text-gray-500 -mt-2">
        Un calendrier mensuel classique, une page A4 par mois, avec les chantiers listés dans chaque case.
      </p>
      <div class="flex justify-end gap-2 pt-2">
        <button type="button" onclick="closeModal()" class="text-sm font-medium px-4 py-2 rounded-lg border border-gray-300">Annuler</button>
        <button type="submit" class="text-sm font-semibold px-4 py-2 rounded-lg btn-primary">Imprimer</button>
      </div>
    </form>
  `);

  document.getElementById('print-form').addEventListener('submit', (e) => {
    e.preventDefault();
    const [y1, m1] = document.getElementById('print-mois-debut').value.split('-').map(Number);
    const [y2, m2] = document.getElementById('print-mois-fin').value.split('-').map(Number);
    const start = new Date(y1, m1 - 1, 1);
    const end = new Date(y2, m2 - 1, 1);
    if (end < start) { UI.toast('Le mois de fin doit être postérieur au mois de début', 'error'); return; }

    const months = [];
    for (let d = new Date(start); d <= end; d.setMonth(d.getMonth() + 1)) months.push(new Date(d));
    if (months.length > 12) { UI.toast('Limite de 12 mois par impression', 'error'); return; }

    closeModal();
    renderPrintCalendars(months);
    setTimeout(() => window.print(), 100);
  });
}

// Retourne la grille (semaines de 7 jours, lundi en premier) d'un mois, avec padding null.
function getMonthGrid(year, monthIndex) {
  const first = new Date(year, monthIndex, 1);
  const last = new Date(year, monthIndex + 1, 0);
  const leadingBlanks = (first.getDay() + 6) % 7; // getDay(): 0=dimanche -> décale pour lundi=0
  const weeks = [];
  let week = new Array(leadingBlanks).fill(null);
  for (let day = 1; day <= last.getDate(); day++) {
    week.push(new Date(year, monthIndex, day));
    if (week.length === 7) { weeks.push(week); week = []; }
  }
  if (week.length) {
    while (week.length < 7) week.push(null);
    weeks.push(week);
  }
  return weeks;
}

function renderPrintCalendars(months) {
  const years = [...new Set(months.map(m => m.getFullYear()))];
  const holidays = new Map();
  years.forEach(y => frenchHolidays(y).forEach(h => holidays.set(pISO(h.date), h.label)));

  const pages = months.map((monthDate, idx) => {
    const year = monthDate.getFullYear(), monthIndex = monthDate.getMonth();
    const weeks = getMonthGrid(year, monthIndex);

    const headerRow = DAY_NAMES_LONG.map((name, i) =>
      `<th class="${i >= 5 ? 'print-weekend' : ''}">${name}</th>`
    ).join('');

    const bodyRows = weeks.map(week => {
      const cells = week.map(d => {
        if (!d) return `<td class="print-cal-empty"></td>`;
        const iso = pISO(d);
        const isWeekend = d.getDay() === 0 || d.getDay() === 6;
        const holidayLabel = holidays.get(iso);
        const isNonWorking = isWeekend || !!holidayLabel;
        const dayChantiers = isNonWorking ? [] : chantiers.filter(c => c.creneaux.some(cr => iso >= cr.date_debut && iso <= cr.date_fin));
        const chips = dayChantiers.map(c => `
          <div class="print-cal-chip" style="border-left-color:${c.couleur}">
            ${esc(c.nom)}${c.ville ? ' · ' + esc(c.ville) : ''}
          </div>
        `).join('');
        const cls = (isWeekend ? 'print-weekend ' : '') + (holidayLabel ? 'print-holiday' : '');
        return `
          <td class="print-cal-day ${cls}">
            <div class="print-cal-daynum">${d.getDate()}</div>
            ${holidayLabel ? `<div class="print-cal-holiday-label">${esc(holidayLabel)}</div>` : ''}
            ${chips}
          </td>`;
      }).join('');
      return `<tr>${cells}</tr>`;
    }).join('');

    return `
      <div class="print-cal-page${idx < months.length - 1 ? ' print-page-break' : ''}">
        <div class="print-header">
          <div class="print-title">${MONTH_NAMES[monthIndex]} ${year}</div>
          <div class="print-header-right">
            <img src="/assets/img/logoMoncomble.webp" alt="Moncomble" class="print-header-logo">
            <div class="print-subtitle">imprimé le ${new Date().toLocaleDateString('fr-FR')}</div>
          </div>
        </div>
        <table class="print-cal-table">
          <thead><tr>${headerRow}</tr></thead>
          <tbody>${bodyRows}</tbody>
        </table>
        <div class="print-footer">
          <span>Développé par Dubois Digital · www.dubois-digital.com</span>
          <img src="/assets/img/logoDuboisDigital.png" alt="Dubois Digital" class="print-footer-logo">
        </div>
      </div>`;
  });

  document.getElementById('print-view').innerHTML = pages.join('');
}

document.getElementById('gantt-add-btn').addEventListener('click', () => openChantierModal(null));
document.getElementById('gantt-today-btn').addEventListener('click', () => scrollToToday(true));
document.getElementById('gantt-print-btn').addEventListener('click', () => openPrintModal());

loadChantiers();
