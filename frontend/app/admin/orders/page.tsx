"use client";

import React, { useEffect, useState } from 'react';
import { Eye, Clock, CheckCircle, Truck, Package, XCircle, RefreshCcw } from 'lucide-react';
import { authFetch } from '@/lib/api';
import clsx from 'clsx';
import toast from 'react-hot-toast';

const AdminOrdersPage = () => {
    const [orders, setOrders] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    const [selectedOrder, setSelectedOrder] = useState<any>(null);
    const [activeStatus, setActiveStatus] = useState('all');
    const [stats, setStats] = useState<any>({
        all: 0,
        pending: 0,
        confirmed: 0,
        purchased_by_admin: 0,
        ready_to_ship_bd: 0,
        shipping: 0,
        delivered: 0,
        cancelled: 0,
        refunded: 0
    });
    const [statusUpdating, setStatusUpdating] = useState(false);

    const statusTabs = [
        { key: 'all', label: 'All Orders', color: 'gray' },
        { key: 'pending', label: 'Pending', color: 'yellow' },
        { key: 'confirmed', label: 'Confirmed', color: 'blue' },
        { key: 'purchased_by_admin', label: 'Purchased', color: 'indigo' },
        { key: 'ready_to_ship_bd', label: 'Ready to Ship', color: 'purple' },
        { key: 'shipping', label: 'Shipping', color: 'orange' },
        { key: 'delivered', label: 'Delivered', color: 'green' },
        { key: 'cancelled', label: 'Cancelled', color: 'red' },
        { key: 'refunded', label: 'Refunded', color: 'pink' }
    ];

    const fetchOrders = async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams();
            if (activeStatus !== 'all') params.append('status', activeStatus);

            const res = await authFetch(`/admin/v1/orders?${params.toString()}`);
            if (res.ok) {
                const data = await res.json();
                setOrders(data.data || []);
            }
        } catch (error) {
            console.error(error);
        } finally {
            setLoading(false);
        }
    };

    const fetchStats = async () => {
        try {
            // This endpoint might need to be adjusted or stats calculated from all orders if API doesn't provide stats endpoint
            // Assuming we fetch all to calc or specific stats endpoint exists. 
            // Original used `api.get('/admin/v1/orders?per_page=1000')` to calc stats client side.
            // Let's try to fetch all IDs or status counts if API supports. 
            // For now, I'll mock stats or just count loaded orders if pagination allows.
            // Let's implement client-side calc if we can fetch lightweight list, but for now simple approach:

            const res = await authFetch('/admin/v1/orders?per_page=1000');
            if (res.ok) {
                const data = await res.json();
                const allOrders = data.data || [];
                const newStats = {
                    all: allOrders.length,
                    pending: allOrders.filter((o: any) => o.status === 'pending').length,
                    confirmed: allOrders.filter((o: any) => o.status === 'confirmed').length,
                    purchased_by_admin: allOrders.filter((o: any) => o.status === 'purchased_by_admin').length,
                    ready_to_ship_bd: allOrders.filter((o: any) => o.status === 'ready_to_ship_bd').length,
                    shipping: allOrders.filter((o: any) => o.status === 'shipping').length,
                    delivered: allOrders.filter((o: any) => o.status === 'delivered').length,
                    cancelled: allOrders.filter((o: any) => o.status === 'cancelled').length,
                    refunded: allOrders.filter((o: any) => o.status === 'refunded').length
                };
                setStats(newStats);
            }

        } catch (error) {
            console.error('Failed to fetch stats', error);
        }
    };

    useEffect(() => {
        fetchOrders();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [activeStatus]);

    useEffect(() => {
        fetchStats();
    }, []);

    const updateStatus = async (orderId: number, newStatus: string) => {
        if (!window.confirm(`Update order status to ${newStatus.replace(/_/g, ' ')}?`)) return;
        setStatusUpdating(true);
        try {
            const res = await authFetch(`/v1/admin/orders/${orderId}`, {
                method: 'PUT',
                body: JSON.stringify({ status: newStatus })
            });

            if (res.ok) {
                fetchOrders();
                fetchStats();
                if (selectedOrder && selectedOrder.id === orderId) {
                    setSelectedOrder({ ...selectedOrder, status: newStatus });
                }
                toast.success("Status updated");
            } else {
                toast.error("Failed to update status");
            }
        } catch (error) {
            console.error(error);
            toast.error("Something went wrong");
        } finally {
            setStatusUpdating(false);
        }
    };

    const getStatusBadge = (status: string) => {
        const styles: any = {
            pending: 'bg-yellow-100 text-yellow-700',
            confirmed: 'bg-blue-100 text-blue-700',
            purchased_by_admin: 'bg-indigo-100 text-indigo-700',
            ready_to_ship_bd: 'bg-purple-100 text-purple-700',
            shipping: 'bg-orange-100 text-orange-700',
            delivered: 'bg-green-100 text-green-700',
            cancelled: 'bg-red-100 text-red-700',
            refunded: 'bg-pink-100 text-pink-700',
        };
        const labels: any = {
            pending: 'Pending',
            confirmed: 'Confirmed',
            purchased_by_admin: 'Purchased',
            ready_to_ship_bd: 'Ready to Ship',
            shipping: 'Shipping',
            delivered: 'Delivered',
            cancelled: 'Cancelled',
            refunded: 'Refunded'
        };
        return (
            <span className={`px-2 py-1 rounded text-xs font-semibold uppercase ${styles[status] || 'bg-gray-100 text-gray-700'}`}>
                {labels[status] || status.replace(/_/g, ' ')}
            </span>
        );
    };

    return (
        <div>
            <h1 className="text-2xl font-bold text-gray-800 mb-6">Orders Management</h1>

            {/* Status Tabs */}
            <div className="bg-white rounded-xl shadow-sm border border-gray-100 mb-6 overflow-x-auto">
                <div className="flex gap-2 p-4 min-w-max">
                    {statusTabs.map(tab => (
                        <button
                            key={tab.key}
                            onClick={() => setActiveStatus(tab.key)}
                            className={clsx(
                                "px-4 py-2 rounded-lg font-medium text-sm transition-all flex items-center gap-2 border-2",
                                activeStatus === tab.key
                                    ? `bg-${tab.color}-50 text-${tab.color}-700 border-${tab.color}-200`
                                    : "bg-gray-50 text-gray-600 border-transparent hover:bg-gray-100"
                            )}
                        >
                            {tab.label}
                            <span className={clsx(
                                "px-2 py-0.5 rounded-full text-xs font-bold",
                                activeStatus === tab.key
                                    ? `bg-${tab.color}-100 text-${tab.color}-800`
                                    : "bg-gray-200 text-gray-700"
                            )}>
                                {stats[tab.key] || 0}
                            </span>
                        </button>
                    ))}
                </div>
            </div>

            <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="w-full text-left">
                        <thead className="bg-gray-50 text-gray-600 font-medium border-b border-gray-200">
                            <tr>
                                <th className="p-4">Order ID</th>
                                <th className="p-4">Customer</th>
                                <th className="p-4">Date</th>
                                <th className="p-4">Total</th>
                                <th className="p-4">Status</th>
                                <th className="p-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100 text-sm">
                            {loading ? (
                                <tr><td colSpan={6} className="p-8 text-center text-gray-500">Loading orders...</td></tr>
                            ) : orders.length > 0 ? (
                                orders.map(order => (
                                    <tr key={order.id} className="hover:bg-gray-50 transition">
                                        <td className="p-4 font-medium">#{order.order_number || order.id}</td>
                                        <td className="p-4">
                                            <div>
                                                <p className="font-medium text-gray-900">{order.name || order.user?.name || 'Guest'}</p>
                                                <p className="text-gray-500 text-xs">{order.phone || order.contact_number}</p>
                                            </div>
                                        </td>
                                        <td className="p-4 text-gray-600">{new Date(order.created_at).toLocaleDateString()}</td>
                                        <td className="p-4 font-bold text-gray-900">৳{order.total_amount || order.total_price}</td>
                                        <td className="p-4">{getStatusBadge(order.status)}</td>
                                        <td className="p-4 text-right">
                                            <button
                                                onClick={() => setSelectedOrder(order)}
                                                className="text-blue-600 hover:text-blue-800 font-medium inline-flex items-center gap-1"
                                            >
                                                <Eye size={16} /> View
                                            </button>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr><td colSpan={6} className="p-8 text-center text-gray-500">No orders found.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            {/* Order Details Modal */}
            {selectedOrder && (
                <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-xl shadow-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto flex flex-col">
                        <div className="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                            <div>
                                <h2 className="text-xl font-bold text-gray-800">Order #{selectedOrder.order_number || selectedOrder.id}</h2>
                                <p className="text-sm text-gray-500">{new Date(selectedOrder.created_at).toLocaleString()}</p>
                            </div>
                            <button onClick={() => setSelectedOrder(null)} className="p-2 hover:bg-gray-100 rounded-full transition"><XCircle className="text-gray-500" /></button>
                        </div>

                        <div className="p-6 flex-1 overflow-y-auto">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                                <div>
                                    <h3 className="text-xs uppercase text-gray-500 font-bold mb-3 tracking-wider">Customer Details</h3>
                                    <div className="bg-gray-50 p-4 rounded-lg space-y-2 text-sm">
                                        <p><span className="text-gray-500">Name:</span> <span className="font-medium">{selectedOrder.name || selectedOrder.user?.name}</span></p>
                                        <p><span className="text-gray-500">Phone:</span> <span className="font-medium">{selectedOrder.phone || selectedOrder.contact_number}</span></p>
                                        <p><span className="text-gray-500">Email:</span> <span className="font-medium">{selectedOrder.email || selectedOrder.user?.email || 'N/A'}</span></p>
                                        <p><span className="text-gray-500">Address:</span> <span className="font-medium">{selectedOrder.shipping_address}</span></p>
                                    </div>
                                </div>
                                <div>
                                    <h3 className="text-xs uppercase text-gray-500 font-bold mb-3 tracking-wider">Order Status</h3>
                                    <div className="flex flex-wrap gap-2">
                                        {['pending', 'confirmed', 'processing', 'shipping', 'delivered', 'cancelled'].map(s => (
                                            <button
                                                key={s}
                                                disabled={statusUpdating}
                                                onClick={() => updateStatus(selectedOrder.id, s)}
                                                className={clsx(
                                                    "px-3 py-1.5 text-xs rounded border font-medium transition-colors capitalize",
                                                    selectedOrder.status === s
                                                        ? "bg-slate-800 text-white border-slate-800"
                                                        : "bg-white text-gray-600 border-gray-200 hover:border-gray-400"
                                                )}
                                            >
                                                {s}
                                            </button>
                                        ))}
                                    </div>
                                </div>
                            </div>

                            <h3 className="text-xs uppercase text-gray-500 font-bold mb-3 tracking-wider">Order Items</h3>
                            <div className="border rounded-lg overflow-hidden mb-6">
                                <table className="w-full text-sm">
                                    <thead className="bg-gray-50 text-gray-600 border-b">
                                        <tr>
                                            <th className="p-3 text-left">Product</th>
                                            <th className="p-3 text-center">Qty</th>
                                            <th className="p-3 text-right">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y">
                                        {selectedOrder.items?.map((item: any, i: number) => (
                                            <tr key={i}>
                                                <td className="p-3">
                                                    <div className="flex items-center gap-3">
                                                        <div className="w-10 h-10 bg-gray-100 rounded shrink-0">
                                                            {/* Image would go here */}
                                                        </div>
                                                        <div>
                                                            <p className="font-medium">{item.product?.name || item.product_name || 'Product'}</p>
                                                            <p className="text-xs text-gray-500">{item.variant_name || item.variation_snapshot}</p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="p-3 text-center">{item.quantity}</td>
                                                <td className="p-3 text-right font-medium">৳{item.total || item.total_price}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            <div className="flex justify-end">
                                <div className="w-64 space-y-2">
                                    <div className="flex justify-between text-sm">
                                        <span className="text-gray-600">Subtotal</span>
                                        <span className="font-medium">৳{selectedOrder.total_amount || selectedOrder.total_price}</span>
                                    </div>
                                    <div className="flex justify-between text-sm">
                                        <span className="text-gray-600">Shipping</span>
                                        <span className="font-medium">৳{selectedOrder.shipping_cost || 0}</span>
                                    </div>
                                    <div className="flex justify-between text-lg font-bold border-t pt-2 mt-2">
                                        <span className="text-gray-800">Total</span>
                                        <span className="text-blue-600">৳{(parseFloat(selectedOrder.total_amount || selectedOrder.total_price) + parseFloat(selectedOrder.shipping_cost || 0))}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
};

export default AdminOrdersPage;
