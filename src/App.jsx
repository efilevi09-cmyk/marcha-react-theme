import React from 'react';
import { Route, Routes } from 'react-router-dom';
import { CartProvider } from './lib/CartContext.jsx';
import Header from './components/Header.jsx';
import Footer from './components/Footer.jsx';
import BasketModal from './components/BasketModal.jsx';
import SideCart from './components/SideCart.jsx';
import Home from './pages/Home.jsx';
import NativeContent from './pages/NativeContent.jsx';
import Category from './pages/Category.jsx';
import Product from './pages/Product.jsx';
import Cart from './pages/Cart.jsx';

const pathOf = (u) => { try { return decodeURI(new URL(u, location.origin).pathname); } catch (e) { return null; } };

export default function App() {
  const native = window.MARCHA?.isNative;
  const shopPath = pathOf(window.MARCHA?.shopUrl);
  const cartPath = pathOf(window.MARCHA?.cartUrl);
  return (
    <CartProvider>
      <Header />
      <main id="main">
        {native ? (
          <NativeContent />
        ) : (
          <Routes>
            <Route path="/" element={<Home />} />
            <Route path="/product-category/:slug/*" element={<Category />} />
            <Route path="/product/:slug/*" element={<Product />} />
            {shopPath && <Route path={shopPath} element={<Category all />} />}
            {cartPath && <Route path={cartPath} element={<Cart />} />}
            <Route path="*" element={<NativeContent />} />
          </Routes>
        )}
      </main>
      <Footer />
      <SideCart />
      <BasketModal />
    </CartProvider>
  );
}
