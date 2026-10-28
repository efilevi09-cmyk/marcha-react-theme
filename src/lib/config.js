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
