import React, { useLayoutEffect, useRef, useState } from 'react';
import { useCart } from '../lib/CartContext.jsx';
import BundleBar from '../components/BundleBar.jsx';
import { MailIcon, BoxIcon, TruckIcon, WhatsAppIcon } from '../components/Icons.jsx';
import '../styles/bundle.css';
import '../styles/page.css';
import '../styles/thankyou.css';

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
const WHATSAPP = 'https://api.whatsapp.com/send?phone=972559296934';

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
  if (src.querySelector('.woocommerce-order')) {
    // A declined payment lands on the same endpoint: no celebration, no "what happens now".
    const failed = !!src.querySelector('.woocommerce-thankyou-order-failed');
    return failed
      ? { title: 'התשלום לא הושלם', accent: 'Oops', rtl: true, legal: false, toc: [], commerce: true, failed: true }
      : { title: 'תודה על ההזמנה!', accent: 'Thank you', rtl: true, legal: false, toc: [], commerce: true, thanks: true };
  }
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

/** Sets expectations right after an order, so the shopper does not have to ask what happens next. */
function NextSteps() {
  const account = window.MARCHA?.accountUrl;
  return (
    <section className="container thanks-next" aria-labelledby="thanks-next-title">
      <h2 id="thanks-next-title">מה קורה עכשיו?</h2>
      <ol>
        <li>
          <span className="thanks-next__icon" aria-hidden="true"><MailIcon /></span>
          <strong>אישור במייל</strong>
          <p>המייל עם מספר ההזמנה וכל הפרטים נשלח אליך. אם הוא לא הגיע, כדאי להציץ בתיקיית הקידומים או הספאם.</p>
        </li>
        <li>
          <span className="thanks-next__icon" aria-hidden="true"><BoxIcon /></span>
          <strong>אריזה ובדיקה</strong>
          <p>כל זוג נבדק ונארז אצלנו בקפידה לפני שהוא יוצא לדרך אליך.</p>
        </li>
        <li>
          <span className="thanks-next__icon" aria-hidden="true"><TruckIcon /></span>
          <strong>יוצא לדרך</strong>
          <p>
            כשההזמנה נמסרת לשליח נעדכן אותך
            {window.MARCHA?.isLoggedIn && account ? <>, ואפשר לעקוב אחרי מצב ההזמנה גם <a href={account}>בחשבון שלך</a></> : null}.
          </p>
        </li>
      </ol>
    </section>
  );
}

/** Declined payment: say what to do instead of leaving the shopper on a dead end. */
function PaymentHelp() {
  return (
    <section className="container thanks-help" aria-labelledby="thanks-help-title">
      <div className="thanks-help__box">
        <h2 id="thanks-help-title" className="sr-only">מה עושים עכשיו</h2>
        <p>התשלום לא עבר, וההזמנה לא נשלחה לטיפול. לרוב זה קורה בגלל פרטי כרטיס או הגבלה של חברת האשראי, ואפשר פשוט לנסות שוב. אם זה חוזר, אנחנו כאן בוואטסאפ ונשמח לסייע.</p>
        <div>
          <a className="btn btn--mint" href={window.MARCHA?.cartUrl || '/'}>חזרה לעגלה</a>
          <a className="btn btn--black" href={WHATSAPP} target="_blank" rel="noopener noreferrer"><WhatsAppIcon /> דברי איתנו בוואטסאפ</a>
        </div>
      </div>
    </section>
  );
}

export default function NativeContent() {
  const ref = useRef(null);
  const { count, celebrate } = useCart();
  const [page, setPage] = useState(null);
  // On checkout, nudge shoppers who have 1-2 pairs towards the 3 for 300 offer.
  const showOffer = samePath(location.href, window.MARCHA?.checkoutUrl || '') && !/order-received|order-pay/.test(location.pathname + location.search) && count > 0 && count % 3 !== 0;
  useLayoutEffect(() => {
    const src = document.getElementById('wp-native-content');
    const host = ref.current;
    let party;
    if (src && host) {
      src.classList.add('is-mounted');
      host.appendChild(src);
      const found = analyse(src);
      setPage(found);
      // Savings read as savings: colour any negative total row green, like the cart does.
      src.querySelectorAll('.woocommerce-order-details tfoot td').forEach((td) => {
        if (/^\s*-/.test(td.textContent || '')) td.closest('tr')?.classList.add('is-discount');
      });
      if (found?.thanks) party = setTimeout(() => celebrate?.(), 450); // confetti for a completed order
    }
    // Checkout form arrives from WooCommerce (maybe after fragments load): bind once it exists.
    let off = bindCheckoutValidation();
    const t = setTimeout(() => { off(); off = bindCheckoutValidation(); }, 1500);
    return () => { clearTimeout(t); clearTimeout(party); off(); };
  }, []);
  const dir = page ? (page.rtl ? 'rtl' : 'ltr') : undefined;
  const commerceClass = page?.commerce ? ` is-commerce${page.thanks || page.failed ? ' is-thanks' : ''}` : '';
  const pageClass = page && !page.commerce ? ` is-page${page.legal ? ' is-legal' : ''}` : '';
  return (
    <>
      {page && (
        <header key="hero" className="pagehero">
          <p className="pagehero__accent" aria-hidden="true">{page.accent}</p>
          <h1 className="pagehero__title">{page.title}</h1>
        </header>
      )}
      {showOffer && <div key="offer" className="container checkout-offer"><BundleBar count={count} shopLink /></div>}
      <div key="body" className={`container native-content${commerceClass}${pageClass}`}>
        {page?.toc.length > 0 && (
          <nav className="page-toc" aria-label="תוכן עניינים" dir={dir}>
            <strong>{page.rtl ? 'תוכן עניינים' : 'Contents'}</strong>
            <ol>{page.toc.map((t) => <li key={t.id}><a href={`#${t.id}`}>{t.text}</a></li>)}</ol>
          </nav>
        )}
        <div className={`page-body${page?.commerce ? ' page-body--commerce' : ''}`} ref={ref} dir={dir} />
      </div>
      {page?.thanks && <NextSteps key="next" />}
      {page?.failed && <PaymentHelp key="help" />}
      {page && !page.legal && (!page.commerce || page.thanks) && (
        <section key="cta" className="container pagecta" dir={dir}>
          <p>{page.thanks ? 'נשמח לראות אותך שוב' : page.rtl ? 'מוכנה למצוא את הזוג שלך?' : 'Ready to find your pair?'}</p>
          <div>
            <a className="btn btn--mint" href={window.MARCHA?.shopUrl || '/'}>{page.rtl ? 'לכל הנעליים' : 'Shop all shoes'}</a>
            <a className="btn btn--black" href={WHATSAPP} target="_blank" rel="noopener noreferrer">{page.rtl ? 'שירות לקוחות בוואטסאפ' : 'WhatsApp support'}</a>
          </div>
        </section>
      )}
    </>
  );
}
