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

/** Checkout: outline every field that blocks the order (red border + message) and bring the first one into view. */
function bindCheckoutValidation() {
  const $ = window.jQuery;
  const form = document.querySelector('form.checkout');
  if (!$ || !form) return () => {};
  const rowOf = (el) => el && el.closest('.form-row');
  const flag = (row) => { row.classList.add('woocommerce-invalid', 'is-flagged'); row.classList.remove('woocommerce-validated'); row.querySelector('input,select,textarea')?.setAttribute('aria-invalid', 'true'); };
  const reveal = (rows) => {
    if (!rows.length) return;
    const first = rows[0];
    first.scrollIntoView({ behavior: 'smooth', block: 'center' });
    setTimeout(() => first.querySelector('input:not([type=hidden]),select,textarea')?.focus({ preventScroll: true }), 350);
  };
  const emptyRequired = () => [...form.querySelectorAll('.form-row.validate-required')].filter((row) => {
    if (row.offsetParent === null) return false; // hidden (e.g. shipping fields)
    const input = row.querySelector('input:not([type=hidden]),select,textarea');
    if (!input) return false;
    return input.type === 'checkbox' ? !input.checked : !String(input.value || '').trim();
  });
  const onError = () => {
    const ids = [...document.querySelectorAll('.woocommerce-error li[data-id]')].map((li) => li.getAttribute('data-id'));
    const rows = ids.map((id) => rowOf(document.getElementById(id))).filter(Boolean);
    const all = [...new Set([...rows, ...emptyRequired()])];
    all.forEach(flag);
    reveal(all);
  };
  const onClick = (e) => {
    if (!e.target.closest('#place_order')) return;
    emptyRequired().forEach(flag);
  };
  const onInput = (e) => {
    const row = rowOf(e.target);
    if (row?.classList.contains('is-flagged') && String(e.target.value || '').trim()) { row.classList.remove('woocommerce-invalid', 'is-flagged'); e.target.removeAttribute('aria-invalid'); }
  };
  $(document.body).on('checkout_error', onError);
  form.addEventListener('click', onClick, true);
  form.addEventListener('input', onInput);
  form.addEventListener('change', onInput);
  return () => { $(document.body).off('checkout_error', onError); form.removeEventListener('click', onClick, true); form.removeEventListener('input', onInput); form.removeEventListener('change', onInput); };
}

const norm = (t) => t.replace(/\s+/g, ' ').trim();

/** Reads title / script accent out of the content and decides how to present it. */
function analyse(src) {
  // Checkout and "order received" keep their WooCommerce markup but get the same branded hero as content pages.
  if (src.querySelector('form.checkout')) return { title: 'תשלום', accent: 'Checkout', rtl: true, legal: false, toc: [], commerce: true };
  if (src.querySelector('.woocommerce-order')) return { title: 'תודה על ההזמנה', accent: 'Thank you', rtl: true, legal: false, toc: [], commerce: true };
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
    // Checkout form arrives from WooCommerce (maybe after fragments load): bind once it exists.
    let off = bindCheckoutValidation();
    const t = setTimeout(() => { off(); off = bindCheckoutValidation(); }, 1500);
    return () => { clearTimeout(t); off(); };
  }, []);
  const dir = page ? (page.rtl ? 'rtl' : 'ltr') : undefined;
  return (
    <>
      {page && (
        <header key="hero" className="pagehero">
          <p className="pagehero__accent" aria-hidden="true">{page.accent}</p>
          <h1 className="pagehero__title">{page.title}</h1>
        </header>
      )}
      {showOffer && <div key="offer" className="container checkout-offer"><BundleBar count={count} shopLink /></div>}
      <div key="body" className={`container native-content${page?.commerce ? ' is-commerce' : page ? ` is-page${page.legal ? ' is-legal' : ''}` : ''}`}>
        {page?.toc.length > 0 && (
          <nav className="page-toc" aria-label="תוכן עניינים" dir={dir}>
            <strong>{page.rtl ? 'תוכן עניינים' : 'Contents'}</strong>
            <ol>{page.toc.map((t) => <li key={t.id}><a href={`#${t.id}`}>{t.text}</a></li>)}</ol>
          </nav>
        )}
        <div className={`page-body${page?.commerce ? ' page-body--commerce' : ''}`} ref={ref} dir={dir} />
      </div>
      {page && !page.legal && !page.commerce && (
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
