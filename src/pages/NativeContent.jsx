import React, { useEffect, useRef } from 'react';

/** Injects server-rendered WordPress content (checkout, my-account, plain pages). */
export default function NativeContent() {
  const ref = useRef(null);
  useEffect(() => {
    const tpl = document.getElementById('wp-native-content');
    if (tpl && ref.current) ref.current.appendChild(tpl.content.cloneNode(true));
  }, []);
  return <div className="container native-content" ref={ref} />;
}
