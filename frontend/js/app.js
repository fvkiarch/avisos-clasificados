import { api } from './api.js';
import { session } from './session.js';
import { debounce } from './format.js';
import * as ui from './ui.js';

const PER_PAGE = 9;
const THEME_KEY = 'classifieds.theme';
const state = { category: '', query: '', mine: false, page: 1, ticket: 0 };
const $ = (selector) => document.querySelector(selector);


async function loadListings({ replace }) {
  const ticket = ++state.ticket;

  if (replace) {
    state.page = 1;
    ui.clearList();
  }

  ui.setMoreButton(false);
  ui.showSkeletons(replace ? 6 : 3);

  try {
    const fetchPage = state.mine ? api.myListings : api.listings;
    const { data, meta } = await fetchPage({
      category: state.category,
      q: state.query,
      page: state.page,
      per_page: PER_PAGE,
    });

    if (ticket !== state.ticket) {
      return;
    }

    ui.removeSkeletons();
    ui.renderListings(data);
    ui.renderSummary({
      category: state.category,
      query: state.query,
      hasMore: meta.has_more,
      mine: state.mine,
    });
    ui.setMoreButton(meta.has_more);
  } catch (error) {
    if (ticket !== state.ticket) {
      return;
    }

    ui.removeSkeletons();
    ui.toast(error.message, 'error');
  }
}

async function loadStats() {
  try {
    ui.renderStats((await api.stats()).data);
  } catch {
  }
}

async function loadCatalog() {
  try {
    ui.fillCatalog((await api.catalog()).data);
  } catch {
  }
}

const reloadAll = () => Promise.all([loadStats(), loadListings({ replace: true })]);

async function restoreSession() {
  ui.renderSession(session.user);

  if (!session.token) {
    return;
  }

  try {
    const { data } = await api.me();

    session.updateUser(data);
    ui.renderSession(data);
  } catch (error) {
    if (error.status === 401) {
      session.clear();
      ui.renderSession(null);
    }
  }
}

window.addEventListener('session:expired', () => {
  session.clear();
  ui.renderSession(null);
  ui.toast('Tu sesión expiró. Ingresá de nuevo.', 'error');
});

function toggleTheme() {
  const next = ui.isDark() ? 'light' : 'dark';

  ui.applyTheme(next);

  try {
    localStorage.setItem(THEME_KEY, next);
  } catch {

  }
}

function resetMine() {
  state.mine = false;
  ui.markMine(false);
}

function toggleMine() {
  state.mine = !state.mine;
  ui.markMine(state.mine);
  loadListings({ replace: true });
}

function selectFilter(filter) {
  state.category = filter.dataset.category;
  ui.markFilter(state.category);
  loadListings({ replace: true });
}

async function openPublishDialog() {
  if ($('#sel-category').options.length < 2) {
    await loadCatalog();
  }

  ui.openDialog('dlg-publish');
}

function handleCta() {
  if (!session.user) {
    ui.openDialog('dlg-login');
  } else if (session.can('listing.create')) {
    openPublishDialog();
  } else {
    $('#list').scrollIntoView?.({ behavior: 'smooth' });
  }
}

function logout() {
  session.clear();
  ui.renderSession(null);
  ui.toast('Sesión cerrada.');
  reloadAll();
}

function fillDemoAccount(button) {
  const form = $('#form-login');

  form.elements.email.value = button.dataset.demo;
  form.elements.password.value = 'Clave123!';
}

async function handleLogin(event) {
  event.preventDefault();

  const form = event.currentTarget;
  const { email = '', password = '' } = ui.readForm(form);

  ui.clearErrors(form);
  ui.setFormLoading(form, true, 'Ingresando…');

  try {
    const result = await api.login(email, password);

    session.save(result.token, result.user);
    ui.renderSession(result.user);
    ui.closeDialog('dlg-login');
    form.reset();
    ui.toast(`¡Hola, ${result.user.name}!`);
    await reloadAll();
  } catch (error) {
    const hasFields = Object.keys(error.fields ?? {}).length > 0;

    ui.showErrors(form, error.fields, hasFields ? '' : error.message);
  } finally {
    ui.setFormLoading(form, false);
  }
}

async function handlePublish(event) {
  event.preventDefault();

  const form = event.currentTarget;

  ui.clearErrors(form);
  ui.setFormLoading(form, true, 'Publicando…');

  try {
    await api.publish(ui.readForm(form));
    ui.closeDialog('dlg-publish');
    form.reset();
    ui.showCategoryFields(form, '');
    ui.toast('Aviso publicado correctamente.');
    await reloadAll();
  } catch (error) {
    const hasFields = Object.keys(error.fields ?? {}).length > 0;

    ui.showErrors(form, error.fields, hasFields ? '' : error.message);
  } finally {
    ui.setFormLoading(form, false);
  }
}

async function handleDelete(event) {
  const button = event.target.closest('[data-action="delete"]');

  if (!button) {
    return;
  }

  const confirmed = await ui.confirmAction('¿Eliminar este aviso? Esta acción no se puede deshacer.');

  if (!confirmed) {
    return;
  }

  try {
    await api.remove(button.dataset.id);
    ui.toast('Aviso eliminado.');
    await reloadAll();
  } catch (error) {
    ui.toast(error.message, 'error');
  }
}

async function openAdminModal() {
  try {
    const { data } = await api.users();
    ui.renderUsersTable(data, session.user.id);
    ui.openDialog('dlg-admin');
  } catch (error) {
    ui.toast(error.message, 'error');
  }
}

async function handleCreateUser(event) {
  event.preventDefault();
  const form = event.currentTarget;
  const formData = ui.readForm(form);

  try {
    await api.createUser(formData);
    form.reset();
    ui.toast('Usuario creado correctamente.');
    const { data } = await api.users();
    ui.renderUsersTable(data, session.user.id);
  } catch (error) {
    ui.toast(error.message, 'error');
  }
}

async function handleDeleteUser(event) {
  const button = event.target.closest('[data-action="delete-user"]');
  if (!button) return;

  const confirmed = await ui.confirmAction('¿Eliminar este usuario?');
  if (!confirmed) return;

  try {
    await api.removeUser(button.dataset.id);
    ui.toast('Usuario eliminado.');
    const { data } = await api.users();
    ui.renderUsersTable(data, session.user.id);
  } catch (error) {
    ui.toast(error.message, 'error');
  }
}



function bindEvents() {
  $('#theme-btn').addEventListener('click', toggleTheme);


  const search = debounce((value) => {
    state.query = value.trim();
    loadListings({ replace: true });
  }, 300);

  $('#q').addEventListener('input', (event) => search(event.target.value));
  $('#search-form').addEventListener('submit', (event) => event.preventDefault());

  document.querySelectorAll('.filter').forEach((filter) => {
    filter.addEventListener('click', () => selectFilter(filter));
  });

  $('#more-btn').addEventListener('click', () => {
    state.page += 1;
    loadListings({ replace: false });
  });
  
  $('#mine-btn').addEventListener('click', toggleMine);

  document.addEventListener('click', (event) => {
    event.target.closest('[data-close]')?.closest('dialog').close();
  });

  document.querySelectorAll('dialog').forEach((dialog) => {
    dialog.addEventListener('click', (event) => {
      if (event.target === dialog) {
        dialog.close();
      }
    });
  });

  $('#login-btn').addEventListener('click', () => ui.openDialog('dlg-login'));
  $('#publish-btn').addEventListener('click', openPublishDialog);
  $('#cta-btn').addEventListener('click', handleCta);
  $('#logout-btn').addEventListener('click', logout);

  // Forms
  document.querySelectorAll('[data-demo]').forEach((button) => {
    button.addEventListener('click', () => fillDemoAccount(button));
  });

  $('#form-login').addEventListener('submit', handleLogin);
  $('#form-publish').addEventListener('submit', handlePublish);
  $('#sel-category').addEventListener('change', (event) => {
    ui.showCategoryFields($('#form-publish'), event.target.value);
  });

  $('#list').addEventListener('click', handleDelete);
  
  $('#admin-settings')?.addEventListener('click', openAdminModal);
  $('#form-create-user')?.addEventListener('submit', handleCreateUser);
  $('#users-tbody')?.addEventListener('click', handleDeleteUser);
}


$('#btn-submit-user')?.addEventListener('click', async () => {
  const name = $('#admin-user-name').value.trim();
  const email = $('#admin-user-email').value.trim();
  const password = $('#admin-user-password').value;
  const role = $('#admin-user-role').value;

  try {
    await api.createUser({ name, email, password, role });
    
    
    $('#form-create-user')?.reset();
    
    ui.toast('Usuario creado correctamente.');
    const { data } = await api.users();
    ui.renderUsersTable(data, session.user.id);
  } catch (error) {
    ui.toast(error.message, 'error');
  }
});


document.querySelectorAll('#dlg-admin [data-close], #dlg-admin').forEach((element) => {
  element.addEventListener('click', (e) => {
    
    if (e.target === element || e.target.closest('[data-close]')) {
      $('#form-create-user')?.reset(); // Limpia los inputs al cerrar
    }
  });
});


async function start() {
  try {
    ui.applyTheme(localStorage.getItem(THEME_KEY) ?? 'auto');
  } catch {
  }

  bindEvents();
  await restoreSession();
  loadCatalog();
  await reloadAll();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', start);
} else {
  start();
}
