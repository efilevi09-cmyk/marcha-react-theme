/** Business rules shown in the UI. Actual discount is applied by WooCommerce (Discount Rules plugin). */
export const BUNDLE = { qty: 3, total: 300, unit: 100, badge: '3 נעליים ב300 ₪', bar: '3 זוגות נעליים ב300 ₪' };

export const COLOR_HEX = {
  חום: '#934000', שחור: '#000000', אדום: '#ff0000', זהב: '#d5b52c', ירוק: '#00852e', אפור: '#737373',
  כחול: '#032191', כתום: '#ff8900', לבן: '#ffffff', צהוב: '#ffff00', כסף: '#c6c6c6', ורוד: '#ed00b8',
  סגול: '#a300ed', שמנת: '#fffdf3', תכלת: '#00d7ff',
};

export const SORTS = [
  ['menu_order', 'סידור ברירת מחדל'], ['popularity', 'למיין לפי פופולריות'], ['rating', 'למיין לפי דירוג ממוצע'],
  ['date', 'למיין לפי המעודכן ביותר'], ['price', 'למיין מהזול ליקר'], ['price-desc', 'למיין מהיקר לזול'],
];

/** Customer-service WhatsApp (same number the old site used). */
export const WHATSAPP_URL = 'https://api.whatsapp.com/send?phone=972559296934';

/**
 * Category page banner art, keyed by category slug.
 *
 * Deliberately the same files the home page tiles use: arriving from the home page
 * the picture is already decoded in the browser, so the banner costs nothing and the
 * two screens read as one place. `pos` is the background-position - these are tall
 * street scenes and the banner is a short strip, so each one is pulled up to keep the
 * model's head in frame rather than centring on her waist.
 */
export const CAT_ART = {
  'balarina-a': { img: '2026/10/marcha-cat-round.jpg', pos: '50% 28%' },
  'בלרינה-שפיץ': { img: '2026/10/marcha-cat-pointed.jpg', pos: '50% 30%' },
  boots: { img: '2026/10/marcha-cat-boots.jpg', pos: '50% 32%' },
  winter: { img: '2026/10/marcha-cat-boots.jpg', pos: '50% 32%' },
};

/** Shown on "all shoes" and on any category without art of its own. */
export const CAT_ART_FALLBACK = { img: '2026/10/marcha-cat-round.jpg', pos: '50% 28%' };
