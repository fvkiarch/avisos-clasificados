const KEY = 'classifieds.session';

function read() {
  try {
    return JSON.parse(localStorage.getItem(KEY) ?? 'null');
  } catch {
    return null;
  }
}

export const session = {
  get token() {
    return read()?.token ?? null;
  },

  get user() {
    return read()?.user ?? null;
  },

  save(token, user) {
    try {
      localStorage.setItem(KEY, JSON.stringify({ token, user }));
    } catch {
    }
  },

  updateUser(user) {
    const current = read();

    if (current) {
      this.save(current.token, user);
    }
  },

  clear() {
    try {
      localStorage.removeItem(KEY);
    } catch {
    }
  },

  can(permission) {
    return this.user?.permissions?.includes(permission) ?? false;
  },
};
