"use client";

import React, { useEffect, useState } from 'react';
import { User, Search, Ban, CheckCircle } from 'lucide-react';
import { authFetch } from '@/lib/api';
import toast from 'react-hot-toast';
import clsx from 'clsx';

const CustomersPage = () => {
    const [customers, setCustomers] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    const [searchTerm, setSearchTerm] = useState("");

    const fetchCustomers = async () => {
        setLoading(true);
        try {
            const res = await authFetch('/admin/v1/customers');
            if (res.ok) {
                const data = await res.json();
                setCustomers(data.data || data || []);
            }
        } catch (error) {
            console.error(error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchCustomers();
    }, []);

    const toggleStatus = async (id: number, currentStatus: boolean) => {
        const action = currentStatus ? "block" : "unblock";
        if (!window.confirm(`Are you sure you want to ${action} this customer?`)) return;

        try {
            const res = await authFetch(`/admin/v1/customers/${id}/status`, {
                method: 'PUT',
                body: JSON.stringify({ is_active: !currentStatus })
            });

            if (res.ok) {
                toast.success(`Customer ${action}ed`);
                setCustomers(customers.map(c => c.id === id ? { ...c, is_active: !currentStatus } : c));
            }
        } catch (e) {
            toast.error("Action failed");
        }
    };

    const filtered = customers.filter(c =>
        c.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        c.email.toLowerCase().includes(searchTerm.toLowerCase()) ||
        (c.phone && c.phone.includes(searchTerm))
    );

    return (
        <div>
            <h1 className="text-2xl font-bold text-gray-800 mb-6">Customers</h1>

            <div className="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6">
                <div className="relative">
                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size={20} />
                    <input
                        type="text"
                        placeholder="Search customers by name, email or phone..."
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                        className="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                    />
                </div>
            </div>

            <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="w-full text-left">
                        <thead className="bg-gray-50 text-gray-600 font-medium border-b border-gray-200">
                            <tr>
                                <th className="p-4 w-16">ID</th>
                                <th className="p-4">Customer</th>
                                <th className="p-4">Contact</th>
                                <th className="p-4">Orders</th>
                                <th className="p-4">Status</th>
                                <th className="p-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100 text-sm">
                            {loading ? (
                                <tr><td colSpan={6} className="p-8 text-center text-gray-500">Loading customers...</td></tr>
                            ) : filtered.length > 0 ? (
                                filtered.map(customer => (
                                    <tr key={customer.id} className="hover:bg-gray-50 transition">
                                        <td className="p-4 text-gray-500">#{customer.id}</td>
                                        <td className="p-4">
                                            <div className="flex items-center gap-3">
                                                <div className="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 overflow-hidden">
                                                    {customer.image ? <img src={customer.image} className="w-full h-full object-cover" /> : <User size={18} />}
                                                </div>
                                                <div>
                                                    <p className="font-medium text-gray-900">{customer.name}</p>
                                                    <p className="text-gray-500 text-xs">Joined {new Date(customer.created_at).toLocaleDateString()}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="p-4">
                                            <p className="text-gray-800">{customer.email}</p>
                                            <p className="text-gray-500 text-xs">{customer.phone}</p>
                                        </td>
                                        <td className="p-4">
                                            <span className="font-medium">{customer.orders_count || 0}</span>
                                        </td>
                                        <td className="p-4">
                                            <span className={clsx(
                                                "px-2 py-1 rounded text-xs capitalize",
                                                customer.is_active !== false ? "bg-green-100 text-green-700" : "bg-red-100 text-red-700"
                                            )}>
                                                {customer.is_active !== false ? 'Active' : 'Blocked'}
                                            </span>
                                        </td>
                                        <td className="p-4 text-right">
                                            <button
                                                onClick={() => toggleStatus(customer.id, customer.is_active !== false)}
                                                className={clsx(
                                                    "p-2 rounded-lg transition text-xs font-medium flex items-center gap-1 ml-auto",
                                                    customer.is_active !== false ? "text-red-600 hover:bg-red-50" : "text-green-600 hover:bg-green-50"
                                                )}
                                            >
                                                {customer.is_active !== false ? <><Ban size={14} /> Block</> : <><CheckCircle size={14} /> Unblock</>}
                                            </button>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr><td colSpan={6} className="p-8 text-center text-gray-500">No customers found.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
};

export default CustomersPage;
