import React, { useEffect, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import { queryProducts } from '../lib/api.js';
import { Price } from './ProductCard.jsx';
import { SearchIcon } from './Icons.jsx';
import '../styles/search.css';

const toPath = (url) => { try { return new URL(url).pathname; } catch (e) { return url; } };
const useDebounced = (value, ms) => {
  const [v, setV] = useState(value);
  useEffect(() => { const t = setTimeout(() => setV(value), ms); return () => clearTimeout(t); }, [value, ms]);
  return v;
};

/** Live product search: results appear while typing (Store API `search`). */
export default function Search({ open, onClose }) {
  const [q, setQ] = useState('');
  const [state, setState] = useState({ items: [], total: 0, loading: false, done: false });
  const input = useRef(null);
  const term = useDebounced(q.trim(), 250);
  const base = window.MARCHA?.siteUrl || '/';

  useEffect(() => {
    if (!open) return undefined;
    const prev = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    input.current?.focus();
    const onKey = (e) => e.key === 'Escape' && onClose();
    window.addEventListener('keydown', onKey);
    return () => { document.body.style.overflow = prev; window.removeEventListener('keydown', onKey); };
  }, [open, onClose]);

  useEffect(() => {
    if (term.length < 2) { setState({ items: [], total: 0, loading: false, done: false }); return undefined; }
    let alive = true;
    setState((s) => ({ ...s, loading: true }));
    queryProducts({ search: term, per_page: 8 })
      .then((r) => alive && setState({ items: r.data, total: r.total, loading: false, done: true }))
      .catch(() => alive && setState({ items: [], total: 0, loading: false, done: true }));
    return () => { alive = false; };
  }, [term]);

  if (!open) return null;
  const submit = (e) => { e.preventDefault(); if (q.trim()) location.href = `${base}?s=${encodeURIComponent(q.trim())}&post_type=product`; };
  return (
    <div className="srch" role="dialog" aria-modal="true" aria-label="חיפוש מוצרים">
      <button className="srch__overlay" aria-label="סגירה" onClick={onClose} />
      <div className="srch__panel">
        <form className="srch__form" onSubmit={submit} role="search">
          <SearchIcon aria-hidden="true" />
          <input ref={input} type="search" value={q} onChange={(e) => setQ(e.target.value)} placeholder="חפשי נעליים, צבע, דגם…" aria-label="חיפוש" autoComplete="off" enterKeyHint="search" />
          <button type="button" className="srch__close" aria-label="סגירה" onClick={onClose}>×</button>
        </form>
        <div className="srch__body" aria-live="polite">
          {state.loading && <p className="srch__msg">מחפשת…</p>}
          {!state.loading && state.done && !state.items.length && <p className="srch__msg">לא נמצאו תוצאות ל“{term}”. נסי מילה אחרת.</p>}
          {!state.loading && term.length < 2 && <p className="srch__msg">התחילי להקליד – התוצאות יופיעו מיד.</p>}
          <ul className="srch__list">
            {state.items.map((p) => (
              <li key={p.id}>
                <Link to={toPath(p.permalink)} onClick={onClose}>
                  {p.images?.[0] && <img src={p.images[0].thumbnail || p.images[0].src} alt="" width="64" height="64" loading="lazy" />}
                  <span className="srch__name" dangerouslySetInnerHTML={{ __html: p.name }} />
                  <Price prices={p.prices} single />
                </Link>
              </li>
            ))}
          </ul>
          {state.total > state.items.length && <a className="srch__all" href={`${base}?s=${encodeURIComponent(term)}&post_type=product`}>לכל {state.total} התוצאות</a>}
        </div>
      </div>
    </div>
  );
}
