"use client";

import React, { useState, useEffect, useCallback } from 'react';
import { useRouter } from 'next/navigation';
import { Plus, X, Upload, Trash2, Save, Image as ImageIcon } from 'lucide-react';
import { authFetch } from '@/lib/api';
import toast from 'react-hot-toast';
import clsx from 'clsx';

interface ProductFormProps {
    initialData?: any;
    isEdit?: boolean;
}

const ProductForm: React.FC<ProductFormProps> = ({ initialData, isEdit = false }) => {
    const router = useRouter();
    const [loading, setLoading] = useState(false);
    const [categories, setCategories] = useState<any[]>([]);
    const [subCategories, setSubCategories] = useState<any[]>([]);
    const [subSubCategories, setSubSubCategories] = useState<any[]>([]);
    const [brands, setBrands] = useState<any[]>([]);

    // Form State
    const [formData, setFormData] = useState({
        name: "",
        description: "",
        category_id: "",
        sub_category_id: "",
        sub_sub_category_id: "",
        brand: "",
        product_type: "Physical",
        product_sku: "",
        unit: "kg",
        price: "",
        purchase_price: "",
        unit_price: "",
        min_order_qty: 1,
        current_stock: 0,
        discount_type: "None",
        discount_amount: 0,
        tax_amount: 0,
        tax_calculation: "Include With Product",
        shipping_cost: 0,
        shipping_multiply: true,
        image: "",
        gallery_images: [] as string[],
        status: "active",
        meta_title: "",
        meta_description: "",
        meta_keywords: ""
    });

    const [tags, setTags] = useState<string[]>([]);
    const [tagInput, setTagInput] = useState("");

    // Specifications
    const [specifications, setSpecifications] = useState<string[]>([]);
    const [specInput, setSpecInput] = useState("");

    // Attributes & Variations
    const [attributes, setAttributes] = useState<any[]>([]); // Selected attributes with values
    const [variations, setVariations] = useState<any[]>([]);
    const [selectedColors, setSelectedColors] = useState<any[]>([]);

    // Available Options (Mock or Fetch)
    const availAttributes = [
        { id: 1, name: "Weight" },
        { id: 2, name: "Size" },
        { id: 3, name: "Ram size" },
        { id: 4, name: "Storage" },
    ];

    const availableColors = [
        { id: 1, name: "Yellow", color: "bg-yellow-500" },
        { id: 2, name: "White", color: "bg-white border-gray-200" },
        { id: 3, name: "Red", color: "bg-red-500" },
        { id: 4, name: "Blue", color: "bg-blue-500" },
        { id: 5, name: "Green", color: "bg-green-500" },
        { id: 6, name: "Black", color: "bg-black" },
    ];

    useEffect(() => {
        fetchCategories();
        fetchBrands();
        if (initialData) {
            initializeForm(initialData);
        }
    }, [initialData]);

    const initializeForm = (data: any) => {
        // Map initial data to form state
        // This would require careful mapping depending on API response structure
        setFormData(prev => ({
            ...prev,
            name: data.name || "",
            price: data.price || "",
            description: data.description || "",
            category_id: data.category_id || "",
            brand: data.brand || "",
            product_type: data.product_type || "Physical",
            product_sku: data.product_sku || "",
            unit: data.unit || "kg",
            purchase_price: data.purchase_price || "",
            unit_price: data.unit_price || "",
            min_order_qty: data.min_order_qty || 1,
            current_stock: data.current_stock || 0,
            discount_type: data.discount_type || "None",
            discount_amount: data.discount_amount || 0,
            tax_amount: data.tax_amount || 0,
            shipping_cost: data.shipping_cost || 0,
            image: data.image || "",
            gallery_images: data.gallery_images || [],
            status: data.status || "active",
            meta_title: data.meta_title || "",
            meta_description: data.meta_description || "",
            meta_keywords: data.meta_keywords || ""
        }));
        // Set variations, tags, etc.
    };

    const fetchCategories = async () => {
        try {
            const res = await authFetch('/admin/v1/categories');
            if (res.ok) {
                const data = await res.json();
                setCategories(data || []);
            }
        } catch (e) {
            console.error("Failed to fetch categories", e);
        }
    };

    const fetchBrands = async () => {
        try {
            const res = await authFetch('/admin/v1/brands');
            if (res.ok) {
                const data = await res.json();
                setBrands(data || []);
            }
        } catch (e) {
            // Mock brands if failed
            setBrands([{ id: 1, name: 'Generic' }, { id: 2, name: 'Premium' }]);
        }
    };

    const handleInputChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
        const { name, value } = e.target;
        setFormData(prev => ({ ...prev, [name]: value }));
    };

    // --- Category Handling ---
    const handleMainCategoryChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
        const id = parseInt(e.target.value);
        const selected = categories.find(c => c.id === id);
        setSubCategories(selected?.children || []);
        setSubSubCategories([]);
        setFormData(prev => ({
            ...prev,
            category_id: e.target.value,
            sub_category_id: "",
            sub_sub_category_id: ""
        }));
    };

    const handleSubCategoryChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
        const id = parseInt(e.target.value);
        const selected = subCategories.find(c => c.id === id);
        setSubSubCategories(selected?.children || []);
        setFormData(prev => ({
            ...prev,
            sub_category_id: e.target.value,
            sub_sub_category_id: ""
        }));
    };

    // --- Tag & Spec Handling ---
    const handleAddTag = () => {
        if (tagInput.trim() && !tags.includes(tagInput.trim())) {
            setTags([...tags, tagInput.trim()]);
            setTagInput("");
        }
    };

    // --- Image Upload ---
    const handleImageUpload = async (e: React.ChangeEvent<HTMLInputElement>, field: 'image' | 'gallery') => {
        const files = e.target.files;
        if (!files || files.length === 0) return;

        const fd = new FormData();
        if (field === 'image') {
            fd.append('image', files[0]);
            fd.append('folder', 'products');
        } else {
            // Handle gallery multiple uploads logic if API supports array or loop
            // For now assuming single upload for simplicity or implementing loop
            fd.append('image', files[0]);
            fd.append('folder', 'products/gallery');
        }

        try {
            const res = await authFetch('/v1/admin/upload', {
                method: 'POST',
                body: fd
            });

            if (res.ok) {
                const data = await res.json();
                if (field === 'image') {
                    setFormData(prev => ({ ...prev, image: data.path.split('/').pop() }));
                } else {
                    setFormData(prev => ({ ...prev, gallery_images: [...prev.gallery_images, data.path.split('/').pop()] }));
                }
                toast.success("Image uploaded");
            } else {
                toast.error("Upload failed");
            }
        } catch (e) {
            toast.error("Upload error");
        }
    };

    // --- Form Submission ---
    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setLoading(true);

        const payload = {
            ...formData,
            tags,
            specifications,
            attributes: attributes.map(a => ({ name: a.name, values: a.values })),
            colors: selectedColors.map(c => ({ id: c.id, name: c.name, color_class: c.color })),
            variations: variations, // Ensure variations structure matches backend expectation
            // Calculate final price logic if needed
        };

        const url = isEdit && initialData
            ? `/v1/admin/products/${initialData.id}`
            : '/v1/admin/products';
        const method = isEdit ? 'PUT' : 'POST';

        try {
            const res = await authFetch(url, {
                method,
                body: JSON.stringify(payload)
            });

            if (res.ok) {
                toast.success(isEdit ? "Product updated" : "Product created");
                router.push('/admin/products');
            } else {
                const err = await res.json();
                toast.error(err.message || "Failed to save product");
            }
        } catch (e) {
            toast.error("An error occurred");
        } finally {
            setLoading(false);
        }
    };

    return (
        <form onSubmit={handleSubmit} className="space-y-8 max-w-6xl mx-auto pb-20">
            {/* General Info */}
            <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h2 className="text-lg font-bold text-gray-800 mb-6">General Information</h2>
                <div className="space-y-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Product Name</label>
                        <input
                            name="name"
                            value={formData.name}
                            onChange={handleInputChange}
                            className="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500/20 outline-none"
                            required
                        />
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Category</label>
                            <select
                                name="category_id"
                                value={formData.category_id}
                                onChange={handleMainCategoryChange}
                                className="w-full p-2.5 border rounded-lg bg-white"
                            >
                                <option value="">Select Category</option>
                                {categories.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Brand</label>
                            <select
                                name="brand"
                                value={formData.brand}
                                onChange={handleInputChange}
                                className="w-full p-2.5 border rounded-lg bg-white"
                            >
                                <option value="">Select Brand</option>
                                {brands.map(b => <option key={b.id} value={b.id}>{b.name}</option>)}
                            </select>
                        </div>
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea
                            name="description"
                            value={formData.description}
                            onChange={handleInputChange}
                            rows={4}
                            className="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500/20 outline-none"
                        />
                    </div>
                </div>
            </div>

            {/* Pricing */}
            <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h2 className="text-lg font-bold text-gray-800 mb-6">Pricing & Stock</h2>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Base Price</label>
                        <input
                            name="price"
                            type="number"
                            value={formData.price}
                            onChange={handleInputChange}
                            className="w-full p-2.5 border rounded-lg"
                            required
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Stock Quantity</label>
                        <input
                            name="current_stock"
                            type="number"
                            value={formData.current_stock}
                            onChange={handleInputChange}
                            className="w-full p-2.5 border rounded-lg"
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                        <input
                            name="product_sku"
                            value={formData.product_sku}
                            onChange={handleInputChange}
                            className="w-full p-2.5 border rounded-lg"
                        />
                    </div>
                </div>
            </div>

            {/* Images */}
            <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h2 className="text-lg font-bold text-gray-800 mb-6">Product Images</h2>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">Main Image</label>
                        <div className="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:bg-gray-50 transition relative">
                            {formData.image ? (
                                <div className="relative w-full h-48">
                                    <img
                                        src={formData.image.startsWith('http') ? formData.image : `http://127.0.0.1:8000/storage/${formData.image}`}
                                        className="w-full h-full object-contain"
                                        alt="Main"
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setFormData(prev => ({ ...prev, image: "" }))}
                                        className="absolute top-2 right-2 bg-red-500 text-white p-1 rounded-full shadow"
                                    >
                                        <X size={16} />
                                    </button>
                                </div>
                            ) : (
                                <label className="cursor-pointer block">
                                    <ImageIcon className="mx-auto text-gray-400 mb-2" size={32} />
                                    <span className="text-gray-500">Click to upload main image</span>
                                    <input type="file" className="hidden" onChange={(e) => handleImageUpload(e, 'image')} accept="image/*" />
                                </label>
                            )}
                        </div>
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">Gallery Images</label>
                        <div className="grid grid-cols-3 gap-2">
                            {formData.gallery_images.map((img, i) => (
                                <div key={i} className="relative aspect-square border rounded-lg overflow-hidden">
                                    <img
                                        src={img.startsWith('http') ? img : `http://127.0.0.1:8000/storage/${img}`}
                                        className="w-full h-full object-cover"
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setFormData(prev => ({
                                            ...prev,
                                            gallery_images: prev.gallery_images.filter((_, idx) => idx !== i)
                                        }))}
                                        className="absolute top-1 right-1 bg-white text-red-500 rounded-full shadow p-0.5"
                                    >
                                        <X size={12} />
                                    </button>
                                </div>
                            ))}
                            <label className="aspect-square border-2 border-dashed border-gray-300 rounded-lg flex flex-col items-center justify-center cursor-pointer hover:bg-gray-50">
                                <Plus className="text-gray-400" />
                                <input type="file" className="hidden" onChange={(e) => handleImageUpload(e, 'gallery')} accept="image/*" />
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            {/* SEO Metadata */}
            <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h2 className="text-lg font-bold text-gray-800 mb-6">SEO Metadata (Optional)</h2>
                <div className="space-y-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Meta Title</label>
                        <input
                            name="meta_title"
                            value={formData.meta_title || ''}
                            onChange={handleInputChange}
                            className="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500/20 outline-none"
                            placeholder="SEO Title"
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Meta Description</label>
                        <textarea
                            name="meta_description"
                            value={formData.meta_description || ''}
                            onChange={handleInputChange}
                            rows={3}
                            className="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500/20 outline-none"
                            placeholder="SEO Description"
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Meta Keywords</label>
                        <input
                            name="meta_keywords"
                            value={formData.meta_keywords || ''}
                            onChange={handleInputChange}
                            className="w-full p-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500/20 outline-none"
                            placeholder="keywords, comma, separated"
                        />
                    </div>
                </div>
            </div>

            {/* Actions */}
            <div className="flex gap-4">
                <button
                    type="button"
                    onClick={() => router.back()}
                    className="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 transition"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    disabled={loading}
                    className="px-6 py-3 bg-blue-600 text-white rounded-lg font-bold hover:bg-blue-700 transition flex items-center gap-2 shadow-lg shadow-blue-200"
                >
                    <Save size={18} /> {loading ? "Saving..." : "Save Product"}
                </button>
            </div>
        </form>
    );
};

export default ProductForm;
