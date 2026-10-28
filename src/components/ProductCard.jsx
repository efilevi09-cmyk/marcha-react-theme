import React, { useMemo, useState } from 'react';
import { useCart } from '../lib/CartContext.jsx';
import { Link } from 'react-router-dom';
import { money, toMinor } from '../lib/format.js';
import { BUNDLE } from '../lib/config.js';

const toPath = (url) => { try { return new URL(url).pathname; } catch (e) { return url; } };

export function Price({ prices, single }) {
  const range = prices.price_range;
  const regular = range
    ? `${money(range.min_amount, prices)} – ${money(range.max_amount, prices)}`
    : money(prices.regular_price || prices.price, prices);
  return (
    <p className="pcard__price">
      <s>{regular}</s>
      <strong>{single ? money(toMinor(BUNDLE.unit, prices), prices) : money(toMinor(BUNDLE.unit, prices), prices)}</strong>
    </p>
  );
}

/** "Sold N pairs" social proof, only shown once something has sold. */
export function Sold({ n, reserve = false }) {
  const sold = Number(n) || 0;
  if (sold < 1) return reserve ? <p className="pcard__sold is-empty" aria-hidden="true">&nbsp;</p> : null;
  return <p className="pcard__sold"><span aria-hidden="true">🔥</span> נמכרו {sold.toLocaleString('he-IL')} {sold === 1 ? 'זוג' : 'זוגות'}</p>;
}

/** Sizes that actually have a variation, so the shopper can pick one without opening the product. */
function useSizes(p) {
  return useMemo(() => {
    const attr = p.attributes?.find((a) => a.has_variations && /size/i.test(a.taxonomy || '')) || p.attributes?.find((a) => a.has_variations);
    if (!attr || !p.variations?.length) return { attr: null, sizes: [] };
    const sizes = attr.terms
      .map((t) => ({ term: t, variation: p.variations.find((v) => v.attributes.some((a) => a.value === t.slug)) }))
      .filter((x) => x.variation);
    return { attr, sizes };
  }, [p]);
}

export default function ProductCard({ p }) {
  const img = p.images?.[0];
  const { add, busy } = useCart();
  const { sizes } = useSizes(p);
  const [size, setSize] = useState(null);
  const [hint, setHint] = useState(false);
  const [error, setError] = useState('');
  const path = toPath(p.permalink);

  const addToCart = async () => {
    if (!sizes.length) return;
    const chosen = sizes.find((x) => x.term.slug === size);
    if (!chosen) { setHint(true); setTimeout(() => setHint(false), 900); return; }
    setError('');
    try {
      await add(chosen.variation.id, 1, chosen.variation.attributes.map((a) => ({ attribute: a.name, value: a.value })), {
        item: { name: p.name, image: img?.thumbnail || img?.src, size: chosen.term.name },
      });
    } catch (e) { setError(e.message); }
  };

  return (
    <article className="pcard">
      <Link to={path} className="pcard__link">
        <span className="pcard__badge">{BUNDLE.badge}</span>
        {img && <img src={img.src} srcSet={img.srcset} sizes="(min-width:1025px) 25vw, 50vw" alt={img.alt || p.name} loading="lazy" />}
        <h3 dangerouslySetInnerHTML={{ __html: p.name }} />
        <Sold n={p.extensions?.marcha?.sold} reserve />
        <Price prices={p.prices} />
      </Link>
      {sizes.length > 0 && (
        <div className={`pcard__sizes${hint ? ' is-hint' : ''}`} role="group" aria-label="בחירת מידה">
          {sizes.map(({ term }) => (
            <button key={term.id} type="button" className={size === term.slug ? 'is-on' : ''} aria-pressed={size === term.slug} onClick={() => { setSize(term.slug); setHint(false); }}>{term.name}</button>
          ))}
        </div>
      )}
      {error && <p className="pcard__error" role="alert">{error}</p>}
      {sizes.length > 0
        ? <button type="button" className="pcard__btn" disabled={busy} onClick={addToCart}>{size ? 'הוספה לסל' : 'בחרי מידה'}</button>
        : <Link to={path} className="pcard__btn">בחר אפשרויות</Link>}
    </article>
  );
}
