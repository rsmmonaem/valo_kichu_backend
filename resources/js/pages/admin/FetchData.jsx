import React, { useEffect, useState } from "react";
import api from "../../services/api";

function FetchData() {
  const [loading, setLoading] = useState(false);
  const [categories, setCategories] = useState([]);
  const [existingProducts, setExistingProducts] = useState([]);
  const [processedCount, setProcessedCount] = useState(0);
  const [importStats, setImportStats] = useState({
    total: 0,
    created: 0,
    skipped: 0,
    failed: 0
  });

  // Fetch existing categories and products from backend
  const fetchExistingData = async () => {
    setLoading(true);
    try {
      // Fetch categories
      const { data: categoriesData } = await api.get("/admin/v1/categories");
      setCategories(categoriesData || []);
      
      // Fetch existing products to check for duplicates
      const { data: productsData } = await api.get("/admin/v1/products");
      // Create a map of existing products by api_id for quick lookup
      const productsMap = {};
      if (productsData && productsData.data) {
        productsData.data.forEach(product => {
          if (product.api_id) {
            productsMap[product.api_id] = true;
          }
        });
      }
      setExistingProducts(productsMap);
      
      console.log(`Loaded ${categoriesData?.length || 0} categories and ${Object.keys(productsMap).length} existing products`);
    } catch (error) {
      console.error("Error fetching existing data:", error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchExistingData();
  }, []);

  // Check if category already exists
  const findCategoryByName = (categoryName) => {
    if (!categoryName) return null;
    return categories.find(cat => 
      cat.name && categoryName && 
      cat.name.toLowerCase().trim() === categoryName.toLowerCase().trim()
    );
  };

  // Check if product already exists by api_id
  const checkProductExists = (apiId) => {
    return existingProducts[apiId] === true;
  };

  // Function to create category if it doesn't exist
  const ensureCategoryExists = async (categoryName, categorySlug, categoryImage) => {
    if (!categoryName) return null;

    const existingCategory = findCategoryByName(categoryName);
    if (existingCategory) {
      console.log(`Category already exists: ${categoryName} (ID: ${existingCategory.id})`);
      return existingCategory.id;
    }

    try {
      console.log(`Creating new category: ${categoryName}`);
      const categoryData = {
        name: categoryName.trim(),
        slug: categorySlug || categoryName.toLowerCase().replace(/\s+/g, '-'),
        image: categoryImage || '',
        is_active: true,
        priority: 1,
      };

      const res = await api.post("/admin/v1/categories", categoryData);
      const newCategory = res.data;
      
      // Update local categories state
      setCategories(prev => [...prev, newCategory]);
      console.log(`Category created: ${newCategory.name} (ID: ${newCategory.id})`);
      
      return newCategory.id;
    } catch (error) {
      console.error(`Error creating category ${categoryName}:`, error.response?.data || error);
      return null;
    }
  };

  const truncateText = (text, maxLength = 255) => {
    if (!text || typeof text !== 'string') return text || '';
    return text.length > maxLength ? text.substring(0, maxLength - 3) + '...' : text;
  };

  // Function to get gallery images from product
  const getGalleryImages = (product) => {
    if (!product || !product.product_images) return [];
    
    if (Array.isArray(product.product_images)) {
      // If it's an array of objects with product_image property
      if (product.product_images.length > 0 && 
          product.product_images[0] && 
          typeof product.product_images[0] === 'object' && 
          product.product_images[0].product_image) {
        return product.product_images.map(img => img.product_image).filter(Boolean);
      }
      // If it's an array of strings
      if (typeof product.product_images[0] === 'string') {
        return product.product_images;
      }
    }
    
    return [];
  };

  const handleFetchData = async () => {
    setLoading(true);
    setImportStats({
      total: 0,
      created: 0,
      skipped: 0,
      failed: 0
    });
    setProcessedCount(0);

    try {
      // Fetch products from Mohasagor API
      const response = await api.get("/mohasagor/products");
      const products = response.data.data.products;

      if (!products || products.length === 0) {
        console.log("No products found.");
        alert("No products found in the API.");
        return;
      }

      setImportStats(prev => ({ ...prev, total: products.length }));
      console.log(`Found ${products.length} products to process`);

      // Process products one by one
      for (let i = 0; i < products.length; i++) {
        const product = products[i];
        setProcessedCount(i + 1);
        
        console.log(`Processing product ${i + 1}/${products.length}: ${product.name || product.id}`);
        
        // Check if product already exists
        if (product.id && checkProductExists(product.id)) {
          console.log(`Product ${product.name || product.id} already exists. Skipping.`);
          setImportStats(prev => ({ ...prev, skipped: prev.skipped + 1 }));
          continue;
        }

        try {
          // Ensure category exists and get category ID
          const categoryId = await ensureCategoryExists(
            product.category,
            product.slug,
            product.thumbnail_img
          );

          if (!categoryId) {
            console.warn(`Skipping product ${product.name || product.id} - category not found/created`);
            setImportStats(prev => ({ ...prev, failed: prev.failed + 1 }));
            continue;
          }

          // Prepare product data WITHOUT gallery_images initially
          const productData = {
            // Basic Info
            name: truncateText(product.name || "Unnamed Product", 255),
            description: truncateText(product.details || "No description available", 5000),
            
            // Category/Brand
            category_id: categoryId,
            brand: truncateText(product.brand || "Unknown", 255),
            category: truncateText(product.category || null, 255),
            
            // API Info
            api_id: product.id || null,
            api_from: "Mohasagor",
            product_code: product.product_code || null,
            
            // Product Info
            product_type: "physical",
            product_sku: truncateText(product.sku || `SKU-${product.id || Date.now()}`, 50),
            unit: truncateText(product.unit || "pcs", 20),
            
            // Pricing
            price: parseFloat(product.price) || 0,
            purchase_price: parseFloat(product.sale_price) || 0,
            unit_price: parseFloat(product.unit_price) || 0,
            
            // Stock
            min_order_qty: parseInt(product.min_order_qty) || 1,
            current_stock: parseInt(product.current_stock) || 0,
            
            // Discount
            discount_type: "None",
            discount_amount: parseFloat(product.discount_amount) || 0,
            
            // Tax
            tax_amount: parseFloat(product.tax_amount) || 0,
            tax_calculation: "exclude",
            
            // Shipping
            shipping_cost: parseFloat(product.shipping_cost) || 0,
            shipping_multiply: false,
            
            // Loyalty
            loyalty_point: 0,
            
            // Main image only initially
            image: truncateText(product.thumbnail_img || "", 500),
            
            // JSON fields - start with empty arrays
            variations: [],
            attributes: [],
            colors: [],
            tags: [],
            
            // Status flags
            is_featured: false,
            is_trending: false,
            is_discounted: false,
            status: "active",
            
            // Slug
            slug: truncateText(
              product.slug || 
              (product.name ? product.name.toLowerCase().replace(/\s+/g, "-") : "") || 
              `product-${product.id || Date.now()}`,
              255
            ),
          };

          // Step 1: Create product without gallery_images
          console.log(`Creating product: ${productData.name}`);
          const createRes = await api.post("/admin/v1/products", productData);
          const productId = createRes.data.id;
          
          // Add to existing products map
          if (product.id) {
            setExistingProducts(prev => ({ ...prev, [product.id]: true }));
          }
          
          console.log(`Product created successfully: ${productId}`);
          setImportStats(prev => ({ ...prev, created: prev.created + 1 }));
          
          // Step 2: Get gallery images
          const galleryImages = getGalleryImages(product);
          
          if (galleryImages.length > 0) {
            console.log(`Adding ${galleryImages.length} gallery images to product ${productId}`);
            
            // Prepare gallery data - limit to 5 images
            const galleryData = {
              gallery_images: galleryImages.slice(0, 5)
            };
            
            // Step 3: Update product with gallery images
            try {
              await api.put(`/admin/v1/products/${productId}`, galleryData);
              console.log(`Gallery images added successfully to product ${productId}`);
            } catch (updateError) {
              console.warn(`Could not add gallery images to product ${productId}:`, updateError.message);
              // Product was still created successfully
            }
          }
          
          // Add a small delay to avoid overwhelming the server
          await new Promise(resolve => setTimeout(resolve, 300));
          
        } catch (productError) {
          console.error(`Error processing product ${product.name || product.id}:`, productError.response?.data || productError.message);
          setImportStats(prev => ({ ...prev, failed: prev.failed + 1 }));
          // Continue with next product
        }
      }
      
      console.log("Finished processing all products");
      alert(`Import completed!\n\nTotal: ${importStats.total}\nCreated: ${importStats.created}\nSkipped (duplicates): ${importStats.skipped}\nFailed: ${importStats.failed}`);
      
      // Refresh the existing products list
      fetchExistingData();
      
    } catch (error) {
      console.error("Error fetching products:", error.response?.data || error);
      alert("Error fetching products from API. Please check the console for details.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="flex flex-col justify-center items-center min-h-screen bg-gray-50 p-6">
      <div className="bg-white rounded-lg shadow-lg p-8 max-w-2xl w-full">
        <h1 className="text-3xl font-bold text-gray-800 mb-2 text-center">
          Import Products from Mohasagor
        </h1>
        <p className="text-gray-600 mb-6 text-center">
          Import products from Mohasagor API to your store
        </p>
        
        <div className="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
          <h3 className="font-semibold text-blue-800 mb-2">How it works:</h3>
          <ul className="text-blue-700 text-sm list-disc pl-5 space-y-1">
            <li>Fetches products from Mohasagor API</li>
            <li>Checks for duplicate products and categories</li>
            <li>Creates categories if they don't exist</li>
            <li>Creates products without gallery images first</li>
            <li>Adds gallery images separately</li>
          </ul>
        </div>

        {importStats.total > 0 && (
          <div className="mb-6">
            <div className="flex justify-between items-center mb-2">
              <span className="text-sm font-medium text-gray-700">Progress</span>
              <span className="text-sm text-gray-600">
                {processedCount} / {importStats.total}
              </span>
            </div>
            <div className="w-full bg-gray-200 rounded-full h-2.5">
              <div 
                className="bg-blue-600 h-2.5 rounded-full transition-all duration-300"
                style={{ width: `${(processedCount / importStats.total) * 100}%` }}
              ></div>
            </div>
            
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
              <div className="bg-green-50 border border-green-200 rounded-lg p-3 text-center">
                <div className="text-2xl font-bold text-green-700">{importStats.created}</div>
                <div className="text-sm text-green-600">Created</div>
              </div>
              <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-center">
                <div className="text-2xl font-bold text-yellow-700">{importStats.skipped}</div>
                <div className="text-sm text-yellow-600">Skipped</div>
              </div>
              <div className="bg-red-50 border border-red-200 rounded-lg p-3 text-center">
                <div className="text-2xl font-bold text-red-700">{importStats.failed}</div>
                <div className="text-sm text-red-600">Failed</div>
              </div>
              <div className="bg-blue-50 border border-blue-200 rounded-lg p-3 text-center">
                <div className="text-2xl font-bold text-blue-700">{importStats.total}</div>
                <div className="text-sm text-blue-600">Total</div>
              </div>
            </div>
          </div>
        )}

        <button
          className="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-4 px-6 rounded-lg transition duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
          onClick={handleFetchData}
          disabled={loading}
        >
          {loading ? (
            <>
              <svg className="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Importing Products...
            </>
          ) : (
            "Start Import"
          )}
        </button>
        
        <div className="mt-6 text-sm text-gray-500 text-center">
          <p>This process may take several minutes depending on the number of products.</p>
          <p className="mt-1">Duplicates will be automatically skipped.</p>
        </div>
      </div>
    </div>
  );
}

export default FetchData;