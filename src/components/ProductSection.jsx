import React, { useEffect, useRef, useState } from 'react';
import { getColorIdsOf, queryProducts } from '../lib/api.js';
import ProductCard from './ProductCard.jsx';
import ColorFilter from './ColorFilter.jsx';
import HScroller from './HScroller.jsx';

/** Home-page product row: one swipeable line, with a colour filter limited to the colours of its own products. */
export default function ProductSection({ title, script, orderby, terms, feature, pattern, id }) {
  const [colors, setColors] = useState([]);
  const [items, setItems] = useState([]);
  const [available, setAvailable] = useState(null);
  const [loading, setLoading] = useState(true);
  const req = useRef(0);
  useEffect(() => {
    const n = ++req.current;
    setLoading(true);
    queryProducts({ per_page: 12, orderby, order: 'desc', colors })
      .then((r) => {
        if (n !== req.current) return;
        setItems(r.data);
        if (!colors.length) getColorIdsOf(r.data.map((p) => p.id)).then((set) => n === req.current && setAvailable(set)).catch(() => {});
      })
      .catch(() => n === req.current && setItems([]))
      .finally(() => n === req.current && setLoading(false));
  }, [orderby, colors]);
  const toggle = (tid) => setColors((c) => (c.includes(tid) ? c.filter((x) => x !== tid) : [...c, tid]));
  return (
    <section className="block block--pattern" style={{ '--pattern': `url(${pattern})` }} aria-labelledby={id}>
      <div className="container">
        <p className="script-title" aria-hidden="true">{script}</p>
        <h2 className="section-title" id={id}>{title}</h2>
        <ColorFilter terms={terms} selected={colors} available={available} onToggle={toggle} onReset={() => setColors([])} label="צבע:" />
        <HScroller label={title} className={loading ? 'is-loading' : ''}>
          {feature && !colors.length && <div className="hs__item hs__item--feature">{feature}</div>}
          {items.map((p) => <div className="hs__item" key={p.id}><ProductCard p={p} /></div>)}
        </HScroller>
        {!loading && !items.length && <p className="grid__empty">אין כרגע דגמים בצבע הזה. נסי צבע אחר.</p>}
      </div>
    </section>
  );
}
