import React, { useEffect, useState } from 'react';
import api from '../../services/api';
import { Plus, Edit, Trash2 } from 'lucide-react';

const Categories = () => {
    const [categories, setCategories] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [editingCategory, setEditingCategory] = useState(null);
    const [formData, setFormData] = useState({ name: '', image: '', is_active: true });

    useEffect(() => {
        fetchCategories();
    }, []);

    const fetchCategories = async () => {
        setLoading(true);
        try {
            const { data } = await api.get('/admin/v1/categories');
            setCategories(data || []);
        } catch (error) {
            console.error(error);
        } finally {
            setLoading(false);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            if (editingCategory) {
                await api.put(`/admin/v1/categories/${editingCategory.id}`, formData);
            } else {
                await api.post('/admin/v1/categories', formData);
            }
            setShowModal(false);
            fetchCategories();
            setFormData({ name: '', image: '', is_active: true });
            setEditingCategory(null);
        } catch (error) {
            console.error("Failed to save category", error);
        }
    };

    const handleDelete = async (id) => {
        if (!window.confirm("Are you sure?")) return;
        try {
            await api.delete(`/admin/v1/categories/${id}`);
            fetchCategories();
        } catch (error) {
            console.error("Failed to delete category", error);
        }
    };

    const openEdit = (cat) => {
        setEditingCategory(cat);
        setFormData({ name: cat.name, image: cat.image || '', is_active: !!cat.is_active });
        setShowModal(true);
    };

    const openCreate = () => {
        setEditingCategory(null);
        setFormData({ name: '', image: '', is_active: true });
        setShowModal(true);
    };

    return (
        <div>
            <div className="flex justify-between items-center mb-6">
                <h1 className="text-2xl font-bold text-gray-800">Categories</h1>
                <button onClick={openCreate} className="bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center gap-2 hover:bg-blue-700">
                    <Plus size={20} /> Add Category
                </button>
            </div>

            <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <ul className="divide-y divide-gray-100">
                    {loading ? (
                        <li className="p-8 text-center text-gray-500">Loading...</li>
                    ) : categories.length > 0 ? (
                        categories.map(cat => (
                            <li key={cat.id} className="p-4 flex items-center justify-between hover:bg-gray-50">
                                <div className="flex items-center gap-4">
                                    <div className="w-12 h-12 bg-gray-100 rounded-lg overflow-hidden flex items-center justify-center text-gray-400">
                                        {cat.image ? <img src={cat.image} className="w-full h-full object-cover" /> : 'Icon'}
                                    </div>
                                    <div>
                                        <h3 className="font-medium text-gray-800">{cat.name}</h3>
                                        <p className="text-xs text-gray-500">slug: {cat.slug}</p>
                                    </div>
                                </div>
                                <div className="flex items-center gap-2">
                                    <button onClick={() => openEdit(cat)} className="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded"><Edit size={18} /></button>
                                    <button onClick={() => handleDelete(cat.id)} className="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded"><Trash2 size={18} /></button>
                                </div>
                            </li>
                        ))
                    ) : (
                        <li className="p-8 text-center text-gray-500">No categories found.</li>
                    )}
                </ul>
            </div>

            {/* Modal */}
            {showModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div className="bg-white rounded-xl p-6 w-full max-w-md">
                        <h2 className="text-xl font-bold mb-4">{editingCategory ? 'Edit Category' : 'New Category'}</h2>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Name</label>
                                <input
                                    type="text"
                                    className="w-full border rounded-lg p-2"
                                    value={formData.name}
                                    onChange={e => setFormData({ ...formData, name: e.target.value })}
                                    required
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Category Image</label>

                                {/* Image Preview */}
                                {formData.image && (
                                    <div className="mb-3 p-2 border rounded-lg bg-gray-50">
                                        <img src={formData.image} alt="Preview" className="w-full h-32 object-cover rounded" />
                                    </div>
                                )}

                                {/* Upload Button */}
                                <div className="flex gap-2 mb-2">
                                    <input
                                        type="file"
                                        accept="image/*"
                                        className="hidden"
                                        id="category-image-upload"
                                        onChange={async (e) => {
                                            const file = e.target.files[0];
                                            if (!file) return;
                                            const formDataUpload = new FormData();
                                            formDataUpload.append('image', file);
                                            formDataUpload.append('folder', 'categories');
                                            try {
                                                const { data } = await api.post('/admin/v1/upload', formDataUpload, {
                                                    headers: { 'Content-Type': 'multipart/form-data' }
                                                });
                                                setFormData({ ...formData, image: data.url });
                                            } catch (error) {
                                                console.error('Upload failed', error);
                                                alert('Upload failed');
                                            }
                                        }}
                                    />
                                    <label
                                        htmlFor="category-image-upload"
                                        className="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 cursor-pointer"
                                    >
                                        Upload Image
                                    </label>
                                    <span className="text-sm text-gray-500 self-center">or</span>
                                </div>

                                {/* URL Input */}
                                <input
                                    type="text"
                                    className="w-full border rounded-lg p-2"
                                    placeholder="Or paste image URL..."
                                    value={formData.image}
                                    onChange={e => setFormData({ ...formData, image: e.target.value })}
                                />
                            </div>
                            <div className="flex justify-end gap-2 mt-6">
                                <button type="button" onClick={() => setShowModal(false)} className="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Cancel</button>
                                <button type="submit" className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
};

export default Categories;
