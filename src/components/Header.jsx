import React, { useState } from 'react';
import { useCart } from '../lib/CartContext.jsx';
import { money } from '../lib/format.js';
import { BasketIcon, FacebookIcon, InstagramIcon, PhoneIcon, SearchIcon, StarIcon, TruckIcon } from './Icons.jsx';
import Search from './Search.jsx';
import '../styles/header.css';

const USPS = [[StarIcon, '25,000+ לקוחות מרוצות'], [PhoneIcon, 'שירות מהיר'], [TruckIcon, 'משלוח מהיר עד הבית']];
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
  const [searching, setSearching] = useState(false);
  const base = window.MARCHA?.siteUrl || '/';
  return (
    <header className="hdr">
      <div className="hdr__promo">מבצע! 3 נעלי בובה ב300 ₪</div>
      <ul className="hdr__usps">{USPS.map(([Icon, t]) => <li key={t}><Icon width={22} height={22} />{t}</li>)}</ul>
      <div className="hdr__bar container">
        <div className="hdr__side">
          <a className="hdr__icon" href="https://www.facebook.com/MarchaBallerina.Israel/" aria-label="Facebook" target="_blank" rel="noopener noreferrer"><FacebookIcon /></a>
          <a className="hdr__icon" href="https://www.instagram.com/marchaballerina/" aria-label="Instagram" target="_blank" rel="noopener noreferrer"><InstagramIcon /></a>
          <button className="hdr__icon hdr__search" aria-label="חיפוש" aria-haspopup="dialog" onClick={() => setSearching(true)}><SearchIcon /></button>
          <button className="hdr__burger" aria-label="תפריט" aria-expanded={menu} onClick={() => setMenu(!menu)}><span /><span /><span /></button>
        </div>
        <a className="hdr__logo" href={base} aria-label="מרשה בלרינה – דף הבית">
          <img src={`${window.MARCHA?.uploadsUrl || ''}/2025/03/Marchalogo-1-300x111.png`} alt="Marcha Ballerina" width="150" height="55" />
        </a>
        <button className="hdr__cart" aria-label="פתחי עגלה" onClick={() => setOpen(true)}>
          <span className="hdr__bag"><BasketIcon /><b className="hdr__count">{count}</b></span>
          <span className="hdr__total">{cart ? money(String(Number(cart.totals.total_items) + Number(cart.totals.total_fees || 0)), cart.totals) : ''}</span>
        </button>
      </div>
      <nav className={`hdr__menu${menu ? ' is-open' : ''}`} aria-label="ראשי">
        {MENU.map((m) => <a key={m.href} href={base + m.href}>{m.label}</a>)}
      </nav>
      <Search open={searching} onClose={() => setSearching(false)} />
    </header>
  );
}
