import React, { useEffect, useRef, useState } from 'react';
import { queryProducts } from '../lib/api.js';
import ProductCard from './ProductCard.jsx';
import ColorFilter from './ColorFilter.jsx';

/** Home-page product block with its own colour filter; the grid re-queries the shop as colours change. */
export default function ProductSection({ title, script, orderby, terms, feature, pattern, id }) {
  const [colors, setColors] = useState([]);
  const [items, setItems] = useState([]);
  const [loading, setLoading] = useState(true);
  const req = useRef(0);
  useEffect(() => {
    const n = ++req.current;
    setLoading(true);
    queryProducts({ per_page: 8, orderby, order: 'desc', colors })
      .then((r) => n === req.current && setItems(r.data))
      .catch(() => n === req.current && setItems([]))
      .finally(() => n === req.current && setLoading(false));
  }, [orderby, colors]);
  const toggle = (tid) => setColors((c) => (c.includes(tid) ? c.filter((x) => x !== tid) : [...c, tid]));
  return (
    <section className="block block--pattern" style={{ '--pattern': `url(${pattern})` }} aria-labelledby={id}>
      <div className="container">
        <p className="script-title" aria-hidden="true">{script}</p>
        <h2 className="section-title" id={id}>{title}</h2>
        <ColorFilter terms={terms} selected={colors} onToggle={toggle} onReset={() => setColors([])} />
        <div className="grid" aria-busy={loading}>
          {feature && !colors.length && feature}
          {items.map((p) => <ProductCard key={p.id} p={p} />)}
        </div>
        {!loading && !items.length && <p className="grid__empty">אין כרגע דגמים בצבע הזה. נסי צבע אחר.</p>}
      </div>
    </section>
  );
}
