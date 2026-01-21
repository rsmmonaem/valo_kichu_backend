import React, { useEffect, useState } from "react";
import api from "../../services/api";
import { data } from "react-router-dom";

const AddNewProduct = () => {
    const [categories, setCategories] = useState([]);
    const [subCategories, setSubCategories] = useState([]);
    const [subSubCategories, setSubSubCategories] = useState([]);
    const [searchTags, setSearchTags] = useState([]);
    const [tagInput, setTagInput] = useState("");
    const [brands, setBrands] = useState([]);
    const [selectedColors, setSelectedColors] = useState([]);
    const [availableColors] = useState([
        { id: 1, name: "Yellow", color: "bg-yellow-500" },
        { id: 2, name: "WhiteSmoke", color: "bg-gray-300" },
        { id: 3, name: "Red", color: "bg-red-500" },
        { id: 4, name: "Blue", color: "bg-blue-500" },
        { id: 5, name: "Green", color: "bg-green-500" },
        { id: 6, name: "Black", color: "bg-black" },
    ]);
    const [variations, setVariations] = useState([
        // {
        //     id: 1,
        //     color: "Yellow",
        //     colorClass: "bg-yellow-500",
        //     code: "",
        //     sku: "-Yellow",
        //     stock: 1,
        // },
        // {
        //     id: 2,
        //     color: "WhiteSmoke",
        //     colorClass: "bg-gray-300",
        //     code: "",
        //     sku: "-WhiteSmoke",
        //     stock: 1,
        // },
    ]);
    const availAttributes = [
        { id: 1, name: "Weight", value: [] },
        { id: 2, name: "Size", value: [] },
        { id: 3, name: "Ram size", value: [] },
        { id: 4, name: "Pic", value: [] },
    ];
    const [selectedAttributes, setSelectedAttributes] = useState([]);
    const [shippingMultiply, setShippingMultiply] = useState(true);

    const [formData, setFormData] = useState({
        name: "",
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
        loyalty_point: 0,
        category_id: "",
        sub_category_id: "",
        sub_sub_category_id: "",
        brand: "",
        product_type: "Physical",
        product_sku: "",
        unit: "kg",
        image: "",
        gallery_images:[],
        description: "",
    });

    /* ================= FETCH CATEGORIES ================= */
    useEffect(() => {
        fetchCategories();
        fetchBrands();
    }, []);

    const fetchCategories = async () => {
        try {
            const { data } = await api.get("/admin/v1/categories");
            console.log("Fetched Categories:", data);
            setCategories(data || []);
        } catch (err) {
            console.error("Failed to load categories", err);
        }
    };

    const fetchBrands = async () => {
        try {
            // Replace with your actual API endpoint
            const { data } = await api.get("/admin/v1/brands");
            setBrands(data || []);
        } catch (err) {
            console.error("Failed to load brands", err);
            // Fallback to mock data
            const mockBrands = [
                { id: 1, name: "Nike" },
                { id: 2, name: "Adidas" },
                { id: 3, name: "Apple" },
                { id: 4, name: "Samsung" },
                { id: 5, name: "Sony" },
            ];
            setBrands(mockBrands);
        }
    };

    /* ================= CATEGORY HANDLERS ================= */
    const handleMainCategoryChange = (id) => {
        const selected = categories.find((c) => c.id === Number(id));
        console.log("Selected Main Category:", selected);
        setSubCategories(selected?.children || []);
        setSubSubCategories([]);

        setFormData({
            ...formData,
            category_id: id,
            sub_category_id: "",
            sub_sub_category_id: "",
        });
    };

    const handleSubCategoryChange = (id) => {
        const selected = subCategories.find((c) => c.id === Number(id));
        console.log("Selected Sub Category:", selected);
        setSubSubCategories(selected?.children || []);

        setFormData({
            ...formData,
            sub_category_id: id,
            sub_sub_category_id: "",
        });
    };

    const handleSubSubCategoryChange = (id) => {
        console.log("Selected Sub Sub Category ID:", id);
        setFormData({
            ...formData,
            sub_sub_category_id: id,
        });
    };

    /* ================= TAG HANDLERS ================= */
    const handleAddTag = () => {
        if (tagInput.trim() && !searchTags.includes(tagInput.trim())) {
            setSearchTags([...searchTags, tagInput.trim()]);
            setTagInput("");
        }
    };

    const handleRemoveTag = (tagToRemove) => {
        setSearchTags(searchTags.filter((tag) => tag !== tagToRemove));
    };

    const handleKeyDown = (e) => {
        if (e.key === "Enter") {
            e.preventDefault();
            handleAddTag();
        }
    };
    // genarate sku code
// Generate variations based on selected colors and attributes
useEffect(() => {
    // If no colors are selected, return early
    if (selectedColors.length === 0) return;
    
    // Start with base color variations
    let baseVariations = selectedColors.map((color, index) => ({
        id: index + 1,
        color: color.name,
        colorClass: color.color,
        code: "",
        sku: `-${color.name}`,
        stock: 1,
    }));
    
    // If no attributes selected, set variations and return
    if (selectedAttributes.length === 0) {
        setVariations(baseVariations);
        return;
    }
    
    // Filter out attributes with values
    const attributesWithValues = selectedAttributes.filter(
        attr => attr.value && attr.value.length > 0
    );
    
    // If no attributes have values, set base variations
    if (attributesWithValues.length === 0) {
        setVariations(baseVariations);
        return;
    }
    
    // Generate combinations of colors and attributes
    let generatedVariations = [...baseVariations];
    
    attributesWithValues.forEach(attribute => {
        const tempVariations = [];
        
        generatedVariations.forEach(variation => {
            attribute.value.forEach(value => {
                // Create new SKU by adding attribute value
                const currentSkuParts = variation.sku.split('-').filter(Boolean);
                const newSkuParts = [...currentSkuParts, value];
                const newSku = `-${newSkuParts.join('-')}`;
                
                tempVariations.push({
                    ...variation,
                    id: 0, // Temporary ID, will be reassigned
                    sku: newSku,
                });
            });
        });
        
        // Remove duplicates based on SKU
        const uniqueVariations = [];
        const seenSkus = new Set();
        
        tempVariations.forEach(variation => {
            if (!seenSkus.has(variation.sku)) {
                seenSkus.add(variation.sku);
                uniqueVariations.push(variation);
            }
        });
        
        generatedVariations = uniqueVariations;
    });
    
    // Assign proper IDs
    const finalVariations = generatedVariations.map((variation, index) => ({
        ...variation,
        id: index + 1,
    }));
    
    setVariations(finalVariations);
}, [selectedColors, selectedAttributes]);
    
    
    /* ================= Attribute HANDLERS ================= */
    const handleAttributeSelect = (e) => {
        const attributeId = parseInt(e.target.value, 10);
        if (!attributeId) return;

        const attribute = availAttributes.find(
            (attr) => attr.id === attributeId
        );
        if (!attribute) return;

        // Prevent duplicate attribute
        if (selectedAttributes.some((sa) => sa.id === attributeId)) {
            return;
        }

        // Clean duplicate values inside the attribute
        const cleanedAttribute = {
            ...attribute,
            value: [...new Set(attribute.value)],
        };

        setSelectedAttributes((prev) => [...prev, cleanedAttribute]);

        // Reset select
        e.target.value = "";
    };

    const addAttributeValue = (attributeId, newValue) => {
        if (!newValue.trim()) return;

        setSelectedAttributes((prev) =>
            prev.map((attr) => {
                if (attr.id !== attributeId) return attr;

                //Prevent duplicate value
                if (attr.value.includes(newValue)) {
                    return attr;
                }

                return {
                    ...attr,
                    value: [...attr.value, newValue],
                };
            })
        );
    };

    const handleRemoveAttribute = (attrId) => {
        const attrToRemove = selectedAttributes.find((a) => a.id === attrId);
        setSelectedAttributes(
            selectedAttributes.filter((a) => a.id !== attrId)
        );

        // Remove corresponding variation
        // if (colorToRemove) {
        //     setVariations(
        //         variations.filter((v) => v.color !== colorToRemove.name)
        //     );
        // }
    };

    /* ================= COLOR HANDLERS ================= */
    const handleColorSelect = (e) => {
        const colorId = parseInt(e.target.value);
        if (!colorId) return;

        const color = availableColors.find((c) => c.id === colorId);
        if (color && !selectedColors.find((sc) => sc.id === colorId)) {
            setSelectedColors([...selectedColors, color]);

            // Add new variation for selected color
            if (!variations.find((v) => v.color === color.name)) {
                const newVariation = {
                    id: variations.length + 1,
                    color: color.name,
                    colorClass: color.color,
                    code: "",
                    sku: `-${color.name}`,
                    stock: 1,
                };
                setVariations([...variations, newVariation]);
            }

            e.target.value = ""; // Reset select
        }
    };

    const handleRemoveColor = (colorId) => {
        const colorToRemove = selectedColors.find((c) => c.id === colorId);
        setSelectedColors(selectedColors.filter((c) => c.id !== colorId));

        // Remove corresponding variation
        if (colorToRemove) {
            setVariations(
                variations.filter((v) => v.color !== colorToRemove.name)
            );
        }
    };

    /* ================= VARIATION HANDLERS ================= */
    const handleVariationChange = (id, field, value) => {
        setVariations(
            variations.map((variation) =>
                variation.id === id
                    ? { ...variation, [field]: value }
                    : variation
            )
        );
    };

    const handleAddVariation = () => {
        const newId =
            variations.length > 0
                ? Math.max(...variations.map((v) => v.id)) + 1
                : 1;
        const defaultColor = { id: 0, name: "New Color", color: "bg-gray-400" };

        const newVariation = {
            id: newId,
            color: "New Color",
            colorClass: "bg-gray-400",
            code: "",
            sku: `-NewColor${newId}`,
            stock: 1,
        };

        setVariations([...variations, newVariation]);

        if (!selectedColors.find((c) => c.name === "New Color")) {
            setSelectedColors([...selectedColors, defaultColor]);
        }
    };

    /* ================= SKU GENERATION ================= */
    const handleGenerateSKU = () => {
        const randomSKU = `PROD-${Math.random()
            .toString(36)
            .substr(2, 9)
            .toUpperCase()}`;
        setFormData({ ...formData, product_sku: randomSKU });
    };

    /* ================= IMAGE UPLOAD ================= */
    const handleImageUpload = async (file) => {
        if (!file) return;

        const fd = new FormData();
        fd.append("image", file);
        fd.append("folder", "products");

        try {
            const { data } = await api.post("/admin/v1/upload", fd, {
                headers: { "Content-Type": "multipart/form-data" },
            });

            setFormData({
                ...formData,
                image: `${import.meta.env.VITE_API_BASE_URL}/storage/${
                    data.path
                }`,
            });
        } catch (err) {
            console.error("Image upload failed", err);
            alert("Image upload failed");
        }
    };

    /* ================= COLOR IMAGE UPLOAD ================= */
    const handleColorImageUpload = async (colorId, file) => {
        if (!file) return;
        // console.log("upload")

        const fd = new FormData();
        fd.append("image", file);
        fd.append("folder", "products");

        try {
            const { data } = await api.post("/admin/v1/upload", fd, {
                headers: { "Content-Type": "multipart/form-data" },
            });

            setSelectedColors((prevColors) =>
                prevColors.map((color) =>
                    color.id === colorId
                        ? {
                              ...color,
                              image: `${
                                  import.meta.env.VITE_API_BASE_URL
                              }/storage/${data.path}`,
                          }
                        : color
                )
            );
        } catch (err) {
            console.error("Image upload failed", err);
            alert("Image upload failed");
        }
    };

    /* ================= SUBMIT ================= */
    // const handleSubmit = async (e) => {
    //     e.preventDefault();

    //     // Calculate final price based on tax and discount
    //     let finalPrice = parseFloat(formData.price) || 0;
    //     if (formData.discount_type === "Flat") {
    //         finalPrice -= parseFloat(formData.discount_amount) || 0;
    //     } else if (formData.discount_type === "Percent") {
    //         finalPrice -=
    //             (finalPrice * (parseFloat(formData.discount_amount) || 0)) /
    //             100;
    //     }

    //     try {
    //         const productData = {
    //             name: formData.name,
    //             price: finalPrice,
    //             purchase_price: parseFloat(formData.purchase_price) || 0,
    //             unit_price: parseFloat(formData.unit_price) || 0,
    //             min_order_qty: parseInt(formData.min_order_qty) || 1,
    //             current_stock: parseInt(formData.current_stock) || 0,
    //             discount_type: formData.discount_type,
    //             discount_amount: parseFloat(formData.discount_amount) || 0,
    //             tax_amount: parseFloat(formData.tax_amount) || 0,
    //             tax_calculation: formData.tax_calculation,
    //             shipping_cost: parseFloat(formData.shipping_cost) || 0,
    //             shipping_multiply: shippingMultiply,
    //             loyalty_point: parseFloat(formData.loyalty_point) || 0,
    //             category_id:
    //                 formData.sub_sub_category_id ||
    //                 formData.sub_category_id ||
    //                 formData.category_id,
    //             brand: formData.brand,
    //             product_type: formData.product_type,
    //             product_sku: formData.product_sku,
    //             unit: formData.unit,
    //             tags: searchTags,
    //             image: formData.image,
    //             description: formData.description,
    //             variations: variations,
    //         };

    //         console.log("Submitting product data:", productData);

    //         await api.post("/admin/v1/products", productData);

    //         alert("Product added successfully");

    //         // Reset form
    //         resetForm();
    //     } catch (err) {
    //         console.error("Product create failed", err);
    //         alert("Failed to add product");
    //     }
    // };
    const handleSubmit = async (e) => {
        e.preventDefault();
    
        // Calculate final price based on tax and discount
        let finalPrice = parseFloat(formData.price) || 0;
        if (formData.discount_type === "Flat") {
            finalPrice -= parseFloat(formData.discount_amount) || 0;
        } else if (formData.discount_type === "Percent") {
            finalPrice -=
                (finalPrice * (parseFloat(formData.discount_amount) || 0)) /
                100;
        }
    
        try {
            // Build the complete payload
            const productData = {
                // Basic Information
                name: formData.name,
                description: formData.description,
                
                // Category Information
                category_id: formData.sub_sub_category_id || formData.sub_category_id || formData.category_id,
                brand: formData.brand,
                
                // Product Details
                product_type: formData.product_type,
                product_sku: formData.product_sku,
                unit: formData.unit,
                
                // Pricing Information
                price: finalPrice,
                purchase_price: parseFloat(formData.purchase_price) || 0,
                unit_price: parseFloat(formData.unit_price) || 0,
                
                // Stock Information
                min_order_qty: parseInt(formData.min_order_qty) || 1,
                current_stock: parseInt(formData.current_stock) || 0,
                
                // Discount Information
                discount_type: formData.discount_type,
                discount_amount: parseFloat(formData.discount_amount) || 0,
                
                // Tax Information
                tax_amount: parseFloat(formData.tax_amount) || 0,
                tax_calculation: formData.tax_calculation,
                
                // Shipping Information
                shipping_cost: parseFloat(formData.shipping_cost) || 0,
                shipping_multiply: shippingMultiply,
                
                // Loyalty Points
                loyalty_point: parseFloat(formData.loyalty_point) || 0,
                
                // Image
                image: formData.image,
                gallery_images: formData.galleryImages || [],
                
                // Tags
                tags: searchTags,
                
                // Variations
                variations: variations.map(variation => ({
                    id: variation.id,
                    color: variation.color,
                    colorClass: variation.colorClass,
                    code: variation.code,
                    sku: variation.sku,
                    stock: variation.stock,
                    // Include color image if available
                    color_image: selectedColors.find(c => c.name === variation.color)?.image || null
                })),
                
                // Attributes
                attributes: selectedAttributes.map(attr => ({
                    id: attr.id,
                    name: attr.name,
                    values: attr.value || []
                })),
                
                // Color Information
                colors: selectedColors.map(color => ({
                    id: color.id,
                    name: color.name,
                    color_class: color.color,
                    image: color.image || null
                })),
                
                // Additional metadata

                
                // Status (you might want to add this)
                status: "active", // or "draft", "pending", etc.
                
                // SEO Fields (optional - you can add these later)
                // meta_title: "",
                // meta_description: "",
                // meta_keywords: "",
                
                // Additional options
                is_featured: false,
                is_trending: false,
                is_discounted: formData.discount_type !== "None"
            };
    
            console.log("Submitting product data:", productData);
            // console.log("Full payload:", JSON.stringify(productData, null, 2));
    
            // Send to API
            await api.post("/admin/v1/products", productData);
            console.log(productData);
    
            alert("Product added successfully");
    
            // Reset form
            resetForm();
        } catch (err) {
            console.error("Product create failed", err);
            console.error("Error details:", err.response?.data);
            alert("Failed to add product: " + (err.response?.data?.message || err.message));
        }
    };
    const resetForm = () => {
        setFormData({
            name: "",
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
            loyalty_point: 0,
            category_id: "",
            sub_category_id: "",
            sub_sub_category_id: "",
            brand: "",
            product_type: "Physical",
            product_sku: "",
            unit: "kg",
            image: "",
            description: "",
        });
        setSearchTags([]);
        setSelectedColors([]);
        setVariations([
            {
                id: 1,
                color: "Yellow",
                colorClass: "bg-yellow-500",
                code: "",
                sku: "-Yellow",
                stock: 1,
            },
            {
                id: 2,
                color: "WhiteSmoke",
                colorClass: "bg-gray-300",
                code: "",
                sku: "-WhiteSmoke",
                stock: 1,
            },
        ]);
        setShippingMultiply(true);
        setSubCategories([]);
        setSubSubCategories([]);
    };

    const handleInputChange = (e) => {
        const { name, value } = e.target;
        setFormData({
            ...formData,
            [name]: value,
        });
    };
    const handleAttributeChange = (attrId, newValue) => {
        setSelectedAttributes((prevAttributes) =>
            prevAttributes.map((attr) =>
                attr.id === attrId
                    ? { ...attr, value: [...(attr.value || []), newValue] }
                    : attr
            )
        );
    };

    return (
        <div className="min-h-screen bg-gray-50 p-4 md:p-6">
            <div className="max-w-6xl mx-auto">
                {/* Header */}
                <div className="mb-8">
                    <h1 className="text-2xl font-bold text-gray-800">
                        General setup
                    </h1>
                </div>

                <form onSubmit={handleSubmit} className="space-y-8">
                    {/* Product Information Card */}
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 className="text-lg font-semibold text-gray-700 mb-6">
                            Product Information
                        </h2>

                        <div className="space-y-6">
                            {/* Product Name */}
                            <div className="space-y-2">
                                <label className="block text-sm font-medium text-gray-600">
                                    Product Name *
                                </label>
                                <input
                                    type="text"
                                    className="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    name="name"
                                    value={formData.name}
                                    onChange={handleInputChange}
                                    placeholder="Enter product name"
                                    required
                                />
                            </div>

                            {/* Price */}
                            <div className="space-y-2">
                                <label className="block text-sm font-medium text-gray-600">
                                    Selling Price (৳) *
                                </label>
                                <div className="relative">
                                    <input
                                        type="number"
                                        className="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        name="price"
                                        value={formData.price}
                                        onChange={handleInputChange}
                                        placeholder="0.00"
                                        step="0.01"
                                        min="0"
                                        required
                                    />
                                    <div className="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <span className="text-gray-500">৳</span>
                                    </div>
                                </div>
                            </div>

                            {/* Description */}
                            <div className="space-y-2">
                                <label className="block text-sm font-medium text-gray-600">
                                    Description
                                </label>
                                <textarea
                                    rows="4"
                                    className="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"
                                    name="description"
                                    value={formData.description}
                                    onChange={handleInputChange}
                                    placeholder="Enter product description"
                                />
                            </div>
                        </div>
                    </div>

                    {/* Category Section */}
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 className="text-lg font-semibold text-gray-700 mb-6">
                            Category
                        </h2>

                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                            {/* Main Category */}
                            <div className="space-y-2">
                                <label className="block text-sm font-medium text-gray-600">
                                    Category *
                                </label>
                                <div className="relative">
                                    <select
                                        className="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg appearance-none focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        value={formData.category_id}
                                        onChange={(e) =>
                                            handleMainCategoryChange(
                                                e.target.value
                                            )
                                        }
                                        required
                                    >
                                        <option value="">
                                            Select category
                                        </option>
                                        {categories.map((cat) => (
                                            <option key={cat.id} value={cat.id}>
                                                {cat.name}
                                            </option>
                                        ))}
                                    </select>
                                    <div className="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <svg
                                            className="w-5 h-5 text-gray-400"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                strokeWidth="2"
                                                d="M19 9l-7 7-7-7"
                                            />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            {/* Sub Category */}
                            {subCategories.length > 0 && (
                                <div className="space-y-2">
                                    <label className="block text-sm font-medium text-gray-600">
                                        Sub Category
                                    </label>
                                    <div className="relative">
                                        <select
                                            className="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg appearance-none focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            value={formData.sub_category_id}
                                            onChange={(e) =>
                                                handleSubCategoryChange(
                                                    e.target.value
                                                )
                                            }
                                        >
                                            <option value="">
                                                Select Sub Category
                                            </option>
                                            {subCategories.map((sub) => (
                                                <option
                                                    key={sub.id}
                                                    value={sub.id}
                                                >
                                                    {sub.name}
                                                </option>
                                            ))}
                                        </select>
                                        <div className="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <svg
                                                className="w-5 h-5 text-gray-400"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    strokeWidth="2"
                                                    d="M19 9l-7 7-7-7"
                                                />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Sub Sub Category */}
                            {subSubCategories.length > 0 && (
                                <div className="space-y-2">
                                    <label className="block text-sm font-medium text-gray-600">
                                        Sub Sub Category
                                    </label>
                                    <div className="relative">
                                        <select
                                            className="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg appearance-none focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            value={formData.sub_sub_category_id}
                                            onChange={(e) =>
                                                handleSubSubCategoryChange(
                                                    e.target.value
                                                )
                                            }
                                        >
                                            <option value="">
                                                Select Sub Sub Category
                                            </option>
                                            {subSubCategories.map((sub) => (
                                                <option
                                                    key={sub.id}
                                                    value={sub.id}
                                                >
                                                    {sub.name}
                                                </option>
                                            ))}
                                        </select>
                                        <div className="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <svg
                                                className="w-5 h-5 text-gray-400"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    strokeWidth="2"
                                                    d="M19 9l-7 7-7-7"
                                                />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Brand */}
                        <div className="mt-6 space-y-2">
                            <label className="block text-sm font-medium text-gray-600">
                                Brand
                            </label>
                            <div className="relative">
                                <select
                                    className="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg appearance-none focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    name="brand"
                                    value={formData.brand}
                                    onChange={handleInputChange}
                                >
                                    <option value="">Select Brand</option>
                                    {brands.map((brand) => (
                                        <option key={brand.id} value={brand.id}>
                                            {brand.name}
                                        </option>
                                    ))}
                                </select>
                                <div className="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <svg
                                        className="w-5 h-5 text-gray-400"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth="2"
                                            d="M19 9l-7 7-7-7"
                                        />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Product Details Section */}
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 className="text-lg font-semibold text-gray-700 mb-6">
                            Product Details
                        </h2>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {/* Product Type */}
                            <div className="space-y-2">
                                <label className="block text-sm font-medium text-gray-600">
                                    Product Type
                                </label>
                                <div className="relative">
                                    <select
                                        className="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg appearance-none focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        name="product_type"
                                        value={formData.product_type}
                                        onChange={handleInputChange}
                                    >
                                        <option value="Physical">
                                            Physical
                                        </option>
                                        <option value="Digital">Digital</option>
                                        <option value="Service">Service</option>
                                    </select>
                                    <div className="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <svg
                                            className="w-5 h-5 text-gray-400"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                strokeWidth="2"
                                                d="M19 9l-7 7-7-7"
                                            />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            {/* Unit */}
                            <div className="space-y-2">
                                <label className="block text-sm font-medium text-gray-600">
                                    Unit
                                </label>
                                <div className="relative">
                                    <select
                                        className="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg appearance-none focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        name="unit"
                                        value={formData.unit}
                                        onChange={handleInputChange}
                                    >
                                        <option value="kg">kg</option>
                                        <option value="g">g</option>
                                        <option value="lb">lb</option>
                                        <option value="oz">oz</option>
                                        <option value="piece">piece</option>
                                        <option value="pack">pack</option>
                                    </select>
                                    <div className="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <svg
                                            className="w-5 h-5 text-gray-400"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                strokeWidth="2"
                                                d="M19 9l-7 7-7-7"
                                            />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Product SKU */}
                        <div className="mt-6 space-y-2">
                            <label className="block text-sm font-medium text-gray-600">
                                Product SKU
                            </label>
                            <div className="flex flex-col sm:flex-row gap-3">
                                <input
                                    type="text"
                                    className="flex-1 px-4 py-3 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Enter product SKU"
                                    name="product_sku"
                                    value={formData.product_sku}
                                    onChange={handleInputChange}
                                />
                                <button
                                    type="button"
                                    onClick={handleGenerateSKU}
                                    className="px-6 py-3 bg-gray-50 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-100 transition-colors whitespace-nowrap"
                                >
                                    Generate Code
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* Pricing & Other */}
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 className="text-lg font-semibold text-gray-700 mb-6">
                            Pricing & Other
                        </h2>

                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                            {/* Purchase Price */}
                            <div className="space-y-2">
                                <label className="block text-sm font-medium text-gray-600">
                                    Purchase price (৳)
                                </label>
                                <div className="relative">
                                    <input
                                        type="number"
                                        className="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Purchase price"
                                        name="purchase_price"
                                        value={formData.purchase_price}
                                        onChange={handleInputChange}
                                        step="0.01"
                                        min="0"
                                    />
                                    <div className="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <span className="text-gray-500">৳</span>
                                    </div>
                                </div>
                            </div>

                            {/* Unit Price */}
                            <div className="space-y-2">
                                <label className="block text-sm font-medium text-gray-600">
                                    Unit Price (৳)
                                </label>
                                <div className="relative">
                                    <input
                                        type="number"
                                        className="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Unit price"
                                        name="unit_price"
                                        value={formData.unit_price}
                                        onChange={handleInputChange}
                                        step="0.01"
                                        min="0"
                                    />
                                    <div className="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <span className="text-gray-500">৳</span>
                                    </div>
                                </div>
                            </div>

                            {/* Minimum Order Qty */}
                            <div className="space-y-2">
                                <label className="block text-sm font-medium text-gray-600">
                                    Minimum order qty
                                </label>
                                <input
                                    type="number"
                                    className="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Minimum order qty"
                                    name="min_order_qty"
                                    value={formData.min_order_qty}
                                    onChange={handleInputChange}
                                    min="1"
                                />
                            </div>

                            {/* Current Stock Qty */}
                            <div className="space-y-2">
                                <label className="block text-sm font-medium text-gray-600">
                                    Current stock qty
                                </label>
                                <input
                                    type="number"
                                    className="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Current stock qty"
                                    name="current_stock"
                                    value={formData.current_stock}
                                    onChange={handleInputChange}
                                    min="0"
                                />
                            </div>

                            {/* Discount Type */}
                            <div className="space-y-2">
                                <label className="block text-sm font-medium text-gray-600">
                                    Discount Type
                                </label>
                                <div className="relative">
                                    <select
                                        className="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg appearance-none focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        name="discount_type"
                                        value={formData.discount_type}
                                        onChange={handleInputChange}
                                    >
                                        <option value="None">None</option>
                                        <option value="Flat">Flat</option>
                                        <option value="Percent">Percent</option>
                                    </select>
                                    <div className="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <svg
                                            className="w-5 h-5 text-gray-400"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                strokeWidth="2"
                                                d="M19 9l-7 7-7-7"
                                            />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            {/* Discount Amount */}
                            <div className="space-y-2">
                                <label className="block text-sm font-medium text-gray-600">
                                    Discount amount
                                </label>
                                <div className="relative">
                                    <input
                                        type="number"
                                        className="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Discount amount"
                                        name="discount_amount"
                                        value={formData.discount_amount}
                                        onChange={handleInputChange}
                                        min="0"
                                        step="0.01"
                                    />
                                    <div className="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <span className="text-gray-500">
                                            {formData.discount_type ===
                                            "Percent"
                                                ? "%"
                                                : "৳"}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {/* Tax Amount */}
                            <div className="space-y-2">
                                <label className="block text-sm font-medium text-gray-600">
                                    Tax amount (%)
                                </label>
                                <input
                                    type="number"
                                    className="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Tax amount"
                                    name="tax_amount"
                                    value={formData.tax_amount}
                                    onChange={handleInputChange}
                                    min="0"
                                    max="100"
                                    step="0.01"
                                />
                            </div>

                            {/* Tax Calculation */}
                            <div className="space-y-2">
                                <label className="block text-sm font-medium text-gray-600">
                                    Tax calculation
                                </label>
                                <div className="relative">
                                    <select
                                        className="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg appearance-none focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        name="tax_calculation"
                                        value={formData.tax_calculation}
                                        onChange={handleInputChange}
                                    >
                                        <option value="Include With Product">
                                            Include With Product
                                        </option>
                                        <option value="Exclude With Product">
                                            Exclude With Product
                                        </option>
                                    </select>
                                    <div className="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <svg
                                            className="w-5 h-5 text-gray-400"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                strokeWidth="2"
                                                d="M19 9l-7 7-7-7"
                                            />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            {/* Shipping Cost */}
                            <div className="space-y-2">
                                <label className="block text-sm font-medium text-gray-600">
                                    Shipping cost (৳)
                                </label>
                                <div className="relative">
                                    <input
                                        type="number"
                                        className="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Shipping cost"
                                        name="shipping_cost"
                                        value={formData.shipping_cost}
                                        onChange={handleInputChange}
                                        min="0"
                                        step="0.01"
                                    />
                                    <div className="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <span className="text-gray-500">৳</span>
                                    </div>
                                </div>
                            </div>

                            {/* Shipping Cost Multiply */}
                            <div className="space-y-2 h-12">
                                <label className="block text-sm font-medium text-gray-600">
                                    Shipping Settings
                                </label>
                                <div className="flex items-center justify-between h-full px-4 py-3 bg-white border border-gray-300 rounded-lg">
                                    <h3 className="font-medium text-gray-800">
                                        Shipping Cost Multiply With Quantity
                                    </h3>
                                    <label className="relative inline-flex items-center cursor-pointer">
                                        <input
                                            type="checkbox"
                                            className="sr-only peer"
                                            checked={shippingMultiply}
                                            onChange={(e) =>
                                                setShippingMultiply(
                                                    e.target.checked
                                                )
                                            }
                                        />
                                        <div className="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                    </label>
                                </div>
                            </div>

                            {/* Loyalty Point */}
                            <div className="space-y-2">
                                <label className="block text-sm font-medium text-gray-600">
                                    Loyalty point (৳)
                                </label>
                                <div className="relative">
                                    <input
                                        type="number"
                                        className="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Loyalty point"
                                        name="loyalty_point"
                                        value={formData.loyalty_point}
                                        onChange={handleInputChange}
                                        min="0"
                                        step="0.01"
                                    />
                                    <div className="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <span className="text-gray-500">৳</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Product Variation Setup */}
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h1 className="text-xl font-bold text-gray-800 mb-6">
                            Product variation setup
                        </h1>

                        <div className="space-y-8">
                            {/* Colors Selection */}
                            <div className="space-y-4 grid grid-cols-2 gap-4">
                                <div className="">
                                    <label className="block text-sm font-medium text-gray-600">
                                        Select Colors:
                                    </label>
                                    <div className="relative">
                                        <select
                                            className="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg appearance-none focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            onChange={handleColorSelect}
                                            defaultValue=""
                                        >
                                            <option value="">
                                                Select Attributes
                                            </option>
                                            {availableColors.map((color) => (
                                                <option
                                                    key={color.id}
                                                    value={color.id}
                                                >
                                                    {color.name}
                                                </option>
                                            ))}
                                        </select>
                                        <div className="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <svg
                                                className="w-5 h-5 text-gray-400"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    strokeWidth="2"
                                                    d="M19 9l-7 7-7-7"
                                                />
                                            </svg>
                                        </div>
                                    </div>

                                    {/* Selected Colors Display */}
                                    <div className="flex flex-wrap gap-3 mt-4">
                                        {selectedColors.map((color) => (
                                            <div
                                                key={color.id}
                                                className="flex items-center gap-2 px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg"
                                            >
                                                <div
                                                    className={`w-4 h-4 ${color.color} rounded-full`}
                                                ></div>
                                                <span className="font-medium text-gray-800">
                                                    {color.name}
                                                </span>
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        handleRemoveColor(
                                                            color.id
                                                        )
                                                    }
                                                    className="ml-2 text-gray-600 hover:text-gray-800"
                                                >
                                                    <svg
                                                        className="w-4 h-4"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        viewBox="0 0 24 24"
                                                    >
                                                        <path
                                                            strokeLinecap="round"
                                                            strokeLinejoin="round"
                                                            strokeWidth="2"
                                                            d="M6 18L18 6M6 6l12 12"
                                                        />
                                                    </svg>
                                                </button>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                                {/* Select attributes : */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-600">
                                        Select attributes:
                                    </label>
                                    <div className="relative">
                                        <select
                                            className="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg appearance-none focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            onChange={handleAttributeSelect}
                                            defaultValue=""
                                        >
                                            <option value="">
                                                Select attributes
                                            </option>
                                            {availAttributes.map((attr) => (
                                                <option
                                                    key={attr.id}
                                                    value={attr.id}
                                                >
                                                    {attr.name}
                                                </option>
                                            ))}
                                        </select>
                                        <div className="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <svg
                                                className="w-5 h-5 text-gray-400"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    strokeWidth="2"
                                                    d="M19 9l-7 7-7-7"
                                                />
                                            </svg>
                                        </div>
                                    </div>

                                    {/* Selected Attribute Display */}
                                    <div className="flex flex-wrap gap-3 mt-4">
                                    
                                        {selectedAttributes.map((attr) => (
                                            <div
                                                key={attr.id}
                                                className="flex items-center gap-2 px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg"
                                            >
                                                <div
                                                    className={`w-4 h-4 rounded-full`}
                                                ></div>
                                                <span className="font-medium text-gray-800">
                                                    {attr.name}
                                                </span>
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        handleRemoveAttribute(
                                                            attr.id
                                                        )
                                                    }
                                                    className="ml-2 text-gray-600 hover:text-gray-800"
                                                >
                                                    <svg
                                                        className="w-4 h-4"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        viewBox="0 0 24 24"
                                                    >
                                                        <path
                                                            strokeLinecap="round"
                                                            strokeLinejoin="round"
                                                            strokeWidth="2"
                                                            d="M6 18L18 6M6 6l12 12"
                                                        />
                                                    </svg>
                                                </button>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                                {/*  */}
                            </div>
                            {/* Attribute input field */}
                            {/* <div className="grid grid-cols-3 gap-4">
                                {selectedAttributes.map((data) => (
                                    <div className="space-y-2" key={data.id}>
                                        <label className="block text-sm font-medium text-gray-600">
                                            {data.name}
                                        </label>
                                        <input
                                            type="text"
                                            className="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            name={data.name}
                                            value={data.value}
                                            onChange={handleAttributeChange}
                                            placeholder={data.name}
                                            required
                                        />
                                    </div>
                                ))}
                            </div> */}
                            {/* Attribute input fields */}
                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-6">
                                {selectedAttributes.map((attr) => (
                                    <div className="space-y-2" key={attr.id}>
                                        <label className="block text-sm font-medium text-gray-600">
                                            {attr.name}
                                        </label>
                                        <input
                                            type="text"
                                            className="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg
             focus:outline-none focus:ring-2 focus:ring-blue-500
             focus:border-blue-500"
                                            placeholder={`Enter ${attr.name.toLowerCase()}`}
                                            onKeyDown={(e) => {
                                                if (e.key === "Enter") {
                                                    e.preventDefault();

                                                    const value =
                                                        e.target.value.trim();
                                                    if (!value) return;

                                                    // ✅ call the correct function
                                                    addAttributeValue(
                                                        attr.id,
                                                        value
                                                    );

                                                    // clear input
                                                    e.target.value = "";
                                                }
                                            }}
                                        />

                                        {/* Display entered values */}
                                        <div className="flex flex-wrap gap-2 mt-2">
                                            {attr.value?.map((val, index) => (
                                                <span
                                                    key={index}
                                                    className="inline-flex items-center gap-2 px-3 py-1 bg-gray-100 text-gray-700 rounded-lg text-sm"
                                                >
                                                    {val}
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            setSelectedAttributes(
                                                                (
                                                                    prevAttributes
                                                                ) =>
                                                                    prevAttributes.map(
                                                                        (a) =>
                                                                            a.id ===
                                                                            attr.id
                                                                                ? {
                                                                                      ...a,
                                                                                      value: a.value.filter(
                                                                                          (
                                                                                              v
                                                                                          ) =>
                                                                                              v !==
                                                                                              val
                                                                                      ),
                                                                                  }
                                                                                : a
                                                                    )
                                                            )
                                                        }
                                                        className="text-gray-500 hover:text-gray-700"
                                                    >
                                                        ×
                                                    </button>
                                                </span>
                                            ))}
                                        </div>
                                    </div>
                                ))}
                            </div>

                            {selectedAttributes.length === 0 && (
                                <div className="text-center py-6 text-gray-500 italic">
                                    No attributes selected yet
                                </div>
                            )}
                            {/* Divider */}
                            <div className="border-t border-gray-200"></div>
                            {/* Attributes Section */}
                            <div>
                                <h2 className="text-lg font-semibold text-gray-700 mb-6">
                                    Select Attributes:
                                </h2>

                                {variations.length > 0 ? (
                                    <>
                                        {/* Table Header */}
                                        <div className="grid grid-cols-12 gap-4 mb-4 px-4">
                                            <div className="col-span-1">
                                                <span className="text-sm font-medium text-gray-600">
                                                    SL
                                                </span>
                                            </div>
                                            <div className="col-span-5">
                                                <span className="text-sm font-medium text-gray-600">
                                                    Attribute Variation
                                                </span>
                                            </div>
                                            <div className="col-span-3">
                                                <span className="text-sm font-medium text-gray-600">
                                                    SKU
                                                </span>
                                            </div>
                                            <div className="col-span-3">
                                                <span className="text-sm font-medium text-gray-600">
                                                    Variation Wise Stock
                                                </span>
                                            </div>
                                        </div>

                                        {/* Variation Rows */}
                                        {variations.map((variation, index) => (
                                            <div
                                                key={variation.id}
                                                className="grid grid-cols-12 gap-4 mb-4 p-4 bg-gray-50 rounded-lg"
                                            >
                                                <div className="col-span-1 flex items-center">
                                                    <span className="font-medium text-gray-700">
                                                        {index + 1}.
                                                    </span>
                                                </div>
                                                <div className="col-span-5">
                                                    <div className="flex items-center gap-3">
                                                        <div
                                                            className={`w-6 h-6 ${variation.colorClass} rounded-full`}
                                                        ></div>
                                                        <div>
                                                            <span className="font-medium text-gray-800">
                                                                {
                                                                    variation.color
                                                                }
                                                            </span>
                                                            <div className="mt-1">
                                                                <input
                                                                    type="text"
                                                                    className="w-full px-3 py-2 bg-white border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                                                    placeholder="Ex: 535"
                                                                    value={
                                                                        variation.code
                                                                    }
                                                                    onChange={(
                                                                        e
                                                                    ) =>
                                                                        handleVariationChange(
                                                                            variation.id,
                                                                            "code",
                                                                            e
                                                                                .target
                                                                                .value
                                                                        )
                                                                    }
                                                                />
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div className="col-span-3">
                                                    <input
                                                        type="text"
                                                        className="w-full px-3 py-2 bg-white border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                                        value={variation.sku}
                                                        onChange={(e) =>
                                                            handleVariationChange(
                                                                variation.id,
                                                                "sku",
                                                                e.target.value
                                                            )
                                                        }
                                                    />
                                                </div>
                                                <div className="col-span-3">
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        className="w-full px-3 py-2 bg-white border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                                        value={variation.stock}
                                                        onChange={(e) =>
                                                            handleVariationChange(
                                                                variation.id,
                                                                "stock",
                                                                parseInt(
                                                                    e.target
                                                                        .value
                                                                ) || 0
                                                            )
                                                        }
                                                    />
                                                </div>
                                            </div>
                                        ))}
                                    </>
                                ) : (
                                    <div className="text-center py-8 text-gray-500">
                                        No variations added yet. Select colors
                                        above to add variations.
                                    </div>
                                )}

                                {/* Add More Variations Button */}
                                <div className="mt-6">
                                    <button
                                        type="button"
                                        onClick={handleAddVariation}
                                        className="flex items-center gap-2 px-4 py-2 text-blue-600 hover:text-blue-800 font-medium"
                                    >
                                        <svg
                                            className="w-5 h-5"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                strokeWidth="2"
                                                d="M12 4v16m8-8H4"
                                            />
                                        </svg>
                                        Add More Variations
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Image Upload Section */}
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 className="text-lg font-semibold text-gray-700 mb-6">
                            Product Image
                        </h2>

                        <div className="space-y-6">
                            {/* Image Preview */}
                            {formData.image && (
                                <div className="space-y-2">
                                    <label className="block text-sm font-medium text-gray-600">
                                        Preview
                                    </label>
                                    <div className="border border-gray-300 rounded-lg p-4">
                                        <img
                                            src={formData.image}
                                            alt="preview"
                                            className="w-full max-w-md h-48 object-cover rounded-lg mx-auto"
                                        />
                                    </div>
                                </div>
                            )}

                            {/* File Upload */}
                            <div className="space-y-2">
                                <label className="block text-sm font-medium text-gray-600">
                                    Upload Image
                                </label>
                                <div className="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-400 transition-colors">
                                    <input
                                        type="file"
                                        accept="image/*"
                                        onChange={(e) =>
                                            handleImageUpload(e.target.files[0])
                                        }
                                        className="hidden"
                                        id="image-upload"
                                    />
                                    <label
                                        htmlFor="image-upload"
                                        className="cursor-pointer"
                                    >
                                        <div className="flex flex-col items-center">
                                            <svg
                                                className="w-12 h-12 text-gray-400 mb-3"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    strokeWidth="2"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                                />
                                            </svg>
                                            <span className="text-gray-600 font-medium">
                                                Click to upload or drag and drop
                                            </span>
                                            <span className="text-gray-500 text-sm mt-1">
                                                SVG, PNG, JPG or GIF (max. 5MB)
                                            </span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            {/* Image URL Input */}
                            <div className="space-y-2">
                                <label className="block text-sm font-medium text-gray-600">
                                    Or enter image URL
                                </label>
                                <input
                                    type="text"
                                    className="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="https://example.com/image.jpg"
                                    name="image"
                                    value={formData.image}
                                    onChange={handleInputChange}
                                />
                            </div>
                        </div>
                    </div>
{/* Image Gallery Section */}
<div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h2 className="text-lg font-semibold text-gray-700 mb-6">
        Image Gallery
    </h2>

    <div className="space-y-6">
        {/* Uploaded Images Preview */}
        {formData.galleryImages && formData.galleryImages.length > 0 && (
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                {formData.galleryImages.map((image, index) => (
                    <div key={index} className="relative group">
                        <img
                            src={image}
                            alt={`Gallery Image ${index + 1}`}
                            className="w-full h-32 object-cover rounded-lg border border-gray-300"
                        />
                        <button
                            type="button"
                            onClick={() =>
                                setFormData((prev) => ({
                                    ...prev,
                                    galleryImages: prev.galleryImages.filter(
                                        (_, i) => i !== index
                                    ),
                                }))
                            }
                            className="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity"
                        >
                            <svg
                                className="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                ))}
            </div>
        )}

        {/* File Upload */}
        <div className="space-y-2">
            <label className="block text-sm font-medium text-gray-600">
                Upload Images
            </label>
            <div className="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-400 transition-colors">
                <input
                    type="file"
                    accept="image/*"
                    multiple
                    onChange={(e) => {
                        const files = Array.from(e.target.files);
                        const fileURLs = files.map((file) =>
                            URL.createObjectURL(file)
                        );
                        setFormData((prev) => ({
                            ...prev,
                            galleryImages: [
                                ...(prev.galleryImages || []),
                                ...fileURLs,
                            ],
                        }));
                    }}
                    className="hidden"
                    id="gallery-upload"
                />
                <label
                    htmlFor="gallery-upload"
                    className="cursor-pointer"
                >
                    <div className="flex flex-col items-center">
                        <svg
                            className="w-12 h-12 text-gray-400 mb-3"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth="2"
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                            />
                        </svg>
                        <span className="text-gray-600 font-medium">
                            Click to upload or drag and drop
                        </span>
                        <span className="text-gray-500 text-sm mt-1">
                            SVG, PNG, JPG or GIF (max. 5MB each)
                        </span>
                    </div>
                </label>
            </div>
        </div>
    </div>
</div>
                    {/* Color-wise Image Upload Section */}
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 className="text-lg font-semibold text-gray-700 mb-6">
                            Color wise Image Upload
                        </h2>

                        <div className="space-y-6">
                            {selectedColors.map((color) => (
                                <div key={color.id} className="space-y-4">
                                    <label className="block text-sm font-medium text-gray-600">
                                        {color.name} Image
                                    </label>
                                    <div className="flex items-center gap-4">
                                        <input
                                            type="file"
                                            accept="image/*"
                                            onChange={(e) =>
                                                handleColorImageUpload(
                                                    color.id,
                                                    e.target.files[0]
                                                )
                                            }
                                            className="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        />
                                        {color.image && (
                                            <img
                                                src={color.image}
                                                alt={`${color.name} preview`}
                                                className="w-16 h-16 object-cover rounded-lg border border-gray-300"
                                            />
                                        )}
                                    </div>
                                </div>
                            ))}
                            {selectedColors.length === 0 && (
                                <div className="text-center py-6 text-gray-500 italic">
                                    No colors selected yet
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Search Tags Section */}
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 className="text-lg font-semibold text-gray-700 mb-6">
                            Search Tags
                        </h2>
                        <div className="space-y-4">
                            <div className="flex flex-wrap gap-2 mb-3">
                                {searchTags.map((tag, index) => (
                                    <span
                                        key={index}
                                        className="inline-flex items-center gap-2 px-3 py-2 bg-blue-50 text-blue-700 rounded-lg text-sm"
                                    >
                                        {tag}
                                        <button
                                            type="button"
                                            onClick={() => handleRemoveTag(tag)}
                                            className="text-blue-700 hover:text-blue-900 text-lg leading-none"
                                        >
                                            ×
                                        </button>
                                    </span>
                                ))}
                                {searchTags.length === 0 && (
                                    <span className="text-gray-500 text-sm">
                                        No tags added yet
                                    </span>
                                )}
                            </div>
                            <div className="flex flex-col sm:flex-row gap-3">
                                <input
                                    type="text"
                                    className="flex-1 px-4 py-3 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Enter tag and press Enter"
                                    value={tagInput}
                                    onChange={(e) =>
                                        setTagInput(e.target.value)
                                    }
                                    onKeyDown={handleKeyDown}
                                />
                                <button
                                    type="button"
                                    onClick={handleAddTag}
                                    className="px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors whitespace-nowrap"
                                >
                                    Add Tag
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* Submit Button */}
                    <div className="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                        <button
                            type="button"
                            onClick={resetForm}
                            className="px-6 py-3 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            className="px-8 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors shadow-sm hover:shadow"
                        >
                            Save Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
};

export default AddNewProduct;
