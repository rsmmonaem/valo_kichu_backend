import React from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider, useAuth } from './context/AuthProvider';
import { CartProvider } from './context/CartContext';
import PublicLayout from './layouts/PublicLayout';
import AdminLayout from './layouts/AdminLayout';

import Home from './pages/public/Home';
import Shop from './pages/public/Shop';
import ProductDetails from './pages/public/ProductDetails';
import Cart from './pages/public/Cart';
import Checkout from './pages/public/Checkout';
import OrderSuccess from './pages/public/OrderSuccess';

import Login from './pages/auth/Login';
import AdminDashboard from './pages/admin/Dashboard';
import Products from './pages/admin/Products';
import Orders from './pages/admin/Orders';
import Categories from './pages/admin/Categories';
import Appearance from './pages/admin/Appearance';

// Protected Route Component
const ProtectedRoute = ({ children, roles = [] }) => {
    const { user, loading } = useAuth();
    if (loading) return <div>Loading...</div>;
    if (!user) return <Navigate to="/login" replace />;
    if (roles.length > 0 && !roles.includes(user.role)) return <Navigate to="/" replace />;
    return children;
};

const AppRouter = () => {
    return (
        <BrowserRouter>
            <AuthProvider>
                <CartProvider>
                    <Routes>
                        {/* Public Routes */}
                        <Route path="/login" element={<Login />} />

                        <Route path="/" element={<PublicLayout />}>
                            <Route index element={<Home />} />
                            <Route path="products" element={<Shop />} />
                            <Route path="products/:id" element={<ProductDetails />} />
                            <Route path="cart" element={<Cart />} />
                            <Route path="checkout" element={
                                <ProtectedRoute>
                                    <Checkout />
                                </ProtectedRoute>
                            } />
                            <Route path="order-success" element={
                                <ProtectedRoute>
                                    <OrderSuccess />
                                </ProtectedRoute>
                            } />
                        </Route>

                        {/* Admin Routes */}
                        <Route path="/admin" element={
                            <ProtectedRoute roles={['super_admin', 'child_admin']}>
                                <AdminLayout />
                            </ProtectedRoute>
                        }>
                            <Route index element={<Navigate to="/admin/dashboard" replace />} />
                            <Route path="dashboard" element={<AdminDashboard />} />
                            <Route path="products" element={<Products />} />
                            <Route path="orders" element={<Orders />} />
                            <Route path="categories" element={<Categories />} />
                            <Route path="appearance" element={<Appearance />} />
                        </Route>

                        <Route path="*" element={<div className="p-10 text-center">404 Not Found</div>} />
                    </Routes>
                </CartProvider>
            </AuthProvider>
        </BrowserRouter>
    );
};

export default AppRouter;
