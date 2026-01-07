import React, { useState } from 'react';
import { useCart } from '../../context/CartContext';
import { useAuth } from '../../context/AuthProvider';
import { useNavigate } from 'react-router-dom';
import api from '../../services/api';
import toast from 'react-hot-toast';
import { MapPin, Phone, CreditCard, CheckCircle } from 'lucide-react';
import clsx from 'clsx';

const Checkout = () => {
    const { cart, cartTotal, clearCart } = useCart();
    const { user } = useAuth();
    const navigate = useNavigate();
    const [loading, setLoading] = useState(false);

    const [checkoutData, setCheckoutData] = useState({
        shipping_address: '',
        contact_number: user?.phone || '',
        payment_method: 'cod',
        notes: ''
    });

    if (cart.length === 0) {
        navigate('/cart');
        return null;
    }

    const handleChange = (e) => {
        setCheckoutData({ ...checkoutData, [e.target.name]: e.target.value });
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);

        const payload = {
            items: cart.map(item => ({
                product_id: item.id,
                product_variation_id: item.variation?.id || null, // Handle variations if implemented later
                quantity: item.quantity
            })),
            shipping_address: checkoutData.shipping_address,
            contact_number: checkoutData.contact_number,
            payment_method: checkoutData.payment_method,
            notes: checkoutData.notes
        };

        try {
            const { data } = await api.post('/orders', payload);

            // Handle Payment Redirection
            if (data.payment_result?.payment_url) {
                toast.loading('Redirecting to bKash...');
                setTimeout(() => {
                    window.location.href = data.payment_result.payment_url;
                }, 1000);
                return;
            }

            toast.success('Order placed successfully!');
            clearCart();
            navigate(`/order-success?order=${data.order.order_number}`);
        } catch (error) {
            console.error("Order placement failed", error);
            toast.error(error.response?.data?.message || "Failed to place order");
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="bg-gray-50 min-h-screen py-10">
            <div className="container mx-auto px-4">
                <h1 className="text-2xl font-bold text-gray-800 mb-8 text-center">Checkout</h1>

                <form onSubmit={handleSubmit} className="grid grid-cols-1 lg:grid-cols-2 gap-8 max-w-5xl mx-auto">
                    {/* Shipping Details */}
                    <div className="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 space-y-6">
                        <div className="flex items-center gap-3 border-b border-gray-100 pb-4 mb-2">
                            <MapPin className="text-red-600" />
                            <h2 className="text-lg font-bold text-gray-800">Shipping Information</h2>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                            <input
                                type="text"
                                value={user?.name || ''}
                                disabled
                                className="w-full p-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-500 cursor-not-allowed"
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                            <div className="relative">
                                <Phone className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size={18} />
                                <input
                                    type="text"
                                    name="contact_number"
                                    required
                                    placeholder="Enter your phone number"
                                    className="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:border-red-500"
                                    value={checkoutData.contact_number}
                                    onChange={handleChange}
                                />
                            </div>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Shipping Address</label>
                            <textarea
                                name="shipping_address"
                                required
                                rows="3"
                                placeholder="Enter full delivery address"
                                className="w-full p-3 border border-gray-200 rounded-lg focus:outline-none focus:border-red-500"
                                value={checkoutData.shipping_address}
                                onChange={handleChange}
                            ></textarea>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Order Notes (Optional)</label>
                            <textarea
                                name="notes"
                                rows="2"
                                placeholder="Any special instructions?"
                                className="w-full p-3 border border-gray-200 rounded-lg focus:outline-none focus:border-red-500"
                                value={checkoutData.notes}
                                onChange={handleChange}
                            ></textarea>
                        </div>
                    </div>

                    {/* Order Summary & Payment */}
                    <div className="space-y-6">
                        <div className="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
                            <div className="flex items-center gap-3 border-b border-gray-100 pb-4 mb-4">
                                <CheckCircle className="text-red-600" />
                                <h2 className="text-lg font-bold text-gray-800">Your Order</h2>
                            </div>

                            <div className="space-y-3 max-h-60 overflow-y-auto pr-2 mb-6 text-sm">
                                {cart.map((item, idx) => (
                                    <div key={idx} className="flex justify-between items-center bg-gray-50 p-2 rounded">
                                        <div className="flex items-center gap-3">
                                            <div className="w-10 h-10 bg-gray-200 rounded overflow-hidden">
                                                {item.images && item.images.length > 0 && <img src={item.images[0]} className="w-full h-full object-cover" />}
                                            </div>
                                            <div>
                                                <p className="font-bold text-gray-800">{item.name}</p>
                                                <p className="text-xs text-gray-500">Qty: {item.quantity}</p>
                                            </div>
                                        </div>
                                        <p className="font-bold">৳{(item.sale_price || item.base_price) * item.quantity}</p>
                                    </div>
                                ))}
                            </div>

                            <div className="space-y-2 pt-4 border-t border-gray-100 text-sm">
                                <div className="flex justify-between text-gray-600">
                                    <span>Subtotal</span>
                                    <span>৳{cartTotal}</span>
                                </div>
                                <div className="flex justify-between text-gray-600">
                                    <span>Shipping</span>
                                    <span>৳100</span>
                                </div>
                                <div className="flex justify-between text-lg font-bold text-gray-800 pt-2">
                                    <span>Total</span>
                                    <span className="text-red-600">৳{cartTotal + 100}</span>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
                            <div className="flex items-center gap-3 mb-4">
                                <CreditCard className="text-red-600" />
                                <h2 className="text-lg font-bold text-gray-800">Payment Method</h2>
                            </div>

                            <div className="space-y-3">
                                <label className="flex items-center gap-3 p-4 border border-red-200 bg-red-50 rounded-lg cursor-pointer">
                                    <input
                                        type="radio"
                                        name="payment_method"
                                        value="cod"
                                        checked={checkoutData.payment_method === 'cod'}
                                        onChange={handleChange}
                                        className="text-red-600 focus:ring-red-500"
                                    />
                                    <div>
                                        <span className="font-bold text-gray-800 block">Cash on Delivery</span>
                                        <span className="text-xs text-gray-500">Pay when you receive your order</span>
                                    </div>
                                </label>
                                <label className={clsx(
                                    "flex items-center gap-3 p-4 border rounded-lg cursor-pointer transition-all",
                                    checkoutData.payment_method === 'bkash' ? "border-red-200 bg-red-50" : "border-gray-200 hover:bg-gray-50"
                                )}>
                                    <input
                                        type="radio"
                                        name="payment_method"
                                        value="bkash"
                                        checked={checkoutData.payment_method === 'bkash'}
                                        onChange={handleChange}
                                        className="text-red-600 focus:ring-red-500"
                                    />
                                    <div>
                                        <span className="font-bold text-gray-800 block">bKash Digital Payment</span>
                                        <span className="text-xs text-gray-500">Secure payment via bKash gateway</span>
                                    </div>
                                </label>
                            </div>

                            <button
                                type="submit"
                                disabled={loading}
                                className="w-full mt-6 bg-red-600 text-white py-4 rounded-xl font-bold hover:bg-red-700 transition shadow-lg shadow-red-200 disabled:bg-gray-400 disabled:cursor-not-allowed"
                            >
                                {loading ? 'Placing Order...' : `Place Order (৳${cartTotal + 100})`}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    );
};

export default Checkout;
