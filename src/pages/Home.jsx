import React, { useEffect, useState } from 'react';
import * as api from '../lib/api.js';
import ProductCard from '../components/ProductCard.jsx';
import '../styles/home.css';

const up = (path) => `${window.MARCHA?.uploadsUrl || ''}/${path}`;

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
      <section className="hero" style={{ backgroundImage: `url(${up('2026/03/מרשה-1.webp')})` }}>
        <h1>מרשה בלרינה</h1>
        <p className="hero__sub">3 זוגות נעלי בובה ב300 ₪</p>
        <div className="hero__cta">
          <a className="btn btn--mint" href={base + 'product-category/balarina-a/'}>נעלי בלרינה לילדות ונשים עגול</a>
          <a className="btn btn--black" href={base + 'product-category/בלרינה-שפיץ/'}>נעלי בלרינה שפיץ</a>
        </div>
      </section>
      <section className="block container">
        <p className="script-title" aria-hidden="true">pariz</p>
        <h2 className="section-title">הכי חם</h2>
        <div className="grid">{hot.map((p) => <ProductCard key={p.id} p={p} />)}</div>
      </section>
      <section className="block container">
        <p className="script-title" aria-hidden="true">Your style</p>
        <h2 className="section-title">קטגוריות</h2>
        <div className="cats">
          <a href={base + 'product-category/balarina-a/'}><b>בלרינה עגול לנשים וילדות</b><span>מידות - 22-43</span></a>
          <a href={base + 'product-category/בלרינה-שפיץ/'}><b>בלרינה שפיץ לנשים</b><span>מידות - 35-43</span></a>
        </div>
      </section>
      <section className="block container">
        <p className="script-title" aria-hidden="true">Warm colors</p>
        <h2 className="section-title">הכי נמכרים</h2>
        <div className="grid">{best.map((p) => <ProductCard key={p.id} p={p} />)}</div>
      </section>
      <section className="block container about">
        <p className="script-title" aria-hidden="true">About</p>
        <h2 className="section-title">מרשה מותג בין לאומי מאז 2005</h2>
        <p><strong>מרשה בלרינה התחילה</strong> את דרכה בתור קונספט בין לאומי ראשוני וייחודי של דוכני נעלי בלרינה קלאסיות ואופנתיות המעוצבות בהשראה פריזאית.</p>
        <a className="btn btn--black" href={base + 'אודות/'}>עוד עלינו</a>
      </section>
    </>
  );
}
