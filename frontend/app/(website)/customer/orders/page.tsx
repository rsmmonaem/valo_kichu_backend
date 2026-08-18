"use client";

import React, { useEffect, useState } from "react";
import { authFetch } from "@/lib/api";
import { ShoppingBag, Calendar, Package, ChevronRight } from "lucide-react";
import Link from "next/link";
import clsx from "clsx";

interface Order {
    id: number;
    order_number: string;
    status: string;
    total_price: string;
    created_at: string;
    products: Array<{
        price: string;
        quantity: number;
        product: {
            name: string;
            images: string[];
        };
        variant?: {
            size?: string;
            color?: string;
        };
    }>;
}

const OrdersPage = () => {
    const [orders, setOrders] = useState<Order[]>([]);
    const [loading, setLoading] = useState(true);
    const [expandedOrder, setExpandedOrder] = useState<number | null>(null);

    useEffect(() => {
        fetchOrders();
    }, []);

    const fetchOrders = async () => {
        try {
            const res = await authFetch("/v1/order/info");
            if (res.ok) {
                const data = await res.json();
                setOrders(Array.isArray(data.data) ? data.data : (Array.isArray(data) ? data : []));
            }
        } catch (error) {
            console.error("Fetch orders error:", error);
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="flex-1">
            <div className="flex items-center justify-between mb-6">
                <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
                    <ShoppingBag className="text-blue-600" /> My Orders
                </h1>
            </div>

            {loading ? (
                <div className="space-y-4">
                    {[1, 2, 3].map((n) => (
                        <div key={n} className="h-24 bg-gray-50 rounded-xl animate-pulse"></div>
                    ))}
                </div>
            ) : orders.length === 0 ? (
                <div className="text-center py-12 bg-white rounded-xl border border-gray-100 shadow-sm">
                    <Package className="mx-auto text-gray-400 mb-4" size={48} />
                    <h3 className="text-lg font-medium text-gray-900">No orders yet</h3>
                    <p className="text-gray-500 mt-1">Start shopping to see your orders here.</p>
                    <Link href="/products" className="inline-block mt-4 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                        Browse Products
                    </Link>
                </div>
            ) : (
                <div className="space-y-4">
                    {orders.map((order) => (
                        <div key={order.id} className="bg-white border border-gray-100 rounded-xl overflow-hidden hover:shadow-md transition-shadow group">
                            <div
                                className="p-6 flex flex-wrap items-center justify-between gap-4 cursor-pointer"
                                onClick={() => setExpandedOrder(expandedOrder === order.id ? null : order.id)}
                            >
                                <div>
                                    <div className="flex items-center gap-3 mb-1">
                                        <h3 className="font-bold text-gray-900">{order.order_number}</h3>
                                        <span className={clsx(
                                            "px-2 py-0.5 rounded-full text-xs font-semibold uppercase",
                                            order.status === 'pending' && "bg-yellow-100 text-yellow-700",
                                            order.status === 'confirmed' && "bg-blue-100 text-blue-700",
                                            order.status === 'delivered' && "bg-green-100 text-green-700",
                                            order.status === 'cancelled' && "bg-red-100 text-red-700",
                                        )}>
                                            {order.status}
                                        </span>
                                    </div>
                                    <div className="flex items-center gap-4 text-sm text-gray-500">
                                        <span className="flex items-center gap-1">
                                            <Calendar size={14} />
                                            {new Date(order.created_at).toLocaleDateString()}
                                        </span>
                                        <span>•</span>
                                        <span>{order.products?.length || 0} items</span>
                                    </div>
                                </div>

                                <div className="flex items-center gap-6">
                                    <div className="text-right">
                                        <p className="text-xs text-gray-500 mb-0.5">Total Amount</p>
                                        <p className="font-bold text-gray-900 text-lg">৳{order.total_price}</p>
                                    </div>
                                    <button className={clsx(
                                        "p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-full transition-all duration-300",
                                        expandedOrder === order.id && "rotate-90 text-blue-600 bg-blue-50"
                                    )}>
                                        <ChevronRight size={20} />
                                    </button>
                                </div>
                            </div>

                            {/* Order Items Expansion */}
                            {expandedOrder === order.id && (
                                <div className="border-t border-gray-100 bg-gray-50/50 p-6 animate-fadeIn">
                                    <h4 className="text-sm font-semibold text-gray-700 mb-4">Order Items</h4>
                                    <div className="space-y-4">
                                        {order.products?.map((item, idx) => (
                                            <div key={idx} className="flex items-center justify-between bg-white p-3 rounded-lg border border-gray-100">
                                                <div className="flex items-center gap-4">
                                                    <div className="w-12 h-12 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                                                        {item.product?.images?.[0] ? (
                                                            <img
                                                                src={item.product.images[0].startsWith('http') ? item.product.images[0] : `http://127.0.0.1:8000/${item.product.images[0]}`}
                                                                alt={item.product.name}
                                                                className="w-full h-full object-cover"
                                                            />
                                                        ) : (
                                                            <div className="w-full h-full flex items-center justify-center text-gray-300">
                                                                <Package size={20} />
                                                            </div>
                                                        )}
                                                    </div>
                                                    <div>
                                                        <p className="font-medium text-gray-900 text-sm line-clamp-1">{item.product?.name}</p>
                                                        {item.variant && (
                                                            <p className="text-xs text-gray-500 mt-0.5">
                                                                {item.variant.size && `Size: ${item.variant.size}`}
                                                                {item.variant.size && item.variant.color && ' • '}
                                                                {item.variant.color && `Color: ${item.variant.color}`}
                                                            </p>
                                                        )}
                                                    </div>
                                                </div>
                                                <div className="text-right flex-shrink-0">
                                                    <p className="font-medium text-gray-900">৳{item.price}</p>
                                                    <p className="text-xs text-gray-500">Qty: {item.quantity}</p>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
};

export default OrdersPage;
