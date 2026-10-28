import React, { useEffect, useState } from 'react';
import * as api from '../lib/api.js';
import Showcase from '../components/Showcase.jsx';
import ProductSection from '../components/ProductSection.jsx';
import { ReviewList } from '../components/Reviews.jsx';
import '../styles/home.css';

const up = (path) => encodeURI(`${window.MARCHA?.uploadsUrl || ''}/${path}`);
const SHOWCASE = [
  { slug: 'flats', side: 'left', img: '2026/10/marcha-hero-flats2-wide.jpg', imgM: '2026/10/marcha-hero-flats2-tall.jpg', alt: 'אישה בשמלה צנועה בבית קפה פריזאי נועלת נעלי בובה ורודות של מרשה בלרינה', eyebrow: 'Café de Paris', title: 'נעלי בובה לכל יום', text: '66 דגמים עגולים בצבעים שאי אפשר לבחור ביניהם – ועוד 3 זוגות ב-300 ₪.', cta: 'לנעלי הבובה', to: '/product-category/balarina-a/' },
  { slug: 'boots', side: 'left', img: '2026/10/marcha-hero-boots-wide.jpg', imgM: '2026/10/marcha-hero-boots-tall.jpg', alt: 'שתי נשים בפריז בגשם נועלות מגפי גומי קצרים של מרשה בלרינה', eyebrow: 'Rainy Paris', title: 'מגפי גומי לחורף', text: 'מגפי צ׳לסי קצרים ואלגנטיים – שחור מבריק, קטיפה ואפור. יוצאים לגשם בסטייל.', cta: 'למגפיים', to: '/product-category/boots/' },
];
const CATS = [
  { href: 'product-category/balarina-a/', img: '2026/10/marcha-cat-round.jpg', alt: 'אישה ברחוב פריזאי נועלת נעלי בלרינה עגולות כתומות', title: 'בלרינה עגול לנשים וילדות', sizes: 'מידות 22-43' },
  { href: 'product-category/בלרינה-שפיץ/', img: '2026/10/marcha-cat-pointed.jpg', alt: 'אישה בבית קפה פריזאי נועלת נעלי בלרינה שפיץ בז׳', title: 'בלרינה שפיץ לנשים', sizes: 'מידות 35-43' },
  { href: 'product-category/boots/', img: '2026/10/marcha-cat-boots.jpg', alt: 'אישה בגשם בפריז נועלת מגפי גומי שחורים', title: 'מגפי גומי', sizes: 'חורף 2026' },
];
const BRANCHES = [
  { name: 'סניף ירושלים', address: 'קניון רב שפע', query: 'קניון רב שפע ירושלים' },
  { name: 'סניף בני ברק', address: 'רחוב ירושלים 10', query: 'ירושלים 10 בני ברק' },
];
const PATTERN = up('2025/03/מרשה-בלרינה-נעלים-1.png');

export default function Home() {
  const [terms, setTerms] = useState([]);
  const [ig, setIg] = useState([]);
  const base = window.MARCHA?.siteUrl || '/';
  useEffect(() => {
    api.getColorTerms().then(setTerms).catch(() => {});
  api.getInstagram().then((f) => setIg(f.items || [])).catch(() => {});
  }, []);
  return (
    <>
      <h1 className="sr-only">מרשה בלרינה – נעלי בובה ובלרינה</h1>
      <Showcase slides={SHOWCASE} up={up} />
      <ProductSection id="hot-title" title="הכי חם" script="pariz" orderby="popularity" terms={terms} pattern={PATTERN} />
      <section className="block container">
        <p className="script-title" aria-hidden="true">Your style</p>
        <h2 className="section-title">קטגוריות</h2>
        <div className="cats">
          {CATS.map((c) => (
            <a key={c.img} href={base + c.href} aria-label={`${c.title} – ${c.sizes}`}>
              <img src={up(c.img)} alt={c.alt} width="1376" height="768" loading="lazy" decoding="async" />
              <span className="cats__text"><b>{c.title}</b><span>{c.sizes}</span></span>
            </a>
          ))}
        </div>
      </section>
      <ProductSection id="new-title" title="חדש באתר" script="New in" orderby="date" terms={terms} pattern={PATTERN} />
      <section className="block container home-reviews" aria-labelledby="reviews-title">
        <p className="script-title" aria-hidden="true">Reviews</p>
        <h2 className="section-title" id="reviews-title">מה הלקוחות אומרות</h2>
        <ReviewList perPage={6} />
      </section>
      <section className="block container about" aria-labelledby="about-title">
        <p className="script-title" aria-hidden="true">About</p>
        <h2 className="section-title" id="about-title">מרשה מותג בין לאומי מאז 2005</h2>
        <div className="about__text">
          <p className="about__lead">נעלי בלרינה עם נשמה פריזאית.</p>
          <p>מרשה בלרינה נולדה מאהבה לסטייל, לנשיות ולנוחות. התחלנו כדוכנים וחנויות בהשראה פריזאית, והיום יש לנו עשרות סניפים ברחבי העולם: הונג קונג, הפיליפינים, פראג, דובאי ודרום אפריקה.</p>
          <p>היום הקסם הזה מגיע ישר אלייך הביתה – נעליים לכל אישה, נערה וילדה, במידות 22 עד 42, בצבעים וסגנונות לכל טעם.</p>
          <p>אלגנטיות, נוחות ואיכות ללא פשרות.</p>
          <p className="about__quote">כל צעד הוא ריקוד קטן של שמחה.</p>
          <a className="btn btn--black" href={base + 'אודות/'}>עוד עלינו</a>
        </div>
        <div className="about__media">
          <img className="about__img" src={up('2025/03/אודות-מרשה.png')} alt="מרשה בלרינה" loading="lazy" />
          {ig.slice(0, 2).map((it) => <img key={it.src} className="about__thumb" src={it.src} alt="" loading="lazy" />)}
        </div>
        <ul className="about__stats">
          <li><b>2005</b><span>שנת הקמה</span></li>
          <li><b><bdi dir="ltr">4.9 ★</bdi></b><span>דירוג מ-165 ביקורות</span></li>
          <li><b><bdi dir="ltr">22–42</bdi></b><span>מידות</span></li>
          <li><b><bdi dir="ltr">25,000+</bdi></b><span>לקוחות מרוצות</span></li>
        </ul>
      </section>
      <section className="branches" aria-labelledby="branches-title" style={{ '--branches-d': `url(${up('2026/03/מרשה-1.webp')})`, '--branches-m': `url(${up('2026/03/נעלי-בלרינה-עגולות-מרשה.webp')})` }}>
        <div className="branches__inner">
          <p className="script-title" aria-hidden="true">Visit us</p>
          <h2 className="section-title" id="branches-title">הסניפים שלנו</h2>
          <ul className="branches__list">
            {BRANCHES.map((b) => (
              <li key={b.name}>
                <h3>{b.name}</h3>
                <p>{b.address}</p>
                <a className="btn btn--mint" href={`https://waze.com/ul?q=${encodeURIComponent(b.query)}&navigate=yes`} target="_blank" rel="noopener noreferrer">ניווט בוויז</a>
              </li>
            ))}
          </ul>
        </div>
      </section>
    </>
  );
}
