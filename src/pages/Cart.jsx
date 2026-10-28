import React, { useEffect, useMemo, useState } from 'react';
import * as api from '../lib/api.js';
import { useCart } from '../lib/CartContext.jsx';
import { money } from '../lib/format.js';
import BundleBar from '../components/BundleBar.jsx';
import ProductCard from '../components/ProductCard.jsx';
import { ReviewList } from '../components/Reviews.jsx';
import '../styles/page.css';
import '../styles/cart.css';

function Coupon() {
  const { cart, applyCoupon, removeCoupon } = useCart();
  const [code, setCode] = useState('');
  const [error, setError] = useState('');
  const submit = async (e) => {
    e.preventDefault();
    if (!code.trim()) return;
    setError('');
    try { await applyCoupon(code.trim()); setCode(''); } catch (err) { setError(err.message); }
  };
  return (
    <div className="coupon">
      <form onSubmit={submit}>
        <input value={code} onChange={(e) => setCode(e.target.value)} placeholder="קוד קופון" aria-label="קוד קופון" />
        <button className="btn btn--black">החלת קופון</button>
      </form>
      {error && <p role="alert" className="coupon__err">{error}</p>}
      <ul>{(cart?.coupons || []).map((c) => <li key={c.code}>{c.code} <button type="button" onClick={() => removeCoupon(c.code)} aria-label={`הסרת קופון ${c.code}`}>×</button></li>)}</ul>
    </div>
  );
}

/** Products related to what is in the cart (same categories), excluding items already added. */
function Related({ items }) {
  const [list, setList] = useState([]);
  const slug = useMemo(() => { try { return decodeURIComponent(new URL(items[0]?.permalink, location.origin).pathname.split('/').filter(Boolean).pop() || ''); } catch (e) { return ''; } }, [items]);
  const inCart = useMemo(() => new Set(items.map((i) => i.id)), [items]);
  useEffect(() => {
    let alive = true;
    (async () => {
      const first = slug ? await api.getProduct(slug).catch(() => null) : null;
      const catId = first?.categories?.[0]?.id;
      const r = await api.getProducts({ per_page: 12, ...(catId ? { category: catId } : { orderby: 'popularity' }) });
      if (alive) setList(r.filter((p) => !inCart.has(p.id) && !p.variations?.some((v) => inCart.has(v.id))).slice(0, 4));
    })().catch(() => {});
    return () => { alive = false; };
  }, [slug, inCart]);
  if (!list.length) return null;
  return (
    <section className="cartpage__related" aria-labelledby="rel-title">
      <h2 id="rel-title">אולי יעניין אותך</h2>
      <div className="cartpage__relgrid">{list.map((p) => <ProductCard key={p.id} p={p} />)}</div>
    </section>
  );
}

export default function Cart() {
  const { cart, update, remove, busy, count } = useCart();
  const items = cart?.items || [];
  const fees = cart?.fees || [];
  useEffect(() => { document.title = 'עגלת קניות - Marcha Ballerina'; }, []);
  return (
    <>
    <header className="pagehero">
      <p className="pagehero__accent" aria-hidden="true">Cart</p>
      <h1 className="pagehero__title">עגלת קניות</h1>
    </header>
    <section className="cartpage container" aria-busy={busy}>
      {cart && !items.length && <p className="cartpage__empty">העגלה ריקה. <a href={window.MARCHA?.siteUrl}>חזרה לחנות</a></p>}
      {items.length > 0 && (
        <div className="cartpage__layout">
          <div className="cartpage__main">
            <BundleBar count={count} shopLink />
            <ul className="cartpage__list">
              {items.map((i) => (
                <li key={i.key}>
                  {i.images?.[0] && <img src={i.images[0].thumbnail} alt="" width="96" height="96" />}
                  <div className="cartpage__meta">
                    <a href={i.permalink} dangerouslySetInnerHTML={{ __html: i.name }} />
                    <span>{[...(i.variation || []).map((v) => `${v.attribute}: ${v.value}`), money(i.prices.price, i.prices)].join(' · ')}</span>
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
            <Coupon />
            <div className="cartpage__sum">
              <p>סכום ביניים: <strong>{money(cart.totals.total_items, cart.totals)}</strong></p>
              {fees.map((f) => <p key={f.id} className="cartpage__fee">{f.name}: <strong>{money(f.totals.total, cart.totals)}</strong></p>)}
              <p className="cartpage__total">סה״כ: <strong>{money(cart.totals.total_price, cart.totals)}</strong></p>
              <small>משלוח יחושב בשלב התשלום</small>
              <a className="btn btn--mint" href={window.MARCHA?.checkoutUrl}>לתשלום</a>
            </div>
          </div>
          <aside className="cartpage__aside">
            <section aria-labelledby="rv-title">
              <h2 id="rv-title">מה הלקוחות אומרות</h2>
              <ReviewList perPage={3} />
            </section>
            <Related items={items} />
          </aside>
        </div>
      )}
    </section>
    </>
  );
}
