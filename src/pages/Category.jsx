import React, { useCallback, useEffect, useRef, useState } from 'react';
import { useParams } from 'react-router-dom';
import * as api from '../lib/api.js';
import { COLOR_HEX, SORTS } from '../lib/config.js';
import ProductCard from '../components/ProductCard.jsx';
import '../styles/shop.css';

export default function Category({ all = false }) {
  const { slug: rawSlug } = useParams();
  const slug = rawSlug ? decodeURIComponent(rawSlug) : null;
  const [cat, setCat] = useState(null);
  const [terms, setTerms] = useState([]);
  const [colors, setColors] = useState([]);
  const [catColors, setCatColors] = useState(null);
  const [orderby, setOrderby] = useState('menu_order');
  const [items, setItems] = useState([]);
  const [meta, setMeta] = useState({ total: 0, pages: 1, page: 1 });
  const [loading, setLoading] = useState(true);
  const reqId = useRef(0);

  useEffect(() => { api.getColorTerms().then(setTerms).catch(() => {}); }, []);
  useEffect(() => {
    setCatColors(null); setColors([]);
    if (!all && cat) api.getCategoryColorIds(cat.id).then(setCatColors).catch(() => {});
  }, [cat, all]);
  useEffect(() => {
    setCat(null);
    if (all || !slug) return;
    api.getCategories().then((cs) => setCat(cs.find((c) => c.slug === slug || decodeURIComponent(c.slug) === slug) || null)).catch(() => {});
  }, [slug, all]);

  const load = useCallback(async (page, append) => {
    setLoading(true);
    const id = ++reqId.current; // ignore out-of-order responses
    const params = { page, orderby: orderby === 'price-desc' ? 'price' : orderby, order: orderby === 'price' ? 'asc' : orderby === 'menu_order' ? 'asc' : 'desc', colors };
    if (!all && cat) params.category = cat.id;
    try {
      const r = await api.queryProducts(params);
      if (id !== reqId.current) return;
      setItems((prev) => (append ? [...prev, ...r.data] : r.data));
      setMeta({ total: r.total, pages: r.pages, page });
    } catch (e) {
      if (id === reqId.current) setItems([]);
    } finally { if (id === reqId.current) setLoading(false); }
  }, [all, cat, colors, orderby]);

  useEffect(() => { if (all || cat) load(1, false); }, [cat, colors, orderby, all, load]);

  const toggle = (id) => setColors((c) => (c.includes(id) ? c.filter((x) => x !== id) : [...c, id]));
  const title = all ? 'כל הנעליים' : cat?.name;

  return (
    <>
      <section className="banner" style={{ backgroundImage: `url(${window.MARCHA?.uploadsUrl}/2025/03/279.webp)` }}>
        <h1 dangerouslySetInnerHTML={{ __html: title || '' }} />
        {cat?.description && <div className="banner__desc" dangerouslySetInnerHTML={{ __html: cat.description }} />}
      </section>
      <div className="container shop">
        <div className="filters">
        <h2 className="filter__title">בחרו צבע מבוקש</h2>
        <div className="filter__row">
        <ul className="swatches">
          {terms.filter((t) => COLOR_HEX[t.name.trim()] && (all || !catColors || catColors.has(t.id))).map((t) => (
            <li key={t.id}>
              <button
                type="button"
                className={`swatch${colors.includes(t.id) ? ' is-on' : ''}`}
                style={{ '--sw': COLOR_HEX[t.name.trim()] }}
                aria-pressed={colors.includes(t.id)}
                aria-label={t.name}
                title={t.name}
                onClick={() => toggle(t.id)}
              />
            </li>
          ))}
        </ul>
        <button type="button" className="reset" onClick={() => setColors([])} disabled={!colors.length}>איפוס הסינון</button>
        </div>
        </div>
        <div className="toolbar">
          <p aria-live="polite">מציגים את כל {meta.total} התוצאות</p>
          <select value={orderby} onChange={(e) => setOrderby(e.target.value)} aria-label="מיון">
            {SORTS.map(([v, l]) => <option key={v} value={v}>{l}</option>)}
          </select>
        </div>
        <div className="grid" aria-busy={loading}>{items.map((p) => <ProductCard key={p.id} p={p} />)}</div>
        {meta.page < meta.pages && (
          <button className="btn btn--black more" disabled={loading} onClick={() => load(meta.page + 1, true)}>טעני עוד</button>
        )}
      </div>
    </>
  );
}
