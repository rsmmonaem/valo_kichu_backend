"use client";

import React, { useState, useEffect } from 'react';
import Link from 'next/link';
import { useSearchParams, useRouter } from 'next/navigation';
import { ShoppingCart, User, Search, Menu, Heart, ChevronDown, LogOut, LayoutDashboard, ShoppingBag } from 'lucide-react';
import { useAuth } from '@/context/AuthContext';
import { Category } from '@/lib/api';
import CategoryDropdown from './CategoryDropdown';
import MobileCategorySidebar from './MobileCategorySidebar';
import AuthMenu from './AuthMenu';

interface HeaderProps {
    categories: Category[];
}

// ... imports
import { useSettings } from '@/context/SettingsContext';

const Header: React.FC<HeaderProps> = ({ categories }) => {
    const { settings } = useSettings();
    const router = useRouter();
    const searchParams = useSearchParams();
    const [search, setSearch] = useState(searchParams.get('search') || '');
    const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
    const [cartCount, setCartCount] = useState(0);

    const handleSearch = () => {
        if (search.trim()) {
            router.push(`/products?search=${encodeURIComponent(search)}`);
        }
    };

    const handleKeyDown = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter') {
            handleSearch();
        }
    };


    return (
        <>
            <header className="sticky top-0 z-50 bg-white shadow-sm border-b border-gray-100">
                <div className="container mx-auto px-4 py-4">
                    <div className="flex items-center justify-between gap-4 md:gap-8">
                        {/* Logo */}
                        <Link href="/" className="flex items-center gap-2 shrink-0 group">
                            {settings.site_logo ? (
                                <img
                                    src={settings.site_logo.startsWith('http') ? settings.site_logo : `${process.env.NEXT_PUBLIC_API_URL || 'https://backend.valokichu.com'}/storage/${settings.site_logo}`}
                                    alt={settings.site_name || "Logo"}
                                    className="h-10 w-auto group-hover:scale-105 transition-transform object-contain"
                                />
                            ) : (
                                <div className="bg-blue-600 text-white p-2 rounded-lg font-bold text-xl group-hover:scale-105 transition-transform duration-200">
                                    {settings.site_name ? settings.site_name.charAt(0) : 'V'}
                                </div>
                            )}
                            <span className="text-2xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-blue-400">
                                {settings.site_name || 'Valokichu'}
                            </span>
                        </Link>

                        {/* Search Bar */}
                        <div className="flex-1 max-w-2xl relative hidden md:block group">
                            <div className="relative overflow-hidden rounded-full border border-gray-200 bg-gray-50 focus-within:bg-white focus-within:border-blue-500 focus-within:ring-4 focus-within:ring-blue-500/10 transition-all duration-300">
                                <input
                                    type="text"
                                    placeholder="Search for products..."
                                    className="w-full pl-6 pr-14 py-3 bg-transparent focus:outline-none text-sm placeholder-gray-400 text-gray-700"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyDown={handleKeyDown}
                                />
                                <button
                                    onClick={handleSearch}
                                    className="absolute right-1 top-1 bottom-1 bg-blue-600 text-white w-10 h-10 rounded-full hover:bg-blue-700 transition-transform hover:scale-105 flex items-center justify-center shadow-md shadow-blue-500/30"
                                >
                                    <Search size={18} />
                                </button>
                            </div>
                        </div>

                        {/* Actions */}
                        <div className="flex items-center gap-6 flex-shrink-0">
                            <Link href="/wishlist" className="relative text-gray-600 hover:text-blue-600 transition-colors flex flex-col items-center gap-0.5 group">
                                <div className="relative">
                                    <Heart size={24} className="group-hover:fill-blue-600/10 transition-colors" />
                                    <span className="absolute -top-1 -right-1 bg-blue-600 text-white text-[10px] w-4 h-4 flex items-center justify-center rounded-full">0</span>
                                </div>
                                <span className="text-[10px] font-medium">Wishlist</span>
                            </Link>

                            <Link href="/cart" className="relative text-gray-600 hover:text-blue-600 transition-colors flex flex-col items-center gap-0.5 group">
                                <div className="relative">
                                    <ShoppingCart size={24} className="group-hover:fill-blue-600/10 transition-colors" />
                                    {cartCount > 0 && (
                                        <span className="absolute -top-1 -right-1 bg-blue-600 text-white text-[10px] w-4 h-4 flex items-center justify-center rounded-full">{cartCount}</span>
                                    )}
                                </div>
                                <span className="text-[10px] font-medium">Cart</span>
                            </Link>

                            <AuthMenu />
                        </div>
                    </div>
                </div>

                {/* Categories Bar (Desktop) */}
                <div className="border-t border-gray-100 bg-white hidden md:block relative">
                    <div className="container mx-auto px-4">
                        <div className="flex items-center gap-6 py-2 text-sm font-medium text-gray-700">
                            {/* All Categories Dropdown Trigger */}
                            <div className="relative group cursor-pointer z-50">
                                <div className="flex items-center gap-2 bg-gray-900 text-white px-4 py-2 rounded-md hover:bg-gray-800 transition-colors">
                                    <Menu size={18} />
                                    <span>All Categories</span>
                                    <ChevronDown size={14} />
                                </div>
                                <CategoryDropdown categories={categories} />
                            </div>

                            {/* Pinned/Top Categories */}
                            <div className="flex items-center gap-4 overflow-x-auto no-scrollbar">
                                {categories.slice(0, 8).map((cat) => (
                                    <Link
                                        key={cat.id}
                                        href={`/products?category=${cat.slug || cat.id}`}
                                        className="hover:text-blue-600 whitespace-nowrap flex items-center gap-1.5 px-2 py-1 hover:bg-blue-50 rounded-md transition-colors"
                                    >
                                        <span>{cat.name}</span>
                                    </Link>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            {/* Mobile Category Sidebar */}
            <MobileCategorySidebar
                isOpen={isMobileMenuOpen}
                onClose={() => setIsMobileMenuOpen(false)}
                categories={categories}
            />
        </>
    );
};

export default Header;
