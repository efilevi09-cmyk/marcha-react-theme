import React, { useLayoutEffect, useRef, useState } from 'react';
import { useCart } from '../lib/CartContext.jsx';
import BundleBar from '../components/BundleBar.jsx';
import '../styles/bundle.css';
import '../styles/page.css';

/**
 * Moves the server-rendered WordPress content (checkout, my-account, plain pages)
 * into the React layout. The node is MOVED, not cloned, so plugin scripts that bound
 * to it on DOMContentLoaded (FunnelKit, WooCommerce, gateways) keep working.
 *
 * Plain content pages (about, contact, policies) additionally get a branded hero,
 * a readable text column, direction by language and a table of contents for long documents.
 */
const samePath = (a, b) => { try { return new URL(a, location.origin).pathname === new URL(b, location.origin).pathname; } catch (e) { return false; } };
const LEGAL = /תנאי|תקנון|פרטיות|נגישות|החלפות|החזרות|terms|privacy|return|shipping|refund|policy/i;
const norm = (t) => t.replace(/\s+/g, ' ').trim();

/** Reads title / script accent out of the content and decides how to present it. */
function analyse(src) {
  if (src.querySelector('.woocommerce, form.checkout, .woocommerce-MyAccount-navigation')) return null; // commerce flows keep their own layout
  const h1 = src.querySelector('h1');
  const title = norm(h1?.textContent || document.title.split(/[–|-]/)[0]);
  if (h1) h1.remove();
  // Decorative leftovers from the old builder ("love pariz", "About") become the script accent.
  let accent = 'Paris';
  src.querySelectorAll('h2, p').forEach((el) => {
    const t = norm(el.textContent);
    if (/^love pariz$/i.test(t)) { accent = 'love pariz'; el.remove(); } else if (/^about$/i.test(t)) { accent = 'About'; el.remove(); }
  });
  const text = src.textContent || '';
  const he = (text.match(/[֐-׿]/g) || []).length;
  const latin = (text.match(/[a-z]/gi) || []).length;
  const rtl = he >= latin;
  const legal = LEGAL.test(title) || text.length > 6000;
  const heads = [...src.querySelectorAll('h2, h3')].filter((h) => norm(h.textContent).length > 2);
  const toc = legal && heads.length >= 3 ? heads.map((h, i) => { h.id = h.id || `sec-${i + 1}`; return { id: h.id, text: norm(h.textContent) }; }) : [];
  return { title, accent, rtl, legal, toc };
}

export default function NativeContent() {
  const ref = useRef(null);
  const { count } = useCart();
  const [page, setPage] = useState(null);
  // On checkout, nudge shoppers who have 1-2 pairs towards the 3 for 300 offer.
  const showOffer = samePath(location.href, window.MARCHA?.checkoutUrl || '') && !/order-received|order-pay/.test(location.pathname + location.search) && count > 0 && count % 3 !== 0;
  useLayoutEffect(() => {
    const src = document.getElementById('wp-native-content');
    const host = ref.current;
    if (src && host) {
      src.classList.add('is-mounted');
      host.appendChild(src);
      setPage(analyse(src));
    }
  }, []);
  const dir = page ? (page.rtl ? 'rtl' : 'ltr') : undefined;
  return (
    <>
      {showOffer && <div key="offer" className="container checkout-offer"><BundleBar count={count} shopLink /></div>}
      {page && (
        <header key="hero" className="pagehero">
          <p className="pagehero__accent" aria-hidden="true">{page.accent}</p>
          <h1 className="pagehero__title">{page.title}</h1>
        </header>
      )}
      <div key="body" className={`container native-content${page ? ` is-page${page.legal ? ' is-legal' : ''}` : ''}`}>
        {page?.toc.length > 0 && (
          <nav className="page-toc" aria-label="תוכן עניינים" dir={dir}>
            <strong>{page.rtl ? 'תוכן עניינים' : 'Contents'}</strong>
            <ol>{page.toc.map((t) => <li key={t.id}><a href={`#${t.id}`}>{t.text}</a></li>)}</ol>
          </nav>
        )}
        <div className="page-body" ref={ref} dir={dir} />
      </div>
      {page && !page.legal && (
        <section key="cta" className="container pagecta" dir={dir}>
          <p>{page.rtl ? 'מוכנה למצוא את הזוג שלך?' : 'Ready to find your pair?'}</p>
          <div>
            <a className="btn btn--mint" href={window.MARCHA?.shopUrl || '/'}>{page.rtl ? 'לכל הנעליים' : 'Shop all shoes'}</a>
            <a className="btn btn--black" href="https://api.whatsapp.com/send?phone=972559296934" target="_blank" rel="noopener noreferrer">{page.rtl ? 'שירות לקוחות בוואטסאפ' : 'WhatsApp support'}</a>
          </div>
        </section>
      )}
    </>
  );
}
