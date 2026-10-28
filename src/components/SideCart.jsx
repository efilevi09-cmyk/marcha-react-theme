import React from 'react';
import { useCart } from '../lib/CartContext.jsx';
import { money } from '../lib/format.js';
import BundleBar from './BundleBar.jsx';
import '../styles/sidecart.css';

export default function SideCart() {
  const { cart, open, setOpen, update, remove, busy, count } = useCart();
  if (!open) return null;
  const fees = cart?.fees || [];
  return (
    <div className="sc" role="dialog" aria-modal="true" aria-label="עגלת קניות">
      <button className="sc__overlay" aria-label="סגרי" onClick={() => setOpen(false)} />
      <aside className="sc__panel" aria-busy={busy}>
        <button className="sc__close" onClick={() => setOpen(false)} aria-label="סגרי">×</button>
        <BundleBar count={count} />
        {cart && !cart.items.length && <p>העגלה ריקה.</p>}
        <ul className="sc__list">
          {(cart?.items || []).map((i) => (
            <li key={i.key}>
              {i.images?.[0] && <img src={i.images[0].thumbnail} alt="" width="72" height="72" />}
              <div>
                <a href={i.permalink} dangerouslySetInnerHTML={{ __html: i.name }} />
                <div>{i.quantity} × {money(i.prices.price, i.prices)}</div>
                <div className="sc__qty">
                  <button onClick={() => update(i.key, i.quantity - 1)} aria-label="פחות">−</button>
                  <button onClick={() => update(i.key, i.quantity + 1)} aria-label="יותר">+</button>
                  <button onClick={() => remove(i.key)}>הסרה</button>
                </div>
              </div>
            </li>
          ))}
        </ul>
        {cart && (
          <div className="sc__sum">
            <p>סכום ביניים: <span>{money(cart.totals.total_items, cart.totals)}</span></p>
            {fees.map((f) => <p key={f.id} className="sc__fee">{f.name}: <span>{money(f.totals.total, cart.totals)}</span></p>)}
            <p className="sc__total">סה״כ: <span>{money(cart.totals.total_price, cart.totals)}</span></p>
          </div>
        )}
        <a className="btn btn--black" href={window.MARCHA?.cartUrl}>צפה בעגלה</a>
        <a className="btn btn--mint" href={window.MARCHA?.checkoutUrl}>לתשלום</a>
      </aside>
    </div>
  );
}
