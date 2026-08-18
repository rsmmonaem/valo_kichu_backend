"use client";

import React, { useEffect, useState } from 'react';
import { authFetch } from '@/lib/api';
import { toast } from 'react-hot-toast';
import { Save, Loader2, Image as ImageIcon } from 'lucide-react';

interface Setting {
    key: string;
    value: string;
}

const SettingsPage = () => {
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [settings, setSettings] = useState<Record<string, string>>({});

    const fields = [
        { key: 'site_name', label: 'Site Name', type: 'text' },
        { key: 'site_title', label: 'Meta Title (Default)', type: 'text' },
        { key: 'site_description', label: 'Meta Description (Default)', type: 'textarea' },
        { key: 'site_keywords', label: 'Meta Keywords (Default)', type: 'text' },
        { key: 'primary_color', label: 'Primary Color (Hex)', type: 'color' },
        { key: 'secondary_color', label: 'Secondary Color (Hex)', type: 'color' },
        { key: 'site_logo', label: 'Site Logo', type: 'image' },
        { key: 'site_favicon', label: 'Favicon', type: 'image' },
    ];

    useEffect(() => {
        fetchSettings();
    }, []);

    const fetchSettings = async () => {
        try {
            const res = await authFetch('/admin/v1/settings');
            if (res.ok) {
                const data: Setting[] = await res.json();
                const settingsMap: Record<string, string> = {};
                data.forEach(s => settingsMap[s.key] = s.value);
                setSettings(settingsMap);
            }
        } catch (error) {
            console.error(error);
            toast.error('Failed to load settings');
        } finally {
            setLoading(false);
        }
    };

    const handleValidColor = (color: string) => {
        return /^#[0-9A-F]{6}$/i.test(color) ? color : '#3B82F6';
    };

    const handleChange = (key: string, value: string) => {
        setSettings(prev => ({ ...prev, [key]: value }));
    };

    const handleImageUpload = async (e: React.ChangeEvent<HTMLInputElement>, key: string) => {
        if (e.target.files && e.target.files[0]) {
            const file = e.target.files[0];
            const formData = new FormData();
            formData.append('image', file);
            formData.append('folder', 'settings');

            const savingToast = toast.loading('Uploading...');
            try {
                const res = await authFetch('/admin/v1/upload', {
                    method: 'POST',
                    body: formData,
                });
                if (res.ok) {
                    const data = await res.json();
                    handleChange(key, data.path);
                    toast.dismiss(savingToast);
                    toast.success('Uploaded successfully');
                } else {
                    toast.dismiss(savingToast);
                    toast.error('Upload failed');
                }
            } catch (error) {
                console.error(error);
                toast.dismiss(savingToast);
                toast.error('Upload error');
            }
        }
    };

    const handleSave = async () => {
        setSaving(true);
        try {
            const payload = {
                settings: Object.entries(settings).map(([key, value]) => ({ key, value }))
            };

            const res = await authFetch('/admin/v1/settings', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });

            if (res.ok) {
                toast.success('Settings saved successfully');
            } else {
                toast.error('Failed to save settings');
            }
        } catch (error) {
            console.error(error);
            toast.error('Error saving settings');
        } finally {
            setSaving(false);
        }
    };

    if (loading) return <div className="p-8 text-center text-gray-500">Loading settings...</div>;

    return (
        <div className="max-w-4xl mx-auto">
            <div className="flex justify-between items-center mb-6">
                <h1 className="text-2xl font-bold text-gray-800">Global Settings</h1>
                <button
                    onClick={handleSave}
                    disabled={saving}
                    className="bg-blue-600 text-white px-6 py-2 rounded-lg flex items-center gap-2 hover:bg-blue-700 shadow-md shadow-blue-500/20 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    {saving ? <Loader2 className="animate-spin" size={20} /> : <Save size={20} />}
                    Save Changes
                </button>
            </div>

            <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-6">

                {/* Visual Identity Section */}
                <div className="border-b border-gray-100 pb-6">
                    <h2 className="text-lg font-semibold text-gray-800 mb-4">Visual Identity</h2>
                    <div className="grid md:grid-cols-2 gap-6">
                        {/* Logo Upload */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Site Logo</label>
                            <div className="flex items-center gap-4">
                                <div className="w-24 h-24 bg-gray-50 border border-gray-200 rounded-lg flex items-center justify-center overflow-hidden relative">
                                    {settings.site_logo ? (
                                        <img src={settings.site_logo.startsWith('http') ? settings.site_logo : `${process.env.NEXT_PUBLIC_API_URL || 'https://backend.valokichu.com'}/storage/${settings.site_logo}`} className="w-full h-full object-contain" />
                                    ) : (
                                        <ImageIcon className="text-gray-300" />
                                    )}
                                </div>
                                <label className="cursor-pointer bg-white border border-gray-300 px-4 py-2 rounded-lg hover:bg-gray-50 text-sm font-medium">
                                    Change Logo
                                    <input type="file" className="hidden" accept="image/*" onChange={(e) => handleImageUpload(e, 'site_logo')} />
                                </label>
                            </div>
                        </div>

                        {/* Colors */}
                        <div className="space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Primary Color</label>
                                <div className="flex gap-3">
                                    <input
                                        type="color"
                                        value={handleValidColor(settings.primary_color)}
                                        onChange={(e) => handleChange('primary_color', e.target.value)}
                                        className="h-10 w-20 p-1 rounded border border-gray-300 cursor-pointer"
                                    />
                                    <input
                                        type="text"
                                        value={settings.primary_color || ''}
                                        onChange={(e) => handleChange('primary_color', e.target.value)}
                                        className="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm uppercase"
                                        placeholder="#3B82F6"
                                    />
                                </div>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">Secondary Color</label>
                                <div className="flex gap-3">
                                    <input
                                        type="color"
                                        value={handleValidColor(settings.secondary_color)}
                                        onChange={(e) => handleChange('secondary_color', e.target.value)}
                                        className="h-10 w-20 p-1 rounded border border-gray-300 cursor-pointer"
                                    />
                                    <input
                                        type="text"
                                        value={settings.secondary_color || ''}
                                        onChange={(e) => handleChange('secondary_color', e.target.value)}
                                        className="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm uppercase"
                                        placeholder="#1E293B"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* SEO Section */}
                <div>
                    <h2 className="text-lg font-semibold text-gray-800 mb-4">SEO & Metadata</h2>
                    <div className="space-y-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Site Title</label>
                            <input
                                type="text"
                                value={settings.site_title || ''}
                                onChange={(e) => handleChange('site_title', e.target.value)}
                                className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500/20 outline-none"
                                placeholder="Valokichu - Premium Marketplace"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Meta Description</label>
                            <textarea
                                rows={3}
                                value={settings.site_description || ''}
                                onChange={(e) => handleChange('site_description', e.target.value)}
                                className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500/20 outline-none"
                                placeholder="Best products at wholesale prices..."
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Keywords</label>
                            <input
                                type="text"
                                value={settings.site_keywords || ''}
                                onChange={(e) => handleChange('site_keywords', e.target.value)}
                                className="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500/20 outline-none"
                                placeholder="e-commerce, wholesale, fashion"
                            />
                        </div>
                    </div>
                </div>

                {/* Page Specific SEO */}
                <div className="border-t border-gray-100 pt-6">
                    <h2 className="text-lg font-semibold text-gray-800 mb-4">Page SEO Configurations</h2>
                    <div className="grid md:grid-cols-2 gap-6">
                        {/* Products Page */}
                        <div className="space-y-4 p-4 bg-gray-50 rounded-lg">
                            <h3 className="font-medium text-gray-700">Products Listing Page</h3>
                            <div>
                                <label className="block text-sm text-gray-600 mb-1">Page Title</label>
                                <input
                                    type="text"
                                    value={settings.products_page_title || ''}
                                    onChange={(e) => handleChange('products_page_title', e.target.value)}
                                    className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500/20 outline-none"
                                />
                            </div>
                            <div>
                                <label className="block text-sm text-gray-600 mb-1">Meta Description</label>
                                <textarea
                                    rows={2}
                                    value={settings.products_page_description || ''}
                                    onChange={(e) => handleChange('products_page_description', e.target.value)}
                                    className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500/20 outline-none"
                                />
                            </div>
                        </div>

                        {/* Categories Page */}
                        <div className="space-y-4 p-4 bg-gray-50 rounded-lg">
                            <h3 className="font-medium text-gray-700">Categories Page</h3>
                            <div>
                                <label className="block text-sm text-gray-600 mb-1">Page Title</label>
                                <input
                                    type="text"
                                    value={settings.categories_page_title || ''}
                                    onChange={(e) => handleChange('categories_page_title', e.target.value)}
                                    className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500/20 outline-none"
                                />
                            </div>
                            <div>
                                <label className="block text-sm text-gray-600 mb-1">Meta Description</label>
                                <textarea
                                    rows={2}
                                    value={settings.categories_page_description || ''}
                                    onChange={(e) => handleChange('categories_page_description', e.target.value)}
                                    className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500/20 outline-none"
                                />
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    );
};

export default SettingsPage;
