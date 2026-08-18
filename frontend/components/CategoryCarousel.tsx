"use client";

import React, { useRef, useEffect } from 'react';
import Link from 'next/link';
import { Category } from '@/lib/api';

interface CategoryCarouselProps {
    categories: Category[];
}

const CategoryCarousel: React.FC<CategoryCarouselProps> = ({ categories }) => {
    const scrollRef = useRef<HTMLDivElement>(null);

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
                    href={`/products?category=${cat.slug || cat.id}`}
                    className="flex-shrink-0 w-32 md:w-40 flex flex-col items-center bg-white border border-gray-100 rounded-xl p-3 hover:shadow-md transition group"
                >
                    <div className="w-20 h-20 md:w-24 md:h-24 rounded-full overflow-hidden bg-gray-50 mb-3 border border-gray-100 group-hover:scale-105 transition-transform">
                        {cat.image || cat.icon ? (
                            <img src={cat.image?.startsWith('http') ? cat.image : `http://127.0.0.1:8000/storage/${cat.image}`} alt={cat.name} className="w-full h-full object-cover" onError={(e) => (e.currentTarget.style.display = 'none')} />
                        ) : (
                            <div className="w-full h-full flex items-center justify-center text-blue-600/10 bg-blue-600/5 text-2xl font-bold text-blue-600 uppercase">
                                {cat.name.charAt(0)}
                            </div>
                        )}
                    </div>
                    <span className="text-center text-xs md:text-sm font-medium text-gray-700 group-hover:text-blue-600 line-clamp-2">{cat.name}</span>
                </Link>
            ))}
        </div>
    );
};

export default CategoryCarousel;
