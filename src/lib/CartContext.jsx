import React, { createContext, useCallback, useContext, useEffect, useState } from 'react';
import * as api from './api.js';

const Ctx = createContext(null);
export const useCart = () => useContext(Ctx);

export function CartProvider({ children }) {
  const [cart, setCart] = useState(null);
  const [open, setOpen] = useState(false);
  const [busy, setBusy] = useState(false);

  const run = useCallback(async (fn) => {
    setBusy(true);
    try { setCart(await fn()); } finally { setBusy(false); }
  }, []);

  useEffect(() => { api.getCart().then(setCart).catch(() => {}); }, []);

  const value = {
    cart, open, busy, setOpen,
    count: cart?.items_count || 0,
    add: async (id, qty, variation) => { await run(() => api.addToCart(id, qty, variation)); setOpen(true); },
    update: (key, qty) => run(() => api.updateCartItem(key, qty)),
    remove: (key) => run(() => api.removeCartItem(key)),
  };
  return <Ctx.Provider value={value}>{children}</Ctx.Provider>;
}
