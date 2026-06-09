/**
 * OGMS – Centralized API helper
 * Thin wrapper around fetch() so every page uses the same base path and
 * error handling without repeating boilerplate.
 */
const API = {
  BASE: '/OGMS-Lubo-National-High-School/api/',

  /**
   * GET  /api/<endpoint>?key=value…
   * @param {string} endpoint  e.g. 'grades.php'
   * @param {object} params    query-string key/value pairs
   * @returns {Promise<object>} parsed JSON
   */
  async get(endpoint, params = {}) {
    const url = new URL(this.BASE + endpoint, window.location.origin);
    Object.entries(params).forEach(([k, v]) => {
      if (v !== undefined && v !== null) url.searchParams.set(k, v);
    });
    const res = await fetch(url.toString());
    if (!res.ok) throw new Error(`API ${res.status} – ${endpoint}`);
    return res.json();
  },

  /**
   * POST /api/<endpoint>
   * @param {string} endpoint  e.g. 'grades.php'
   * @param {object} data      key/value pairs → FormData
   * @returns {Promise<object>} parsed JSON
   */
  async post(endpoint, data = {}) {
    const body = new FormData();
    Object.entries(data).forEach(([k, v]) => {
      if (v !== undefined && v !== null) body.append(k, v);
    });
    const res = await fetch(this.BASE + endpoint, { method: 'POST', body });
    if (!res.ok) throw new Error(`API ${res.status} – ${endpoint}`);
    return res.json();
  },
};
