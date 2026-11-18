const fromUrl = new URLSearchParams(globalThis.location?.search ?? '').get('api');

export const API_URL = fromUrl ?? 'http://127.0.0.1:8099/api/v1';
