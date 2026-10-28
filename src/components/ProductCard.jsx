import React from 'react';
import { Link } from 'react-router-dom';
import { money } from '../lib/format.js';
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
      <strong>{single ? money(BUNDLE.unit * 100, prices) : money(BUNDLE.unit * 100, prices)}</strong>
    </p>
  );
}

export default function ProductCard({ p }) {
  const img = p.images?.[0];
  return (
    <article className="pcard">
      <Link to={toPath(p.permalink)} className="pcard__link">
        <span className="pcard__badge">{BUNDLE.badge}</span>
        {img && <img src={img.src} srcSet={img.srcset} sizes="(min-width:1025px) 25vw, 50vw" alt={img.alt || p.name} loading="lazy" />}
        <h3 dangerouslySetInnerHTML={{ __html: p.name }} />
        <Price prices={p.prices} />
      </Link>
      <Link to={toPath(p.permalink)} className="pcard__btn">בחר אפשרויות</Link>
    </article>
  );
}
