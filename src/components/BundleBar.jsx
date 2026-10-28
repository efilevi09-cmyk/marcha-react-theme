import React from 'react';
import { bundleMessage, bundleProgress } from '../lib/bundle.js';
import '../styles/bundle.css';

/** "How many pairs left to get 3 for 300" progress bar. */
export default function BundleBar({ count, shopLink = false }) {
  const { pct } = bundleProgress(count);
  const msg = bundleMessage(count);
  return (
    <div className={`bundle${msg.done ? ' is-done' : ''}`} role="status">
      <p>{msg.text}</p>
      <div className="bundle__track" aria-hidden="true"><i style={{ width: `${pct}%` }} /></div>
      {shopLink && !msg.done && <a className="bundle__link" href={window.MARCHA?.siteUrl}>המשך קנייה ←</a>}
    </div>
  );
}
