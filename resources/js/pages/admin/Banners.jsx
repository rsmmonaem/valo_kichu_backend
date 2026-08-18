import React, { useEffect, useState } from 'react';
import api from '../../services/api';
import { Plus, Edit, Trash2, Image as ImageIcon } from 'lucide-react';
import toast from 'react-hot-toast';

const Banners = () => {
    const [banners, setBanners] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [editingBanner, setEditingBanner] = useState(null);

    const initialForm = {
        title: '',
        subtitle: '',
        image_url: '',
        link: '',
        order_index: 0,
        is_active: true
    };

    const [formData, setFormData] = useState(initialForm);

    useEffect(() => {
        fetchBanners();
    }, []);

    const fetchBanners = async () => {
        setLoading(true);
        try {
            const { data } = await api.get('/admin/v1/banners');
            setBanners(data.data || data || []);
        } catch (error) {
            console.error('Failed to fetch banners', error);
        } finally {
            setLoading(false);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            if (editingBanner) {
                await api.put(`/admin/v1/banners/${editingBanner.id}`, formData);
                toast.success('Banner updated successfully');
            } else {
                await api.post('/admin/v1/banners', formData);
                toast.success('Banner created successfully');
            }
            setShowModal(false);
            setFormData(initialForm);
            setEditingBanner(null);
            fetchBanners();
        } catch (error) {
            console.error(error);
            toast.error('Operation failed');
        }
    };

    const handleDelete = async (id) => {
        if (!window.confirm('Are you sure?')) return;
        try {
            await api.delete(`/admin/v1/banners/${id}`);
            toast.success('Banner deleted');
            fetchBanners();
        } catch (error) {
            toast.error('Failed to delete banner');
        }
    };

    const openCreate = () => {
        setEditingBanner(null);
        setFormData(initialForm);
        setShowModal(true);
    };

    const openEdit = (banner) => {
        setEditingBanner(banner);
        setFormData({
            title: banner.title || '',
            subtitle: banner.subtitle || '',
            image_url: banner.image_url || '',
            link: banner.link || '',
            order_index: banner.order_index || 0,
            is_active: banner.is_active !== undefined ? banner.is_active : true
        });
        setShowModal(true);
    };

    return (
        <div>
            <div className="flex justify-between items-center mb-6">
                <h1 className="text-2xl font-bold text-gray-800 flex items-center gap-2">
                    <ImageIcon className="text-blue-600" /> Banners
                </h1>
                <button onClick={openCreate} className="bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center gap-2 hover:bg-blue-700">
                    <Plus size={20} /> Add Banner
                </button>
            </div>

            <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="w-full text-left">
                        <thead className="bg-gray-50 text-gray-600 font-medium border-b border-gray-200">
                            <tr>
                                <th className="p-4">Preview</th>
                                <th className="p-4">Title</th>
                                <th className="p-4">Subtitle</th>
                                <th className="p-4">Order</th>
                                <th className="p-4">Status</th>
                                <th className="p-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {loading ? (
                                <tr><td colSpan="6" className="p-8 text-center text-gray-500">Loading...</td></tr>
                            ) : banners.length > 0 ? (
                                banners.map(banner => (
                                    <tr key={banner.id} className="hover:bg-gray-50 transition">
                                        <td className="p-4">
                                            <div className="w-32 h-20 bg-gray-100 rounded-lg overflow-hidden border border-gray-200">
                                                {banner.image_url ? (
                                                    <img src={banner.image_url} alt={banner.title} className="w-full h-full object-cover" />
                                                ) : (
                                                    <div className="w-full h-full flex items-center justify-center text-gray-300">
                                                        <ImageIcon size={24} />
                                                    </div>
                                                )}
                                            </div>
                                        </td>
                                        <td className="p-4 font-medium text-gray-800">{banner.title || '-'}</td>
                                        <td className="p-4 text-gray-600 text-sm">{banner.subtitle || '-'}</td>
                                        <td className="p-4">
                                            <span className="px-2 py-1 bg-gray-100 text-gray-700 rounded text-sm font-medium">
                                                {banner.order_index}
                                            </span>
                                        </td>
                                        <td className="p-4">
                                            <span className={`px-2 py-1 rounded text-xs font-semibold ${banner.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'}`}>
                                                {banner.is_active ? 'Active' : 'Inactive'}
                                            </span>
                                        </td>
                                        <td className="p-4 text-right">
                                            <div className="flex items-center justify-end gap-2">
                                                <button onClick={() => openEdit(banner)} className="p-1 text-blue-600 hover:bg-blue-50 rounded">
                                                    <Edit size={18} />
                                                </button>
                                                <button onClick={() => handleDelete(banner.id)} className="p-1 text-red-600 hover:bg-red-50 rounded">
                                                    <Trash2 size={18} />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr><td colSpan="6" className="p-8 text-center text-gray-500">No banners found.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            {/* Modal */}
            {showModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-xl shadow-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                        <div className="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50 rounded-t-xl">
                            <h2 className="text-lg font-bold">{editingBanner ? 'Edit Banner' : 'New Banner'}</h2>
                            <button onClick={() => setShowModal(false)} className="text-gray-400 hover:text-gray-600 text-2xl">×</button>
                        </div>
                        <form onSubmit={handleSubmit} className="p-6 space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Title</label>
                                    <input
                                        type="text"
                                        className="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500 outline-none"
                                        value={formData.title}
                                        onChange={e => setFormData({ ...formData, title: e.target.value })}
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Subtitle</label>
                                    <input
                                        type="text"
                                        className="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500 outline-none"
                                        value={formData.subtitle}
                                        onChange={e => setFormData({ ...formData, subtitle: e.target.value })}
                                    />
                                </div>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Banner Image</label>

                                {/* Image Preview */}
                                {formData.image_url && (
                                    <div className="mb-3 p-2 border rounded-lg bg-gray-50">
                                        <img src={formData.image_url} alt="Preview" className="w-full h-32 object-cover rounded" />
                                    </div>
                                )}

                                {/* Upload Button */}
                                <div className="flex gap-2 mb-2">
                                    <input
                                        type="file"
                                        accept="image/*"
                                        className="hidden"
                                        id="banner-image-upload"
                                        onChange={async (e) => {
                                            const file = e.target.files[0];
                                            if (!file) return;
                                            const formDataUpload = new FormData();
                                            formDataUpload.append('image', file);
                                            formDataUpload.append('folder', 'banners');
                                            try {
                                                const { data } = await api.post('/admin/v1/upload', formDataUpload, {
                                                    headers: { 'Content-Type': 'multipart/form-data' }
                                                });
                                                setFormData({ ...formData, image_url: data.url });
                                                toast.success('Image uploaded');
                                            } catch (error) {
                                                console.error('Upload failed', error);
                                                toast.error('Upload failed');
                                            }
                                        }}
                                    />
                                    <label
                                        htmlFor="banner-image-upload"
                                        className="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 cursor-pointer"
                                    >
                                        Upload Image
                                    </label>
                                    <span className="text-sm text-gray-500 self-center">or</span>
                                </div>

                                {/* URL Input */}
                                <input
                                    type="text"
                                    className="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500 outline-none"
                                    placeholder="Or paste image URL..."
                                    value={formData.image_url}
                                    onChange={e => setFormData({ ...formData, image_url: e.target.value })}
                                    required
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Link (Optional)</label>
                                <input
                                    type="text"
                                    className="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500 outline-none"
                                    placeholder="/products/category-slug"
                                    value={formData.link}
                                    onChange={e => setFormData({ ...formData, link: e.target.value })}
                                />
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Order Index</label>
                                    <input
                                        type="number"
                                        className="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500 outline-none"
                                        value={formData.order_index}
                                        onChange={e => setFormData({ ...formData, order_index: parseInt(e.target.value) || 0 })}
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                    <select
                                        className="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500 outline-none"
                                        value={formData.is_active ? '1' : '0'}
                                        onChange={e => setFormData({ ...formData, is_active: e.target.value === '1' })}
                                    >
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div className="flex justify-end gap-2 pt-4">
                                <button type="button" onClick={() => setShowModal(false)} className="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">
                                    Cancel
                                </button>
                                <button type="submit" className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                    Save Banner
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
};

export default Banners;
