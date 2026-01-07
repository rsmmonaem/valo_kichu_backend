import React, { useEffect, useState } from 'react';
import { useSearchParams, Link } from 'react-router-dom';
import api from '../../services/api';
import { Filter, Star } from 'lucide-react';

const Shop = () => {
    const [searchParams] = useSearchParams();
    const categorySlug = searchParams.get('category');
    const searchQuery = searchParams.get('search');

    const [products, setProducts] = useState([]);
    const [categories, setCategories] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchProducts();
        fetchCategories();
    }, [categorySlug, searchQuery]);

    const fetchCategories = async () => {
        try {
            const { data } = await api.get('/categories');
            setCategories(data);
        } catch (error) {
            console.error("Failed to fetch categories", error);
        }
    };

    const fetchProducts = async () => {
        setLoading(true);
        try {
            const params = {};
            if (categorySlug) params.category_slug = categorySlug;
            if (searchQuery) params.search = searchQuery;

            const { data } = await api.get('/products', { params });
            setProducts(data.data || []);
        } catch (error) {
            console.error("Failed to fetch products", error);
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="bg-gray-50 min-h-screen py-8">
            <div className="container mx-auto px-4">
                <div className="flex flex-col md:flex-row gap-8">
                    {/* Sidebar Filters */}
                    <div className="w-full md:w-64 flex-shrink-0">
                        <div className="bg-white rounded-lg shadow-sm p-6 sticky top-24">
                            <div className="flex items-center gap-2 mb-4 text-gray-800 font-bold border-b pb-2">
                                <Filter size={20} />
                                <span>Filters</span>
                            </div>

                            <div className="mb-6">
                                <h3 className="font-semibold mb-3 text-sm">Categories</h3>
                                <ul className="space-y-2 text-sm text-gray-600">
                                    <li>
                                        <Link to="/products" className={`block hover:text-primary ${!categorySlug ? 'text-primary font-bold' : ''}`}>
                                            All Categories
                                        </Link>
                                    </li>
                                    {categories.map(cat => (
                                        <li key={cat.id}>
                                            <Link
                                                to={`/products?category=${cat.slug}`}
                                                className={`block hover:text-primary ${categorySlug === cat.slug ? 'text-primary font-bold' : ''}`}
                                            >
                                                {cat.name}
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                            </div>

                            <div className="mb-6">
                                <h3 className="font-semibold mb-3 text-sm">Price Range</h3>
                                <input type="range" min="0" max="10000" className="w-full accent-primary" />
                                <div className="flex justify-between text-xs text-gray-500 mt-2">
                                    <span>৳0</span>
                                    <span>৳10,000+</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Product Grid */}
                    <div className="flex-1">
                        <div className="flex justify-between items-center mb-6">
                            <h1 className="text-2xl font-bold text-gray-800">
                                {categorySlug ? categories.find(c => c.slug === categorySlug)?.name || 'Products' : 'All Products'}
                            </h1>
                            <span className="text-sm text-gray-500">{products.length} Items Found</span>
                        </div>

                        {loading ? (
                            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                                {Array(8).fill(0).map((_, i) => (
                                    <div key={i} className="bg-white rounded-lg h-80 animate-pulse border border-gray-100"></div>
                                ))}
                            </div>
                        ) : products.length > 0 ? (
                            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                                {products.map(product => (
                                    <Link key={product.id} to={`/products/${product.id}`} className="bg-white rounded-xl border border-gray-100 hover:shadow-lg transition group overflow-hidden">
                                        <div className="aspect-square bg-gray-100 relative overflow-hidden">
                                            {product.images && product.images.length > 0 ? (
                                                <img src={product.images[0]} alt={product.name} className="w-full h-full object-cover group-hover:scale-105 transition duration-500" />
                                            ) : (
                                                <div className="w-full h-full flex items-center justify-center text-gray-400">No Image</div>
                                            )}
                                        </div>
                                        <div className="p-4">
                                            <h3 className="text-sm text-gray-700 font-medium line-clamp-2 h-10 mb-2 group-hover:text-primary transition">{product.name}</h3>
                                            <div className="flex items-end justify-between">
                                                <div>
                                                    <div className="text-lg font-bold text-primary">৳{product.sale_price || product.base_price}</div>
                                                    {product.sale_price && <span className="text-xs text-gray-400 line-through">৳{product.base_price}</span>}
                                                </div>
                                            </div>
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        ) : (
                            <div className="bg-white p-12 text-center rounded-lg border border-gray-100">
                                <div className="text-gray-400 text-lg mb-2">No products found</div>
                                <p className="text-gray-500 text-sm">Try adjusting your filters or search criteria.</p>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
};

export default Shop;
