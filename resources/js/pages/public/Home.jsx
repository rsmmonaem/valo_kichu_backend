import React, { useEffect, useState } from 'react';
import { ArrowRight, Star, ShoppingBag, ChevronRight, Truck, Ship, Clock } from 'lucide-react';
import api from '../../services/api';
import { Link } from 'react-router-dom';
import clsx from 'clsx';

const Home = () => {
    const [categories, setCategories] = useState([]);
    const [banners, setBanners] = useState([]);
    const [sectionProducts, setSectionProducts] = useState({});
    const [recommendedProducts, setRecommendedProducts] = useState([]);
    const [loading, setLoading] = useState(true);

    const targetCategories = ['Shoes', 'Bags', 'Jewelry', 'Watches', 'Sunglasses'];

    useEffect(() => {
        const fetchHomeData = async () => {
            setLoading(true);
            try {
                // Fetch Categories, Banners
                const [catRes, bannerRes] = await Promise.all([
                    api.get('/categories'),
                    api.get('/banners')
                ]);

                const allCats = catRes.data || [];
                setCategories(allCats);
                setBanners(bannerRes.data || []);

                // Fetch Products for specific sections
                const prodRes = await api.get('/products');
                const allProducts = prodRes.data.data || [];

                const newSectionProducts = {};
                targetCategories.forEach(cat => {
                    newSectionProducts[cat] = allProducts.filter(p =>
                        p.category?.name?.includes(cat) || p.name?.includes(cat) || p.description?.includes(cat)
                    ).slice(0, 6);

                    if (newSectionProducts[cat].length === 0) {
                        newSectionProducts[cat] = allProducts.sort(() => 0.5 - Math.random()).slice(0, 6);
                    }
                });

                setSectionProducts(newSectionProducts);
                setRecommendedProducts(allProducts.sort(() => 0.5 - Math.random()).slice(0, 18));

            } catch (error) {
                console.error("Failed to fetch home data", error);
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
                                {categories.slice(0, 12).map(cat => (
                                    <li key={cat.id}>
                                        <Link to={`/products?category=${cat.slug}`} className="block px-4 py-2 text-sm text-gray-600 hover:bg-primary/5 hover:text-primary border-b border-gray-50 flex items-center justify-between group">
                                            {cat.name}
                                            <ChevronRight size={14} className="opacity-0 group-hover:opacity-100 transition-opacity" />
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        </div>

                        {/* Main Banner Slider */}
                        <div className="col-span-12 md:col-span-9">
                            <HeroSlider banners={banners} />
                        </div>
                    </div>
                </div>
            </div>

            {/* Category Carousel (Auto-Slide, 2 Rows) */}
            <section className="py-8 bg-white mb-4">
                <div className="container mx-auto px-4">
                    <h2 className="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                        <span className="w-1 h-6 bg-primary rounded-full"></span>
                        Shop by Category
                    </h2>

                    <CategoryCarousel categories={categories} />
                </div>
            </section>

            {/* Product Sections */}
            {targetCategories.map(cat => (
                <CategorySection
                    key={cat}
                    title={cat}
                    categorySlug={cat.toLowerCase()}
                    products={sectionProducts[cat] || []}
                    loading={loading}
                />
            ))}

            {/* Recommended Section (Infinite Scroll mock) */}
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
                            : recommendedProducts.map(product => <ProductCard key={product.id} product={product} />)
                        }
                    </div>

                    <div className="text-center mt-10">
                        <button className="bg-white border-2 border-primary text-primary px-10 py-3 rounded-full font-bold hover:bg-primary hover:text-white transition shadow-sm">
                            Load More Products
                        </button>
                    </div>
                </div>
            </section>
        </div>
    );
};

const CategorySection = ({ title, categorySlug, products, loading }) => (
    <section className="py-8 border-b border-gray-100 bg-white mb-4">
        <div className="container mx-auto px-4">
            <div className="flex items-center justify-between mb-6">
                <h3 className="text-xl font-bold text-gray-800 uppercase flex items-center gap-3">
                    <span className="bg-primary/10 text-primary p-1.5 rounded-lg"><Star size={20} className="fill-primary" /></span>
                    {title}
                </h3>
                <Link to={`/products?category=${categorySlug}`} className="text-sm font-semibold text-gray-500 hover:text-primary flex items-center gap-1 transition">
                    View All <ChevronRight size={16} />
                </Link>
            </div>

            <div className="relative">
                <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    {loading ? (
                        Array(6).fill(0).map((_, i) => <ProductSkeleton key={i} />)
                    ) : products.length > 0 ? (
                        products.map(product => <ProductCard key={product.id} product={product} />)
                    ) : (
                        <div className="col-span-full py-8 text-center text-gray-400 bg-gray-50 rounded-lg border border-dashed border-gray-200">
                            No products found in {title}
                        </div>
                    )}
                </div>
            </div>
        </div>
    </section>
);

const ProductCard = ({ product }) => (
    <Link to={`/products/${product.id}`} className="group bg-white rounded-xl border border-gray-100 hover:border-primary/30 hover:shadow-lg transition duration-300 overflow-hidden flex flex-col h-full relative">
        <div className="aspect-square bg-gray-100 relative overflow-hidden">
            {product.images && product.images.length > 0 ? (
                <img src={product.images[0]} alt={product.name} className="w-full h-full object-cover group-hover:scale-110 transition duration-500" />
            ) : (
                <div className="w-full h-full bg-gray-50 flex items-center justify-center text-gray-300 text-xs font-medium">
                    No Image
                </div>
            )}

            {/* Discount Badge */}
            {product.sale_price && (
                <div className="absolute top-2 left-2 bg-primary text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-sm">
                    -{Math.round(((product.base_price - product.sale_price) / product.base_price) * 100)}%
                </div>
            )}
        </div>

        <div className="p-3 flex flex-col flex-grow">
            <h4 className="text-sm text-gray-700 font-medium mb-1 line-clamp-2 leading-relaxed group-hover:text-primary transition" title={product.name}>
                {product.name}
            </h4>
            <div className="mt-auto pt-2">
                <div className="flex items-baseline gap-2 mb-1">
                    <span className="text-lg font-bold text-primary">৳{product.sale_price || product.base_price}</span>
                    {product.sale_price && (
                        <span className="text-xs text-gray-400 line-through">৳{product.base_price}</span>
                    )}
                </div>
                <div className="flex items-center justify-between text-[10px] text-gray-500">
                    <span>{Math.floor(Math.random() * 500) + 10} sold</span>
                    <div className="flex items-center gap-0.5 text-yellow-500">
                        <Star size={10} className="fill-yellow-500" />
                        <span>4.8</span>
                    </div>
                </div>
            </div>
        </div>
    </Link>
);

const CategoryCarousel = ({ categories }) => {
    // Determine screen size for slides per view (mocking responsive logic simply for this demo)
    // Mobile: 2 cols, Desktop: 6 cols
    // Since we want "2 columns" as per request, we can force a view, or responsive.
    // "2 columns and auto slide" might mean showing 2 items at a time on mobile.

    // Using a simpler approach: CSS Animation Marquee for smooth continuous slide
    // or a set interval index change. Let's do CSS Scroll Snap with auto-interval.

    const scrollRef = React.useRef(null);

    useEffect(() => {
        const interval = setInterval(() => {
            if (scrollRef.current) {
                const { scrollLeft, clientWidth, scrollWidth } = scrollRef.current;
                // If reached end, wrap to start, else scroll one chunk
                if (scrollLeft + clientWidth >= scrollWidth - 10) { // buffer
                    scrollRef.current.scrollTo({ left: 0, behavior: 'smooth' });
                } else {
                    scrollRef.current.scrollBy({ left: 200, behavior: 'smooth' });
                }
            }
        }, 3000); // Slide every 3 seconds

        return () => clearInterval(interval);
    }, []);

    return (
        <div
            ref={scrollRef}
            className="flex overflow-x-auto gap-4 scroll-smooth pb-4 no-scrollbar"
            style={{ scrollbarWidth: 'none', msOverflowStyle: 'none' }}
        >
            {/* Create 2 rows by grouping pairs if needed, or just a single long row. 
               The user asked for "2 columns", assuming grid-like behavior.
               Let's do a single row of cards that look like standard category cards.
            */}
            {categories.map(cat => (
                <Link
                    key={cat.id}
                    to={`/products?category=${cat.slug}`}
                    className="flex-shrink-0 w-32 md:w-40 flex flex-col items-center bg-white border border-gray-100 rounded-xl p-3 hover:shadow-md transition group"
                >
                    <div className="w-24 h-24 rounded-full overflow-hidden bg-gray-50 mb-3 border border-gray-100 group-hover:scale-105 transition-transform">
                        {cat.image ? (
                            <img src={cat.image} alt={cat.name} className="w-full h-full object-cover" />
                        ) : (
                            <div className="w-full h-full flex items-center justify-center text-primary/10 bg-primary/5 text-2xl font-bold text-primary">
                                {cat.name.charAt(0)}
                            </div>
                        )}
                    </div>
                    <span className="text-center text-sm font-medium text-gray-700 group-hover:text-primary line-clamp-2">{cat.name}</span>
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
        // Fallback Default Banner
        return (
            <div className="relative h-[250px] md:h-[350px] lg:h-[400px] rounded-2xl overflow-hidden bg-gray-900 group">
                <img
                    src="https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?ixlib=rb-1.2.1&auto=format&fit=crop&w=1200&q=80"
                    alt="Default Banner"
                    className="w-full h-full object-cover opacity-60 group-hover:scale-105 transition-transform duration-700"
                />
                <div className="absolute inset-0 flex flex-col justify-center px-6 md:px-16 text-white space-y-4">
                    <span className="inline-block bg-primary text-white text-[10px] md:text-xs font-bold px-3 py-1 rounded-full w-fit uppercase">Direct from 1688</span>
                    <h2 className="text-2xl md:text-5xl font-extrabold leading-tight">
                        Import Business <br /> <span className="text-primary">Made Simple</span>
                    </h2>
                    <div className="flex flex-col sm:flex-row gap-4 text-xs md:text-sm">
                        <div className="flex items-center gap-2">
                            <Ship size={18} className="text-primary" />
                            <span>By Sea: 25-35 Days</span>
                        </div>
                        <div className="flex items-center gap-2">
                            <Truck size={18} className="text-primary" />
                            <span>By Air: 7-14 Days</span>
                        </div>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="relative h-[250px] md:h-[350px] lg:h-[400px] rounded-2xl overflow-hidden bg-gray-100 group">
            {banners.map((banner, index) => (
                <div
                    key={banner.id}
                    className={clsx(
                        "absolute inset-0 transition-opacity duration-1000 ease-in-out",
                        index === currentIndex ? "opacity-100 z-10" : "opacity-0 z-0"
                    )}
                >
                    <img
                        src={banner.image_url}
                        alt={banner.title || 'Banner'}
                        className="w-full h-full object-cover"
                    />
                    {(banner.title || banner.subtitle) && (
                        <div className="absolute inset-0 bg-black/30 flex flex-col justify-center px-6 md:px-16 text-white text-left">
                            {banner.subtitle && (
                                <span className="inline-block bg-primary/90 text-white text-[10px] md:text-xs font-bold px-3 py-1 rounded-full w-fit mb-2 uppercase">
                                    {banner.subtitle}
                                </span>
                            )}
                            <h2 className="text-2xl md:text-5xl font-extrabold max-w-lg leading-tight uppercase">
                                {banner.title}
                            </h2>
                            {banner.link && (
                                <Link to={banner.link} className="mt-6 bg-white text-gray-900 px-6 py-2 rounded-full font-bold text-sm w-fit hover:bg-gray-100 transition shadow-lg">
                                    Shop Now
                                </Link>
                            )}
                        </div>
                    )}
                </div>
            ))}

            {/* Pagination Dots */}
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
