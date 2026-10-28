import React, { useEffect, useState } from 'react';
import * as api from '../lib/api.js';
import ProductCard from '../components/ProductCard.jsx';
import { Link } from 'react-router-dom';
import { ReviewList } from '../components/Reviews.jsx';
import '../styles/home.css';

const up = (path) => encodeURI(`${window.MARCHA?.uploadsUrl || ''}/${path}`);
const SHOWCASE = [
  { slug: 'boots', side: 'left', img: '2026/10/marcha-hero-boots.jpg', alt: 'שתי נשים בפריז בגשם נועלות מגפי גומי קצרים של מרשה בלרינה', eyebrow: 'Rainy Paris', title: 'מגפי גומי לחורף', text: 'מגפי צ׳לסי קצרים ואלגנטיים – שחור מבריק, קטיפה ואפור. יוצאים לגשם בסטייל.', cta: 'למגפיים', to: '/product-category/boots/' },
  { slug: 'flats', side: 'right', img: '2026/10/marcha-hero-flats.jpg', alt: 'אישה בבית קפה פריזאי נועלת נעלי בובה ורודות של מרשה בלרינה', eyebrow: 'Café de Paris', title: 'נעלי בובה לכל יום', text: '66 דגמים עגולים בצבעים שאי אפשר לבחור ביניהם – ועוד 3 זוגות ב-300 ₪.', cta: 'לנעלי הבובה', to: '/product-category/balarina-a/' },
];
const BRANCHES = [
  { name: 'סניף ירושלים', address: 'קניון רב שפע', query: 'קניון רב שפע ירושלים' },
  { name: 'סניף בני ברק', address: 'רחוב ירושלים 10', query: 'ירושלים 10 בני ברק' },
];
const PATTERN = up('2025/03/מרשה-בלרינה-נעלים-1.png');

export default function Home() {
  const [hot, setHot] = useState([]);
  const [best, setBest] = useState([]);
  const [ig, setIg] = useState([]);
  const base = window.MARCHA?.siteUrl || '/';
  useEffect(() => {
    api.getProducts({ per_page: 8, orderby: 'date' }).then(setHot).catch(() => {});
    api.getProducts({ per_page: 8, orderby: 'popularity' }).then(setBest).catch(() => {});
  api.getInstagram().then((f) => setIg(f.items || [])).catch(() => {});
  }, []);
  return (
    <>
      <section className="hero" style={{ '--hero-d': `url(${up('2026/03/מרשה-1.webp')})`, '--hero-m': `url(${up('2026/03/נעלי-בלרינה-עגולות-מרשה.webp')})` }}>
        <h1>מרשה בלרינה</h1>
        <p className="hero__sub">3 זוגות נעלי בובה ב300 ₪</p>
        <div className="hero__cta">
          <a className="btn btn--mint" href={base + 'product-category/balarina-a/'}>נעלי בלרינה לילדות ונשים עגול</a>
          <a className="btn btn--black" href={base + 'product-category/בלרינה-שפיץ/'}>נעלי בלרינה שפיץ</a>
        </div>
      </section>
      <section className="showcase" aria-label="הקולקציות שלנו">
        {SHOWCASE.map((b) => (
          <article key={b.slug} className={`showcase__item showcase__item--${b.side}`}>
            <img src={up(b.img)} alt={b.alt} width="1376" height="768" loading="lazy" decoding="async" />
            <div className="showcase__text">
              <p className="showcase__eyebrow" aria-hidden="true">{b.eyebrow}</p>
              <h2>{b.title}</h2>
              <p>{b.text}</p>
              <Link className="btn btn--black" to={b.to}>{b.cta}</Link>
            </div>
          </article>
        ))}
      </section>
      <section className="block block--pattern" style={{ '--pattern': `url(${PATTERN})` }}>
        <div className="container">
          <p className="script-title" aria-hidden="true">pariz</p>
          <h2 className="section-title">הכי חם</h2>
          <div className="grid">
            <figure className="grid__feature"><img src={up('2026/03/נעל-תכלת.webp')} alt="נעלי בלרינה בצבע תכלת" loading="lazy" /></figure>
            {hot.map((p) => <ProductCard key={p.id} p={p} />)}
          </div>
        </div>
      </section>
      <section className="block container">
        <p className="script-title" aria-hidden="true">Your style</p>
        <h2 className="section-title">קטגוריות</h2>
        <div className="cats">
          <a href={base + 'product-category/balarina-a/'} style={{ backgroundImage: `url(${up('2026/03/נעליים-כתומות.webp')})` }}><b>בלרינה עגול לנשים וילדות</b><span>מידות - 22-43</span></a>
          <a href={base + 'product-category/בלרינה-שפיץ/'} style={{ backgroundImage: `url(${up('2026/03/נעלי-שפיץ-1.webp')})` }}><b>בלרינה שפיץ לנשים</b><span>מידות - 35-43</span></a>
        </div>
      </section>
      <section className="container block">
        <a className="story" href={base + 'product/gerbera-woman/'}>
          <img src={up('2026/04/כל-צעד-הוא-סיפור-יפה.webp')} alt="כל צעד הוא סיפור יפה" loading="lazy" />
        </a>
      </section>
      <section className="block block--pattern" style={{ '--pattern': `url(${PATTERN})` }}>
        <div className="container">
          <p className="script-title" aria-hidden="true">Warm colors</p>
          <h2 className="section-title">הכי נמכרים</h2>
          <div className="grid">{best.map((p) => <ProductCard key={p.id} p={p} />)}</div>
        </div>
      </section>
      <section className="block container home-reviews" aria-labelledby="reviews-title">
        <p className="script-title" aria-hidden="true">Reviews</p>
        <h2 className="section-title" id="reviews-title">מה הלקוחות אומרות</h2>
        <ReviewList perPage={6} />
      </section>
      <section className="block container about" aria-labelledby="about-title">
        <p className="script-title" aria-hidden="true">About</p>
        <h2 className="section-title" id="about-title">מרשה מותג בין לאומי מאז 2005</h2>
        <div className="about__text">
          <p className="about__lead">נעלי בלרינה<br />עם נשמה פריזאית.</p>
          <p>מרשה בלרינה נולדה מאהבה<br />לסטייל, לנשיות ולנוחות.</p>
          <p>התחלנו כדוכנים וחנויות<br />בהשראה פריזאית.</p>
          <p>עשרות סניפים ברחבי העולם:<br />הונג קונג, הפיליפינים,<br />פראג, דובאי ודרום אפריקה.</p>
          <p>היום הקסם הזה מגיע<br />ישר אלייך הביתה.</p>
          <p>נעליים לכל אישה, נערה וילדה,<br />במידות 22 עד 42,<br />בצבעים וסגנונות לכל טעם.</p>
          <p>אלגנטיות, נוחות ואיכות<br />ללא פשרות.</p>
          <p className="about__quote">כל צעד הוא ריקוד קטן של שמחה.</p>
          <a className="btn btn--black" href={base + 'אודות/'}>עוד עלינו</a>
        </div>
        <div className="about__media">
          <img className="about__img" src={up('2025/03/אודות-מרשה.png')} alt="מרשה בלרינה" loading="lazy" />
          {ig.slice(0, 2).map((it) => <img key={it.src} className="about__thumb" src={it.src} alt="" loading="lazy" />)}
        </div>
        <ul className="about__stats">
          <li><b>2005</b><span>שנת הקמה</span></li>
          <li><b>עשרות</b><span>סניפים בעולם</span></li>
          <li><b>22–42</b><span>מידות</span></li>
          <li><b>25,000+</b><span>לקוחות מרוצות</span></li>
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
