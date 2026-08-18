import React from 'react';
import Link from 'next/link';
import { ChevronRight } from 'lucide-react';
import {
  getCategoryList,
  getBanners,
  getCategorySections,
  getNewArrivals,
  getRecommendedProducts
} from '@/lib/api';
import CategorySidebar from '@/components/CategorySidebar';
import HeroSlider from '@/components/HeroSlider';
import CategoryCarousel from '@/components/CategoryCarousel';
import ProductCard from '@/components/ProductCard';
import CategorySection from '@/components/CategorySection';

export default async function Home() {
  const [
    categoriesRes,
    banners,
    categorySections,
    newArrivals,
    recommendedProducts
  ] = await Promise.all([
    getCategoryList(),
    getBanners(),
    getCategorySections(),
    getNewArrivals(),
    getRecommendedProducts()
  ]);

  const categories = categoriesRes.data || [];

  return (
    <div className="bg-gray-50 min-h-screen">
      {/* Hero Slider Area */}
      <div className="bg-white">
        <div className="container mx-auto px-4 py-4 md:py-6">
          <div className="grid grid-cols-1 md:grid-cols-12 gap-6">
            {/* Sidebar Categories (Desktop only) */}
            <CategorySidebar categories={categories} />

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
            <span className="w-1 h-6 bg-blue-600 rounded-full"></span>
            Shop by Category
          </h2>
          <CategoryCarousel categories={categories} />
        </div>
      </section>

      {/* New Arrivals Section */}
      <section className="py-8 bg-white mb-4">
        <div className="container mx-auto px-4">
          <div className="flex items-center justify-between mb-6">
            <h2 className="text-xl font-bold text-gray-800 flex items-center gap-2">
              <span className="w-1 h-6 bg-green-500 rounded-full"></span>
              New Arrivals
            </h2>
            <Link href="/products?sort_by=newest" className="text-sm font-semibold text-gray-500 hover:text-blue-600 flex items-center gap-1 transition">
              View All <ChevronRight size={16} />
            </Link>
          </div>

          <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            {newArrivals.length > 0 ? (
              newArrivals.map(product => <ProductCard key={product.id} product={product} />)
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
          categorySlug={section.category.slug || section.category.id.toString()}
          products={section.products || []}
        />
      ))}

      {/* Recommended Section */}
      <section className="py-12 bg-gray-100">
        <div className="container mx-auto px-4">
          <div className="text-center mb-10">
            <h2 className="text-2xl font-bold text-gray-800 flex items-center justify-center gap-3">
              <span className="h-[2px] w-8 bg-blue-600"></span>
              Recommended For You
              <span className="h-[2px] w-8 bg-blue-600"></span>
            </h2>
            <p className="text-gray-500 mt-2">Personalized picks based on trending items</p>
          </div>

          <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            {recommendedProducts.map(product => <ProductCard key={product.id} product={product} />)}
          </div>

          {recommendedProducts.length > 0 && (
            <div className="text-center mt-10">
              <Link href="/products" className="inline-block bg-white border-2 border-blue-600 text-blue-600 px-10 py-3 rounded-full font-bold hover:bg-blue-600 hover:text-white transition shadow-sm">
                View All Products
              </Link>
            </div>
          )}
        </div>
      </section>
    </div>
  );
}
