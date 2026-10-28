import React, { useState } from 'react';
import { useCart } from '../lib/CartContext.jsx';
import { money } from '../lib/format.js';
import '../styles/header.css';

const USPS = ['25,000+ לקוחות מרוצות', 'שירות מהיר', 'משלוח מהיר עד הבית'];
const MENU = [
  { label: 'בלרינה עגול לנשים וילדות', href: 'product-category/balarina-a/' },
  { label: 'בלרינה שפיץ', href: 'product-category/בלרינה-שפיץ/' },
  { label: 'מגפיים', href: 'product-category/boots/' },
  { label: 'אודות', href: 'אודות/' },
  { label: 'צרי קשר', href: 'צרי-קשר/' },
];

export default function Header() {
  const { cart, count, setOpen } = useCart();
  const [menu, setMenu] = useState(false);
  const base = window.MARCHA?.siteUrl || '/';
  return (
    <header className="hdr">
      <div className="hdr__promo">מבצע! 3 נעלי בובה ב300 ₪</div>
      <ul className="hdr__usps">{USPS.map((u) => <li key={u}>{u}</li>)}</ul>
      <div className="hdr__bar container">
        <button className="hdr__burger" aria-label="תפריט" aria-expanded={menu} onClick={() => setMenu(!menu)}><span /><span /><span /></button>
        <a className="hdr__logo" href={base} aria-label="מרשה בלרינה – דף הבית">
          <img src={`${window.MARCHA?.uploadsUrl || ''}/2025/03/Marchalogo-1-300x111.png`} alt="Marcha Ballerina" width="150" height="55" />
        </a>
        <button className="hdr__cart" aria-label="פתחי עגלה" onClick={() => setOpen(true)}>
          <span>{cart ? money(cart.totals.total_items, cart.totals) : ''}</span>
          <b className="hdr__count">{count}</b>
        </button>
      </div>
      <nav className={`hdr__menu${menu ? ' is-open' : ''}`} aria-label="ראשי">
        {MENU.map((m) => <a key={m.href} href={base + m.href}>{m.label}</a>)}
      </nav>
    </header>
  );
}
