import React, { useEffect, useRef } from 'react';
import { useCart } from '../lib/CartContext.jsx';
import '../styles/basket.css';

/** Add-to-cart confirmation: the product drops into the basket of the Paris shopper. */
export default function BasketModal() {
  const { added, closeAdded, count, setOpen } = useCart();
  const closeRef = useRef(null);

  useEffect(() => {
    if (!added) return undefined;
    const onKey = (e) => e.key === 'Escape' && closeAdded();
    const prev = document.activeElement;
    document.addEventListener('keydown', onKey);
    document.body.style.overflow = 'hidden';
    closeRef.current?.focus();
    return () => { document.removeEventListener('keydown', onKey); document.body.style.overflow = ''; prev?.focus?.(); };
  }, [added, closeAdded]);

  if (!added) return null;
  const photo = `${window.MARCHA?.themeUrl || ''}/assets/paris-basket.webp`;
  return (
    <div className="bm" role="dialog" aria-modal="true" aria-labelledby="bm-title">
      <button type="button" className="bm__overlay" aria-label="סגרי" tabIndex="-1" onClick={closeAdded} />
      <div className="bm__card">
        <button ref={closeRef} type="button" className="bm__close" onClick={closeAdded} aria-label="סגרי">×</button>
        <div className="bm__scene" key={added.at} aria-hidden="true">
          <img className="bm__photo" src={photo} alt="" width="683" height="1024" />
          {added.image && <img className="bm__fly" src={added.image} alt="" />}
          <span className="bm__pop">+1</span>
        </div>
        <div className="bm__body">
          <h2 id="bm-title">נוסף לסל! 🧺</h2>
          <p className="bm__name"><span dangerouslySetInnerHTML={{ __html: added.name }} />{added.size && <b> · מידה {added.size}</b>}</p>
          <p className="bm__count" aria-live="polite">{count} {count === 1 ? 'פריט' : 'פריטים'} בסל</p>
          <a className="btn btn--mint" href={window.MARCHA?.checkoutUrl}>לתשלום</a>
          <a className="btn btn--black" href={window.MARCHA?.cartUrl}>צפה בעגלה</a>
          <button type="button" className="bm__more" onClick={() => { closeAdded(); setOpen(false); }}>המשך קנייה</button>
        </div>
      </div>
    </div>
  );
}
