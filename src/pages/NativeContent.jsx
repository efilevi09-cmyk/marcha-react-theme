import React, { useEffect, useRef } from 'react';
import { useCart } from '../lib/CartContext.jsx';
import BundleBar from '../components/BundleBar.jsx';
import '../styles/bundle.css';

/**
 * Moves the server-rendered WordPress content (checkout, my-account, plain pages)
 * into the React layout. The node is MOVED, not cloned, so plugin scripts that bound
 * to it on DOMContentLoaded (FunnelKit, WooCommerce, gateways) keep working.
 */
const samePath = (a, b) => { try { return new URL(a, location.origin).pathname === new URL(b, location.origin).pathname; } catch (e) { return false; } };

export default function NativeContent() {
  const ref = useRef(null);
  const { count } = useCart();
  // On checkout, nudge shoppers who have 1-2 pairs towards the 3 for 300 offer.
  const showOffer = samePath(location.href, window.MARCHA?.checkoutUrl || '') && !/order-received|order-pay/.test(location.pathname + location.search) && count > 0 && count % 3 !== 0;
  useEffect(() => {
    const src = document.getElementById('wp-native-content');
    const host = ref.current;
    if (src && host) {
      src.classList.add('is-mounted');
      host.appendChild(src);
    }
  }, []);
  return (
    <>
      {showOffer && <div className="container checkout-offer"><BundleBar count={count} shopLink /></div>}
      <div className="container native-content" ref={ref} />
    </>
  );
}
