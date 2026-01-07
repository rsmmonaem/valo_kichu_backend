import React from 'react';
import { useCart } from '../../context/CartContext';
import { Link, useNavigate } from 'react-router-dom';
import { Trash2, Plus, Minus, ArrowRight } from 'lucide-react';

const Cart = () => {
    const { cart, removeFromCart, updateQuantity, cartTotal, clearCart } = useCart();
    const navigate = useNavigate();

    if (cart.length === 0) {
        return (
            <div className="min-h-screen bg-gray-50 flex flex-col items-center justify-center py-20">
                <div className="bg-white p-10 rounded-2xl shadow-sm text-center">
                    <div className="w-20 h-20 bg-red-50 text-red-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <Trash2 size={32} />
                    </div>
                    <h2 className="text-2xl font-bold text-gray-800 mb-2">Your cart is empty</h2>
                    <p className="text-gray-500 mb-8">Looks like you haven't added any items yet.</p>
                    <Link to="/products" className="bg-red-600 text-white px-8 py-3 rounded-xl font-bold hover:bg-red-700 transition">
                        Start Shopping
                    </Link>
                </div>
            </div>
        );
    }

    return (
        <div className="bg-gray-50 min-h-screen py-10">
            <div className="container mx-auto px-4">
                <h1 className="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-3">
                    Your Cart <span className="text-sm font-normal text-gray-500">({cart.length} items)</span>
                </h1>

                <div className="flex flex-col lg:flex-row gap-8">
                    {/* Cart Items */}
                    <div className="flex-1 space-y-4">
                        {cart.map((item, idx) => (
                            <div key={`${item.id}-${idx}`} className="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex gap-4">
                                <div className="w-24 h-24 bg-gray-100 rounded-lg flex-shrink-0 overflow-hidden">
                                    {item.images && item.images.length > 0 ? (
                                        <img src={item.images[0]} alt={item.name} className="w-full h-full object-cover" />
                                    ) : (
                                        <div className="w-full h-full flex items-center justify-center text-gray-400 text-xs">No Image</div>
                                    )}
                                </div>
                                <div className="flex-1 flex flex-col justify-between">
                                    <div className="flex justify-between items-start">
                                        <div>
                                            <h3 className="font-bold text-gray-800 line-clamp-1">{item.name}</h3>
                                            <p className="text-sm text-gray-500">{item.category?.name}</p>
                                        </div>
                                        <button
                                            onClick={() => removeFromCart(item.id, item.variation)}
                                            className="text-gray-400 hover:text-red-500 transition"
                                        >
                                            <Trash2 size={18} />
                                        </button>
                                    </div>

                                    <div className="flex justify-between items-end mt-4">
                                        <div className="flex items-center border border-gray-200 rounded-lg">
                                            <button
                                                onClick={() => updateQuantity(item.id, item.quantity - 1, item.variation)}
                                                className="p-2 hover:bg-gray-50 text-gray-600"
                                            >
                                                <Minus size={14} />
                                            </button>
                                            <span className="w-8 text-center text-sm font-bold text-gray-800">{item.quantity}</span>
                                            <button
                                                onClick={() => updateQuantity(item.id, item.quantity + 1, item.variation)}
                                                className="p-2 hover:bg-gray-50 text-gray-600"
                                            >
                                                <Plus size={14} />
                                            </button>
                                        </div>
                                        <div className="text-right">
                                            <div className="font-bold text-red-600">৳{(item.sale_price || item.base_price) * item.quantity}</div>
                                            <div className="text-xs text-gray-400">৳{item.sale_price || item.base_price} / unit</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>

                    {/* Summary */}
                    <div className="w-full lg:w-96">
                        <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100 sticky top-24">
                            <h3 className="font-bold text-gray-800 mb-4">Order Summary</h3>
                            <div className="space-y-3 mb-6">
                                <div className="flex justify-between text-gray-600">
                                    <span>Subtotal</span>
                                    <span>৳{cartTotal}</span>
                                </div>
                                <div className="flex justify-between text-gray-600">
                                    <span>Shipping</span>
                                    <span>৳100</span>
                                </div>
                                <div className="border-t border-gray-100 pt-3 flex justify-between font-bold text-gray-800 text-lg">
                                    <span>Total</span>
                                    <span className="text-red-600">৳{cartTotal + 100}</span>
                                </div>
                            </div>
                            <Link
                                to="/checkout"
                                className="w-full bg-red-600 text-white py-3.5 rounded-xl font-bold hover:bg-red-700 transition flex items-center justify-center gap-2"
                            >
                                Proceed to Checkout <ArrowRight size={18} />
                            </Link>
                            <button
                                onClick={clearCart}
                                className="w-full mt-3 text-sm text-gray-500 hover:text-red-500 underline"
                            >
                                Clear Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default Cart;
