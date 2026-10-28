import React from 'react';

const base = { width: 24, height: 24, viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', strokeWidth: 1.7, strokeLinecap: 'round', strokeLinejoin: 'round', 'aria-hidden': true, focusable: false };

export const StarIcon = (p) => (<svg {...base} {...p} fill="#f5b400" stroke="#f5b400"><path d="m12 2.8 2.8 5.9 6.4.8-4.7 4.4 1.2 6.4L12 17.2 6.3 20.3l1.2-6.4L2.8 9.5l6.4-.8z" /></svg>);
export const PhoneIcon = (p) => (<svg {...base} {...p}><path d="M5 3.5h3.2l1.6 4-2 1.3a11 11 0 0 0 5.4 5.4l1.3-2 4 1.6V17a2.5 2.5 0 0 1-2.7 2.5A15.5 15.5 0 0 1 3 6.2 2.5 2.5 0 0 1 5 3.5z" /></svg>);
export const TruckIcon = (p) => (<svg {...base} {...p}><path d="M2.5 6h11v10h-11zM13.5 9.5h4l3 3.2V16h-7z" /><circle cx="7" cy="17.5" r="1.8" /><circle cx="17" cy="17.5" r="1.8" /></svg>);
export const BagIcon = (p) => (<svg {...base} {...p}><path d="M5 8h14l-1 12H6zM9 8V6.5a3 3 0 0 1 6 0V8" /></svg>);
export const SearchIcon = (p) => (<svg {...base} {...p}><circle cx="11" cy="11" r="6.5" /><path d="m16 16 4.5 4.5" /></svg>);
export const FacebookIcon = (p) => (<svg {...base} {...p} fill="currentColor" stroke="none"><path d="M13.5 21v-7.6h2.6l.4-3h-3V8.5c0-.9.3-1.5 1.5-1.5h1.6V4.3c-.3 0-1.2-.1-2.3-.1-2.3 0-3.8 1.4-3.8 3.9v2.3H8v3h2.5V21z" /></svg>);
export const InstagramIcon = (p) => (<svg {...base} {...p}><rect x="3.5" y="3.5" width="17" height="17" rx="5" /><circle cx="12" cy="12" r="4" /><circle cx="17" cy="7" r=".8" fill="currentColor" /></svg>);
