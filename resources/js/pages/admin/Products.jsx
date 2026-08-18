import React, { useEffect, useState, useCallback } from "react";
import api from "../../services/api";

const Products = () => {
    const [categories, setCategories] = useState([]);
    const [subCategories, setSubCategories] = useState([]);
    const [subSubCategories, setSubSubCategories] = useState([]);
    const [searchTags, setSearchTags] = useState([]);
    const [tagInput, setTagInput] = useState("");
    const [brands, setBrands] = useState([]);
    const [selectedColors, setSelectedColors] = useState([]);

    // gallery image
    const [galleryUploading, setGalleryUploading] = useState(false);
    const [availableColors] = useState([
        { id: 1, name: "Yellow", color: "bg-yellow-500" },
        { id: 2, name: "WhiteSmoke", color: "bg-gray-300" },
        { id: 3, name: "Red", color: "bg-red-500" },
        { id: 4, name: "Blue", color: "bg-blue-500" },
        { id: 5, name: "Green", color: "bg-green-500" },
        { id: 6, name: "Black", color: "bg-black" },
    ]);

    const [variations, setVariations] = useState([]);

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
        gallery_images: [],
        description: "",
    });

    // Add state for specifications
    const [specifications, setSpecifications] = useState([]);
    const [specInput, setSpecInput] = useState("");

    // Handler to add a specification
    const handleAddSpecification = () => {
        if (specInput.trim() && !specifications.includes(specInput.trim())) {
            setSpecifications([...specifications, specInput.trim()]);
            setSpecInput("");
        }
    };

    // Handler to remove a specification
    const handleRemoveSpecification = (specToRemove) => {
        setSpecifications(
            specifications.filter((spec) => spec !== specToRemove)
        );
    };

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
            const { data } = await api.get("/admin/v1/brands");
            setBrands(data || []);
        } catch (err) {
            console.error("Failed to load brands", err);
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

    /* ================= VARIATION GENERATION - FIXED ================= */
    const generateVariations = useCallback(() => {
        // If no colors are selected, return empty
        if (selectedColors.length === 0) {
            setVariations([]);
            return;
        }

        // Filter out attributes with values
        const attributesWithValues = selectedAttributes.filter(
            (attr) => attr.value && attr.value.length > 0
        );

        // Generate base variations from selected colors
        let baseVariations = selectedColors.map((color, index) => ({
            id: index + 1,
            color: color.name,
            colorClass: color.color,
            code: "",
            sku: `${formData.product_sku || "VAR"}-${color.name.replace(
                /\s+/g,
                "-"
            )}`,
            stock: 1,
            price: parseFloat(formData.price) || 0,
            colorId: color.id,
        }));

        // If no attributes with values, set base variations
        if (attributesWithValues.length === 0) {
            setVariations(baseVariations);
            return;
        }

        // Generate all combinations of colors and attribute values
        let allVariations = [];

        // For each base variation (color), create combinations with attribute values
        baseVariations.forEach((baseVar) => {
            // Start with the base variation itself
            let variationsForThisColor = [baseVar];

            // For each attribute, create new variations
            attributesWithValues.forEach((attr) => {
                const newVariations = [];

                variationsForThisColor.forEach((variation) => {
                    attr.value.forEach((attrValue) => {
                        // Create SKU: BaseSKU-AttributeValue (without starting dash)
                        const attributePart = attrValue.replace(/\s+/g, "-");
                        const newSku = `${
                            variation.sku.split("-")[0]
                        }-${variation.color.replace(
                            /\s+/g,
                            "-"
                        )}-${attributePart}`;

                        newVariations.push({
                            ...variation,
                            id: 0, // Temporary ID
                            sku: newSku,
                            attributes: {
                                ...(variation.attributes || {}),
                                [attr.name]: attrValue,
                            },
                        });
                    });
                });

                variationsForThisColor = newVariations;
            });

            allVariations.push(...variationsForThisColor);
        });

        // Remove duplicates based on SKU
        const uniqueVariations = [];
        const seenSkus = new Set();

        allVariations.forEach((variation) => {
            if (!seenSkus.has(variation.sku)) {
                seenSkus.add(variation.sku);
                uniqueVariations.push(variation);
            }
        });

        // Assign proper IDs and ensure price is set
        const finalVariations = uniqueVariations.map((variation, index) => ({
            ...variation,
            id: index + 1,
            price: variation.price || parseFloat(formData.price) || 0,
        }));

        setVariations(finalVariations);
    }, [
        selectedColors,
        selectedAttributes,
        formData.price,
        formData.product_sku,
    ]);

    useEffect(() => {
        generateVariations();
    }, [generateVariations]);

    /* ================= ATTRIBUTE HANDLERS ================= */
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

        setSelectedAttributes((prev) => [...prev, { ...attribute, value: [] }]);
        e.target.value = "";
    };

    const addAttributeValue = (attributeId, newValue) => {
        if (!newValue.trim()) return;

        setSelectedAttributes((prev) =>
            prev.map((attr) => {
                if (attr.id !== attributeId) return attr;

                // Prevent duplicate value
                if (attr.value.includes(newValue.trim())) {
                    return attr;
                }

                return {
                    ...attr,
                    value: [...attr.value, newValue.trim()],
                };
            })
        );
    };

    const removeAttributeValue = (attributeId, valueToRemove) => {
        setSelectedAttributes((prev) =>
            prev.map((attr) => {
                if (attr.id !== attributeId) return attr;
                return {
                    ...attr,
                    value: attr.value.filter((val) => val !== valueToRemove),
                };
            })
        );
    };

    const handleRemoveAttribute = (attrId) => {
        setSelectedAttributes(
            selectedAttributes.filter((a) => a.id !== attrId)
        );
    };

    /* ================= COLOR HANDLERS ================= */
    const handleColorSelect = (e) => {
        const colorId = parseInt(e.target.value);
        if (!colorId) return;

        const color = availableColors.find((c) => c.id === colorId);
        if (color && !selectedColors.find((sc) => sc.id === colorId)) {
            const newSelectedColors = [...selectedColors, color];
            setSelectedColors(newSelectedColors);
            e.target.value = "";
        }
    };

    const handleRemoveColor = (colorId) => {
        const colorToRemove = selectedColors.find((c) => c.id === colorId);
        if (colorToRemove) {
            const newSelectedColors = selectedColors.filter(
                (c) => c.id !== colorId
            );
            setSelectedColors(newSelectedColors);
        }
    };

    /* ================= VARIATION HANDLERS ================= */
    const handleVariationChange = (id, field, value) => {
        setVariations((prevVariations) =>
            prevVariations.map((variation) =>
                variation.id === id
                    ? {
                          ...variation,
                          [field]:
                              field === "price"
                                  ? parseFloat(value) || 0
                                  : value,
                      }
                    : variation
            )
        );
    };

    const handleAddVariation = () => {
        const newId =
            variations.length > 0
                ? Math.max(...variations.map((v) => v.id)) + 1
                : 1;

        const newVariation = {
            id: newId,
            color: "Custom",
            colorClass: "bg-gray-400",
            code: "",
            sku: `${formData.product_sku || "CUSTOM"}-${newId}`,
            stock: 1,
            price: parseFloat(formData.price) || 0,
            isCustom: true,
        };

        setVariations([...variations, newVariation]);
    };

    /* ================= SKU GENERATION ================= */
    const handleGenerateSKU = () => {
        const randomSKU = `PROD-${Math.random()
            .toString(36)
            .substr(2, 9)
            .toUpperCase()}`;
        setFormData({ ...formData, product_sku: randomSKU });

        // Update variations SKUs with new base SKU
        if (variations.length > 0) {
            setVariations(
                variations.map((v) => ({
                    ...v,
                    sku: v.sku.replace(/^[^-]+/, randomSKU.split("-")[0]),
                }))
            );
        }
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
                image: data.path.split("/").pop(),
            });
        } catch (err) {
            console.error("Image upload failed", err);
            alert("Image upload failed");
        }
    };

    /* ================= COLOR IMAGE UPLOAD ================= */
    const handleColorImageUpload = async (colorId, file) => {
        if (!file) return;

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
                              image: data.path.split("/").pop(),
                          }
                        : color
                )
            );
        } catch (err) {
            console.error("Image upload failed", err);
            alert("Image upload failed");
        }
    };

    /* ================= GALLERY IMAGE UPLOAD ================= */
    const handleGalleryImageUpload = async (files) => {
        if (!files || files.length === 0) return;

        setGalleryUploading(true);
        const uploadedImages = [];

        const uploadPromises = Array.from(files).map(async (file, index) => {
            if (file.size > 5 * 1024 * 1024) {
                throw new Error(
                    `File "${file.name}" is too large. Maximum size is 5MB.`
                );
            }

            const validTypes = [
                "image/jpeg",
                "image/png",
                "image/gif",
                "image/svg+xml",
                "image/webp",
            ];
            if (!validTypes.includes(file.type)) {
                throw new Error(
                    `File "${file.name}" is not a valid image type.`
                );
            }

            const fd = new FormData();
            fd.append("image", file);
            fd.append("folder", "products/gallery");

            try {
                const { data } = await api.post("/admin/v1/upload", fd, {
                    headers: { "Content-Type": "multipart/form-data" },
                });

                const imageUrl = data.path.split("/").pop();
                return { success: true, url: imageUrl, fileName: file.name };
            } catch (err) {
                console.error(`Failed to upload image: ${file.name}`, err);
                return {
                    success: false,
                    fileName: file.name,
                    error: err.message,
                };
            }
        });

        try {
            const results = await Promise.all(uploadPromises);
            const successfulUploads = results.filter(
                (result) => result.success
            );
            const failedUploads = results.filter((result) => !result.success);

            if (successfulUploads.length > 0) {
                const newImageUrls = successfulUploads.map(
                    (result) => result.url
                );
                setFormData((prev) => ({
                    ...prev,
                    gallery_images: [...prev.gallery_images, ...newImageUrls],
                }));

                if (successfulUploads.length > 0) {
                    alert(
                        `Successfully uploaded ${successfulUploads.length} image(s).`
                    );
                }
            }

            if (failedUploads.length > 0) {
                const errorMessages = failedUploads
                    .map((result) => `${result.fileName}: ${result.error}`)
                    .join("\n");
                alert(
                    `Failed to upload ${failedUploads.length} image(s):\n${errorMessages}`
                );
            }
        } catch (error) {
            console.error("Gallery upload failed:", error);
            alert("Failed to upload gallery images. Please try again.");
        } finally {
            setGalleryUploading(false);
        }
    };

    // Remove single gallery image
    const handleRemoveGalleryImage = (index) => {
        setFormData((prev) => ({
            ...prev,
            gallery_images: prev.gallery_images.filter((_, i) => i !== index),
        }));
    };

    // Clear all gallery images
    const handleClearAllGalleryImages = () => {
        if (
            window.confirm(
                "Are you sure you want to remove all gallery images?"
            )
        ) {
            setFormData((prev) => ({
                ...prev,
                gallery_images: [],
            }));
        }
    };

    /* ================= SUBMIT HANDLER ================= */
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
                category_id:
                    formData.sub_sub_category_id ||
                    formData.sub_category_id ||
                    formData.category_id,
                brand: formData.brand,

                // Specifications
                specifications: specifications,

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
                gallery_images: formData.gallery_images || [],

                // Tags
                tags: searchTags,

                // Variations
                variations: variations.map((variation) => ({
                    id: variation.id,
                    color: variation.color,
                    colorClass: variation.colorClass,
                    code: variation.code,
                    sku: variation.sku,
                    stock: parseInt(variation.stock) || 1,
                    price: parseFloat(variation.price) || finalPrice,
                    color_image:
                        selectedColors.find((c) => c.name === variation.color)
                            ?.image || null,
                    attributes: variation.attributes || {},
                })),

                // Attributes (formatted as JSON string)
                attributes: selectedAttributes.map(attr => ({
                    name: attr.name,
                    values: attr.value || []
                })),
                // NEW WAY (associative object)
                // attributes: selectedAttributes.reduce((acc, attr) => {
                //     // This creates: {"Size": ["M", "L"], "Weight": ["1kg"]}
                //     acc[attr.name] = attr.value || [];
                //     return acc;
                // }, {}),

                // Colors
                colors: selectedColors.map((color) => ({
                    id: color.id,
                    name: color.name,
                    color_class: color.color,
                    image: color.image || null,
                })),

                // Additional metadata
                status: "active",
                is_featured: false,
                is_trending: false,
                is_discounted: formData.discount_type !== "None",
            };

            console.log("Submitting product data:", productData);

            // Send to API
            await api.post("/admin/v1/products", productData);

            alert("Product added successfully");

            // Reset form
            resetForm();
        } catch (err) {
            console.error("Product create failed", err);
            alert(
                "Failed to add product: " +
                    (err.response?.data?.message || err.message)
            );
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
        setSelectedAttributes([]);
        setVariations([]);
        setShippingMultiply(true);
        setSubCategories([]);
        setSubSubCategories([]);
        setSpecifications([]);
    };

    const handleInputChange = (e) => {
        const { name, value } = e.target;
        setFormData({
            ...formData,
            [name]: value,
        });

        // If price changes, update variations with new base price
        if (name === "price" && variations.length > 0) {
            const newPrice = parseFloat(value) || 0;
            setVariations((prev) =>
                prev.map((v) => ({
                    ...v,
                    price: v.price === 0 ? newPrice : v.price,
                }))
            );
        }
    };

    return (
        <div className="min-h-screen bg-gray-50 p-4 md:p-6 text-black">
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

                    {/* Specifications Section */}
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 className="text-lg font-semibold text-gray-700 mb-6">
                            Specifications
                        </h2>
                        <div className="space-y-4">
                            {/* Display Added Specifications */}
                            <div className="flex flex-wrap gap-2 mb-3">
                                {specifications.map((spec, index) => (
                                    <span
                                        key={index}
                                        className="inline-flex items-center gap-2 px-3 py-2 bg-green-50 text-green-700 rounded-lg text-sm"
                                    >
                                        {spec}
                                        <button
                                            type="button"
                                            onClick={() =>
                                                handleRemoveSpecification(spec)
                                            }
                                            className="text-green-700 hover:text-green-900 text-lg leading-none"
                                        >
                                            ×
                                        </button>
                                    </span>
                                ))}
                                {specifications.length === 0 && (
                                    <span className="text-gray-500 text-sm">
                                        No specifications added yet
                                    </span>
                                )}
                            </div>

                            {/* Input Field to Add Specifications */}
                            <div className="flex flex-col sm:flex-row gap-3">
                                <input
                                    type="text"
                                    className="flex-1 px-4 py-3 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Enter specification and press +"
                                    value={specInput}
                                    onChange={(e) =>
                                        setSpecInput(e.target.value)
                                    }
                                />
                                <button
                                    type="button"
                                    onClick={handleAddSpecification}
                                    className="px-6 py-3 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors whitespace-nowrap"
                                >
                                    + Add Specification
                                </button>
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
                            {/* Colors and Attributes Selection */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {/* Colors Selection */}
                                <div className="space-y-4">
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
                                                Select Color
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

                                {/* Attributes Selection */}
                                <div className="space-y-4">
                                    <label className="block text-sm font-medium text-gray-600">
                                        Select Attributes:
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
                                                    className={`w-4 h-4 rounded-full bg-blue-200`}
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
                            </div>

                            {/* Attribute input fields */}
                            {selectedAttributes.length > 0 && (
                                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-6">
                                    {selectedAttributes.map((attr) => (
                                        <div
                                            className="space-y-2"
                                            key={attr.id}
                                        >
                                            <label className="block text-sm font-medium text-gray-600">
                                                {attr.name}
                                            </label>
                                            <input
                                                type="text"
                                                className="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                placeholder={`Enter ${attr.name.toLowerCase()}`}
                                                onKeyDown={(e) => {
                                                    if (e.key === "Enter") {
                                                        e.preventDefault();
                                                        const value =
                                                            e.target.value.trim();
                                                        if (!value) return;
                                                        addAttributeValue(
                                                            attr.id,
                                                            value
                                                        );
                                                        e.target.value = "";
                                                    }
                                                }}
                                                onBlur={(e) => {
                                                    const value =
                                                        e.target.value.trim();
                                                    if (!value) return;
                                                    addAttributeValue(
                                                        attr.id,
                                                        value
                                                    );
                                                    e.target.value = "";
                                                }}
                                            />

                                            {/* Display entered values */}
                                            <div className="flex flex-wrap gap-2 mt-2">
                                                {attr.value?.map(
                                                    (val, index) => (
                                                        <span
                                                            key={index}
                                                            className="inline-flex items-center gap-2 px-3 py-1 bg-blue-50 text-blue-700 rounded-lg text-sm"
                                                        >
                                                            {val}
                                                            <button
                                                                type="button"
                                                                onClick={() =>
                                                                    removeAttributeValue(
                                                                        attr.id,
                                                                        val
                                                                    )
                                                                }
                                                                className="text-blue-700 hover:text-blue-900"
                                                            >
                                                                ×
                                                            </button>
                                                        </span>
                                                    )
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}

                            {/* Divider */}
                            <div className="border-t border-gray-200"></div>

                            {/* Variations Table */}
                            <div>
                                <div className="flex justify-between items-center mb-4">
                                    <h2 className="text-lg font-semibold text-gray-700">
                                        Generated Variations:
                                    </h2>
                                    <span className="text-sm text-gray-500">
                                        {variations.length} variation(s)
                                    </span>
                                </div>

                                {variations.length > 0 ? (
                                    <>
                                        {/* Table Header */}
                                        <div className="grid grid-cols-12 gap-4 mb-4 px-4">
                                            <div className="col-span-1">
                                                <span className="text-sm font-medium text-gray-600">
                                                    SL
                                                </span>
                                            </div>
                                            <div className="col-span-4">
                                                <span className="text-sm font-medium text-gray-600">
                                                    Variation Details
                                                </span>
                                            </div>
                                            <div className="col-span-3">
                                                <span className="text-sm font-medium text-gray-600">
                                                    SKU
                                                </span>
                                            </div>
                                            <div className="col-span-2">
                                                <span className="text-sm font-medium text-gray-600">
                                                    Stock
                                                </span>
                                            </div>
                                            <div className="col-span-2">
                                                <span className="text-sm font-medium text-gray-600">
                                                    Price (৳)
                                                </span>
                                            </div>
                                        </div>

                                        {/* Variation Rows */}
                                        {variations.map((variation, index) => (
                                            <div
                                                key={variation.id}
                                                className="grid grid-cols-12 gap-4 mb-4 p-4 bg-gray-50 rounded-lg border border-gray-200"
                                            >
                                                <div className="col-span-1 flex items-center">
                                                    <span className="font-medium text-gray-700">
                                                        {index + 1}.
                                                    </span>
                                                </div>
                                                <div className="col-span-4">
                                                    <div className="flex items-center gap-3">
                                                        <div
                                                            className={`w-6 h-6 ${variation.colorClass} rounded-full border border-gray-300`}
                                                        ></div>
                                                        <div>
                                                            <span className="font-medium text-gray-800 block">
                                                                {
                                                                    variation.color
                                                                }
                                                            </span>
                                                            {variation.attributes &&
                                                                Object.keys(
                                                                    variation.attributes
                                                                ).length >
                                                                    0 && (
                                                                    <div className="flex flex-wrap gap-1 mt-1">
                                                                        {Object.entries(
                                                                            variation.attributes
                                                                        ).map(
                                                                            ([
                                                                                key,
                                                                                value,
                                                                            ]) => (
                                                                                <span
                                                                                    key={
                                                                                        key
                                                                                    }
                                                                                    className="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded"
                                                                                >
                                                                                    {
                                                                                        key
                                                                                    }
                                                                                    :{" "}
                                                                                    {
                                                                                        value
                                                                                    }
                                                                                </span>
                                                                            )
                                                                        )}
                                                                    </div>
                                                                )}
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
                                                <div className="col-span-2">
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
                                                <div className="col-span-2">
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        step="0.01"
                                                        placeholder="Price"
                                                        className="w-full px-3 py-2 bg-white border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                                        value={
                                                            variation.price ||
                                                            ""
                                                        }
                                                        onChange={(e) =>
                                                            handleVariationChange(
                                                                variation.id,
                                                                "price",
                                                                e.target.value
                                                            )
                                                        }
                                                    />
                                                </div>
                                            </div>
                                        ))}
                                    </>
                                ) : (
                                    <div className="text-center py-8 text-gray-500 bg-gray-50 rounded-lg">
                                        <svg
                                            className="w-16 h-16 mx-auto text-gray-300 mb-3"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                strokeWidth="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                            />
                                        </svg>
                                        <p>No variations generated yet.</p>
                                        <p className="text-sm mt-1">
                                            Select colors and attributes above
                                            to generate variations.
                                        </p>
                                    </div>
                                )}

                                {/* Add More Variations Button */}
                                <div className="mt-6">
                                    <button
                                        type="button"
                                        onClick={handleAddVariation}
                                        className="flex items-center gap-2 px-4 py-2 text-blue-600 hover:text-blue-800 font-medium border border-blue-200 rounded-lg hover:bg-blue-50"
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
                                        Add Custom Variation
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
                                            src={`${
                                                import.meta.env
                                                    .VITE_API_BASE_URL
                                            }/storage/products/${
                                                formData.image
                                            }`}
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
                                    value={
                                        formData.image
                                            ? `${
                                                  import.meta.env
                                                      .VITE_API_BASE_URL
                                              }/storage/products/${
                                                  formData.image
                                              }`
                                            : ""
                                    }
                                    onChange={(e) => {
                                        const url = e.target.value;
                                        const filename = url.split("/").pop();
                                        setFormData({
                                            ...formData,
                                            image: filename,
                                        });
                                    }}
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
                            {(formData.gallery_images ?? []).length > 0 ? (
                                <div>
                                    <div className="flex justify-between items-center mb-3">
                                        <label className="block text-sm font-medium text-gray-600">
                                            Gallery Images (
                                            {formData.gallery_images.length})
                                        </label>
                                        <button
                                            type="button"
                                            onClick={
                                                handleClearAllGalleryImages
                                            }
                                            className="text-sm text-red-600 hover:text-red-800 font-medium"
                                        >
                                            Clear All
                                        </button>
                                    </div>
                                    <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                                        {formData.gallery_images.map(
                                            (image, index) => (
                                                <div
                                                    key={index}
                                                    className="relative group"
                                                >
                                                    <div className="aspect-square overflow-hidden rounded-lg border border-gray-300 bg-gray-100">
                                                        <img
                                                            src={`${
                                                                import.meta.env
                                                                    .VITE_API_BASE_URL
                                                            }/storage/products/gallery/${image}`}
                                                            alt={`Gallery Image ${
                                                                index + 1
                                                            }`}
                                                            className="w-full h-full object-cover hover:scale-105 transition-transform duration-200"
                                                        />
                                                    </div>
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            handleRemoveGalleryImage(
                                                                index
                                                            )
                                                        }
                                                        className="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-600"
                                                        title="Remove image"
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
                                                    <div className="absolute bottom-0 left-0 right-0 bg-black bg-opacity-60 text-white text-xs p-1 text-center truncate">
                                                        Image {index + 1}
                                                    </div>
                                                </div>
                                            )
                                        )}
                                    </div>
                                </div>
                            ) : (
                                <div className="text-center py-8 border-2 border-dashed border-gray-300 rounded-lg">
                                    <svg
                                        className="w-16 h-16 text-gray-400 mx-auto mb-3"
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
                                    <p className="text-gray-500">
                                        No gallery images uploaded yet
                                    </p>
                                </div>
                            )}

                            {/* File Upload Section */}
                            <div className="space-y-4">
                                <label className="block text-sm font-medium text-gray-600">
                                    Upload Multiple Images
                                </label>

                                <div className="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-400 transition-colors">
                                    <input
                                        type="file"
                                        accept="image/*"
                                        multiple
                                        onChange={(e) => {
                                            const files = e.target.files;
                                            if (files && files.length > 0) {
                                                handleGalleryImageUpload(files);
                                            }
                                            e.target.value = "";
                                        }}
                                        className="hidden"
                                        id="gallery-upload"
                                        disabled={galleryUploading}
                                    />
                                    <label
                                        htmlFor="gallery-upload"
                                        className={`cursor-pointer ${
                                            galleryUploading
                                                ? "opacity-50 cursor-not-allowed"
                                                : ""
                                        }`}
                                    >
                                        <div className="flex flex-col items-center">
                                            {galleryUploading ? (
                                                <>
                                                    <div className="w-12 h-12 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mb-3"></div>
                                                    <span className="text-gray-600 font-medium">
                                                        Uploading Images...
                                                    </span>
                                                </>
                                            ) : (
                                                <>
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
                                                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                                                        />
                                                    </svg>
                                                    <span className="text-gray-600 font-medium">
                                                        Click to upload or drag
                                                        and drop
                                                    </span>
                                                    <span className="text-gray-500 text-sm mt-1">
                                                        PNG, JPG, GIF, SVG, WEBP
                                                        (max. 5MB each)
                                                    </span>
                                                    <span className="text-blue-500 text-sm mt-2 font-medium">
                                                        Select multiple files
                                                    </span>
                                                </>
                                            )}
                                        </div>
                                    </label>

                                    <p className="text-xs text-gray-400 mt-4">
                                        You can select multiple images at once
                                    </p>
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
                                                src={`${
                                                    import.meta.env
                                                        .VITE_API_BASE_URL
                                                }/storage/products/${
                                                    color.image
                                                }`}
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

export default Products;
