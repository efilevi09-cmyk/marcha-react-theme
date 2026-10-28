import { BUNDLE } from './config.js';

/** Progress towards the "3 pairs for 300" offer based on the number of pairs in the cart. */
export function bundleProgress(count) {
  const inGroup = count % BUNDLE.qty;
  const remaining = count === 0 ? BUNDLE.qty : (BUNDLE.qty - inGroup) % BUNDLE.qty;
  const unlocked = count >= BUNDLE.qty;
  return { count, remaining, unlocked, pct: Math.round(((inGroup === 0 && count > 0 ? BUNDLE.qty : inGroup) / BUNDLE.qty) * 100) };
}

export const pairsWord = (n) => (n === 1 ? 'זוג נעליים' : `${n} זוגות נעליים`);

export function bundleMessage(count) {
  const { remaining, unlocked } = bundleProgress(count);
  if (count > 0 && remaining === 0) return { done: true, text: `מעולה! ${BUNDLE.qty} נעליים ב-${BUNDLE.total} ₪ נוספו לעגלה 🎉` };
  if (count === 0) return { done: false, text: `קונה ${BUNDLE.qty} נעליים? משלמת רק ${BUNDLE.total} ₪` };
  return { done: false, text: `הוסיפי עוד ${pairsWord(remaining)} וקבלי ${BUNDLE.qty} נעליים ב-${BUNDLE.total} ₪`, unlocked };
}
