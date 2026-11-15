import React from 'react';
import { Link } from 'react-router-dom';
import { COLOR_COLLECTIONS } from '../lib/config.js';

/**
 * The colour collections, linked from the home page and from every category page.
 *
 * Shoppers who already know the colour they want get there in one tap. The links are
 * also what let these pages rank for their own terms: a page nothing links to reads to
 * Google as a page that does not matter, and Google keeps showing the home page instead —
 * whose thumbnails, for a query about black flats, are whatever happens to be newest.
 */
export default function ColorCollections({ currentSlug = null }) {
  const items = COLOR_COLLECTIONS.filter((c) => c.slug !== currentSlug);
  if (!items.length) return null;

  return (
    <nav className="collections" aria-label="אוספים לפי צבע">
      <h2 className="collections__title">אוספים לפי צבע</h2>
      <ul>
        {items.map((c) => (
          <li key={c.slug}>
            <Link to={`/product-category/${c.slug}/`}>{c.label}</Link>
          </li>
        ))}
      </ul>
    </nav>
  );
}
