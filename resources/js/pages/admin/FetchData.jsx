import React, { useEffect, useState } from "react";
import api from "../../services/api";

function FetchData() {
  const [loading, setLoading] = useState(false);
  const [categories, setCategories] = useState([]);

  function checkCategory(categoryData) {
    const cat = categories.find((c) => c.name === categoryData.name);
    if (cat) {
      return { status: true, id: cat.id };
    } else {
      return { status: false };
    }
  }

  const fetchCategories = async () => {
    setLoading(true);
    try {
      const { data } = await api.get("/admin/v1/categories");
      setCategories(data || []);
    } catch (error) {
      console.error(error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchCategories();
  }, []);

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

    try {
      // Fetch products from Mohasagor API
      const response = await api.get("/mohasagor/products");
      const products = response.data.data.products;

      if (!products || products.length === 0) {
        console.log("No products found.");
        return;
      }

      // Process all products
      for (const product of products) {
        try {
          console.log("Processing product:", product.name);

          // Prepare category data
          const categoryData = {
            name: truncateText(product.category, 255),
            slug: truncateText(product.slug, 255),
            image: truncateText(product.thumbnail_img, 500),
            is_active: true,
            priority: 1,
          };

          // Check if category exists
          let cat = checkCategory(categoryData);

          // If category doesn't exist, create it
          if (!cat.status) {
            const res = await api.post("/admin/v1/categories", categoryData);
            cat = { status: true, id: res.data.id };
            setCategories((prev) => [...prev, res.data]);
          }

          // Prepare product data WITHOUT gallery_images initially
          const productData = {
            // Basic Info
            name: truncateText(product.name || "Unnamed Product", 255),
            description: truncateText(product.details || "No description available", 5000),
            
            // Category/Brand
            category_id: cat.id || null,
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

          console.log(`Creating product: ${productData.name}`);
          
          // Step 1: Create product without gallery_images
          const createRes = await api.post("/admin/v1/products", productData);
          console.log(`Product created successfully: ${createRes.data.id}`);
          
          // Step 2: Get gallery images
          const galleryImages = getGalleryImages(product);
          
          if (galleryImages.length > 0) {
            console.log(`Adding ${galleryImages.length} gallery images to product ${createRes.data.id}`);
            
            // Prepare gallery data - you might want to limit or chunk it
            const galleryData = {
              gallery_images: galleryImages.slice(0, 5) // Limit to 5 images to avoid size issues
            };
            
            // Step 3: Update product with gallery images
            try {
              const updateRes = await api.put(`/admin/v1/products/${createRes.data.id}`, galleryData);
              console.log(`Gallery images added successfully to product ${createRes.data.id}`);
            } catch (updateError) {
              console.warn(`Could not add gallery images to product ${createRes.data.id}:`, updateError.message);
              // Product was still created successfully, just without gallery
            }
          }
          
          // Add a small delay between products to avoid overwhelming the server
          await new Promise(resolve => setTimeout(resolve, 500));
          
        } catch (productError) {
          console.error(`Error processing product ${product.name}:`, productError.message);
          // Continue with next product even if this one fails
        }
      }
      
      console.log("Finished processing all products");
      
    } catch (error) {
      console.error("Error fetching products:", error.response?.data || error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="flex flex-col justify-center items-center">
      <h1 className="text-2xl font-bold mb-4">Fetch data from Mohasagor API</h1>
      <p className="text-gray-600 mb-6">Click the button to import products</p>
      
      <button
        className="mt-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-md transition duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
        onClick={handleFetchData}
        disabled={loading}
      >
        {loading ? (
          <>
            <span className="inline-block animate-spin mr-2">⟳</span>
            Importing Products...
          </>
        ) : (
          "Import Products"
        )}
      </button>
      
      {loading && (
        <div className="mt-4 text-sm text-gray-500">
          Products are being imported. This may take a few moments...
        </div>
      )}
    </div>
  );
}

export default FetchData;