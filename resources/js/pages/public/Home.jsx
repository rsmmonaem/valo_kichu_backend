
import React, { useEffect, useState } from 'react';
import { ChevronRight, Star, TrendingUp, Truck, ShieldCheck, Clock, Layers } from 'lucide-react';
import ProductModal from '../common/ProductModal';
import api from '../../services/api';
import { Link } from 'react-router-dom';
import clsx from 'clsx';
// import ProductCard from '../common/ProductCard';
import { parseGalleryImages } from '../utils/parseGalleryImages';

const Home = () => {
    const [categories, setCategories] = useState([]);
    const [banners, setBanners] = useState([]);
    const [categorySections, setCategorySections] = useState([]);
    const [newArrivals, setNewArrivals] = useState([]);
    const [recommendedProducts, setRecommendedProducts] = useState([]);
    const [loading, setLoading] = useState(true);
    const [selectedProduct, setSelectedProduct] = useState(null);
    const [isModalOpen, setIsModalOpen] = useState(false);

    const handleProductClick = (product) => {
        setSelectedProduct(product);
        setIsModalOpen(true);
    };

    useEffect(() => {
        const fetchHomeData = async () => {
            setLoading(true);
            try {
                // 1. Fetch Basic Data (Categories, Banners)
                // 2. Fetch Category Sections (Categories with Products)
                // 3. Fetch New Arrivals (New Section)
                // 4. Fetch Recommended

                const [catRes, bannerRes, catSectionsRes, newArrivalsRes, recommendedRes] = await Promise.allSettled([
                    api.get('/categories'),
                    api.get('/banners'),
                    api.get('/v1/categories-with-products'),
                    api.get('/v1/items-sections?type=newarrival&limit=12'),
                    api.get('/v1/recommended-products')
                ]);

                // Helper to get data or empty array
                const getData = (res) => (res.status === 'fulfilled' ? (res.value?.data?.data || res.value?.data || []) : []);

                setCategories(getData(catRes));
                setBanners(getData(bannerRes));
                setCategorySections(getData(catSectionsRes));
                setNewArrivals(getData(newArrivalsRes)?.results?.products || []);
                setRecommendedProducts(getData(recommendedRes)?.products || []);

            } catch (error) {
                console.error("Failed to fetch home data:", error);
            } finally {
                setLoading(false);
            }
        };

        fetchHomeData();
    }, []);

    return (
        <div className="bg-gray-50 min-h-screen">
            {/* Hero Slider Area */}
            <div className="bg-white">
                <div className="container mx-auto px-4 py-4 md:py-6">
                    <div className="grid grid-cols-1 md:grid-cols-12 gap-6">
                        {/* Sidebar Categories (Desktop only) */}
                        <div className="hidden md:block col-span-3 bg-white border border-gray-200 rounded-lg shadow-sm h-[350px] lg:h-[400px] overflow-y-auto">
                            <div className="bg-gray-100 p-3 font-bold text-gray-800 border-b border-gray-200 sticky top-0 z-10">
                                Categories
                            </div>
                            <ul>
                                {loading ? (
                                    Array(8).fill(0).map((_, i) => (
                                        <div key={i} className="h-10 w-full bg-gray-100 animate-pulse border-b border-gray-50" />
                                    ))
                                ) : (
                                    categories.slice(0, 12).map(cat => (
                                        <li key={cat.id}>
                                            <Link to={`/ products ? category = ${cat.slug || cat.id} `} className="block px-4 py-2 text-sm text-gray-600 hover:bg-primary/5 hover:text-primary border-b border-gray-50 flex items-center justify-between group">
                                                {cat.name}
                                                <ChevronRight size={14} className="opacity-0 group-hover:opacity-100 transition-opacity" />
                                            </Link>
                                        </li>
                                    ))
                                )}
                            </ul>
                        </div>

                        {/* Main Banner Slider */}
                        <div className="col-span-12 md:col-span-9">
                            <HeroSlider banners={banners} />
                        </div>
                    </div>
                </div>
            </div>

            {/* Category Carousel */}
            <section className="py-8 bg-white mb-4">
                <div className="container mx-auto px-4">
                    <h2 className="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                        <span className="w-1 h-6 bg-primary rounded-full"></span>
                        Shop by Category
                    </h2>
                    <CategoryCarousel categories={categories} />
                </div>
            </section>

            {/* New Arrivals Section (NEW) */}
            <section className="py-8 bg-white mb-4">
                <div className="container mx-auto px-4">
                    <div className="flex items-center justify-between mb-6">
                        <h2 className="text-xl font-bold text-gray-800 flex items-center gap-2">
                            <span className="w-1 h-6 bg-green-500 rounded-full"></span>
                            New Arrivals
                        </h2>
                        <Link to="/products?sort_by=newest" className="text-sm font-semibold text-gray-500 hover:text-primary flex items-center gap-1 transition">
                            View All <ChevronRight size={16} />
                        </Link>
                    </div>

                    <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        {loading ? (
                            Array(6).fill(0).map((_, i) => <ProductSkeleton key={i} />)
                        ) : newArrivals.length > 0 ? (
                            newArrivals.map(product => <ProductCard key={product.id} product={product} onClick={() => handleProductClick(product)} />)
                        ) : (
                            <div className="col-span-full py-8 text-center text-gray-400 bg-gray-50 rounded-lg">
                                No new arrivals yet.
                            </div>
                        )}
                    </div>
                </div>
            </section>

            {/* Dynamic Category Sections */}
            {categorySections.map((section, index) => (
                <CategorySection
                    key={section.category.id || index}
                    title={section.category.name}
                    categorySlug={section.category.slug || section.category.id}
                    products={section.products || []}
                    loading={loading}
                    onProductClick={handleProductClick}
                />
            ))}

            {/* Recommended Section */}
            <section className="py-12 bg-gray-100">
                <div className="container mx-auto px-4">
                    <div className="text-center mb-10">
                        <h2 className="text-2xl font-bold text-gray-800 flex items-center justify-center gap-3">
                            <span className="h-[2px] w-8 bg-primary"></span>
                            Recommended For You
                            <span className="h-[2px] w-8 bg-primary"></span>
                        </h2>
                        <p className="text-gray-500 mt-2">Personalized picks based on trending items</p>
                    </div>

                    <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        {loading
                            ? Array(12).fill(0).map((_, i) => <ProductSkeleton key={i} />)
                            : recommendedProducts.map(product => <ProductCard key={product.id} product={product} onClick={() => handleProductClick(product)} />)
                        }
                    </div>

                    {!loading && recommendedProducts.length > 0 && (
                        <div className="text-center mt-10">
                            <Link to="/products" className="inline-block bg-white border-2 border-primary text-primary px-10 py-3 rounded-full font-bold hover:bg-primary hover:text-white transition shadow-sm">
                                View All Products
                            </Link>
                        </div>
                    )}
                </div>
            </section>

            {/* Product Modal */}
            {isModalOpen && (
                <ProductModal
                    product={selectedProduct}
                    onClose={() => {
                        setIsModalOpen(false);
                        setSelectedProduct(null);
                    }}
                />
            )}
        </div>
    );
};

const CategorySection = ({ title, categorySlug, products, loading, onProductClick }) => (
    <section className="py-8 border-b border-gray-100 bg-white mb-4">
        <div className="container mx-auto px-4">
            <div className="flex items-center justify-between mb-6">
                <h3 className="text-xl font-bold text-gray-800 uppercase flex items-center gap-3">
                    <span className="bg-primary/10 text-primary p-1.5 rounded-lg"><Star size={20} className="fill-primary" /></span>
                    {title}
                </h3>
                <Link to={`/ products ? category = ${categorySlug} `} className="text-sm font-semibold text-gray-500 hover:text-primary flex items-center gap-1 transition">
                    View All <ChevronRight size={16} />
                </Link>
            </div>

            <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 border-t border-gray-50 pt-4">
                {products.length > 0 ? (
                    products.slice(0, 6).map(product => <ProductCard key={product.id} product={product} onClick={() => onProductClick(product)} />)
                ) : (
                    <div className="col-span-full py-8 text-center text-gray-400 bg-gray-50 rounded-lg border border-dashed border-gray-200">
                        No products found in {title}
                    </div>
                )}
            </div>
        </div>
    </section>
);

const ProductCard = ({ product, onClick }) => {
    console.log(product);
    // Handling different image key names (image, thumbnail, or images array)
    // Safe check if images is array

    let displayImage = null;

    // 1. Try simple string properties first
    if (typeof product.image === 'string') {
        displayImage = product.image;
    } else if (typeof product.thumbnail === 'string') {
        displayImage = product.thumbnail;
    }

    // 2. Try images array (ProductResource format)
    if (!displayImage && Array.isArray(product.images) && product.images.length > 0) {
        const firstImg = product.images[0];
        if (typeof firstImg === 'string') {
            displayImage = firstImg;
        } else if (typeof firstImg === 'object' && firstImg?.image) {
            displayImage = firstImg.image;
        }
    }

    // 3. Try parsing gallery_images (legacy/raw format)
    if (!displayImage) {
        const gallery = parseGalleryImages(product.gallery_images);
        if (gallery.length > 0) displayImage = gallery[0];
    }

    // 4. Object fallback (safeguard)
    if (typeof displayImage === 'object' && displayImage?.image) {
        displayImage = displayImage.image;
    }

    const finalImage = (displayImage && typeof displayImage === 'string' && displayImage.startsWith('http'))
        ? displayImage
        : displayImage
            ? `${import.meta.env.VITE_API_BASE_URL || ''} /storage/${displayImage.replace(/^\/?storage\//, '')} `
            : 'https://placehold.co/400x400?text=No+Image';

    // Fix price parsing
    const basePrice = parseFloat(product.base_price || product.price || 0);
    const salePrice = product.sale_price ? parseFloat(product.sale_price) : null;
    const hasDiscount = salePrice && salePrice > 0 && salePrice < basePrice;

    return (
        <div onClick={onClick} className="group bg-white rounded-xl border border-gray-100 hover:border-primary/30 hover:shadow-lg transition duration-300 overflow-hidden flex flex-col h-full relative cursor-pointer">
            <div className="aspect-square bg-gray-100 relative overflow-hidden">
                {displayImage ? (
                    <img src={finalImage} alt={product.name} className="w-full h-full object-cover group-hover:scale-110 transition duration-500" onError={(e) => e.target.src = 'https://placehold.co/400x400?text=No+Image'} />
                ) : (
                    <div className="w-full h-full bg-gray-50 flex items-center justify-center text-gray-300 text-xs font-medium">
                        No Image
                    </div>
                )}

                {hasDiscount && (
                    <div className="absolute top-2 left-2 bg-primary text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-sm">
                        -{Math.round(((basePrice - salePrice) / basePrice) * 100)}%
                    </div>
                )}
            </div>

            <div className="p-3 flex flex-col flex-grow">
                <h4 className="text-sm text-gray-700 font-medium mb-1 line-clamp-2 leading-relaxed group-hover:text-primary transition" title={product.name}>
                    {product.name}
                </h4>
                <div className="mt-auto pt-2">
                    <div className="flex items-baseline gap-2 mb-1">
                        <span className="text-lg font-bold text-primary">৳{hasDiscount ? salePrice : basePrice}</span>
                        {hasDiscount && (
                            <span className="text-xs text-gray-400 line-through">৳{basePrice}</span>
                        )}
                    </div>
                    <div className="flex items-center justify-between text-[10px] text-gray-500">
                        <span>{product.sold_count || '0'} sold</span>
                        {product.rating > 0 && (
                            <div className="flex items-center gap-0.5 text-yellow-500">
                                <Star size={10} className="fill-yellow-500" />
                                <span>{product.rating || '0.0'}</span>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
};

const CategoryCarousel = ({ categories }) => {
    const scrollRef = React.useRef(null);

    useEffect(() => {
        if (categories.length === 0) return;
        const interval = setInterval(() => {
            if (scrollRef.current) {
                const { scrollLeft, clientWidth, scrollWidth } = scrollRef.current;
                if (scrollLeft + clientWidth >= scrollWidth - 10) {
                    scrollRef.current.scrollTo({ left: 0, behavior: 'smooth' });
                } else {
                    scrollRef.current.scrollBy({ left: 200, behavior: 'smooth' });
                }
            }
        }, 4000);
        return () => clearInterval(interval);
    }, [categories]);

    return (
        <div
            ref={scrollRef}
            className="flex overflow-x-auto gap-4 scroll-smooth pb-4 no-scrollbar"
            style={{ scrollbarWidth: 'none', msOverflowStyle: 'none' }}
        >
            {categories.map(cat => (
                <Link
                    key={cat.id}
                    to={`/ products ? category = ${cat.slug || cat.id} `}
                    className="flex-shrink-0 w-32 md:w-40 flex flex-col items-center bg-white border border-gray-100 rounded-xl p-3 hover:shadow-md transition group"
                >
                    <div className="w-20 h-20 md:w-24 md:h-24 rounded-full overflow-hidden bg-gray-50 mb-3 border border-gray-100 group-hover:scale-105 transition-transform">
                        {cat.image || cat.icon ? (
                            <img src={cat.image?.startsWith('http') ? cat.image : `${import.meta.env.VITE_API_BASE_URL || ''} /storage/${cat.image} `} alt={cat.name} className="w-full h-full object-cover" onError={(e) => e.target.style.display = 'none'} />
                        ) : (
                            <div className="w-full h-full flex items-center justify-center text-primary/10 bg-primary/5 text-2xl font-bold text-primary uppercase">
                                {cat.name.charAt(0)}
                            </div>
                        )}
                    </div>
                    <span className="text-center text-xs md:text-sm font-medium text-gray-700 group-hover:text-primary line-clamp-2">{cat.name}</span>
                </Link>
            ))}
        </div>
    );
};

const ProductSkeleton = () => (
    <div className="bg-white rounded-xl border border-gray-100 overflow-hidden animate-pulse">
        <div className="aspect-square bg-gray-200"></div>
        <div className="p-3 space-y-2">
            <div className="h-3 bg-gray-200 rounded w-3/4"></div>
            <div className="h-3 bg-gray-200 rounded w-1/2"></div>
            <div className="flex justify-between pt-2">
                <div className="h-4 bg-gray-200 rounded w-1/3"></div>
                <div className="h-4 bg-gray-200 rounded w-1/4"></div>
            </div>
        </div>
    </div>
);

const HeroSlider = ({ banners }) => {
    const [currentIndex, setCurrentIndex] = useState(0);

    useEffect(() => {
        if (banners.length <= 1) return;
        const interval = setInterval(() => {
            setCurrentIndex(prev => (prev + 1) % banners.length);
        }, 5000);
        return () => clearInterval(interval);
    }, [banners.length]);

    if (banners.length === 0) {
        return (
            <div className="relative h-[250px] md:h-[350px] lg:h-[400px] rounded-2xl overflow-hidden bg-gray-900 group">
                <div className="absolute inset-0 flex flex-col justify-center items-center text-white">
                    <p className="text-lg opacity-50">No Banners Available</p>
                </div>
            </div>
        );
    }

    return (
        <div className="relative h-[250px] md:h-[350px] lg:h-[400px] rounded-2xl overflow-hidden bg-gray-100 group">
            {banners.map((banner, index) => (
                <div
                    key={banner.id || index}
                    className={clsx(
                        "absolute inset-0 transition-opacity duration-1000 ease-in-out",
                        index === currentIndex ? "opacity-100 z-10" : "opacity-0 z-0"
                    )}
                >
                    <img
                        src={banner.image_url || ((banner.image && banner.image.startsWith('http')) ? banner.image : `${import.meta.env.VITE_API_BASE_URL || ''} /storage/${banner.image} `)}
                        alt={banner.title || 'Banner'}
                        className="w-full h-full object-cover"
                        onError={(e) => e.target.style.display = 'none'}
                    />
                    <div className="absolute inset-0 bg-black/20 flex flex-col justify-center px-6 md:px-16 text-white text-left">
                        {banner.title && (
                            <h2 className="text-2xl md:text-5xl font-extrabold max-w-lg leading-tight uppercase drop-shadow-md">
                                {banner.title}
                            </h2>
                        )}
                        {banner.link && (
                            <Link to={banner.link} className="mt-6 bg-primary text-white px-6 py-2 rounded-full font-bold text-sm w-fit hover:bg-primary/90 transition shadow-lg">
                                Shop Now
                            </Link>
                        )}
                    </div>
                </div>
            ))}

            {banners.length > 1 && (
                <div className="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2 z-20">
                    {banners.map((_, index) => (
                        <button
                            key={index}
                            onClick={() => setCurrentIndex(index)}
                            className={clsx(
                                "w-2 h-2 rounded-full transition-all",
                                index === currentIndex ? "bg-primary w-6" : "bg-white/50"
                            )}
                        />
                    ))}
                </div>
            )}
        </div>
    );
};

export default Home;