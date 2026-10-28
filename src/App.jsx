import React from 'react';
import { Route, Routes } from 'react-router-dom';
import { CartProvider } from './lib/CartContext.jsx';
import Header from './components/Header.jsx';
import Footer from './components/Footer.jsx';
import SideCart from './components/SideCart.jsx';
import Home from './pages/Home.jsx';
import NativeContent from './pages/NativeContent.jsx';

export default function App() {
  const native = window.MARCHA?.isNative;
  return (
    <CartProvider>
      <Header />
      <main id="main">
        {native ? (
          <NativeContent />
        ) : (
          <Routes>
            <Route path="/" element={<Home />} />
            <Route path="*" element={<NativeContent />} />
          </Routes>
        )}
      </main>
      <Footer />
      <SideCart />
    </CartProvider>
  );
}
