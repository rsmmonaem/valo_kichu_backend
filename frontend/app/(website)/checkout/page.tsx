"use client";

import React, { useState, useEffect } from "react";
import { useRouter } from "next/navigation";
import { useCart } from "@/context/CartContext";
import { useAuth } from "@/context/AuthContext";
import { authFetch } from "@/lib/api"; // Using authFetch for potential authenticated calls
import { MapPin, Phone, Mail, CheckCircle, CreditCard, ShieldCheck, ShoppingBag } from "lucide-react";
import clsx from "clsx";
import Link from 'next/link';
import toast from 'react-hot-toast';

const CheckoutPage = () => {
    const { cart, cartTotal, clearCart } = useCart();
    const { user, loading: authLoading } = useAuth();
    const router = useRouter();
    const [loading, setLoading] = useState(false);

    // Initial state setup needs to leverage user data if available
    const [checkoutData, setCheckoutData] = useState({
        name: "",
        phone: "",
        email: "",
        address_line1: "",
        address_line2: "",
        city: "",
        district: "",
        country: "Bangladesh",
        zip_code: "",
        payment_method: "cod",
        notes: "",
        area: "" // "Inside Dhaka" | "Outside Dhaka"
    });

    const [shippingCost, setShippingCost] = useState(0);

    // Load user data into form when user is available
    useEffect(() => {
        if (user) {
            setCheckoutData(prev => ({
                ...prev,
                name: `${user.first_name || ''} ${user.last_name || ''}`.trim() || user.name || prev.name,
                email: user.email || prev.email,
                phone: user.phone_number || prev.phone,
                address_line1: user.address || prev.address_line1
            }));
        }
    }, [user]);

    const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
        const { name, value } = e.target;
        setCheckoutData(prev => ({
            ...prev,
            [name]: value
        }));

        // Calculate shipping
        if (name === "area") {
            if (value === "Inside Dhaka") {
                setShippingCost(60); // Default, ideally fetch from config
            } else if (value === "Outside Dhaka") {
                setShippingCost(120);
            } else {
                setShippingCost(0);
            }
        }
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setLoading(true);

        const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://backend.valokichu.com/api';

        try {
            // 1. Prepare Payload
            const orderPayload = {
                name: checkoutData.name,
                products: cart.map((item) => ({
                    product_id: item.id,
                    product_variation_id: item.variant?.id || null, // Ensure your API expects 'product_variation_id' or 'variant_id'
                    quantity: item.quantity,
                })),
                shipping_address: `${checkoutData.address_line1}, ${checkoutData.area}, ${checkoutData.city}, ${checkoutData.country}`, // Simplified address string
                contact_number: checkoutData.phone,
                payment_method: checkoutData.payment_method,
                notes: checkoutData.notes,
                shipping_cost: shippingCost,
                // Guest specific info if needed by backend, though usually name/contact/address is enough
                email: checkoutData.email
            };

            // 2. Send Request
            // Use authFetch if logged in, otherwise regular fetch (but we need to handle guest checkout endpoint if it's different)
            // The Laravel 'checkout' endpoint typically handles both if configured, or we might need to send guest info specifically.
            // If user is logged in, authFetch adds the token, so backend knows the user.

            const res = await authFetch("/v1/order/checkout", {
                method: "POST",
                body: JSON.stringify(orderPayload),
            });

            const data = await res.json();

            if (res.ok) {
                // Hande success
                clearCart();
                router.push(`/order-success?order=${data.order ? data.order.id : (data.id || data.order_id)}`);
                // toast.success("Order placed successfully!");
            } else {
                // toast.error(data.message || "Failed to place order");
                alert(data.message || "Failed to place order");
            }

        } catch (error) {
            console.error("Checkout Error:", error);
            // toast.error("Something went wrong");
            alert("Something went wrong");
        } finally {
            setLoading(false);
        }
    };

    if (cart.length === 0) {
        return (
            <div className="min-h-[60vh] flex flex-col items-center justify-center text-center px-4">
                <ShoppingBag className="text-gray-300 mb-6" size={64} />
                <h2 className="text-2xl font-bold text-gray-800 mb-2">Your Cart is Empty</h2>
                <Link href="/products" className="text-blue-600 hover:text-blue-800 font-medium">
                    Go back to shopping
                </Link>
            </div>
        );
    }

    return (
        <div className="bg-gray-50 min-h-screen py-10">
            <div className="container mx-auto px-4">
                <div className="flex items-center justify-between mb-8 max-w-5xl mx-auto">
                    <h1 className="text-2xl font-bold text-gray-800">Checkout</h1>
                    {!user && (
                        <div className="text-sm">
                            Already have an account? <Link href="/login?redirect=/checkout" className="text-blue-600 font-bold hover:underline">Login here</Link>
                        </div>
                    )}
                </div>

                <form onSubmit={handleSubmit} className="grid grid-cols-1 lg:grid-cols-2 gap-8 max-w-5xl mx-auto">

                    {/* Left Column: Details */}
                    <div className="space-y-6">
                        {/* 1. Contact Info */}
                        <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div className="flex items-center gap-3 border-b border-gray-100 pb-4 mb-4">
                                <ShieldCheck className="text-blue-600" />
                                <h2 className="text-lg font-bold text-gray-800">Contact Information</h2>
                            </div>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="md:col-span-2">
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                                    <input
                                        type="text"
                                        name="name"
                                        value={checkoutData.name}
                                        onChange={handleChange}
                                        required
                                        className="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-blue-600/20 outline-none"
                                    />
                                </div>
                                <div className="md:col-span-1">
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                    <input
                                        type="tel"
                                        name="phone"
                                        value={checkoutData.phone}
                                        onChange={handleChange}
                                        required
                                        className="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-blue-600/20 outline-none"
                                    />
                                </div>
                                <div className="md:col-span-1">
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Email <span className="text-gray-400 font-normal">(Optional)</span></label>
                                    <input
                                        type="email"
                                        name="email"
                                        value={checkoutData.email}
                                        onChange={handleChange}
                                        className="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-blue-600/20 outline-none"
                                    />
                                </div>
                            </div>
                        </div>

                        {/* 2. Shipping Info */}
                        <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div className="flex items-center gap-3 border-b border-gray-100 pb-4 mb-4">
                                <MapPin className="text-blue-600" />
                                <h2 className="text-lg font-bold text-gray-800">Shipping Address</h2>
                            </div>
                            <div className="space-y-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Address</label>
                                    <input
                                        type="text"
                                        name="address_line1"
                                        value={checkoutData.address_line1}
                                        onChange={handleChange}
                                        required
                                        placeholder="House, Road, Area"
                                        className="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-blue-600/20 outline-none"
                                    />
                                </div>
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">City</label>
                                        <input
                                            type="text"
                                            name="city"
                                            value={checkoutData.city}
                                            onChange={handleChange}
                                            required
                                            className="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-blue-600/20 outline-none"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">Postal Code</label>
                                        <input
                                            type="text"
                                            name="zip_code"
                                            value={checkoutData.zip_code}
                                            onChange={handleChange}
                                            className="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-blue-600/20 outline-none"
                                        />
                                    </div>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Delivery Area</label>
                                    <select
                                        name="area"
                                        value={checkoutData.area}
                                        onChange={handleChange}
                                        required
                                        className="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-blue-600/20 outline-none bg-white"
                                    >
                                        <option value="">Select Area</option>
                                        <option value="Inside Dhaka">Inside Dhaka (৳60)</option>
                                        <option value="Outside Dhaka">Outside Dhaka (৳120)</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Order Notes (Optional)</label>
                                    <textarea
                                        name="notes"
                                        value={checkoutData.notes}
                                        onChange={handleChange}
                                        rows={2}
                                        className="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-blue-600/20 outline-none"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Right Column: Order Summary & Payment */}
                    <div className="space-y-6">
                        <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100 sticky top-24">
                            <h3 className="text-lg font-bold text-gray-900 mb-4">Your Order</h3>
                            <div className="space-y-3 max-h-60 overflow-y-auto pr-2 mb-6">
                                {cart.map((item) => (
                                    <div key={`${item.id}-${item.variant?.id}`} className="flex justify-between items-start text-sm">
                                        <div className="flex gap-2">
                                            <div className="w-10 h-10 bg-gray-100 rounded shrink-0 overflow-hidden">
                                                <img src={item.image && item.image.startsWith('http') ? item.image : (item.image ? `${process.env.NEXT_PUBLIC_API_URL || 'https://backend.valokichu.com'}/${item.image}` : '/placeholder.png')} className="w-full h-full object-cover" />
                                            </div>
                                            <div>
                                                <p className="font-medium text-gray-900 line-clamp-1">{item.name}</p>
                                                <p className="text-xs text-gray-500">Qty: {item.quantity}</p>
                                            </div>
                                        </div>
                                        <p className="font-medium">৳{item.price * item.quantity}</p>
                                    </div>
                                ))}
                            </div>

                            <div className="border-t border-gray-100 pt-3 space-y-2 text-sm">
                                <div className="flex justify-between text-gray-600">
                                    <span>Subtotal</span>
                                    <span>৳{cartTotal}</span>
                                </div>
                                <div className="flex justify-between text-gray-600">
                                    <span>Shipping</span>
                                    <span>৳{shippingCost}</span>
                                </div>
                                <div className="flex justify-between text-lg font-bold text-gray-900 pt-2 border-t border-gray-100 mt-2">
                                    <span>Total</span>
                                    <span className="text-blue-600">৳{cartTotal + shippingCost}</span>
                                </div>
                            </div>

                            <div className="mt-6">
                                <h4 className="font-bold text-gray-800 mb-3 flex items-center gap-2">
                                    <CreditCard size={18} /> Payment Method
                                </h4>
                                <div className="space-y-2">
                                    <label className={clsx("flex items-center gap-3 p-3 border rounded-lg cursor-pointer transition-colors", checkoutData.payment_method === 'cod' ? "bg-blue-50 border-blue-200" : "hover:bg-gray-50")}>
                                        <input type="radio" name="payment_method" value="cod" checked={checkoutData.payment_method === 'cod'} onChange={handleChange} className="text-blue-600" />
                                        <div>
                                            <span className="font-medium block text-gray-900">Cash on Delivery</span>
                                            <span className="text-xs text-gray-500">Pay when you receive your order</span>
                                        </div>
                                    </label>
                                    <label className={clsx("flex items-center gap-3 p-3 border rounded-lg cursor-pointer transition-colors", checkoutData.payment_method === 'online' ? "bg-blue-50 border-blue-200" : "hover:bg-gray-50")}>
                                        <input type="radio" name="payment_method" value="online" checked={checkoutData.payment_method === 'online'} onChange={handleChange} className="text-blue-600" />
                                        <div>
                                            <span className="font-medium block text-gray-900">Online Payment</span>
                                            <span className="text-xs text-gray-500">Pay securely via bKash/Nagad/Card</span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <button
                                type="submit"
                                disabled={loading || cart.length === 0}
                                className="w-full mt-6 bg-blue-600 text-white py-3.5 rounded-lg font-bold hover:bg-blue-700 transition shadow-lg shadow-blue-200 disabled:opacity-70 disabled:cursor-not-allowed"
                            >
                                {loading ? 'Processing...' : 'Place Order'}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    );
};

export default CheckoutPage;
