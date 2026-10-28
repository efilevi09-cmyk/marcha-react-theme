import React, { useCallback, useEffect, useRef, useState } from 'react';

/** Single-row horizontal scroller (scroll-snap, touch swipe, arrows). Works in RTL pages. */
export default function HScroller({ children, label, className = '' }) {
  const ref = useRef(null);
  const [edge, setEdge] = useState({ start: true, end: false });
  const update = useCallback(() => {
    const el = ref.current;
    if (!el) return;
    const max = el.scrollWidth - el.clientWidth;
    const pos = Math.abs(el.scrollLeft); // negative in RTL
    setEdge({ start: pos < 4, end: pos > max - 4 });
  }, []);
  useEffect(() => {
    update();
    const el = ref.current;
    const ro = new ResizeObserver(update);
    ro.observe(el);
    return () => ro.disconnect();
  }, [update, children]);
  const move = (dir) => {
    const el = ref.current;
    const rtl = getComputedStyle(el).direction === 'rtl';
    el.scrollBy({ left: dir * (rtl ? -1 : 1) * el.clientWidth * 0.85, behavior: 'smooth' });
  };
  return (
    <div className={`hs ${className}`}>
      <button type="button" className="hs__btn hs__btn--prev" aria-label="הקודמים" disabled={edge.start} onClick={() => move(-1)}>›</button>
      <div className="hs__track" ref={ref} onScroll={update} role="region" aria-label={label} tabIndex={0}>{children}</div>
      <button type="button" className="hs__btn hs__btn--next" aria-label="הבאים" disabled={edge.end} onClick={() => move(1)}>‹</button>
    </div>
  );
}
