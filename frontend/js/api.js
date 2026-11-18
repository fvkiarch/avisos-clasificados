import { API_URL } from './config.js';
import { session } from './session.js';

export class ApiError extends Error {
  constructor(status, message, fields = {}) {
    super(message);
    this.status = status;
    this.fields = fields;
  }
}

async function request(method, path, { body, params } = {}) {
  const url = new URL(API_URL + path);

  for (const [key, value] of Object.entries(params ?? {})) {
    if (value !== '' && value != null) {
      url.searchParams.set(key, value);
    }
  }

  const headers = { Accept: 'application/json' };

  if (body !== undefined) {
    headers['Content-Type'] = 'application/json';
  }

  if (session.token) {
    headers.Authorization = `Bearer ${session.token}`;
  }

  let response;

  try {
    response = await fetch(url, {
      method,
      headers,
      body: body === undefined ? undefined : JSON.stringify(body),
    });
  } catch {
    throw new ApiError(0, 'No se pudo conectar con el servidor.');
  }

  if (response.status === 204) {
    return null;
  }

  const payload = await response.json().catch(() => null);

  if (!response.ok) {
    const error = payload?.error ?? {};

    if (response.status === 401 && session.token) {
      window.dispatchEvent(new CustomEvent('session:expired'));
    }

    throw new ApiError(response.status, error.message ?? `Error ${response.status}`, error.fields ?? {});
  }

  return payload;
}

export const api = {
  login: (email, password) => request('POST', '/auth/login', { body: { email, password } }),
  me: () => request('GET', '/auth/me'),
  catalog: () => request('GET', '/catalog'),
  stats: () => request('GET', '/stats'),
  listings: (params) => request('GET', '/listings', { params }),
  myListings: (params) => request('GET', '/listings/mine', { params }),
  publish: (data) => request('POST', '/listings', { body: data }),
  remove: (id) => request('DELETE', `/listings/${encodeURIComponent(id)}`),
  users: () => request('GET', '/users'),
  createUser: (data) => request('POST', '/users', { body: data }),
  removeUser: (id) => request('DELETE', `/users/${encodeURIComponent(id)}`),
};
