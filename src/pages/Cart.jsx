import React from 'react';
import { useCart } from '../lib/CartContext.jsx';
import { money } from '../lib/format.js';
import '../styles/cart.css';

export default function Cart() {
  const { cart, update, remove, busy } = useCart();
  const items = cart?.items || [];
  return (
    <section className="cartpage container" aria-busy={busy}>
      <h1 className="section-title">עגלת קניות</h1>
      {cart && !items.length && <p className="cartpage__empty">העגלה ריקה.</p>}
      <ul className="cartpage__list">
        {items.map((i) => (
          <li key={i.key}>
            {i.images?.[0] && <img src={i.images[0].thumbnail} alt="" width="96" height="96" />}
            <div className="cartpage__meta">
              <a href={i.permalink} dangerouslySetInnerHTML={{ __html: i.name }} />
              <span>{i.variation?.map((v) => `${v.attribute}: ${v.value}`).join(' · ')}</span>
              <span>{money(i.prices.price, i.prices)}</span>
            </div>
            <div className="cartpage__qty">
              <button onClick={() => update(i.key, i.quantity - 1)} aria-label="פחות">−</button>
              <output>{i.quantity}</output>
              <button onClick={() => update(i.key, i.quantity + 1)} aria-label="יותר">+</button>
            </div>
            <strong>{money(i.totals.line_total, i.totals)}</strong>
            <button className="cartpage__rm" onClick={() => remove(i.key)} aria-label="הסרה">×</button>
          </li>
        ))}
      </ul>
      {cart && items.length > 0 && (
        <div className="cartpage__sum">
          <p>סכום ביניים: <strong>{money(cart.totals.total_items, cart.totals)}</strong></p>
          <p>סה״כ: <strong>{money(cart.totals.total_price, cart.totals)}</strong></p>
          <a className="btn btn--mint" href={window.MARCHA?.checkoutUrl}>לתשלום</a>
        </div>
      )}
    </section>
  );
}
