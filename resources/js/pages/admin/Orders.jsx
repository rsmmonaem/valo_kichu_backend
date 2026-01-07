import React, { useEffect, useState } from 'react';
import api from '../../services/api';
import { Eye, Clock, CheckCircle, Truck, Package } from 'lucide-react';

const Orders = () => {
    const [selectedOrder, setSelectedOrder] = useState(null);
    const [statusUpdating, setStatusUpdating] = useState(false);

    useEffect(() => {
        fetchOrders();
    }, []);

    const fetchOrders = async () => {
        setLoading(true);
        try {
            const { data } = await api.get('/admin/orders');
            setOrders(data.data || []);
        } catch (error) {
            console.error(error);
        } finally {
            setLoading(false);
        }
    };

    const updateStatus = async (orderId, newStatus) => {
        if (!window.confirm(`Update order status to ${newStatus}?`)) return;
        setStatusUpdating(true);
        try {
            await api.put(`/admin/orders/${orderId}`, { status: newStatus });
            fetchOrders();
            // Close modal if open on this order, or update local state
            if (selectedOrder && selectedOrder.id === orderId) {
                setSelectedOrder({ ...selectedOrder, status: newStatus });
            }
        } catch (error) {
            console.error(error);
            alert("Failed to update status");
        } finally {
            setStatusUpdating(false);
        }
    };

    const getStatusBadge = (status) => {
        const styles = {
            pending: 'bg-yellow-100 text-yellow-700',
            confirmed: 'bg-blue-100 text-blue-700',
            shipped: 'bg-purple-100 text-purple-700',
            delivered: 'bg-green-100 text-green-700',
            cancelled: 'bg-red-100 text-red-700',
        };
        return (
            <span className={`px-2 py-1 rounded text-xs font-semibold uppercase ${styles[status] || 'bg-gray-100'}`}>
                {status.replace('_', ' ')}
            </span>
        );
    };

    return (
        <div>
            <h1 className="text-2xl font-bold text-gray-800 mb-6">Orders</h1>

            <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="w-full text-left">
                        <thead className="bg-gray-50 text-gray-600 font-medium border-b border-gray-200">
                            <tr>
                                <th className="p-4">Order ID</th>
                                <th className="p-4">Customer</th>
                                <th className="p-4">Date</th>
                                <th className="p-4">Total</th>
                                <th className="p-4">Payment</th>
                                <th className="p-4">Status</th>
                                <th className="p-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {loading ? (
                                <tr><td colSpan="7" className="p-8 text-center text-gray-500">Loading...</td></tr>
                            ) : orders.length > 0 ? (
                                orders.map(order => (
                                    <tr key={order.id} className="hover:bg-gray-50 transition">
                                        <td className="p-4 font-medium">{order.order_number}</td>
                                        <td className="p-4">
                                            <div className="text-sm">
                                                <p className="font-medium text-gray-800">{order.user?.name}</p>
                                                <p className="text-gray-500 text-xs">{order.user?.email}</p>
                                            </div>
                                        </td>
                                        <td className="p-4 text-sm text-gray-600">{new Date(order.created_at).toLocaleDateString()}</td>
                                        <td className="p-4 font-bold">৳ {order.total_amount}</td>
                                        <td className="p-4">
                                            <span className={`px-2 py-1 rounded text-xs ${order.payment_status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700'}`}>
                                                {order.payment_status}
                                            </span>
                                        </td>
                                        <td className="p-4">{getStatusBadge(order.status)}</td>
                                        <td className="p-4 text-right">
                                            <button
                                                onClick={() => setSelectedOrder(order)}
                                                className="text-blue-600 hover:underline text-sm flex items-center justify-end gap-1"
                                            >
                                                <Eye size={16} /> View
                                            </button>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr><td colSpan="7" className="p-8 text-center text-gray-500">No orders found.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            {/* Order Details Modal */}
            {selectedOrder && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-xl shadow-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                        <div className="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                            <h2 className="text-lg font-bold">Order Details #{selectedOrder.order_number}</h2>
                            <button onClick={() => setSelectedOrder(null)} className="text-gray-400 hover:text-gray-600">×</button>
                        </div>
                        <div className="p-6 space-y-6">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <h3 className="text-xs uppercase text-gray-500 font-bold mb-1">Customer</h3>
                                    <p className="font-medium">{selectedOrder.user?.name}</p>
                                    <p className="text-sm text-gray-500">{selectedOrder.contact_number}</p>
                                    <p className="text-sm text-gray-500">{selectedOrder.shipping_address}</p>
                                </div>
                                <div>
                                    <h3 className="text-xs uppercase text-gray-500 font-bold mb-1">Status</h3>
                                    <div className="flex flex-wrap gap-2">
                                        {['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'].map(s => (
                                            <button
                                                key={s}
                                                disabled={statusUpdating}
                                                onClick={() => updateStatus(selectedOrder.id, s)}
                                                className={`px-2 py-1 text-xs rounded border ${selectedOrder.status === s ? 'bg-gray-800 text-white border-gray-800' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-400'}`}
                                            >
                                                {s}
                                            </button>
                                        ))}
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h3 className="text-xs uppercase text-gray-500 font-bold mb-2">Items</h3>
                                <div className="space-y-2">
                                    {selectedOrder.items?.map((item, i) => (
                                        <div key={i} className="flex justify-between items-center bg-gray-50 p-3 rounded-lg">
                                            <div className="flex items-center gap-3">
                                                {/* In real app, load product image from product rel */}
                                                <div className="w-10 h-10 bg-gray-200 rounded"></div>
                                                <div>
                                                    <p className="text-sm font-bold">{item.product_name}</p>
                                                    <p className="text-xs text-gray-500">{item.variation_snapshot || 'Standard'}</p>
                                                </div>
                                            </div>
                                            <div className="text-right">
                                                <p className="text-sm font-bold">৳{item.total_price}</p>
                                                <p className="text-xs text-gray-500">Qty: {item.quantity}</p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <div className="flex justify-between items-center pt-4 border-t border-gray-100">
                                <span className="font-bold text-gray-600">Total Amount</span>
                                <span className="text-xl font-bold text-red-600">৳{selectedOrder.total_amount}</span>
                            </div>
                        </div>
                        <div className="p-4 border-t border-gray-100 bg-gray-50 flex justify-end">
                            <button onClick={() => setSelectedOrder(null)} className="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 text-sm font-medium">Close</button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
};

export default Orders;
