"use client";

import React, { useState } from 'react';
import Link from 'next/link';
import { User, LogOut, LayoutDashboard, ShoppingBag } from 'lucide-react';
import { useAuth } from '@/context/AuthContext';

const AuthMenu = () => {
    const { user, logout } = useAuth();
    const [isOpen, setIsOpen] = useState(false);

    if (!user) {
        return (
            <Link href="/login" className="flex flex-col items-center gap-0.5 text-gray-600 hover:text-blue-600 transition-colors">
                <User size={24} />
                <span className="text-[10px] font-medium">Login</span>
            </Link>
        );
    }

    return (
        <div className="relative group z-50">
            <button
                className="flex flex-col items-center gap-0.5 text-gray-600 hover:text-blue-600 transition-colors outline-none"
                onClick={() => setIsOpen(!isOpen)}
                onBlur={() => setTimeout(() => setIsOpen(false), 200)}
            >
                <div className="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-xs uppercase">
                    {user.name ? user.name.charAt(0) : 'U'}
                </div>
                <span className="text-[10px] font-medium max-w-[60px] truncate">{user.name}</span>
            </button>

            {/* Dropdown */}
            <div className={`absolute right-0 top-full mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-100 py-2 transition-all duration-200 transform origin-top-right ${isOpen ? 'opacity-100 scale-100 visible' : 'opacity-0 scale-95 invisible group-hover:opacity-100 group-hover:scale-100 group-hover:visible'}`}>
                <div className="px-4 py-3 border-b border-gray-100 bg-gray-50/50">
                    <p className="text-sm font-bold text-gray-900 truncate">{user.name}</p>
                    <p className="text-xs text-gray-500 truncate">{user.email}</p>
                </div>

                <div className="py-1">
                    {(user.role === 'admin' || user.role === 'super_admin') && (
                        <Link href="/admin/dashboard" className="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600">
                            <LayoutDashboard size={16} /> Admin Panel
                        </Link>
                    )}
                    <Link href="/customer/dashboard" className="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600">
                        <LayoutDashboard size={16} /> Dashboard
                    </Link>
                    <Link href="/customer/orders" className="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600">
                        <ShoppingBag size={16} /> My Orders
                    </Link>
                </div>

                <div className="border-t border-gray-100 mt-1 py-1">
                    <button
                        onClick={logout}
                        className="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50 text-left"
                    >
                        <LogOut size={16} /> Logout
                    </button>
                </div>
            </div>
        </div>
    );
};

export default AuthMenu;
