import React, { useEffect, useMemo, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import * as api from '../lib/api.js';
import { useCart } from '../lib/CartContext.jsx';
import { BUNDLE } from '../lib/config.js';
import { money, toMinor } from '../lib/format.js';
import ProductCard from '../components/ProductCard.jsx';
import '../styles/product.css';

const INFO = [
  ['משלוחים מהירים לכל הארץ', 'קבלי את הנעליים עד פתח הבית תוך 3 ימי עסקים'],
  ['החלפות והחזרות בקלות', 'לא התאימו? תוכלי להחליף או להחזיר ללא דאגות.'],
  ['נוחות ללא פשרות', 'עיצוב אורגונומי המותאם גם לאמא וגם לבת'],
  ['תשלום מאובטח', 'קנייה בטוחה עם כל אמצעי התשלום המובילים'],
];

export default function Product() {
  const slug = decodeURIComponent(useParams().slug);
  const { add, busy } = useCart();
  const [p, setP] = useState(null);
  const [active, setActive] = useState(0);
  const [size, setSize] = useState(null);
  const [error, setError] = useState('');
  const [related, setRelated] = useState([]);

  useEffect(() => {
    setP(null); setSize(null); setActive(0); setError('');
    api.getProduct(slug).then((prod) => {
      setP(prod);
      if (prod?.categories?.[0]) api.getProducts({ category: prod.categories[0].id, per_page: 5 }).then((r) => setRelated(r.filter((x) => x.id !== prod.id).slice(0, 4)));
      if (prod) document.title = `${prod.name.replace(/<[^>]+>/g, '')} - Marcha Ballerina`;
    });
  }, [slug]);

  const sizeAttr = useMemo(() => p?.attributes?.find((a) => a.has_variations && /size/i.test(a.taxonomy || '')) || p?.attributes?.find((a) => a.has_variations), [p]);
  const variation = useMemo(() => p?.variations?.find((v) => v.attributes.some((a) => a.value === size)), [p, size]);

  if (!p) return <div className="container product__loading" aria-busy="true">טוענים…</div>;

  const submit = async () => {
    if (sizeAttr && !size) { setError('נא לבחור מידה'); return; }
    setError('');
    try {
      if (variation) await add(variation.id, 1, variation.attributes.map((a) => ({ attribute: a.name, value: a.value })));
      else await add(p.id, 1);
    } catch (e) { setError(e.message); }
  };

  return (
    <article className="product container">
      <div className="product__gallery">
        {p.images[active] && <img className="product__main" src={p.images[active].src} srcSet={p.images[active].srcset} sizes="(min-width:1025px) 50vw, 100vw" alt={p.images[active].alt || p.name} />}
        <ul className="product__thumbs">
          {p.images.map((im, i) => (
            <li key={im.id}><button type="button" className={i === active ? 'is-on' : ''} onClick={() => setActive(i)} aria-label={`תמונה ${i + 1}`}><img src={im.thumbnail} alt="" loading="lazy" /></button></li>
          ))}
        </ul>
      </div>
      <div className="product__info">
        <p className="product__bar">{BUNDLE.bar}</p>
        <nav className="crumbs" aria-label="פירורי לחם"><Link to="/">עמוד בית</Link> / {p.categories?.[0] && <Link to={`/product-category/${p.categories[0].slug}/`} dangerouslySetInnerHTML={{ __html: p.categories[0].name }} />}</nav>
        <h1 dangerouslySetInnerHTML={{ __html: p.name }} />
        <div className="product__desc" dangerouslySetInnerHTML={{ __html: p.short_description || p.description }} />
        <p className="product__price"><s>{money(p.prices.regular_price, p.prices)}</s> <strong>{money(toMinor(BUNDLE.unit, p.prices), p.prices)}</strong></p>
        {sizeAttr && (
          <fieldset className="sizes">
            <legend>{sizeAttr.name}</legend>
            <div>{sizeAttr.terms.filter((t) => p.variations.some((v) => v.attributes.some((a) => a.value === t.slug))).map((t) => (
              <button key={t.id} type="button" className={`size${size === t.slug ? ' is-on' : ''}`} aria-pressed={size === t.slug} onClick={() => { setSize(t.slug); setError(''); }}>{t.name}</button>
            ))}</div>
          </fieldset>
        )}
        {error && <p role="alert" className="product__error">{error}</p>}
        <button className="btn btn--black product__add" disabled={busy} onClick={submit}>הוספה לסל</button>
        <ul className="product__info-list">{INFO.map(([t, d]) => <li key={t}><b>{t}</b><span>{d}</span></li>)}</ul>
      </div>
      {related.length > 0 && (
        <section className="product__related">
          <h2 className="section-title">יכול להוסיף לסטייל שלך</h2>
          <div className="grid">{related.map((r) => <ProductCard key={r.id} p={r} />)}</div>
        </section>
      )}
    </article>
  );
}
