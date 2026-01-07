import React, { useEffect, useState } from 'react';
import api from '../../services/api';
import { Plus, Edit, Trash2, Search } from 'lucide-react';

const Products = () => {
    const [showModal, setShowModal] = useState(false);
    const [editingProduct, setEditingProduct] = useState(null);
    const [categories, setCategories] = useState([]);

    // Initial Form State
    const initialForm = {
        name: '',
        description: '',
        category_id: '',
        base_price: '',
        sale_price: '',
        stock_quantity: '',
        image_url: '', // Simple single image URL input for demo
        is_active: true
    };

    const [formData, setFormData] = useState(initialForm);

    useEffect(() => {
        fetchProducts();
        fetchCategories();
    }, [search]);

    const fetchCategories = async () => {
        try {
            const { data } = await api.get('/admin/categories');
            setCategories(data || []);
        } catch (e) { console.error(e); }
    };

    const fetchProducts = async () => {
        setLoading(true);
        try {
            const { data } = await api.get('/admin/products', { params: { search } });
            setProducts(data.data || []);
        } catch (error) {
            console.error(error);
        } finally {
            setLoading(false);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            // Prepare payload
            const payload = {
                ...formData,
                // Handle image logic: if URL provided, wrap in array as controller expects
                images: formData.image_url ? [formData.image_url] : []
            };

            if (editingProduct) {
                await api.put(`/admin/products/${editingProduct.id}`, payload);
            } else {
                await api.post('/admin/products', payload);
            }
            setShowModal(false);
            fetchProducts();
        } catch (error) {
            console.error("Failed to save product", error);
            alert("Failed to save product. Check console.");
        }
    };

    const handleDelete = async (id) => {
        if (!window.confirm("Are you sure?")) return;
        try {
            await api.delete(`/admin/products/${id}`);
            fetchProducts();
        } catch (error) {
            console.error("Failed to delete product", error);
        }
    };

    const openCreate = () => {
        setEditingProduct(null);
        setFormData(initialForm);
        setShowModal(true);
    };

    const openEdit = (product) => {
        setEditingProduct(product);
        setFormData({
            name: product.name,
            description: product.description || '',
            category_id: product.category_id,
            base_price: product.base_price,
            sale_price: product.sale_price || '',
            stock_quantity: product.stock_quantity,
            image_url: product.images && product.images.length > 0 ? product.images[0] : '',
            is_active: !!product.is_active
        });
        setShowModal(true);
    };

    return (
        <div>
            <div className="flex justify-between items-center mb-6">
                <h1 className="text-2xl font-bold text-gray-800">Products</h1>
                <button onClick={openCreate} className="bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center gap-2 hover:bg-blue-700">
                    <Plus size={20} /> Add Product
                </button>
            </div>

            <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                {/* Filters */}
                <div className="p-4 border-b border-gray-100 flex gap-4">
                    <div className="relative flex-1 max-w-sm">
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size={18} />
                        <input
                            type="text"
                            placeholder="Search products..."
                            className="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:border-blue-500"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                        />
                    </div>
                </div>

                {/* Table */}
                <div className="overflow-x-auto">
                    <table className="w-full text-left">
                        <thead className="bg-gray-50 text-gray-600 font-medium border-b border-gray-200">
                            <tr>
                                <th className="p-4">Product</th>
                                <th className="p-4">Category</th>
                                <th className="p-4">Price</th>
                                <th className="p-4">Stock</th>
                                <th className="p-4">Source</th>
                                <th className="p-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {loading ? (
                                <tr><td colSpan="6" className="p-8 text-center text-gray-500">Loading...</td></tr>
                            ) : products.length > 0 ? (
                                products.map(product => (
                                    <tr key={product.id} className="hover:bg-gray-50 transition">
                                        <td className="p-4">
                                            <div className="flex items-center gap-3">
                                                <div className="w-10 h-10 bg-gray-100 rounded overflow-hidden">
                                                    {product.images && product.images.length > 0 ? (
                                                        <img src={product.images[0]} alt="" className="w-full h-full object-cover" />
                                                    ) : (
                                                        <div className="w-full h-full flex items-center justify-center text-xs text-gray-400">Img</div>
                                                    )}
                                                </div>
                                                <span className="font-medium text-gray-800">{product.name}</span>
                                            </div>
                                        </td>
                                        <td className="p-4 text-gray-600">{product.category?.name || 'Uncategorized'}</td>
                                        <td className="p-4 font-medium">৳ {product.base_price}</td>
                                        <td className="p-4">
                                            <span className={`px-2 py-1 rounded text-xs ${product.stock_quantity > 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`}>
                                                {product.stock_quantity} in stock
                                            </span>
                                        </td>
                                        <td className="p-4">
                                            <span className="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs uppercase">{product.source_type || 'local'}</span>
                                        </td>
                                        <td className="p-4 text-right">
                                            <div className="flex items-center justify-end gap-2">
                                                <button onClick={() => openEdit(product)} className="p-1 text-blue-600 hover:bg-blue-50 rounded"><Edit size={18} /></button>
                                                <button onClick={() => handleDelete(product.id)} className="p-1 text-red-600 hover:bg-red-50 rounded"><Trash2 size={18} /></button>
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr><td colSpan="6" className="p-8 text-center text-gray-500">No products found.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination (Mock UI) */}
                <div className="p-4 border-t border-gray-100 flex justify-between items-center text-sm text-gray-500">
                    <span>Showing 1 to {products.length} of {products.length} results</span>
                    <div className="flex gap-1">
                        <button className="px-3 py-1 border rounded hover:bg-gray-50" disabled>Previous</button>
                        <button className="px-3 py-1 border rounded hover:bg-gray-50" disabled>Next</button>
                    </div>
                </div>
            </div>

            {/* Modal */}
            {showModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-xl shadow-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                        <div className="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                            <h2 className="text-lg font-bold">{editingProduct ? 'Edit Product' : 'New Product'}</h2>
                            <button onClick={() => setShowModal(false)} className="text-gray-400 hover:text-gray-600">×</button>
                        </div>
                        <form onSubmit={handleSubmit} className="p-6 space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div className="col-span-2">
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Name</label>
                                    <input type="text" className="w-full border rounded-lg p-2" required
                                        value={formData.name} onChange={e => setFormData({ ...formData, name: e.target.value })} />
                                </div>
                                <div className="col-span-2">
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                    <textarea className="w-full border rounded-lg p-2" rows="3"
                                        value={formData.description} onChange={e => setFormData({ ...formData, description: e.target.value })}></textarea>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Category</label>
                                    <select className="w-full border rounded-lg p-2" required
                                        value={formData.category_id} onChange={e => setFormData({ ...formData, category_id: e.target.value })}>
                                        <option value="">Select Category</option>
                                        {categories.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Stock</label>
                                    <input type="number" className="w-full border rounded-lg p-2" required
                                        value={formData.stock_quantity} onChange={e => setFormData({ ...formData, stock_quantity: e.target.value })} />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Base Price</label>
                                    <input type="number" className="w-full border rounded-lg p-2" required
                                        value={formData.base_price} onChange={e => setFormData({ ...formData, base_price: e.target.value })} />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Sale Price</label>
                                    <input type="number" className="w-full border rounded-lg p-2"
                                        value={formData.sale_price} onChange={e => setFormData({ ...formData, sale_price: e.target.value })} />
                                </div>
                                <div className="col-span-2">
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Image URL</label>
                                    <input type="text" className="w-full border rounded-lg p-2" placeholder="https://..."
                                        value={formData.image_url} onChange={e => setFormData({ ...formData, image_url: e.target.value })} />
                                </div>
                            </div>
                            <div className="flex justify-end gap-2 pt-4">
                                <button type="button" onClick={() => setShowModal(false)} className="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Cancel</button>
                                <button type="submit" className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save Product</button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
};

export default Products;
