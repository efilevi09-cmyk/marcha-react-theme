const cfg = () => window.MARCHA || { restUrl: '/wp-json/', siteUrl: '/', nonce: '' };

let cartToken = null;
let storeNonce = null;
try { cartToken = sessionStorage.getItem('marcha_cart_token'); } catch (e) { /* storage unavailable */ }

async function request(path, { method = 'GET', body, headers = {}, withMeta = false } = {}) {
  const { restUrl } = cfg();
  const res = await fetch(restUrl.replace(/\/$/, '') + '/' + path.replace(/^\//, ''), {
    method,
    credentials: 'same-origin',
    // Store API uses its own rotating `Nonce` header; wp/v2 reads are public, so no nonce is sent
    // (a stale/foreign X-WP-Nonce makes WordPress reject the request with "cookie check failed").
    headers: { 'Content-Type': 'application/json', ...(path.startsWith('wc/store') && storeNonce ? { Nonce: storeNonce } : {}), ...(cartToken ? { 'Cart-Token': cartToken } : {}), ...headers },
    body: body ? JSON.stringify(body) : undefined,
  });
  const t = res.headers.get('Cart-Token');
  if (t) { cartToken = t; try { sessionStorage.setItem('marcha_cart_token', t); } catch (e) { /* ignore */ } }
  const nn = res.headers.get('Nonce');
  if (nn) storeNonce = nn;
  if (!res.ok) throw new Error((await res.json().catch(() => ({}))).message || res.statusText);
  const data = await res.json();
  return withMeta ? { data, total: Number(res.headers.get('X-WP-Total') || 0), pages: Number(res.headers.get('X-WP-TotalPages') || 1) } : data;
}

export const getProducts = (params = {}) =>
  request('wc/store/v1/products?' + new URLSearchParams({ per_page: 24, ...params }));
/** Colour filter uses the shop's own `zeva` taxonomy (same one the old JetSmartFilters used). */
export const getColorTerms = () => request('wp/v2/zeva?per_page=100&hide_empty=true&_fields=id,name,count');
/** Colour term ids that actually occur in a category (so empty swatches are hidden). */
export const getCategoryColorIds = (category) =>
  request(`wp/v2/product?per_page=100&_fields=zeva&product_cat=${category}`).then((r) => new Set(r.flatMap((x) => x.zeva || [])));
const productIdsByColor = (colors, category) =>
  request(`wp/v2/product?per_page=100&_fields=id&zeva=${colors.join(',')}${category ? `&product_cat=${category}` : ''}`).then((r) => r.map((x) => x.id));

export const queryProducts = async ({ colors = [], ...params } = {}) => {
  if (colors.length) {
    const ids = await productIdsByColor(colors, params.category);
    if (!ids.length) return { data: [], total: 0, pages: 1 };
    params = { ...params, include: ids.join(',') };
    delete params.category;
  }
  return request('wc/store/v1/products?' + new URLSearchParams({ per_page: 24, ...params }).toString(), { withMeta: true });
};
export const getProduct = (slug) => request('wc/store/v1/products?slug=' + encodeURIComponent(slug)).then((r) => r[0]);
export const getCategories = () => request('wc/store/v1/products/categories?per_page=50');
export const getCart = () => request('wc/store/v1/cart');
export const addToCart = (id, quantity = 1, variation = []) =>
  request('wc/store/v1/cart/add-item', { method: 'POST', body: { id, quantity, variation } });
export const updateCartItem = (key, quantity) =>
  request('wc/store/v1/cart/update-item', { method: 'POST', body: { key, quantity } });
export const removeCartItem = (key) =>
  request('wc/store/v1/cart/remove-item', { method: 'POST', body: { key } });
