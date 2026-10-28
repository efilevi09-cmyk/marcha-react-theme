import React, { useEffect, useRef, useState } from 'react';
import { useCart } from '../lib/CartContext.jsx';
import { WHATSAPP_URL } from '../lib/config.js';
import { AccessIcon, BasketIcon, WhatsAppIcon } from './Icons.jsx';
import '../styles/floating.css';

const STORE_KEY = 'marcha_a11y';
const DEFAULTS = { size: 0, contrast: false, gray: false, links: false, font: false, motion: false };
const read = () => { try { return { ...DEFAULTS, ...JSON.parse(localStorage.getItem(STORE_KEY) || '{}') }; } catch (e) { return DEFAULTS; } };

/** Apply the saved accessibility choices as classes/vars on <html>. */
function apply(s) {
  const r = document.documentElement;
  r.style.setProperty('--a11y-scale', String(1 + s.size * 0.1));
  r.classList.toggle('a11y-contrast', s.contrast);
  r.classList.toggle('a11y-gray', s.gray);
  r.classList.toggle('a11y-links', s.links);
  r.classList.toggle('a11y-font', s.font);
  r.classList.toggle('a11y-motion', s.motion);
}

function AccessibilityPanel({ onClose }) {
  const [s, setS] = useState(read);
  const ref = useRef(null);
  useEffect(() => { apply(s); try { localStorage.setItem(STORE_KEY, JSON.stringify(s)); } catch (e) { /* ignore */ } }, [s]);
  useEffect(() => {
    ref.current?.querySelector('button')?.focus();
    const onKey = (e) => e.key === 'Escape' && onClose();
    document.addEventListener('keydown', onKey);
    return () => document.removeEventListener('keydown', onKey);
  }, [onClose]);
  const toggle = (k) => setS((cur) => ({ ...cur, [k]: !cur[k] }));
  const size = (d) => setS((cur) => ({ ...cur, size: Math.max(-1, Math.min(4, cur.size + d)) }));
  const opts = [['contrast', 'ניגודיות גבוהה'], ['gray', 'גווני אפור'], ['links', 'הדגשת קישורים'], ['font', 'גופן קריא'], ['motion', 'עצירת אנימציות']];
  return (
    <div className="a11y" role="dialog" aria-label="תפריט נגישות" ref={ref}>
      <div className="a11y__head"><b>נגישות</b><button type="button" onClick={onClose} aria-label="סגרי תפריט נגישות">×</button></div>
      <div className="a11y__row" role="group" aria-label="גודל טקסט">
        <button type="button" onClick={() => size(-1)} aria-label="הקטנת טקסט">A−</button>
        <span aria-live="polite">{Math.round((1 + s.size * 0.1) * 100)}%</span>
        <button type="button" onClick={() => size(1)} aria-label="הגדלת טקסט">A+</button>
      </div>
      <div className="a11y__grid">
        {opts.map(([k, label]) => <button key={k} type="button" aria-pressed={s[k]} className={s[k] ? 'is-on' : ''} onClick={() => toggle(k)}>{label}</button>)}
      </div>
      <button type="button" className="a11y__reset" onClick={() => setS(DEFAULTS)}>איפוס הגדרות</button>
    </div>
  );
}

/** Fixed tools: cart (left), accessibility (left), WhatsApp service (right). */
export default function FloatingTools() {
  const { count, open, setOpen } = useCart();
  const [panel, setPanel] = useState(false);
  useEffect(() => { apply(read()); }, []);
  return (
    <>
      <a className="float float--wa" href={WHATSAPP_URL} target="_blank" rel="noopener noreferrer" aria-label="שירות לקוחות בוואטסאפ"><WhatsAppIcon /></a>
      {!open && (
        <button type="button" className="float float--cart" onClick={() => setOpen(true)} aria-label={`פתיחת העגלה, ${count} פריטים`}>
          <BasketIcon width="32" height="32" />
          {count > 0 && <span className="float__badge">{count}</span>}
        </button>
      )}
      <button type="button" className="float float--a11y" aria-expanded={panel} onClick={() => setPanel((v) => !v)} aria-label="תפריט נגישות"><AccessIcon /></button>
      {panel && <AccessibilityPanel onClose={() => setPanel(false)} />}
    </>
  );
}
