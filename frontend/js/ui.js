import { formatAmount, relativeTime, initials } from './format.js';

const $ = (selector, root = document) => root.querySelector(selector);
const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];
const reducedMotion = () => globalThis.matchMedia?.('(prefers-reduced-motion: reduce)').matches ?? true;

export function fill(root, data) {
  for (const element of $$('[data-field]', root)) {
    const value = data[element.dataset.field];

    if (value !== undefined) {
      element.textContent = value ?? '';
    }
  }

  for (const element of $$('[data-if]', root)) {
    if (element.dataset.if in data) {
      element.hidden = !data[element.dataset.if];
    }
  }
}

export function applyTheme(preference) {
  document.documentElement.dataset.theme = preference;
}

export function isDark() {
  const theme = document.documentElement.dataset.theme;
  const prefersDark = globalThis.matchMedia?.('(prefers-color-scheme: dark)').matches ?? false;

  return theme === 'dark' || (theme === 'auto' && prefersDark);
}

export function renderSession(user) {
  for (const element of $$('[data-only]')) {
    element.hidden = (element.dataset.only === 'auth') !== Boolean(user);
  }

  for (const element of $$('[data-permission]')) {
    element.hidden = !user?.permissions?.includes(element.dataset.permission);
  }

  if (user) {
    fill($('.user'), { name: user.name, role: user.role, initials: initials(user.name) });
  }

  const canCreate = user?.permissions?.includes('listing.create');
  let ctaText = 'Ingresar';

  if (user) {
    ctaText = canCreate ? 'Publicar aviso' : 'Ver avisos';
  }

  $('[data-field="cta-text"]').textContent = ctaText;
}

export function renderStats(stats) {
  const values = {
    total: stats.total,
    highlighted: stats.highlighted,
    'count-all': stats.total,
  };

  for (const [category, count] of Object.entries(stats.by_category)) {
    values[`count-${category}`] = count;
  }

  for (const [field, value] of Object.entries(values)) {
    const element = $(`[data-field="${field}"]`);

    if (element) {
      countUp(element, value);
    }
  }
}

function countUp(element, target) {
  const from = Number(element.dataset.value ?? 0);
  element.dataset.value = String(target);

  if (reducedMotion() || from === target) {
    element.textContent = String(target);

    return;
  }

  const start = performance.now();

  const step = (now) => {
    const progress = Math.min(1, (now - start) / 600);
    const eased = 1 - (1 - progress) ** 3;
    element.textContent = String(Math.round(from + (target - from) * eased));

    if (progress < 1) {
      requestAnimationFrame(step);
    }
  };

  requestAnimationFrame(step);
}

export function clearList() {
  $('#list').replaceChildren();
}

export function showSkeletons(count) {
  const list = $('#list');
  list.setAttribute('aria-busy', 'true');

  for (let i = 0; i < count; i++) {
    list.append($('#tpl-skeleton').content.firstElementChild.cloneNode(true));
  }
}

export function removeSkeletons() {
  $$('#list .skeleton').forEach((node) => node.remove());
  $('#list').setAttribute('aria-busy', 'false');
}

export function renderListings(listings) {
  const list = $('#list');
  const template = $('#tpl-listing');

  listings.forEach((listing, index) => {
    const node = template.content.firstElementChild.cloneNode(true);

    node.dataset.category = listing.category;
    node.dataset.id = String(listing.id);
    node.classList.toggle('featured', listing.highlighted);
    node.style.setProperty('--i', String(index));

    fill(node, {
      category: listing.category,
      title: listing.title,
      detail: listing.detail ?? '',
      reference: listing.reference ?? '',
      amountText: formatAmount(listing.amount, listing.category),
      author: listing.author,
      initials: initials(listing.author),
      time: relativeTime(listing.created_at),
      highlighted: listing.highlighted,
      canDelete: listing.can_delete,
    });

    $('[data-action="delete"]', node).dataset.id = String(listing.id);
    list.append(node);
  });
}

export function renderSummary({ category, query, hasMore, mine }) {
  const count = $$('#list .listing:not(.skeleton)').length;
  const parts = [`${count}${hasMore ? '+' : ''} ${count === 1 ? 'aviso' : 'avisos'}`];

  if (mine) {
    parts.push('Mis avisos');
  }

  if (category) {
    parts.push(category);
  }

  if (query) {
    parts.push(`“${query}”`);
  }

  $('#summary').textContent = parts.join(' · ');
  $('#empty').hidden = count > 0;

  let emptyText = 'Todavía no hay avisos publicados.';

  if (query || category) {
    emptyText = 'Probá con otra búsqueda o categoría.';
  } else if (mine) {
    emptyText = 'Todavía no publicaste avisos.';
  }

  fill($('#empty'), { 'empty-text': emptyText });
}

export function setMoreButton(visible) {
  $('#more-btn').hidden = !visible;
}

export function markFilter(category) {
  for (const filter of $$('.filter')) {
    filter.setAttribute('aria-pressed', String(filter.dataset.category === category));
  }
}

export function markMine(active) {
  $('#mine-btn').setAttribute('aria-pressed', String(active));
}

export function fillCatalog({ categories, property_types: types }) {
  $('#sel-category').replaceChildren(
    new Option('Seleccione…', ''),
    ...categories.map((category) => new Option(category, category))
  );
  $('#sel-type').replaceChildren(...types.map((type) => new Option(type.name, String(type.id))));
}

export function openDialog(id) {
  const dialog = document.getElementById(id);

  if (!dialog.open) {
    dialog.showModal();
  }
}

export function closeDialog(id) {
  document.getElementById(id).close();
}

export function confirmAction(text) {
  const dialog = $('#dlg-confirm');
  fill(dialog, { 'confirm-text': text });

  return new Promise((resolve) => {
    dialog.returnValue = 'no';
    dialog.addEventListener('close', () => resolve(dialog.returnValue === 'yes'), { once: true });
    dialog.showModal();
  });
}

export function readForm(form) {
  const data = {};

  for (const [key, value] of new FormData(form)) {
    if (typeof value === 'string') {
      data[key] = value.trim();
    }
  }

  if ('highlighted' in data) {
    data.highlighted = true;
  }

  return data;
}

export function showCategoryFields(form, category) {
  for (const fieldset of $$('fieldset[data-category-fields]', form)) {
    const active = fieldset.dataset.categoryFields === category;

    fieldset.hidden = !active;
    fieldset.disabled = !active;
  }
}

export function clearErrors(form) {
  for (const element of $$('[data-error-for]', form)) {
    element.textContent = '';

    if (element.classList.contains('alert')) {
      element.hidden = true;
    }
  }

  for (const control of $$('[aria-invalid]', form)) {
    control.removeAttribute('aria-invalid');
  }
}

export function showErrors(form, fields = {}, general = '') {
  clearErrors(form);

  for (const [field, message] of Object.entries(fields)) {
    for (const element of $$('[data-error-for]', form)) {
      if (element.dataset.errorFor === field && !element.closest('fieldset[disabled]')) {
        element.textContent = message;
      }
    }

    for (const control of [...form.elements].filter((item) => item.name === field && !item.disabled)) {
      control.setAttribute('aria-invalid', 'true');
    }
  }

  if (general) {
    const alert = $('[data-error-for="_general"]', form);

    alert.textContent = general;
    alert.hidden = false;
  }
}

export function renderUsersTable(users, currentUserId) {
  const tbody = $('#users-tbody');
  tbody.innerHTML = '';

  users.forEach((user) => {
    const tr = document.createElement('tr');
    tr.style.borderBottom = '1px solid var(--border-color)';
    tr.innerHTML = `
      <td style="padding: 10px 14px;">${user.id}</td>
      <td style="padding: 10px 14px;">${user.name}</td>
      <td style="padding: 10px 14px;">${user.email}</td>
      <td style="padding: 10px 14px;"><span class="chip">${user.role}</span></td>
      <td style="padding: 10px 14px; text-align: right;">
        ${user.id !== currentUserId ? `<button class="btn btn-icon btn-danger" type="button" data-action="delete-user" data-id="${user.id}" aria-label="Eliminar usuario"><svg class="icon"><use href="#i-trash" /></svg></button>` : '<small style="opacity: 0.6;">Actual</small>'}
      </td>
    `;
    tbody.append(tr);
  });
}

export function setFormLoading(form, loading, text) {
  const button = $('button[type="submit"]', form);
  const label = $('[data-field="btn-text"]', button);

  if (loading) {
    button.dataset.original = label.textContent;
  }

  label.textContent = loading ? text : (button.dataset.original ?? label.textContent);
  button.disabled = loading;
  button.toggleAttribute('data-loading', loading);
}

export function toast(message, type = 'ok') {
  const element = document.createElement('div');

  element.className = `toast toast-${type}`;
  element.textContent = message;
  $('#toasts').append(element);

  setTimeout(() => {
    element.classList.add('leaving');
    setTimeout(() => element.remove(), 300);
  }, 4000);
}
