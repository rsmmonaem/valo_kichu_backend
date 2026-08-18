"use client";

import React, { useEffect, useState } from 'react';
import { Plus, Search, Edit, Trash2, MoreVertical } from 'lucide-react';
import { authFetch } from '@/lib/api';
import clsx from 'clsx';
import Link from 'next/link';
import toast from 'react-hot-toast';

const AdminProductsPage = () => {
    const [products, setProducts] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    const [searchTerm, setSearchTerm] = useState('');
    const [currentPage, setCurrentPage] = useState(1);
    const [totalPages, setTotalPages] = useState(1);

    const fetchProducts = async (page = 1) => {
        setLoading(true);
        try {
            const res = await authFetch(`/admin/v1/products?page=${page}&search=${searchTerm}`);
            if (res.ok) {
                const data = await res.json();
                setProducts(data.data || []);
                setCurrentPage(data.current_page || 1);
                setTotalPages(data.last_page || 1);
            }
        } catch (error) {
            console.error(error);
            toast.error("Failed to load products");
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        const timeoutId = setTimeout(() => {
            fetchProducts(1);
        }, 500);
        return () => clearTimeout(timeoutId);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [searchTerm]);

    const handleDelete = async (id: number) => {
        if (!window.confirm("Are you sure you want to delete this product?")) return;

        try {
            const res = await authFetch(`/v1/admin/products/${id}`, { method: 'DELETE' });
            if (res.ok) {
                toast.success("Product deleted successfully");
                fetchProducts(currentPage);
            } else {
                toast.error("Failed to delete product");
            }
        } catch (error) {
            console.error(error);
            toast.error("An error occurred");
        }
    };

    return (
        <div>
            <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <h1 className="text-2xl font-bold text-gray-800">Products</h1>
                <Link
                    href="/admin/products/create"
                    className="bg-blue-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-blue-700 transition flex items-center gap-2"
                >
                    <Plus size={20} /> Add New Product
                </Link>
            </div>

            <div className="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6">
                <div className="relative">
                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size={20} />
                    <input
                        type="text"
                        placeholder="Search products..."
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                        className="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition"
                    />
                </div>
            </div>

            <div className="bg-white rounded-xl shadow-lg shadow-gray-200/50 border border-gray-100 overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="w-full text-left border-collapse">
                        <thead className="bg-gray-50/50 text-gray-500 text-xs font-semibold uppercase tracking-wider border-b border-gray-200">
                            <tr>
                                <th className="p-4 pl-6 w-20">ID</th>
                                <th className="p-4">Product Details</th>
                                <th className="p-4">Category</th>
                                <th className="p-4">Stock Status</th>
                                <th className="p-4">Price</th>
                                <th className="p-4">Status</th>
                                <th className="p-4 text-right pr-6">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100 text-sm">
                            {loading ? (
                                <tr><td colSpan={7} className="p-8 text-center text-gray-500">Loading products...</td></tr>
                            ) : products.length > 0 ? (
                                products.map(product => (
                                    <tr key={product.id} className="hover:bg-gray-50 transition">
                                        <td className="p-4 text-gray-500">#{product.id}</td>
                                        <td className="p-4">
                                            <div className="flex items-center gap-3">
                                                <div className="w-10 h-10 bg-gray-100 rounded shrink-0 overflow-hidden">
                                                    <img
                                                        src={product.image && product.image.startsWith('http') ? product.image : (product.image ? `http://127.0.0.1:8000/${product.image}` : '/placeholder.png')}
                                                        alt={product.name}
                                                        className="w-full h-full object-cover"
                                                    />
                                                </div>
                                                <span className="font-medium text-gray-900 line-clamp-1 max-w-xs">{product.name}</span>
                                            </div>
                                        </td>
                                        <td className="p-4 text-gray-600">{product.category?.name || 'Uncategorized'}</td>
                                        <td className="p-4">
                                            <span className={clsx(
                                                "font-medium",
                                                (product.current_stock || product.stock_quantity) > 10 ? "text-green-600" : "text-red-500"
                                            )}>
                                                {product.current_stock || product.stock_quantity || 0}
                                            </span>
                                        </td>
                                        <td className="p-4 font-medium">৳{product.price}</td>
                                        <td className="p-4">
                                            <span className={clsx(
                                                "px-2 py-1 rounded text-xs capitalize",
                                                product.status === 'active' ? "bg-green-100 text-green-700" : "bg-gray-100 text-gray-700"
                                            )}>
                                                {product.status || 'Active'}
                                            </span>
                                        </td>
                                        <td className="p-4 text-right">
                                            <div className="flex items-center justify-end gap-2">
                                                <Link href={`/admin/products/${product.id}/edit`} className="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition">
                                                    <Edit size={16} />
                                                </Link>
                                                <button
                                                    onClick={() => handleDelete(product.id)}
                                                    className="p-2 text-red-500 hover:bg-red-50 rounded-lg transition"
                                                >
                                                    <Trash2 size={16} />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr><td colSpan={7} className="p-8 text-center text-gray-500">No products found.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination */}
                <div className="p-4 border-t border-gray-100 flex items-center justify-between">
                    <button
                        disabled={currentPage <= 1}
                        onClick={() => fetchProducts(currentPage - 1)}
                        className="px-4 py-2 border rounded-lg text-sm disabled:opacity-50 hover:bg-gray-50"
                    >
                        Previous
                    </button>
                    <span className="text-sm text-gray-600">Page {currentPage} of {totalPages}</span>
                    <button
                        disabled={currentPage >= totalPages}
                        onClick={() => fetchProducts(currentPage + 1)}
                        className="px-4 py-2 border rounded-lg text-sm disabled:opacity-50 hover:bg-gray-50"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>
    );
};

export default AdminProductsPage;
