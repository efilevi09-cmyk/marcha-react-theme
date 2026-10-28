const cfg = () => window.MARCHA || { restUrl: '/wp-json/', siteUrl: '/', nonce: '' };

/** In-memory product cache: list responses already contain the full product, so opening one is instant. */
const productCache = new Map();
const key = (slug) => { try { return decodeURIComponent(slug); } catch (e) { return slug; } };
export const cacheProducts = (list) => { (Array.isArray(list) ? list : []).forEach((p) => p?.slug && productCache.set(key(p.slug), p)); return list; };
export const cachedProduct = (slug) => productCache.get(key(slug)) || null;

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
  if (path.startsWith('wc/store/v1/products') && !path.includes('/categories') && !path.includes('/attributes')) cacheProducts(data);
  return withMeta ? { data, total: Number(res.headers.get('X-WP-Total') || 0), pages: Number(res.headers.get('X-WP-TotalPages') || 1) } : data;
}

/** Resolve by slug through wp/v2 (handles Hebrew slugs reliably), then load the full product from the Store API. */
export const getProduct = async (slug) => {
  const hit = cachedProduct(slug);
  if (hit) return hit;
  const [found] = await request('wp/v2/product?' + new URLSearchParams({ slug: key(slug), _fields: 'id' }).toString());
  if (!found) return null;
  const [prod] = await request(`wc/store/v1/products?include=${found.id}`);
  return prod || null;
};
export const getCategories = () => request('wc/store/v1/products/categories?per_page=50');
export const getCart = () => request('wc/store/v1/cart');
export const addToCart = (id, quantity = 1, variation = []) =>
  request('wc/store/v1/cart/add-item', { method: 'POST', body: { id, quantity, variation } });
export const updateCartItem = (key, quantity) =>
  request('wc/store/v1/cart/update-item', { method: 'POST', body: { key, quantity } });
export const removeCartItem = (key) =>
  request('wc/store/v1/cart/remove-item', { method: 'POST', body: { key } });

/** Reviews live in WordPress (native review comments) behind a small theme endpoint. */
export const getReviews = ({ product = 0, page = 1, per_page = 6 } = {}) =>
  request(`marcha/v1/reviews?${new URLSearchParams({ product, page, per_page })}`);
export const submitReview = (data) => request('marcha/v1/reviews', { method: 'POST', body: data });
export const getInstagram = () => request('marcha/v1/instagram');
