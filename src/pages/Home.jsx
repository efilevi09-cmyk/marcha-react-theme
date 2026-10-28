import React, { useEffect, useState } from 'react';
import * as api from '../lib/api.js';
import ProductCard from '../components/ProductCard.jsx';
import { ReviewList } from '../components/Reviews.jsx';
import '../styles/home.css';

const up = (path) => encodeURI(`${window.MARCHA?.uploadsUrl || ''}/${path}`);
const PATTERN = up('2025/03/מרשה-בלרינה-נעלים-1.png');

export default function Home() {
  const [hot, setHot] = useState([]);
  const [best, setBest] = useState([]);
  const base = window.MARCHA?.siteUrl || '/';
  useEffect(() => {
    api.getProducts({ per_page: 8, orderby: 'date' }).then(setHot).catch(() => {});
    api.getProducts({ per_page: 8, orderby: 'popularity' }).then(setBest).catch(() => {});
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
      <section className="block container about">
        <p className="script-title" aria-hidden="true">About</p>
        <h2 className="section-title">מרשה מותג בין לאומי מאז 2005</h2>
        <p><strong>מרשה בלרינה התחילה</strong> את דרכה בתור קונספט בין לאומי ראשוני וייחודי של דוכני נעלי בלרינה קלאסיות ואופנתיות המעוצבות בהשראה פריזאית.</p>
        <img className="about__img" src={up('2025/03/אודות-מרשה.png')} alt="מרשה בלרינה" loading="lazy" />
        <a className="btn btn--black" href={base + 'אודות/'}>עוד עלינו</a>
      </section>
      <section className="branches" style={{ backgroundImage: `url(${up('2025/03/הסניפים-שלנו.png')})` }}>
        <h2 className="section-title">הסניפים שלנו</h2>
        <p>סניף ירושלים – קניון רב שפע</p>
        <p>סניף בני ברק – רחוב ירושלים 10</p>
      </section>
    </>
  );
}
