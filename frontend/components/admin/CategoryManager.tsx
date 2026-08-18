"use client";

import React, { useEffect, useState } from 'react';
import { Plus, Edit, Trash2, Folder, FolderOpen } from 'lucide-react';
import { authFetch } from '@/lib/api';
import clsx from 'clsx';
import { toast } from 'react-hot-toast';

interface Category {
    id: number;
    name: string;
    slug: string;
    image: string | null;
    parent_id: number | null;
    children?: Category[];
    priority?: number;
    is_active: boolean;
}

interface CategoryManagerProps {
    title: string;
    level: 'main' | 'sub' | 'sub-sub';
}

const CategoryManager: React.FC<CategoryManagerProps> = ({ title, level }) => {
    const [categories, setCategories] = useState<Category[]>([]);
    const [displayCategories, setDisplayCategories] = useState<Category[]>([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [editingCategory, setEditingCategory] = useState<Category | null>(null);

    // Form State
    const [formData, setFormData] = useState({
        name: '',
        image: '',
        is_active: true,
        parent_id: '' as string | number,
        main_id: '' as string | number, // For sub-sub level to filter sub-cats
        priority: 0,
        meta_title: '',
        meta_description: '',
        meta_keywords: ''
    });

    useEffect(() => {
        fetchCategories();
    }, []);

    useEffect(() => {
        filterCategories();
    }, [categories, level]);

    const fetchCategories = async () => {
        setLoading(true);
        try {
            const res = await authFetch('/admin/v1/categories');
            if (res.ok) {
                const data = await res.json();
                setCategories(data);
            }
        } catch (error) {
            console.error(error);
        } finally {
            setLoading(false);
        }
    };

    const filterCategories = () => {
        if (!categories.length) return;

        let filtered: Category[] = [];

        if (level === 'main') {
            // Main categories (parent_id is null) - but existing API returns tree
            // We can just use the top level items if API returns tree
            // Or filter by parent_id === null if flat
            // Based on prev implementation, API returns tree.
            filtered = categories;
        } else if (level === 'sub') {
            // Flatten children of main categories
            filtered = categories.flatMap(cat => cat.children || []);
        } else if (level === 'sub-sub') {
            // Flatten children of sub categories
            filtered = categories.flatMap(main =>
                main.children?.flatMap(sub => sub.children || []) || []
            );
        }

        setDisplayCategories(filtered);
    };

    const getParents = () => {
        if (level === 'sub') {
            return categories; // Main categories are parents
        } else if (level === 'sub-sub') {
            // Need to select Main first, then Sub.
            // This helper returns Main categories, logic in form handles sub-selection
            return categories;
        }
        return [];
    };

    const getSubParents = (mainId: number) => {
        const main = categories.find(c => c.id === mainId);
        return main?.children || [];
    };

    // Helper to find Main Category ID for a sub-sub category
    const findMainCategoryId = (cat: Category) => {
        // Logic: Search through tree to find which main category contains this sub-sub
        for (const main of categories) {
            if (main.children) {
                for (const sub of main.children) {
                    if (sub.id === cat.parent_id) {
                        return main.id;
                    }
                }
            }
        }
        return '';
    };

    const openCreate = () => {
        setEditingCategory(null);
        setFormData({ name: '', image: '', is_active: true, parent_id: '', main_id: '', priority: 0, meta_title: '', meta_description: '', meta_keywords: '' });
        setShowModal(true);
    };

    const openEdit = (cat: Category) => {
        setEditingCategory(cat);

        let mainId: string | number = '';
        if (level === 'sub-sub') {
            mainId = findMainCategoryId(cat);
        }

        setFormData({
            name: cat.name,
            image: cat.image || '',
            is_active: cat.is_active,
            parent_id: cat.parent_id || '',
            main_id: mainId,
            priority: cat.priority || 0,
            meta_title: (cat as any).meta_title || '',
            meta_description: (cat as any).meta_description || '',
            meta_keywords: (cat as any).meta_keywords || ''
        });
        setShowModal(true);
    };

    const handleImageUpload = async (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files && e.target.files[0]) {
            const formData = new FormData();
            formData.append('image', e.target.files[0]);
            formData.append('folder', 'categories');

            try {
                const res = await authFetch('/admin/v1/upload', {
                    method: 'POST',
                    body: formData,
                });
                if (res.ok) {
                    const data = await res.json();
                    setFormData(prev => ({ ...prev, image: data.path })); // Store relative path
                }
            } catch (error) {
                console.error('Upload failed', error);
                toast.error('Upload failed');
            }
        }
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        try {
            const url = editingCategory ? `/admin/v1/categories/${editingCategory.id}` : '/admin/v1/categories';
            const method = editingCategory ? 'PUT' : 'POST';

            const payload = {
                ...formData,
                parent_id: formData.parent_id || null
            };

            const res = await authFetch(url, {
                method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });

            if (res.ok) {
                setShowModal(false);
                fetchCategories(); // Refetch to update tree
                toast.success(`Category ${editingCategory ? 'updated' : 'created'} successfully`);
            } else {
                toast.error('Failed to save category');
            }
        } catch (error) {
            console.error(error);
            toast.error('An error occurred');
        }
    };

    const handleDelete = async (id: number) => {
        if (!confirm('Are you sure? This will delete the category and potential sub-categories.')) return;
        try {
            const res = await authFetch(`/admin/v1/categories/${id}`, { method: 'DELETE' });
            if (res.ok) {
                fetchCategories();
                toast.success('Category deleted');
            }
        } catch (error) {
            console.error(error);
        }
    };

    return (
        <div>
            <div className="flex justify-between items-center mb-6">
                <h1 className="text-2xl font-bold text-gray-800">{title}</h1>
                <button
                    onClick={openCreate}
                    className="bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center gap-2 hover:bg-blue-700 shadow-md shadow-blue-500/20 transition-all active:scale-95"
                >
                    <Plus size={20} /> Add {level === 'main' ? 'Category' : level === 'sub' ? 'Sub Category' : 'Sub Sub Category'}
                </button>
            </div>

            <div className="bg-white rounded-xl shadow-lg shadow-gray-200/50 border border-gray-100 overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="w-full text-left border-collapse">
                        <thead className="bg-gray-50/50 text-gray-500 text-xs font-semibold uppercase tracking-wider border-b border-gray-200">
                            <tr>
                                <th className="p-4 pl-6 w-20">ID</th>
                                <th className="p-4">Image</th>
                                <th className="p-4">Name</th>
                                <th className="p-4">Slug</th>
                                {level !== 'main' && <th className="p-4">Parent</th>}
                                <th className="p-4 text-right pr-6">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100 text-sm">
                            {loading ? (
                                <tr>
                                    <td colSpan={6} className="p-8 text-center text-gray-500">Loading...</td>
                                </tr>
                            ) : displayCategories.length > 0 ? (
                                displayCategories.map((cat) => {
                                    // Find parent name for display
                                    // This is expensive O(N^2) but ok for small lists.
                                    // Better: create a lookup map.
                                    // For now, let's just display what we have. API might not return parent object, just ID.

                                    return (
                                        <tr key={cat.id} className="hover:bg-gray-50/50 transition-colors">
                                            <td className="p-4 pl-6 font-mono text-gray-500">#{cat.id}</td>
                                            <td className="p-4">
                                                <div className="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center overflow-hidden border border-gray-200">
                                                    {cat.image ? (
                                                        <img
                                                            src={cat.image.startsWith('http') ? cat.image : `${process.env.NEXT_PUBLIC_API_URL || 'https://backend.valokichu.com'}/storage/${cat.image}`}
                                                            alt={cat.name}
                                                            className="w-full h-full object-cover"
                                                        />
                                                    ) : (
                                                        <FolderOpen size={20} className="text-gray-400" />
                                                    )}
                                                </div>
                                            </td>
                                            <td className="p-4 font-medium text-gray-800">{cat.name}</td>
                                            <td className="p-4 text-gray-500">{cat.slug}</td>
                                            {level !== 'main' && (
                                                <td className="p-4 text-gray-500">
                                                    {cat.parent_id} {/* Ideally replace with name if available in nested structure */}
                                                </td>
                                            )}
                                            <td className="p-4 text-right pr-6">
                                                <div className="flex items-center justify-end gap-2">
                                                    <button
                                                        onClick={() => openEdit(cat)}
                                                        className="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                                    >
                                                        <Edit size={18} />
                                                    </button>
                                                    <button
                                                        onClick={() => handleDelete(cat.id)}
                                                        className="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                                    >
                                                        <Trash2 size={18} />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })
                            ) : (
                                <tr>
                                    <td colSpan={6} className="p-8 text-center text-gray-500">No categories found.</td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            {/* Modal */}
            {showModal && (
                <div className="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl">
                        <h2 className="text-xl font-bold mb-4 text-gray-800">
                            {editingCategory ? "Edit Category" : "New Category"}
                        </h2>
                        <form onSubmit={handleSubmit} className="space-y-4">

                            {/* Level Specific Logic */}
                            {level === 'sub' && (
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Main Category</label>
                                    <select
                                        className="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all"
                                        value={formData.parent_id}
                                        onChange={(e) => setFormData({ ...formData, parent_id: e.target.value })}
                                        required
                                    >
                                        <option value="">Select Main Category</option>
                                        {categories.map(c => (
                                            <option key={c.id} value={c.id}>{c.name}</option>
                                        ))}
                                    </select>
                                </div>
                            )}

                            {level === 'sub-sub' && (
                                <>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">Main Category</label>
                                        <select
                                            className="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all"
                                            value={formData.main_id}
                                            onChange={(e) => setFormData({ ...formData, main_id: e.target.value, parent_id: '' })} // Reset sub when main changes
                                            required
                                        >
                                            <option value="">Select Main Category</option>
                                            {categories.map(c => (
                                                <option key={c.id} value={c.id}>{c.name}</option>
                                            ))}
                                        </select>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">Sub Category</label>
                                        <select
                                            className="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all"
                                            value={formData.parent_id}
                                            onChange={(e) => setFormData({ ...formData, parent_id: e.target.value })}
                                            required
                                            disabled={!formData.main_id}
                                        >
                                            <option value="">Select Sub Category</option>
                                            {formData.main_id && getSubParents(Number(formData.main_id)).map(c => (
                                                <option key={c.id} value={c.id}>{c.name}</option>
                                            ))}
                                        </select>
                                    </div>
                                </>
                            )}

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Name</label>
                                <input
                                    type="text"
                                    className="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all"
                                    value={formData.name}
                                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                    required
                                    placeholder="Category Name"
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                                <select
                                    className="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all"
                                    value={formData.priority}
                                    onChange={(e) => setFormData({ ...formData, priority: Number(e.target.value) })}
                                >
                                    <option value={0}>Set Priority</option>
                                    {[1, 2, 3, 4, 5, 6, 7, 8, 9, 10].map(n => (
                                        <option key={n} value={n}>{n}</option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Image</label>
                                <div className="flex gap-2">
                                    <label className="flex-1 cursor-pointer">
                                        <div className="border border-gray-300 border-dashed rounded-lg p-4 text-center hover:bg-gray-50 transition-colors">
                                            <span className="text-sm text-gray-500">Click to upload</span>
                                            <input type="file" className="hidden" accept="image/*" onChange={handleImageUpload} />
                                        </div>
                                    </label>
                                    {formData.image && (
                                        <div className="w-16 h-16 rounded-lg border border-gray-200 overflow-hidden shrink-0">
                                            <img src={formData.image.startsWith('http') ? formData.image : `${process.env.NEXT_PUBLIC_API_URL || 'https://backend.valokichu.com'}/storage/${formData.image}`} className="w-full h-full object-cover" />
                                        </div>
                                    )}
                                </div>
                            </div>

                            <div className="border-t border-gray-100 pt-4">
                                <h3 className="text-sm font-bold text-gray-800 mb-3">SEO (Optional)</h3>
                                <div className="space-y-3">
                                    <div>
                                        <label className="block text-xs font-medium text-gray-700 mb-1">Meta Title</label>
                                        <input
                                            type="text"
                                            className="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500/20 outline-none text-sm"
                                            value={formData.meta_title || ''}
                                            onChange={(e) => setFormData({ ...formData, meta_title: e.target.value })}
                                            placeholder="SEO Title"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-xs font-medium text-gray-700 mb-1">Meta Description</label>
                                        <textarea
                                            rows={2}
                                            className="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500/20 outline-none text-sm"
                                            value={formData.meta_description || ''}
                                            onChange={(e) => setFormData({ ...formData, meta_description: e.target.value })}
                                            placeholder="SEO Description"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-xs font-medium text-gray-700 mb-1">Meta Keywords</label>
                                        <input
                                            type="text"
                                            className="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500/20 outline-none text-sm"
                                            value={formData.meta_keywords || ''}
                                            onChange={(e) => setFormData({ ...formData, meta_keywords: e.target.value })}
                                            placeholder="keywords, comma, separated"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div className="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
                                <button
                                    type="button"
                                    onClick={() => setShowModal(false)}
                                    className="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg font-medium transition-colors"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium shadow-md shadow-blue-500/20 transition-all active:scale-95"
                                >
                                    {editingCategory ? 'Update' : 'Create'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
};

export default CategoryManager;
