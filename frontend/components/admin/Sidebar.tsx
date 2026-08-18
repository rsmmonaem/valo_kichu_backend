"use client";

import React from 'react';
import Link from 'next/link';
import { usePathname, useRouter } from 'next/navigation';
import {
    LayoutDashboard,
    Package,
    ShoppingCart,
    Users,
    Settings,
    LogOut,
    Menu,
    X,
    FolderTree,
    Folder,
    FolderOpen,
    Tags,
    Image as ImageIcon
} from 'lucide-react';
import { useAuth } from '@/context/AuthContext';
import clsx from 'clsx';

interface AdminSidebarProps {
    isOpen: boolean;
    setIsOpen: (open: boolean) => void;
}

const AdminSidebar: React.FC<AdminSidebarProps> = ({ isOpen, setIsOpen }) => {
    const { logout } = useAuth();
    const router = useRouter();
    const pathname = usePathname();

    const handleLogout = () => {
        logout();
        router.push('/login');
    };

    const navItems = [
        { path: '/admin/dashboard', label: 'Dashboard', icon: LayoutDashboard },
        { path: '/admin/products', label: 'Products', icon: Package },
        { path: '/admin/orders', label: 'Orders', icon: ShoppingCart },
        { path: '/admin/categories', label: 'Categories', icon: FolderTree },
        { path: '/admin/sub-categories', label: 'Sub Categories', icon: Folder },
        { path: '/admin/sub-sub-categories', label: 'Sub Sub Categories', icon: FolderOpen },
        { path: '/admin/brands', label: 'Brands', icon: Tags },
        { path: '/admin/banners', label: 'Banners', icon: ImageIcon },
        { path: '/admin/customers', label: 'Customers', icon: Users },
        { path: '/admin/settings', label: 'Settings', icon: Settings },
    ];

    return (
        <>
            {/* Overlay for mobile */}
            {isOpen && (
                <div
                    className="fixed inset-0 bg-black/50 z-40 md:hidden"
                    onClick={() => setIsOpen(false)}
                />
            )}

            {/* Sidebar */}
            <div className={clsx(
                "fixed top-0 left-0 h-full bg-gradient-to-b from-slate-900 to-slate-950 text-white w-64 z-50 transition-transform duration-300 ease-in-out md:translate-x-0 md:static md:h-screen flex flex-col shadow-xl",
                isOpen ? "translate-x-0" : "-translate-x-full"
            )}>
                {/* Logo Area */}
                <div className="p-6 border-b border-slate-800/50 flex items-center justify-between bg-slate-900/50 backdrop-blur-sm">
                    <Link href="/admin/dashboard" className="text-xl font-bold flex items-center gap-3">
                        <div className="bg-gradient-to-tr from-blue-600 to-blue-500 p-2 rounded-lg shadow-lg shadow-blue-500/30">
                            <span className="text-white font-bold text-lg leading-none">V</span>
                        </div>
                        <span className="tracking-tight text-slate-100">Valokichu<span className="text-blue-500">.</span></span>
                    </Link>
                    <button onClick={() => setIsOpen(false)} className="md:hidden text-gray-400 hover:text-white transition-colors">
                        <X size={20} />
                    </button>
                </div>

                {/* Nav Items */}
                <div className="flex-1 overflow-y-auto py-6 px-3 custom-scrollbar">
                    <nav className="space-y-1">
                        {navItems.map((item) => {
                            const isActive = pathname.startsWith(item.path);
                            return (
                                <Link
                                    key={item.path}
                                    href={item.path}
                                    onClick={() => setIsOpen(false)}
                                    className={clsx(
                                        "flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium transition-all duration-200 group relative overflow-hidden",
                                        isActive
                                            ? "bg-blue-600 text-white shadow-md shadow-blue-500/20"
                                            : "text-slate-400 hover:bg-slate-800/50 hover:text-white"
                                    )}
                                >
                                    <item.icon size={18} className={clsx("transition-transform group-hover:scale-110", isActive ? "text-white" : "text-slate-500 group-hover:text-white")} />
                                    <span className="relative z-10">{item.label}</span>
                                    {isActive && <div className="absolute inset-0 bg-gradient-to-r from-white/10 to-transparent pointer-events-none" />}
                                </Link>
                            );
                        })}
                    </nav>
                </div>

                {/* Footer / Logout */}
                <div className="p-4 border-t border-slate-800/50 bg-slate-900/30">
                    <button
                        onClick={handleLogout}
                        className="w-full flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium text-slate-400 hover:bg-red-500/10 hover:text-red-400 transition-all group border border-transparent hover:border-red-500/20"
                    >
                        <LogOut size={18} className="group-hover:-translate-x-1 transition-transform" />
                        Logout
                    </button>
                </div>
            </div>
        </>
    );
};

export default AdminSidebar;
