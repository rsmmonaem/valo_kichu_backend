"use client";

import React, { useState, useEffect } from 'react';
import { useAuth } from '@/context/AuthContext';
import { useRouter } from 'next/navigation';
import AdminSidebar from '@/components/admin/Sidebar';
import { Menu } from 'lucide-react';

export default function AdminLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    const { user, loading } = useAuth();
    const router = useRouter();
    const [isSidebarOpen, setIsSidebarOpen] = useState(false);

    useEffect(() => {
        if (!loading) {
            if (!user) {
                router.push('/login');
            } else if (user.role !== 'admin' && user.role !== 'super_admin') {
                router.push('/'); // Redirect non-admins
                // alert("Access Denied");
            }
        }
    }, [loading, user, router]);

    if (loading) {
        return <div className="min-h-screen flex items-center justify-center bg-gray-50 text-gray-500">Loading Admin Panel...</div>;
    }

    if (!user || (user.role !== 'admin' && user.role !== 'super_admin')) {
        return null;
    }

    return (
        <div className="flex min-h-screen bg-gray-100">
            {/* Sidebar */}
            <AdminSidebar isOpen={isSidebarOpen} setIsOpen={setIsSidebarOpen} />

            {/* Main Content */}
            <div className="flex-1 flex flex-col min-w-0 overflow-hidden">
                {/* Mobile Header */}
                <header className="md:hidden bg-white border-b border-gray-200 p-4 flex items-center">
                    <button
                        onClick={() => setIsSidebarOpen(true)}
                        className="text-gray-600 hover:text-gray-900"
                    >
                        <Menu size={24} />
                    </button>
                    <span className="ml-4 font-bold text-gray-800">Admin Panel</span>
                </header>

                <main className="flex-1 overflow-y-auto p-4 md:p-8">
                    {children}
                </main>
            </div>
        </div>
    );
}
