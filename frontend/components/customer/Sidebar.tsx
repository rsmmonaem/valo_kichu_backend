"use client";

import React from 'react';
import Link from 'next/link';
import { usePathname, useRouter } from 'next/navigation';
import { User, Heart, Package, LogOut, LayoutDashboard } from 'lucide-react';
import { useAuth } from '@/context/AuthContext';
import clsx from 'clsx';

const CustomerSidebar = () => {
    const { logout } = useAuth();
    const router = useRouter();
    const pathname = usePathname();

    const handleLogout = () => {
        logout();
        router.push('/login');
    };

    const navItems = [
        { path: '/customer/dashboard', label: 'My Profile', icon: User },
        { path: '/customer/orders', label: 'My Orders', icon: Package },
        { path: '/customer/wishlist', label: 'Wishlist', icon: Heart },
    ];

    return (
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-4 h-fit">
            <nav className="space-y-1">
                {navItems.map((item) => (
                    <Link
                        key={item.path}
                        href={item.path}
                        className={clsx(
                            "flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-colors",
                            pathname === item.path
                                ? "bg-blue-50 text-blue-600"
                                : "text-gray-600 hover:bg-blue-50/50 hover:text-blue-600"
                        )}
                    >
                        <item.icon size={18} />
                        {item.label}
                    </Link>
                ))}

                <button
                    onClick={handleLogout}
                    className="w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-red-600 hover:bg-red-50 transition-colors mt-4"
                >
                    <LogOut size={18} />
                    Logout
                </button>
            </nav>
        </div>
    );
};

export default CustomerSidebar;
