"use client";

import React from 'react';
import Link from 'next/link';
import { useCart } from '@/context/CartContext';
import { Trash2, Plus, Minus, ShoppingBag } from 'lucide-react';

const CartPage = () => {
    const { cart, removeFromCart, updateQuantity, cartTotal, clearCart } = useCart();

    if (cart.length === 0) {
        return (
            <div className="min-h-[60vh] flex flex-col items-center justify-center text-center px-4">
                <ShoppingBag size={64} className="text-gray-300 mb-6" />
                <h2 className="text-2xl font-bold text-gray-800 mb-2">Your Cart is Empty</h2>
                <p className="text-gray-500 mb-8">Looks like you haven't added anything to your cart yet.</p>
                <Link href="/products" className="bg-blue-600 text-white px-8 py-3 rounded-lg font-bold hover:bg-blue-700 transition shadow-lg shadow-blue-200">
                    Start Shopping
                </Link>
            </div>
        );
    }

    return (
        <div className="container mx-auto px-4 py-8">
            <h1 className="text-2xl font-bold text-gray-900 mb-8">Shopping Cart ({cart.length} items)</h1>

            <div className="flex flex-col lg:flex-row gap-8">
                {/* Cart Items */}
                <div className="flex-1 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div className="hidden md:grid grid-cols-12 gap-4 p-4 border-b border-gray-100 bg-gray-50 text-sm font-medium text-gray-500">
                        <div className="col-span-6">Product</div>
                        <div className="col-span-2 text-center">Price</div>
                        <div className="col-span-2 text-center">Quantity</div>
                        <div className="col-span-2 text-right">Total</div>
                    </div>

                    <div className="divide-y divide-gray-100">
                        {cart.map((item) => (
                            <div key={`${item.id}-${item.variant?.id || 'base'}`} className="grid grid-cols-1 md:grid-cols-12 gap-4 p-4 items-center">
                                <div className="col-span-6 flex gap-4">
                                    <div className="w-20 h-20 bg-gray-100 rounded-lg overflow-hidden shrink-0">
                                        <img
                                            src={item.image && item.image.startsWith('http') ? item.image : (item.image ? `http://127.0.0.1:8000/${item.image}` : '/placeholder.png')}
                                            alt={item.name}
                                            className="w-full h-full object-cover"
                                        />
                                    </div>
                                    <div>
                                        <Link href={`/products/${item.slug}`} className="font-medium text-gray-900 hover:text-blue-600 line-clamp-2">
                                            {item.name}
                                        </Link>
                                        {item.variant && (
                                            <p className="text-sm text-gray-500 mt-1">
                                                {item.variant.size && <span className="mr-2">Size: {item.variant.size}</span>}
                                                {item.variant.color && <span>Color: {item.variant.color}</span>}
                                            </p>
                                        )}
                                        <button
                                            onClick={() => removeFromCart(item.id, item.variant?.id)}
                                            className="text-red-500 text-sm flex items-center gap-1 mt-2 hover:text-red-700 md:hidden"
                                        >
                                            <Trash2 size={14} /> Remove
                                        </button>
                                    </div>
                                </div>

                                <div className="col-span-2 text-center font-medium hidden md:block">
                                    ৳{item.price}
                                </div>

                                <div className="col-span-2 flex items-center justify-between md:justify-center bg-gray-50 rounded-lg p-1 md:p-0">
                                    <span className="text-sm font-medium text-gray-500 md:hidden px-2">Qty:</span>
                                    <div className="flex items-center gap-3">
                                        <button
                                            onClick={() => updateQuantity(item.id, item.quantity - 1, item.variant?.id)}
                                            className="p-1 hover:bg-white rounded shadow-sm disabled:opacity-50"
                                            disabled={item.quantity <= 1}
                                        >
                                            <Minus size={14} />
                                        </button>
                                        <span className="font-medium w-6 text-center">{item.quantity}</span>
                                        <button
                                            onClick={() => updateQuantity(item.id, item.quantity + 1, item.variant?.id)}
                                            className="p-1 hover:bg-white rounded shadow-sm"
                                        >
                                            <Plus size={14} />
                                        </button>
                                    </div>
                                </div>

                                <div className="col-span-2 text-right font-bold text-gray-900 hidden md:block">
                                    ৳{item.price * item.quantity}
                                </div>
                                <div className="col-span-12 flex justify-between items-center md:hidden border-t pt-3 mt-2">
                                    <span className="font-bold text-gray-900">Total: ৳{item.price * item.quantity}</span>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Summary */}
                <div className="w-full lg:w-80 shrink-0">
                    <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100 sticky top-24">
                        <h3 className="text-lg font-bold text-gray-900 mb-4">Order Summary</h3>

                        <div className="space-y-3 mb-6">
                            <div className="flex justify-between text-gray-600">
                                <span>Subtotal</span>
                                <span>৳{cartTotal}</span>
                            </div>
                            <div className="flex justify-between text-gray-600">
                                <span>Delivery</span>
                                <span className="text-xs text-gray-400">(Calculated at checkout)</span>
                            </div>
                            <div className="border-t border-gray-100 pt-3 flex justify-between items-center">
                                <span className="font-bold text-gray-900">Total</span>
                                <span className="font-bold text-xl text-blue-600">৳{cartTotal}</span>
                            </div>
                        </div>

                        <Link
                            href="/checkout"
                            className="block w-full bg-blue-600 text-white text-center py-3 rounded-lg font-bold hover:bg-blue-700 transition shadow-lg shadow-blue-200"
                        >
                            Proceed to Checkout
                        </Link>

                        <button
                            onClick={clearCart}
                            className="block w-full text-red-500 text-center py-3 mt-2 text-sm hover:underline"
                        >
                            Clear Cart
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default CartPage;
