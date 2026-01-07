import React, { useState, useEffect } from 'react';
import api from '../../services/api';
import { Plus, Trash2, Save, Image as ImageIcon } from 'lucide-react';
import toast from 'react-hot-toast';

const Appearance = () => {
    const [settings, setSettings] = useState({
        site_logo: '',
    });
    const [banners, setBanners] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchData();
    }, []);

    const fetchData = async () => {
        setLoading(true);
        try {
            const [settingsRes, bannersRes] = await Promise.all([
                api.get('/admin/settings'),
                api.get('/admin/banners')
            ]);

            // Map settings list to object
            const settingsObj = {};
            settingsRes.data.forEach(s => {
                settingsObj[s.key] = s.value;
            });
            setSettings(prev => ({ ...prev, ...settingsObj }));
            setBanners(bannersRes.data || []);
        } catch (error) {
            console.error("Failed to fetch appearance data", error);
            toast.error("Failed to load settings");
        } finally {
            setLoading(false);
        }
    };

    const handleSaveSettings = async () => {
        try {
            const settingsArray = Object.keys(settings).map(key => ({
                key,
                value: settings[key]
            }));
            await api.post('/admin/settings', { settings: settingsArray });
            toast.success("Settings saved successfully!");
        } catch (error) {
            toast.error("Failed to save settings");
        }
    };

    const handleAddBanner = () => {
        setBanners([...banners, { image_url: '', link: '', title: '', subtitle: '', order_index: banners.length }]);
    };

    const handleRemoveBanner = async (index, id) => {
        if (id) {
            if (!window.confirm("Are you sure you want to delete this banner?")) return;
            try {
                await api.delete(`/admin/banners/${id}`);
                toast.success("Banner deleted");
            } catch (error) {
                toast.error("Failed to delete banner");
                return;
            }
        }
        const newBanners = banners.filter((_, i) => i !== index);
        setBanners(newBanners);
    };

    const handleBannerChange = (index, field, value) => {
        const newBanners = [...banners];
        newBanners[index][field] = value;
        setBanners(newBanners);
    };

    const handleSaveBanners = async () => {
        try {
            const savePromises = banners.map(banner => {
                if (banner.id) {
                    return api.put(`/admin/banners/${banner.id}`, banner);
                } else {
                    return api.post('/admin/banners', banner);
                }
            });
            await Promise.all(savePromises);
            toast.success("Banners saved successfully!");
            fetchData(); // Refresh to get IDs for new banners
        } catch (error) {
            toast.error("Failed to save banners");
        }
    };

    if (loading) return <div className="p-8 text-center">Loading...</div>;

    return (
        <div className="space-y-8">
            <h1 className="text-2xl font-bold text-gray-800">Appearance Settings</h1>

            {/* General Settings */}
            <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h2 className="text-lg font-semibold mb-4 flex items-center gap-2">
                    <ImageIcon size={20} className="text-blue-600" /> Site Logo
                </h2>
                <div className="space-y-4 max-w-xl">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Logo URL</label>
                        <input
                            type="text"
                            className="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500 outline-none"
                            placeholder="https://..."
                            value={settings.site_logo}
                            onChange={e => setSettings({ ...settings, site_logo: e.target.value })}
                        />
                    </div>
                    {settings.site_logo && (
                        <div className="mt-2 p-2 border rounded-lg bg-gray-50 flex justify-center">
                            <img src={settings.site_logo} alt="Logo Preview" className="h-12 object-contain" />
                        </div>
                    )}
                    <button
                        onClick={handleSaveSettings}
                        className="bg-blue-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-blue-700 flex items-center gap-2"
                    >
                        <Save size={18} /> Save Site Logo
                    </button>
                </div>
            </div>

            {/* Hero Banners */}
            <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <div className="flex justify-between items-center mb-6">
                    <h2 className="text-lg font-semibold flex items-center gap-2">
                        <Plus size={20} className="text-green-600" /> Hero Banners
                    </h2>
                    <button
                        onClick={handleAddBanner}
                        className="text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1"
                    >
                        <Plus size={18} /> Add New Banner
                    </button>
                </div>

                <div className="space-y-6">
                    {banners.map((banner, index) => (
                        <div key={index} className="p-4 border border-gray-200 rounded-xl bg-gray-50/50 space-y-4">
                            <div className="flex justify-between">
                                <span className="text-sm font-bold text-gray-500 uppercase">Banner #{index + 1}</span>
                                <button
                                    onClick={() => handleRemoveBanner(index, banner.id)}
                                    className="text-red-500 hover:text-red-700 p-1"
                                >
                                    <Trash2 size={18} />
                                </button>
                            </div>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Image URL</label>
                                    <input
                                        type="text"
                                        className="w-full border rounded-lg p-2 bg-white"
                                        placeholder="https://..."
                                        value={banner.image_url}
                                        onChange={e => handleBannerChange(index, 'image_url', e.target.value)}
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Click Link (Optional)</label>
                                    <input
                                        type="text"
                                        className="w-full border rounded-lg p-2 bg-white"
                                        placeholder="/products/category-slug"
                                        value={banner.link || ''}
                                        onChange={e => handleBannerChange(index, 'link', e.target.value)}
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Title (Overlay Text)</label>
                                    <input
                                        type="text"
                                        className="w-full border rounded-lg p-2 bg-white"
                                        value={banner.title || ''}
                                        onChange={e => handleBannerChange(index, 'title', e.target.value)}
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Subtitle (Overlay Text)</label>
                                    <input
                                        type="text"
                                        className="w-full border rounded-lg p-2 bg-white"
                                        value={banner.subtitle || ''}
                                        onChange={e => handleBannerChange(index, 'subtitle', e.target.value)}
                                    />
                                </div>
                            </div>
                            {banner.image_url && (
                                <div className="relative h-32 rounded-lg overflow-hidden bg-gray-200">
                                    <img src={banner.image_url} alt="Preview" className="w-full h-full object-cover" />
                                    <div className="absolute inset-0 bg-black/30 flex flex-col justify-center px-6 text-white">
                                        <p className="font-bold text-lg">{banner.title}</p>
                                        <p className="text-sm opacity-80">{banner.subtitle}</p>
                                    </div>
                                </div>
                            )}
                        </div>
                    ))}

                    {banners.length === 0 && (
                        <div className="text-center py-8 text-gray-400 border-2 border-dashed rounded-xl">
                            No banners added yet.
                        </div>
                    )}

                    <div className="pt-4">
                        <button
                            onClick={handleSaveBanners}
                            className="bg-green-600 text-white px-8 py-3 rounded-lg font-bold hover:bg-green-700 flex items-center gap-2 shadow-lg"
                        >
                            <Save size={20} /> Save All Banners
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default Appearance;
