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

/**
 * Pairs in the cart that the offer actually covers.
 *
 * The server marks each line (extensions.marcha.bundle); boots are excluded, so they
 * must not move the progress bar or set off the confetti. Lines from before the flag
 * existed count, which keeps older carts behaving as they did.
 */
export function bundleCount(cart) {
  return (cart?.items || []).reduce(
    (n, i) => n + (i.extensions?.marcha?.bundle === false ? 0 : (i.quantity || 0)),
    0,
  );
}
