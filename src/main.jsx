import React from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import App from './App.jsx';
import './styles/tokens.css';
import './styles/base.css';
import './styles/checkout.css';
import './styles/mobile.css';
import './styles/buttons.css';

try {
  window.MARCHA = JSON.parse(document.getElementById('marcha-config').textContent);
} catch (e) {
  window.MARCHA = window.MARCHA || {};
}

createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <BrowserRouter>
      <App />
    </BrowserRouter>
  </React.StrictMode>
);
