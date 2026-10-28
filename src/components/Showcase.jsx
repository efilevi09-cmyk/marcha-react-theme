import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Link } from 'react-router-dom';

const DELAY = 6000;

/**
 * Hero slider. Images keep their native 16:9 ratio at every width (never cropped);
 * slides are moved with a transform inside an LTR track so swiping works the same in RTL pages.
 */
export default function Showcase({ slides, up }) {
  const [i, setI] = useState(0);
  const [paused, setPaused] = useState(false);
  const drag = useRef(null);
  const n = slides.length;
  const go = useCallback((k) => setI(((k % n) + n) % n), [n]);

  useEffect(() => {
    if (paused || n < 2 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return undefined;
    const t = setTimeout(() => go(i + 1), DELAY);
    return () => clearTimeout(t);
  }, [i, paused, n, go]);

  const onDown = (e) => { drag.current = e.clientX; };
  const onUp = (e) => {
    if (drag.current == null) return;
    const dx = e.clientX - drag.current;
    drag.current = null;
    if (Math.abs(dx) > 50) go(i + (dx < 0 ? 1 : -1)); // swipe left = next
  };

  return (
    <section
      className="showcase"
      aria-roledescription="carousel"
      aria-label="הקולקציות שלנו"
      onMouseEnter={() => setPaused(true)}
      onMouseLeave={() => setPaused(false)}
      onFocus={() => setPaused(true)}
      onBlur={() => setPaused(false)}
    >
      <div className="showcase__viewport" dir="ltr" onPointerDown={onDown} onPointerUp={onUp} onPointerCancel={() => { drag.current = null; }}>
        <div className="showcase__track" style={{ transform: `translateX(${-i * 100}%)` }}>
          {slides.map((b, k) => (
            <article key={b.slug} dir="rtl" className={`showcase__item showcase__item--${b.side}`} role="group" aria-roledescription="slide" aria-label={`${k + 1} מתוך ${n}`} aria-hidden={k !== i}>
              <img src={up(b.img)} alt={b.alt} width="1376" height="768" decoding="async" loading={k === 0 ? 'eager' : 'lazy'} fetchpriority={k === 0 ? 'high' : undefined} draggable="false" />
              <div className="showcase__text">
                <p className="showcase__eyebrow" aria-hidden="true">{b.eyebrow}</p>
                <h2>{b.title}</h2>
                <p>{b.text}</p>
                <Link className="btn btn--black" to={b.to} tabIndex={k === i ? 0 : -1}>{b.cta}</Link>
              </div>
            </article>
          ))}
        </div>
      </div>
      {n > 1 && (
        <>
          <button className="showcase__arrow showcase__arrow--prev" aria-label="הקודם" onClick={() => go(i - 1)}>‹</button>
          <button className="showcase__arrow showcase__arrow--next" aria-label="הבא" onClick={() => go(i + 1)}>›</button>
          <div className="showcase__dots" role="tablist" aria-label="בחירת שקופית">
            {slides.map((b, k) => <button key={b.slug} role="tab" aria-selected={k === i} aria-label={`שקופית ${k + 1}`} className={k === i ? 'is-on' : ''} onClick={() => go(k)} />)}
          </div>
        </>
      )}
    </section>
  );
}
