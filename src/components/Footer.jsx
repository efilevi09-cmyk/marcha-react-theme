import React, { useEffect, useState } from 'react';
import * as api from '../lib/api.js';
import { FacebookIcon, InstagramIcon } from './Icons.jsx';
import '../styles/footer.css';

const SITEMAP = [['דף בית', ''], ['אודות', 'אודות/'], ['החלפות והחזרות', 'החלפות-והחזרות/'], ['הצהרת נגישות', 'הצהרת-נגישות/'], ['ווישליסט', 'ווישליסט/'], ['מדיניות פרטיות', 'privacy-policy/'], ['צרי קשר', 'צרי-קשר/']];

/** Instagram strip: 4x2 square grid of the latest posts (thumbnails served from our own media library). */
function InstagramStrip() {
  const [feed, setFeed] = useState(null);
  useEffect(() => { api.getInstagram().then(setFeed).catch(() => {}); }, []);
  if (!feed?.items?.length) return null;
  return (
    <section className="ig" aria-labelledby="ig-title">
      <h2 id="ig-title" className="ig__title" dir="ltr">STAY UPDATED ON INSTAGRAM</h2>
      <p className="ig__handle" dir="ltr"><a href={`https://www.instagram.com/${feed.handle}/`} target="_blank" rel="noopener noreferrer">@{feed.handle}</a></p>
      <ul className="ig__grid">
        {feed.items.slice(0, 8).map((it, i) => (
          <li key={it.url}>
            <a href={it.url} target="_blank" rel="noopener noreferrer" aria-label={`פוסט ${i + 1} באינסטגרם`}>
              <img src={it.src} width={it.w} height={it.h} alt="" loading="lazy" />
              <InstagramIcon className="ig__icon" />
            </a>
          </li>
        ))}
      </ul>
    </section>
  );
}

export default function Footer() {
  const base = window.MARCHA?.siteUrl || '/';
  return (
    <>
    <InstagramStrip />
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
    </>
  );
}
