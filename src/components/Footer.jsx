import React from 'react';
import { FacebookIcon, InstagramIcon } from './Icons.jsx';
import '../styles/footer.css';

const SITEMAP = [['דף בית', ''], ['אודות', 'אודות/'], ['החלפות והחזרות', 'החלפות-והחזרות/'], ['הצהרת נגישות', 'הצהרת-נגישות/'], ['ווישליסט', 'ווישליסט/'], ['מדיניות פרטיות', 'privacy-policy/'], ['צרי קשר', 'צרי-קשר/']];

export default function Footer() {
  const base = window.MARCHA?.siteUrl || '/';
  return (
    <footer className="ftr">
      <div className="container ftr__grid">
        <section><h2>מפת האתר</h2><ul>{SITEMAP.map(([l, h]) => <li key={h}><a href={base + h}>{l}</a></li>)}</ul></section>
        <section><h2>קטגוריות</h2><ul>
          <li><a href={base + 'product-category/balarina-a/'}>בלרינה עגול לנשים וילדות</a><small> מידות - 22-43</small></li>
          <li><a href={base + 'product-category/בלרינה-שפיץ/'}>בלרינה שפיץ לנשים</a><small> מידות - 35-43</small></li>
        </ul></section>
        <section><h2>הסניפים שלנו</h2><ul><li>סניף ירושלים – קניון רב שפע</li><li>סניף בני ברק – רחוב ירושלים 10</li></ul></section>
        <section><h2>עקבי אחרינו</h2><p className="ftr__social"><a href="https://www.facebook.com/MarchaBallerina.Israel/" aria-label="Facebook" target="_blank" rel="noopener noreferrer"><FacebookIcon /></a><a href="https://www.instagram.com/marchaballerina/" aria-label="Instagram" target="_blank" rel="noopener noreferrer"><InstagramIcon /></a></p><p>תשלום מאובטח באשראי ובביט</p><p>שירות אישי ינתן באהבה <a href="https://api.whatsapp.com/send?phone=0559296934" target="_blank" rel="noopener noreferrer">בווצאפ</a></p></section>
      </div>
      <p className="ftr__credit">אפיון עיצוב ופיתוח אפרת אסולין</p>
    </footer>
  );
}
