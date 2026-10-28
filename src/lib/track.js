/**
 * Ecommerce events for GTM / GA4 / Meta. The SPA never triggers WooCommerce's jQuery events, so the
 * plugins that listen for them (PixelYourSite etc.) would miss these actions; we push standard GA4
 * dataLayer events instead and let GTM fan them out. Native pages (checkout, thank-you) stay with the plugins.
 */
const push = (event, ecommerce) => {
  try {
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({ ecommerce: null });
    window.dataLayer.push({ event, ecommerce });
  } catch (e) { /* tracking must never break the shop */ }
};

const num = (minor, unit = 2) => Number((Number(minor || 0) / 10 ** unit).toFixed(unit));
const clean = (s) => String(s || '').replace(/<[^>]+>/g, '');

export function itemFromCartLine(line, qty) {
  const unit = line.prices?.currency_minor_unit ?? 2;
  return {
    item_id: String(line.id),
    item_name: clean(line.name),
    item_variant: (line.variation || []).map((v) => v.value).join(' / ') || undefined,
    price: num(line.prices?.price, unit),
    quantity: qty ?? line.quantity,
  };
}

export const trackAddToCart = (line, qty) => {
  if (!line) return;
  const item = itemFromCartLine(line, qty);
  push('add_to_cart', { currency: line.prices?.currency_code || 'ILS', value: Number((item.price * item.quantity).toFixed(2)), items: [item] });
};

export const trackRemoveFromCart = (line) => {
  if (!line) return;
  const item = itemFromCartLine(line);
  push('remove_from_cart', { currency: line.prices?.currency_code || 'ILS', value: Number((item.price * item.quantity).toFixed(2)), items: [item] });
};

export const trackViewItem = (p) => {
  if (!p) return;
  const unit = p.prices?.currency_minor_unit ?? 2;
  push('view_item', {
    currency: p.prices?.currency_code || 'ILS',
    value: num(p.prices?.price, unit),
    items: [{ item_id: String(p.id), item_name: clean(p.name), item_category: p.categories?.[0]?.name, price: num(p.prices?.price, unit), quantity: 1 }],
  });
};

export const trackViewCart = (cart) => {
  if (!cart?.items?.length) return;
  push('view_cart', { currency: cart.totals?.currency_code || 'ILS', value: num(cart.totals?.total_price, cart.totals?.currency_minor_unit ?? 2), items: cart.items.map((l) => itemFromCartLine(l)) });
};
