import React, { useCallback, useEffect, useRef, useState } from 'react';
import * as api from '../lib/api.js';
import '../styles/reviews.css';

export const Stars = ({ value, label = true }) => (
  <span className="stars" role={label ? 'img' : undefined} aria-label={label ? `דירוג ${value} מתוך 5` : undefined} aria-hidden={label ? undefined : 'true'}>
    {[1, 2, 3, 4, 5].map((n) => <i key={n} className={n <= Math.round(value) ? 'on' : ''}>★</i>)}
  </span>
);

const fmtDate = (iso) => { try { return new Date(iso).toLocaleDateString('he-IL', { year: 'numeric', month: 'long' }); } catch (e) { return ''; } };

export function ReviewCard({ r }) {
  return (
    <li className="review">
      <div className="review__head">
        <Stars value={r.stars} />
        <time dateTime={r.date}>{fmtDate(r.date)}</time>
      </div>
      <p className="review__text">{r.text}</p>
      {r.images?.length > 0 && (
        <div className="review__imgs">{r.images.map((src) => <a key={src} href={src} target="_blank" rel="noopener noreferrer"><img src={src} alt="תמונה מהביקורת" loading="lazy" /></a>)}</div>
      )}
      <p className="review__by"><b>{r.name}</b>{r.verified && <span className="review__ok">✓ רכישה מאומתת</span>}</p>
    </li>
  );
}

/** Paged review list with a "load more" button. `product = 0` shows the whole shop's reviews. */
export function ReviewList({ product = 0, perPage = 6, onSummary }) {
  const [items, setItems] = useState([]);
  const [summary, setSummary] = useState(null);
  const [hasMore, setHasMore] = useState(false);
  const [page, setPage] = useState(0);
  const [loading, setLoading] = useState(false);
  const [failed, setFailed] = useState(false);
  const seq = useRef(0);

  const load = useCallback(async (next) => {
    const id = ++seq.current;
    setLoading(true); setFailed(false);
    try {
      const res = await api.getReviews({ product, page: next, per_page: perPage });
      if (id !== seq.current) return;
      setItems((cur) => (next === 1 ? res.items : [...cur, ...res.items]));
      setSummary(res.summary); setHasMore(res.hasMore); setPage(next);
      onSummary?.(res.summary);
    } catch (e) { if (id === seq.current) setFailed(true); } finally { if (id === seq.current) setLoading(false); }
  }, [product, perPage, onSummary]);

  useEffect(() => { setItems([]); load(1); }, [product]); // eslint-disable-line react-hooks/exhaustive-deps

  return (
    <div className="reviews" aria-busy={loading}>
      {summary?.count > 0 && (
        <p className="reviews__summary"><Stars value={summary.average} /> <b>{Number(summary.average).toFixed(1)}</b> מתוך 5 · {summary.count} ביקורות</p>
      )}
      {!loading && !failed && items.length === 0 && <p>אין ביקורות עדיין. תהיי הראשונה לכתוב!</p>}
      {failed && <p role="alert">לא הצלחנו לטעון ביקורות. <button type="button" className="linklike" onClick={() => load(page + 1 || 1)}>נסי שוב</button></p>}
      <ul className="reviews__grid">{items.map((r) => <ReviewCard key={r.id} r={r} />)}</ul>
      {hasMore && <button type="button" className="btn btn--black more" disabled={loading} onClick={() => load(page + 1)}>{loading ? 'טוענים…' : 'טען עוד'}</button>}
    </div>
  );
}

export function ReviewForm({ product }) {
  const [stars, setStars] = useState(0);
  const [status, setStatus] = useState('idle');
  const [error, setError] = useState('');

  const onSubmit = async (e) => {
    e.preventDefault();
    const f = new FormData(e.currentTarget);
    if (!stars) { setError('נא לבחור דירוג'); return; }
    setStatus('sending'); setError('');
    try {
      await api.submitReview({ product, stars, name: f.get('name'), email: f.get('email'), text: f.get('text'), website: f.get('website') });
      setStatus('done');
    } catch (err) { setError(err.message); setStatus('idle'); }
  };

  if (status === 'done') return <p className="rform__done" role="status">תודה! הביקורת התקבלה ותפורסם לאחר אישור.</p>;
  return (
    <form className="rform" onSubmit={onSubmit} noValidate>
      <h3>כתבי ביקורת</h3>
      <fieldset className="rform__stars">
        <legend>הדירוג שלך</legend>
        <div role="radiogroup" aria-label="דירוג">
          {[1, 2, 3, 4, 5].map((n) => (
            <button key={n} type="button" role="radio" aria-checked={stars === n} aria-label={`${n} כוכבים`} className={n <= stars ? 'on' : ''} onClick={() => setStars(n)}>★</button>
          ))}
        </div>
      </fieldset>
      <label>השם שלך<input name="name" required autoComplete="name" maxLength="80" /></label>
      <label>אימייל (לא יפורסם)<input name="email" type="email" required autoComplete="email" dir="ltr" /></label>
      <label>הביקורת שלך<textarea name="text" rows="4" required minLength="3" maxLength="1500" /></label>
      <input name="website" tabIndex="-1" autoComplete="off" aria-hidden="true" className="rform__hp" />
      {error && <p role="alert" className="rform__error">{error}</p>}
      <button className="btn btn--black" disabled={status === 'sending'}>{status === 'sending' ? 'שולחים…' : 'שליחת ביקורת'}</button>
    </form>
  );
}
