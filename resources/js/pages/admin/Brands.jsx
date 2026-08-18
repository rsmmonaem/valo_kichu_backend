import React, { useEffect, useState } from 'react';
import api from '../../services/api';
import { Plus, Edit, Trash2, Search, Tag } from 'lucide-react';
import toast from 'react-hot-toast';

const Brands = () => {
    const [brands, setBrands] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [editingBrand, setEditingBrand] = useState(null);
    const [search, setSearch] = useState('');

    // Form State
    const initialForm = { name: '', logo: '' };
    const [formData, setFormData] = useState(initialForm);

    useEffect(() => {
        fetchBrands();
    }, [search]);

    const fetchBrands = async () => {
        setLoading(true);
        try {
            const { data } = await api.get('/admin/v1/brands', { params: { search } });
            setBrands(data.data || data || []);
        } catch (error) {
            console.error("Failed to fetch brands", error);
            // toast.error("Failed to load brands");
        } finally {
            setLoading(false);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            if (editingBrand) {
                await api.put(`/admin/v1/brands/${editingBrand.id}`, formData);
                toast.success("Brand updated successfully");
            } else {
                await api.post('/admin/v1/brands', formData);
                toast.success("Brand created successfully");
            }
            setShowModal(false);
            fetchBrands();
        } catch (error) {
            console.error(error);
            toast.error("Operation failed");
        }
    };

    const handleDelete = async (id) => {
        if (!window.confirm("Are you sure?")) return;
        try {
            await api.delete(`/admin/v1/brands/${id}`);
            toast.success("Brand deleted");
            fetchBrands();
        } catch (error) {
            toast.error("Failed to delete brand");
        }
    };

    const openCreate = () => {
        setEditingBrand(null);
        setFormData(initialForm);
        setShowModal(true);
    };

    const openEdit = (brand) => {
        setEditingBrand(brand);
        setFormData({ name: brand.name, logo: brand.logo || '' });
        setShowModal(true);
    };

    return (
        <div>
            <div className="flex justify-between items-center mb-6">
                <h1 className="text-2xl font-bold text-gray-800 flex items-center gap-2">
                    <Tag className="text-blue-600" /> Brands
                </h1>
                <button onClick={openCreate} className="bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center gap-2 hover:bg-blue-700">
                    <Plus size={20} /> Add Brand
                </button>
            </div>

            <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div className="p-4 border-b border-gray-100 flex gap-4">
                    <div className="relative flex-1 max-w-sm">
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size={18} />
                        <input
                            type="text"
                            placeholder="Search brands..."
                            className="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:border-blue-500"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                        />
                    </div>
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full text-left">
                        <thead className="bg-gray-50 text-gray-600 font-medium border-b border-gray-200">
                            <tr>
                                <th className="p-4">Logo</th>
                                <th className="p-4">Name</th>
                                <th className="p-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {loading ? (
                                <tr><td colSpan="3" className="p-8 text-center text-gray-500">Loading...</td></tr>
                            ) : brands.length > 0 ? (
                                brands.map(brand => (
                                    <tr key={brand.id} className="hover:bg-gray-50 transition">
                                        <td className="p-4">
                                            <div className="w-12 h-12 bg-gray-100 rounded-lg overflow-hidden border border-gray-200">
                                                {brand.logo ? (
                                                    <img src={brand.logo} alt={brand.name} className="w-full h-full object-contain p-1" />
                                                ) : (
                                                    <div className="w-full h-full flex items-center justify-center text-gray-300"><Tag size={20} /></div>
                                                )}
                                            </div>
                                        </td>
                                        <td className="p-4 font-medium text-gray-800">{brand.name}</td>
                                        <td className="p-4 text-right">
                                            <div className="flex items-center justify-end gap-2">
                                                <button onClick={() => openEdit(brand)} className="p-1 text-blue-600 hover:bg-blue-50 rounded"><Edit size={18} /></button>
                                                <button onClick={() => handleDelete(brand.id)} className="p-1 text-red-600 hover:bg-red-50 rounded"><Trash2 size={18} /></button>
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr><td colSpan="3" className="p-8 text-center text-gray-500">No brands found.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            {/* Modal */}
            {showModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white rounded-xl shadow-lg w-full max-w-md">
                        <div className="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50 rounded-t-xl">
                            <h2 className="text-lg font-bold">{editingBrand ? 'Edit Brand' : 'New Brand'}</h2>
                            <button onClick={() => setShowModal(false)} className="text-gray-400 hover:text-gray-600">×</button>
                        </div>
                        <form onSubmit={handleSubmit} className="p-6 space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Brand Name</label>
                                <input type="text" className="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500 outline-none" required
                                    value={formData.name} onChange={e => setFormData({ ...formData, name: e.target.value })} />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Brand Logo</label>

                                {/* Logo Preview */}
                                {formData.logo && (
                                    <div className="mb-3 p-2 border rounded-lg bg-gray-50 flex justify-center">
                                        <img src={formData.logo} alt="Preview" className="h-24 object-contain" />
                                    </div>
                                )}

                                {/* Upload Button */}
                                <div className="flex gap-2 mb-2">
                                    <input
                                        type="file"
                                        accept="image/*"
                                        className="hidden"
                                        id="brand-logo-upload"
                                        onChange={async (e) => {
                                            const file = e.target.files[0];
                                            if (!file) return;
                                            const formDataUpload = new FormData();
                                            formDataUpload.append('image', file);
                                            formDataUpload.append('folder', 'brands');
                                            try {
                                                const { data } = await api.post('/admin/v1/upload', formDataUpload, {
                                                    headers: { 'Content-Type': 'multipart/form-data' }
                                                });
                                                setFormData({ ...formData, logo: data.url });
                                                toast.success('Logo uploaded');
                                            } catch (error) {
                                                console.error('Upload failed', error);
                                                toast.error('Upload failed');
                                            }
                                        }}
                                    />
                                    <label
                                        htmlFor="brand-logo-upload"
                                        className="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 cursor-pointer"
                                    >
                                        Upload Logo
                                    </label>
                                    <span className="text-sm text-gray-500 self-center">or</span>
                                </div>

                                {/* URL Input */}
                                <input
                                    type="text"
                                    className="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500 outline-none"
                                    placeholder="Or paste logo URL..."
                                    value={formData.logo}
                                    onChange={e => setFormData({ ...formData, logo: e.target.value })}
                                />
                            </div>
                            <div className="flex justify-end gap-2 pt-4">
                                <button type="button" onClick={() => setShowModal(false)} className="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Cancel</button>
                                <button type="submit" className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save Brand</button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </div>
    );
};

export default Brands;
