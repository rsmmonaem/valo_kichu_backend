"use client";

import Link from 'next/link';
import { ChevronRight } from 'lucide-react';
import { Category } from '@/lib/api';

interface CategorySidebarProps {
    categories: Category[];
}

const CategorySidebar: React.FC<CategorySidebarProps> = ({ categories }) => {
    return (
        <div className="hidden md:block col-span-3 bg-white border border-gray-200 rounded-lg shadow-sm h-[350px] lg:h-[400px] overflow-y-auto">
            <div className="bg-gray-100 p-3 font-bold text-gray-800 border-b border-gray-200 sticky top-0 z-10">
                Categories
            </div>
            <ul>
                {categories.slice(0, 12).map(cat => (
                    <li key={cat.id}>
                        <Link href={`/products?category=${cat.slug || cat.id}`} className="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 border-b border-gray-50 flex items-center justify-between group">
                            {cat.name}
                            <ChevronRight size={14} className="opacity-0 group-hover:opacity-100 transition-opacity" />
                        </Link>
                    </li>
                ))}
            </ul>
        </div>
    );
};

export default CategorySidebar;
