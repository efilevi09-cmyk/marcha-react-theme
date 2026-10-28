import React from 'react';
import { money } from '../lib/format.js';

export default function ProductCard({ p }) {
  const pr = p.prices;
  const range = pr.price_range;
  const price = range ? `${money(range.min_amount, pr)} – ${money(range.max_amount, pr)}` : money(pr.price, pr);
  return (
    <article className="pcard">
      <a href={p.permalink}>
        <span className="pcard__badge">3 נעליים ב300 ₪</span>
        {p.images?.[0] && <img src={p.images[0].src} srcSet={p.images[0].srcset} sizes="(min-width:1025px) 25vw, 50vw" alt={p.images[0].alt || p.name} loading="lazy" />}
        <h3 dangerouslySetInnerHTML={{ __html: p.name }} />
        <p className="pcard__price">{price}</p>
      </a>
    </article>
  );
}
