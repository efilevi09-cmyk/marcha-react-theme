import React, { useEffect, useRef } from 'react';

/**
 * Moves the server-rendered WordPress content (checkout, my-account, plain pages)
 * into the React layout. The node is MOVED, not cloned, so plugin scripts that bound
 * to it on DOMContentLoaded (FunnelKit, WooCommerce, gateways) keep working.
 */
export default function NativeContent() {
  const ref = useRef(null);
  useEffect(() => {
    const src = document.getElementById('wp-native-content');
    const host = ref.current;
    if (src && host) {
      src.classList.add('is-mounted');
      host.appendChild(src);
    }
  }, []);
  return <div className="container native-content" ref={ref} />;
}
