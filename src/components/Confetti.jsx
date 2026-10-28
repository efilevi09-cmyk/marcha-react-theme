import React, { useEffect, useRef } from 'react';

const COLORS = ['#03ffc3', '#f58220', '#ff4d8d', '#ffd23f', '#64c8d3', '#ffffff'];

/** Lightweight canvas confetti (no dependency). Plays once per `burst` value and cleans itself up. */
export default function Confetti({ burst }) {
  const ref = useRef(null);
  useEffect(() => {
    if (!burst || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return undefined;
    const canvas = ref.current;
    const ctx = canvas.getContext('2d');
    const dpr = Math.min(window.devicePixelRatio || 1, 2);
    const w = (canvas.width = innerWidth * dpr);
    const h = (canvas.height = innerHeight * dpr);
    const parts = Array.from({ length: 140 }, () => ({
      x: w / 2 + (Math.random() - 0.5) * w * 0.3, y: h * 0.45,
      vx: (Math.random() - 0.5) * 16 * dpr, vy: (-Math.random() * 14 - 6) * dpr,
      s: (6 + Math.random() * 7) * dpr, r: Math.random() * Math.PI, vr: (Math.random() - 0.5) * 0.4,
      c: COLORS[(Math.random() * COLORS.length) | 0],
    }));
    let raf; const start = performance.now();
    const tick = (t) => {
      ctx.clearRect(0, 0, w, h);
      const life = (t - start) / 3200;
      parts.forEach((p) => {
        p.vy += 0.35 * dpr; p.vx *= 0.995; p.x += p.vx; p.y += p.vy; p.r += p.vr;
        ctx.save(); ctx.globalAlpha = Math.max(0, 1 - life); ctx.translate(p.x, p.y); ctx.rotate(p.r);
        ctx.fillStyle = p.c; ctx.fillRect(-p.s / 2, -p.s / 4, p.s, p.s / 2); ctx.restore();
      });
      if (life < 1) raf = requestAnimationFrame(tick); else ctx.clearRect(0, 0, w, h);
    };
    raf = requestAnimationFrame(tick);
    return () => { cancelAnimationFrame(raf); ctx.clearRect(0, 0, w, h); };
  }, [burst]);
  return <canvas ref={ref} className="confetti" aria-hidden="true" />;
}
