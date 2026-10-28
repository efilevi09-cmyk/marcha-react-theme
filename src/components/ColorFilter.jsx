import React from 'react';
import { COLOR_HEX } from '../lib/config.js';
import '../styles/shop.css';

/** Colour swatches. `selected` holds term ids; `available` (optional Set of ids) hides colours with no products. */
export default function ColorFilter({ terms, selected, onToggle, onReset, available, label = 'סינון לפי צבע' }) {
  const list = terms.filter((t) => COLOR_HEX[t.name.trim()] && (!available || available.has(t.id)));
  if (!list.length) return null;
  return (
    <div className="filters filters--inline" role="group" aria-label={label}>
      <h3 className="filter__title">{label}</h3>
      <div className="filter__row">
        <ul className="swatches">
          {list.map((t) => (
            <li key={t.id}>
              <button type="button" className={`swatch${selected.includes(t.id) ? ' is-on' : ''}`} style={{ '--sw': COLOR_HEX[t.name.trim()] }} aria-pressed={selected.includes(t.id)} aria-label={t.name} title={t.name} onClick={() => onToggle(t.id)} />
            </li>
          ))}
        </ul>
        <button type="button" className="reset" onClick={onReset} disabled={!selected.length}>איפוס הסינון</button>
      </div>
    </div>
  );
}
