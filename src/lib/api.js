const cfg = () => window.MARCHA || { restUrl: '/wp-json/', siteUrl: '/', nonce: '' };

let cartToken = null;
try { cartToken = sessionStorage.getItem('marcha_cart_token'); } catch (e) { /* storage unavailable */ }

async function request(path, { method = 'GET', body, headers = {}, withMeta = false } = {}) {
  const { restUrl, nonce } = cfg();
  const res = await fetch(restUrl.replace(/\/$/, '') + '/' + path.replace(/^\//, ''), {
    method,
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce, ...(cartToken ? { 'Cart-Token': cartToken } : {}), ...headers },
    body: body ? JSON.stringify(body) : undefined,
  });
  const t = res.headers.get('Cart-Token');
  if (t) { cartToken = t; try { sessionStorage.setItem('marcha_cart_token', t); } catch (e) { /* ignore */ } }
  const nn = res.headers.get('Nonce');
  if (nn && window.MARCHA) window.MARCHA.nonce = nn;
  if (!res.ok) throw new Error((await res.json().catch(() => ({}))).message || res.statusText);
  const data = await res.json();
  return withMeta ? { data, total: Number(res.headers.get('X-WP-Total') || 0), pages: Number(res.headers.get('X-WP-TotalPages') || 1) } : data;
}

export const getProducts = (params = {}) =>
  request('wc/store/v1/products?' + new URLSearchParams({ per_page: 24, ...params }));
export const queryProducts = ({ colors = [], ...params } = {}) => {
  const q = new URLSearchParams({ per_page: 24, ...params });
  colors.forEach((id, i) => {
    q.set(`attributes[${i}][attribute]`, 'pa_color');
    q.set(`attributes[${i}][term_id]`, id);
  });
  if (colors.length) q.set('attribute_relation', 'IN');
  return request('wc/store/v1/products?' + q.toString(), { withMeta: true });
};
export const getColorTerms = () => request('wc/store/v1/products/attributes/2/terms?per_page=100');
export const getProduct = (slug) => request('wc/store/v1/products?slug=' + encodeURIComponent(slug)).then((r) => r[0]);
export const getCategories = () => request('wc/store/v1/products/categories?per_page=50');
export const getCart = () => request('wc/store/v1/cart');
export const addToCart = (id, quantity = 1, variation = []) =>
  request('wc/store/v1/cart/add-item', { method: 'POST', body: { id, quantity, variation } });
export const updateCartItem = (key, quantity) =>
  request('wc/store/v1/cart/update-item', { method: 'POST', body: { key, quantity } });
export const removeCartItem = (key) =>
  request('wc/store/v1/cart/remove-item', { method: 'POST', body: { key } });
