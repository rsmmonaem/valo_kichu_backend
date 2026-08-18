import React, { useEffect, useState } from "react";
import api from "../../services/api";
import Sidebar from "../../components/customer/Sidebar";
import { ShoppingBag, Calendar, Package, ChevronRight, XCircle } from "lucide-react";
import toast from "react-hot-toast";

const Orders = () => {
    const [orders, setOrders] = useState([]);
    const [loading, setLoading] = useState(true);
    const [expandedOrder, setExpandedOrder] = useState(null);

    useEffect(() => {
        fetchOrders();
    }, []);

    const fetchOrders = async () => {
        try {
            const res = await api.get("/v1/order/info");
            // Handle pagination or straight array
            setOrders(res.data.data || res.data);
        } catch (error) {
            console.error("Fetch orders error:", error);
            toast.error("Failed to load orders");
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="max-w-7xl mx-auto px-4 py-8">
            <div className="flex flex-col md:flex-row gap-8">
                {/* Sidebar */}
                <div className="w-full md:w-64 shrink-0">
                    <Sidebar />
                </div>

                {/* Main Content */}
                <div className="flex-1">
                    <div className="flex items-center justify-between mb-6">
                        <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
                            <ShoppingBag className="text-red-600" /> My Orders
                        </h1>
                    </div>

                    {loading ? (
                        <div className="space-y-4">
                            {[1, 2, 3].map((n) => (
                                <div key={n} className="h-24 bg-gray-50 rounded-xl animate-pulse"></div>
                            ))}
                        </div>
                    ) : orders.length === 0 ? (
                        <div className="text-center py-12 bg-gray-50 rounded-xl border border-gray-100">
                            <Package className="mx-auto text-gray-400 mb-4" size={48} />
                            <h3 className="text-lg font-medium text-gray-900">No orders yet</h3>
                            <p className="text-gray-500 mt-1">Start shopping to see your orders here.</p>
                            <a href="/" className="inline-block mt-4 px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                                Browse Products
                            </a>
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
                                                <span className={`px-2 py-0.5 rounded-full text-xs font-semibold
                                                    ${order.status === 'pending' ? 'bg-yellow-100 text-yellow-700' : ''}
                                                    ${order.status === 'confirmed' ? 'bg-blue-100 text-blue-700' : ''}
                                                    ${order.status === 'delivered' ? 'bg-green-100 text-green-700' : ''}
                                                    ${order.status === 'cancelled' ? 'bg-red-100 text-red-700' : ''}
                                                `}>
                                                    {order.status.toUpperCase()}
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
                                            <button className={`p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-full transition-all duration-300 ${expandedOrder === order.id ? 'rotate-90 text-red-600 bg-red-50' : ''}`}>
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
                                                                        src={item.product.images[0]}
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
                                                                {/* Helper to show variant info if available */}
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
            </div>
        </div>
    );
};

export default Orders;
