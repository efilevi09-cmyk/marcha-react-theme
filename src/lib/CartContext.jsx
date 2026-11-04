import React, { createContext, useCallback, useContext, useEffect, useRef, useState } from 'react';
import Confetti from '../components/Confetti.jsx';
import { BUNDLE } from './config.js';
import { bundleCount } from './bundle.js';
import * as api from './api.js';
import { trackAddToCart, trackRemoveFromCart } from './track.js';

const Ctx = createContext(null);
export const useCart = () => useContext(Ctx);

export function CartProvider({ children }) {
  const [cart, setCart] = useState(null);
  const [open, setOpen] = useState(false);
  const [busy, setBusy] = useState(false);
  const [added, setAdded] = useState(null);
  const [burst, setBurst] = useState(0);
  const prevCount = useRef(null);

  const run = useCallback(async (fn) => {
    setBusy(true);
    try {
      const next = await fn();
      // Confetti each time the cart reaches a full bundle (3, 6, 9 ...) by adding pairs.
      // Counted over the lines the offer covers, so adding boots never sets it off.
      const n = bundleCount(next);
      if (prevCount.current !== null && n > prevCount.current && n >= BUNDLE.qty && n % BUNDLE.qty === 0) setBurst(Date.now());
      prevCount.current = n;
      setCart(next);
      return next;
    } finally { setBusy(false); }
  }, []);

  useEffect(() => { api.getCart().then((c) => { prevCount.current = bundleCount(c); setCart(c); }).catch(() => {}); }, []);

  const value = {
    cart, open, busy, setOpen, burst, added, closeAdded: () => setAdded(null),
    celebrate: () => setBurst(Date.now()),
    count: cart?.items_count || 0,
    /** Pairs the 3-for-300 offer covers - boots are not among them. */
    bundleCount: bundleCount(cart),
    add: async (id, qty, variation, { open = true, item } = {}) => { const next = await run(() => api.addToCart(id, qty, variation)); trackAddToCart(next?.items?.find((l) => l.id === id), qty); if (open) { if (item) setAdded({ ...item, at: Date.now() }); else setOpen(true); } },
    update: (key, qty) => run(() => api.updateCartItem(key, qty)),
    remove: (key) => { trackRemoveFromCart(cart?.items?.find((l) => l.key === key)); return run(() => api.removeCartItem(key)); },
    applyCoupon: (code) => run(() => api.applyCoupon(code)),
    removeCoupon: (code) => run(() => api.removeCoupon(code)),
  };
  return <Ctx.Provider value={value}>{children}<Confetti burst={burst} /></Ctx.Provider>;
}
