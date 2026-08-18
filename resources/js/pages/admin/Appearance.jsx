import React, { useState, useEffect } from 'react';
import api from '../../services/api';
import { Plus, Trash2, Save, Image as ImageIcon, Settings } from 'lucide-react';
import toast from 'react-hot-toast';

const Appearance = () => {
    const [settings, setSettings] = useState({
        site_logo: '',
        favicon: '',
        business_name: '',
        site_title: '',
        primary_color: '#2563eb',
        secondary_color: '#1e293b',
        shipping_charge_inside_dhaka: '',
        shipping_charge_outside_dhaka: ''
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
                api.get('/admin/v1/settings'),
                api.get('/admin/v1/banners')
            ]);

            // Map settings list to object
            const settingsObj = {};
            settingsRes.data.forEach(s => {
                settingsObj[s.key] = s.value;
            });

            // Handle potentially different key names if needed, but strive for consistency
            // If DB calls it 'site_name', map it, otherwise assume 'business_name'
            if (settingsObj.site_name && !settingsObj.business_name) {
                settingsObj.business_name = settingsObj.site_name; // migration support
            }

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
            await api.post('/admin/v1/settings', { settings: settingsArray });
            toast.success("Settings saved successfully!");
            // Optionally reload to reflect changes globally if layout listens to local storage or similar, 
            // but PublicLayout fetches fresh on mount/reload.
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
                await api.delete(`/admin/v1/banners/${id}`);
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
                    return api.put(`/admin/v1/banners/${banner.id}`, banner);
                } else {
                    return api.post('/admin/v1/banners', banner);
                }
            });
            await Promise.all(savePromises);
            toast.success("Banners saved successfully!");
            fetchData(); // Refresh to get IDs for new banners
        } catch (error) {
            toast.error("Failed to save banners");
        }
    };

    const handleImageUpload = async (e, field) => {
        const file = e.target.files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('image', file);
        formData.append('folder', 'business_profile'); // Logo goes to business_profile folder

        const toastId = toast.loading("Uploading...");
        try {
            const { data } = await api.post('/admin/v1/upload', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            setSettings(prev => ({ ...prev, [field]: data.path }));
            toast.success("Uploaded successfully!", { id: toastId });
        } catch (error) {
            console.error("Upload failed", error);
            toast.error("Upload failed", { id: toastId });
        }
    };

    if (loading) return <div className="p-8 text-center">Loading...</div>;

    return (
        <div className="space-y-8">
            <h1 className="text-2xl font-bold text-gray-800">Appearance Settings</h1>

            {/* General Settings */}
            <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h2 className="text-lg font-semibold mb-4 flex items-center gap-2">
                    <Settings size={20} className="text-blue-600" /> Business Settings
                </h2>
                <div className="space-y-4 max-w-xl">
                    {/* Business Name */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Business Name</label>
                        <input
                            type="text"
                            className="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500 outline-none"
                            placeholder="My E-Commerce Store"
                            value={settings.business_name || ''}
                            onChange={e => setSettings({ ...settings, business_name: e.target.value })}
                        />
                        <p className="text-xs text-gray-500 mt-1">Displayed in footer and navigation.</p>
                    </div>

                    {/* Site Title */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Site Title (Browser Tab)</label>
                        <input
                            type="text"
                            className="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500 outline-none"
                            placeholder="Store Name - Best Deals"
                            value={settings.site_title || ''}
                            onChange={e => setSettings({ ...settings, site_title: e.target.value })}
                        />
                    </div>

                    {/* Logo & Favicon Row */}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {/* Logo */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Logo</label>
                            <div className="flex items-center gap-4">
                                <div className="w-20 h-20 border rounded-lg bg-gray-50 flex items-center justify-center overflow-hidden">
                                    {settings.site_logo ? (
                                        <img
                                            src={settings.site_logo?.startsWith('http') ? settings.site_logo : `/storage/${settings.site_logo}`}
                                            alt="Logo"
                                            className="w-full h-full object-contain p-2"
                                        />
                                    ) : (
                                        <ImageIcon className="text-gray-400" size={24} />
                                    )}
                                </div>
                                <div className="flex flex-col">
                                    <input
                                        type="file"
                                        accept="image/*"
                                        className="hidden"
                                        id="logo-upload"
                                        onChange={(e) => handleImageUpload(e, 'site_logo')}
                                    />
                                    <label
                                        htmlFor="logo-upload"
                                        className="px-3 py-1.5 border border-gray-300 rounded-lg text-xs font-medium hover:bg-gray-50 cursor-pointer inline-flex items-center gap-1 w-fit"
                                    >
                                        <ImageIcon size={14} /> Upload Logo
                                    </label>
                                    <p className="text-[10px] text-gray-500 mt-1">Recommended size: 200x50px</p>
                                </div>
                            </div>
                        </div>

                        {/* Favicon */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Favicon</label>
                            <div className="flex items-center gap-4">
                                <div className="w-12 h-12 border rounded-lg bg-gray-50 flex items-center justify-center overflow-hidden">
                                    {settings.favicon ? (
                                        <img
                                            src={settings.favicon?.startsWith('http') ? settings.favicon : `/storage/${settings.favicon}`}
                                            alt="Favicon"
                                            className="w-full h-full object-contain p-2"
                                        />
                                    ) : (
                                        <ImageIcon className="text-gray-400" size={20} />
                                    )}
                                </div>
                                <div className="flex flex-col">
                                    <input
                                        type="file"
                                        accept="image/*"
                                        className="hidden"
                                        id="favicon-upload"
                                        onChange={(e) => handleImageUpload(e, 'favicon')}
                                    />
                                    <label
                                        htmlFor="favicon-upload"
                                        className="px-3 py-1.5 border border-gray-300 rounded-lg text-xs font-medium hover:bg-gray-50 cursor-pointer inline-flex items-center gap-1 w-fit"
                                    >
                                        <ImageIcon size={14} /> Upload Icon
                                    </label>
                                    <p className="text-[10px] text-gray-500 mt-1">Recommended size: 32x32px</p>
                                </div>
                            </div>

                            {/* Shipping Charges */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Shipping Charge (Inside Dhaka)</label>
                                    <input
                                        type="number"
                                        className="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500 outline-none"
                                        placeholder="60"
                                        value={settings.shipping_charge_inside_dhaka || ''}
                                        onChange={e => setSettings({ ...settings, shipping_charge_inside_dhaka: e.target.value })}
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Shipping Charge (Outside Dhaka)</label>
                                    <input
                                        type="number"
                                        className="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500 outline-none"
                                        placeholder="120"
                                        value={settings.shipping_charge_outside_dhaka || ''}
                                        onChange={e => setSettings({ ...settings, shipping_charge_outside_dhaka: e.target.value })}
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Colors */}
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Primary Color</label>
                            <div className="flex gap-2">
                                <input
                                    type="color"
                                    className="h-10 w-10 border rounded cursor-pointer"
                                    value={settings.primary_color || '#2563eb'}
                                    onChange={e => setSettings({ ...settings, primary_color: e.target.value })}
                                />
                                <input
                                    type="text"
                                    className="flex-1 border rounded-lg p-2"
                                    value={settings.primary_color || '#2563eb'}
                                    onChange={e => setSettings({ ...settings, primary_color: e.target.value })}
                                />
                            </div>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Secondary Color</label>
                            <div className="flex gap-2">
                                <input
                                    type="color"
                                    className="h-10 w-10 border rounded cursor-pointer"
                                    value={settings.secondary_color || '#1e293b'}
                                    onChange={e => setSettings({ ...settings, secondary_color: e.target.value })}
                                />
                                <input
                                    type="text"
                                    className="flex-1 border rounded-lg p-2"
                                    value={settings.secondary_color || '#1e293b'}
                                    onChange={e => setSettings({ ...settings, secondary_color: e.target.value })}
                                />
                            </div>
                        </div>
                    </div>

                    <button
                        onClick={handleSaveSettings}
                        className="bg-blue-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-blue-700 flex items-center gap-2 mt-4"
                    >
                        <Save size={18} /> Save Settings
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
                                    title="Remove Banner"
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
                                    <img
                                        src={banner.image_url}
                                        alt="Preview"
                                        className="w-full h-full object-cover"
                                        onError={(e) => e.target.style.display = 'none'}
                                    />
                                    <div className="absolute inset-0 bg-black/30 flex flex-col justify-center px-6 text-white text-left">
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
