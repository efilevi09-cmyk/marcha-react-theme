export const money = (minor, cur = {}) => {
  const d = cur.currency_minor_unit ?? 2;
  const n = Number(minor) / 10 ** d;
  return `${cur.currency_prefix || '₪ '}${n.toLocaleString('he-IL', { maximumFractionDigits: d })}${cur.currency_suffix || ''}`;
};

/** Price in minor units for a whole-currency amount, respecting the store's decimals. */
export const toMinor = (amount, cur = {}) => amount * 10 ** (cur.currency_minor_unit ?? 2);
