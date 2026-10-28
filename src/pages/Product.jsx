import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import * as api from '../lib/api.js';
import { trackViewItem } from '../lib/track.js';
import { useCart } from '../lib/CartContext.jsx';
import { BUNDLE } from '../lib/config.js';
import { money, toMinor } from '../lib/format.js';
import ProductCard, { Sold } from '../components/ProductCard.jsx';
import { Stars, ReviewList, ReviewForm } from '../components/Reviews.jsx';
import { ShareIcon, TruckIcon, ReturnIcon, ShieldIcon, CardIcon, ChatIcon, ZoomIcon, WhatsAppIcon, BasketIcon } from '../components/Icons.jsx';
import '../styles/product.css';
import '../styles/reviews.css';

const TRUST = [
  [TruckIcon, 'משלוחים מהירים לכל הארץ', 'עד פתח הבית תוך 3 ימי עסקים'],
  [ReturnIcon, 'החלפות והחזרות בקלות', 'לא התאימו? תוכלי להחליף או להחזיר ללא דאגות'],
  [ShieldIcon, 'נוחות ללא פשרות', 'עיצוב אורגונומי המותאם גם לאמא וגם לבת'],
  [CardIcon, 'תשלום מאובטח', 'קנייה בטוחה עם כל אמצעי התשלום המובילים'],
];
const WHATSAPP = 'https://api.whatsapp.com/send?phone=0559296934';

function Lightbox({ image, onClose }) {
  useEffect(() => {
    const onKey = (e) => e.key === 'Escape' && onClose();
    document.addEventListener('keydown', onKey);
    document.body.style.overflow = 'hidden';
    return () => { document.removeEventListener('keydown', onKey); document.body.style.overflow = ''; };
  }, [onClose]);
  return (
    <div className="lightbox" role="dialog" aria-modal="true" aria-label="תמונה מוגדלת" onClick={onClose}>
      <button className="lightbox__close" onClick={onClose} aria-label="סגרי">×</button>
      <img src={image.src} alt={image.alt || ''} onClick={(e) => e.stopPropagation()} />
    </div>
  );
}

export default function Product() {
  const slug = decodeURIComponent(useParams().slug);
  const { add, busy } = useCart();
  const [p, setP] = useState(() => api.cachedProduct(slug));
  const [active, setActive] = useState(0);
  const [size, setSize] = useState(null);
  const [qty, setQty] = useState(1);
  const [error, setError] = useState('');
  const [related, setRelated] = useState([]);
  const [tab, setTab] = useState('desc');
  const [zoom, setZoom] = useState(false);
  const [copied, setCopied] = useState(false);
  const [rating, setRating] = useState(null);

  useEffect(() => { document.body.classList.add('is-product'); return () => document.body.classList.remove('is-product'); }, []);

  useEffect(() => {
    setSize(null); setActive(0); setError(''); setQty(1); setTab('desc'); setRelated([]);
    window.scrollTo(0, 0);
    setRating(null);
    let alive = true;
    api.getProduct(slug).then((prod) => {
      if (!alive) return;
      setP(prod);
      trackViewItem(prod);
      if (prod) api.getReviews({ product: prod.id, per_page: 1 }).then((r) => alive && setRating(r.summary)).catch(() => {});
      if (prod) document.title = `${prod.name.replace(/<[^>]+>/g, '')} - Marcha Ballerina`;
      // Related products load lazily so they never block the first paint.
      if (prod?.categories?.[0]) api.getProducts({ category: prod.categories[0].id, per_page: 9 }).then((r) => alive && setRelated(r.filter((x) => x.id !== prod.id).slice(0, 8))).catch(() => {});
    });
    return () => { alive = false; };
  }, [slug]);

  const sizeAttr = useMemo(() => p?.attributes?.find((a) => a.has_variations && /size/i.test(a.taxonomy || '')) || p?.attributes?.find((a) => a.has_variations), [p]);
  const variation = useMemo(() => p?.variations?.find((v) => v.attributes.some((a) => a.value === size)), [p, size]);
  const closeZoom = useCallback(() => setZoom(false), []);

  if (!p) {
    return (
      <div className="product container skeleton" aria-busy="true" aria-label="טוענים">
        <div className="skeleton__img" />
        <div className="skeleton__lines"><i /><i /><i /><i /></div>
      </div>
    );
  }

  const submit = async (goCheckout) => {
    if (sizeAttr && !size) { setError('נא לבחור מידה'); return; }
    setError('');
    try {
      const item = { name: p.name, image: p.images?.[0]?.thumbnail || p.images?.[0]?.src, size: sizeAttr?.terms.find((t) => t.slug === size)?.name };
      if (variation) await add(variation.id, qty, variation.attributes.map((a) => ({ attribute: a.name, value: a.value })), { open: !goCheckout, item });
      else await add(p.id, qty, undefined, { open: !goCheckout, item });
      if (goCheckout) window.location.href = window.MARCHA?.checkoutUrl;
    } catch (e) { setError(e.message); }
  };

  const share = async () => {
    const data = { title: p.name.replace(/<[^>]+>/g, ''), url: window.location.href };
    try {
      if (navigator.share) await navigator.share(data);
      else { await navigator.clipboard.writeText(data.url); setCopied(true); setTimeout(() => setCopied(false), 2000); }
    } catch (e) { /* user cancelled */ }
  };

  const img = p.images[active];
  const extraAttrs = (p.attributes || []).filter((a) => a.terms?.length);
  const unitPrice = money(toMinor(BUNDLE.unit, p.prices), p.prices);

  return (
    <>
      <article className="product container">
        <div className="product__gallery">
          {img && (
            <div className="product__stage">
              <img className="product__main" src={img.src} srcSet={img.srcset} sizes="(min-width:1025px) 50vw, 100vw" alt={img.alt || p.name} />
              <button type="button" className="product__zoom" onClick={() => setZoom(true)}><ZoomIcon /> הגדלה</button>
            </div>
          )}
          {p.images.length > 1 && (
            <ul className="product__thumbs">
              {p.images.map((im, i) => (
                <li key={im.id}><button type="button" className={i === active ? 'is-on' : ''} onClick={() => setActive(i)} aria-label={`תמונה ${i + 1}`}><img src={im.thumbnail} alt="" loading="lazy" /></button></li>
              ))}
            </ul>
          )}
        </div>

        <div className="product__info">
          <nav className="crumbs" aria-label="פירורי לחם">
            <Link to="/">בית</Link> / {p.categories?.[0] && <><Link to={`/product-category/${p.categories[0].slug}/`} dangerouslySetInnerHTML={{ __html: p.categories[0].name }} /> / </>}
            <span dangerouslySetInnerHTML={{ __html: p.name }} />
          </nav>
          <p className="product__bar">{BUNDLE.bar}</p>
          <div className="product__head">
            {p.categories?.[0] && <p className="product__cat" dangerouslySetInnerHTML={{ __html: p.categories[0].name }} />}
            <button type="button" className="product__share" onClick={share} aria-label="שיתוף המוצר"><ShareIcon /><span>{copied ? 'הקישור הועתק' : 'שיתוף'}</span></button>
          </div>
          <h1 dangerouslySetInnerHTML={{ __html: p.name }} />
          <p className="product__meta">
            {rating?.count > 0 ? <button type="button" className="linklike" onClick={() => { setTab('reviews'); document.querySelector('.ptabs')?.scrollIntoView({ behavior: 'smooth' }); }}><Stars value={rating.average} /> <span>({rating.count} ביקורות)</span></button> : <span>(אין ביקורות עדיין)</span>}
            <b className={p.is_in_stock ? 'in' : 'out'}>{p.is_in_stock ? 'במלאי' : 'אזל מהמלאי'}</b>
          </p>
          <Sold n={p.extensions?.marcha?.sold} />
          <p className="product__price"><s>{money(p.prices.regular_price, p.prices)}</s> <strong>{unitPrice}</strong></p>
          <div className="product__desc" dangerouslySetInnerHTML={{ __html: p.short_description || '' }} />

          {sizeAttr && (
            <fieldset className="sizes">
              <legend>{sizeAttr.name}{size && <em>: {sizeAttr.terms.find((t) => t.slug === size)?.name}</em>}</legend>
              <div>{sizeAttr.terms.filter((t) => p.variations.some((v) => v.attributes.some((a) => a.value === t.slug))).map((t) => (
                <button key={t.id} type="button" className={`size${size === t.slug ? ' is-on' : ''}`} aria-pressed={size === t.slug} onClick={() => { setSize(t.slug); setError(''); }}>{t.name}</button>
              ))}</div>
            </fieldset>
          )}
          {error && <p role="alert" className="product__error">{error}</p>}

          <div className="product__buy">
            <div className="qty" role="group" aria-label="כמות">
              <button type="button" onClick={() => setQty((q) => Math.max(1, q - 1))} aria-label="פחות">−</button>
              <output>{qty}</output>
              <button type="button" onClick={() => setQty((q) => q + 1)} aria-label="יותר">+</button>
            </div>
            <button className="btn btn--mint" disabled={busy || !p.is_in_stock} onClick={() => submit(true)}>קנה עכשיו</button>
            <button className="btn btn--black" disabled={busy || !p.is_in_stock} aria-busy={busy} onClick={() => submit(false)}><BasketIcon className="btn__basket" />הוסף לסל</button>
          </div>

          <ul className="trust">{TRUST.map(([Icon, t, d]) => <li key={t}><Icon /><b>{t}</b><span>{d}</span></li>)}</ul>
          <a className="advice" href={WHATSAPP} target="_blank" rel="noopener noreferrer"><WhatsAppIcon /><span><b>צריכה עזרה במידה?</b></span></a>
        </div>
      </article>

      <section className="container ptabs">
        <div role="tablist" className="ptabs__list">
          {[['desc', 'תיאור המוצר'], ['spec', 'פרטים נוספים'], ['reviews', `ביקורות${rating ? ` (${rating.count})` : ''}`]].map(([k, l]) => (
            <button key={k} role="tab" aria-selected={tab === k} className={tab === k ? 'is-on' : ''} onClick={() => setTab(k)}>{l}</button>
          ))}
        </div>
        <div role="tabpanel" className="ptabs__panel">
          {tab === 'desc' && <div dangerouslySetInnerHTML={{ __html: p.description || p.short_description || '' }} />}
          {tab === 'spec' && (extraAttrs.length ? <dl className="spec">{extraAttrs.map((a) => <React.Fragment key={a.id || a.name}><dt>{a.name}</dt><dd>{a.terms.map((t) => t.name).join(', ')}</dd></React.Fragment>)}</dl> : <p>אין פרטים נוספים.</p>)}
          {tab === 'reviews' && <><ReviewList product={p.id} perPage={6} /><ReviewForm product={p.id} /></>}
        </div>
      </section>

      {related.length > 0 && (
        <section className="container product__related">
          <p className="script-title" aria-hidden="true">Paris under my feet</p>
          <h2 className="section-title">יכול להוסיף לסטייל שלך</h2>
          <div className="grid">{related.map((r) => <ProductCard key={r.id} p={r} />)}</div>
        </section>
      )}

      <div className="stickybuy" aria-hidden={false}>
        <span>{unitPrice}</span>
        <button className="btn btn--black" disabled={busy || !p.is_in_stock} aria-busy={busy} onClick={() => submit(false)}><BasketIcon className="btn__basket" />הוסף לסל</button>
      </div>
      {zoom && img && <Lightbox image={img} onClose={closeZoom} />}
    </>
  );
}
