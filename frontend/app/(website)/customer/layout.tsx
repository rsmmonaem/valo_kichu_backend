"use client";

import React from 'react';
import CustomerSidebar from '@/components/customer/Sidebar';
import { useAuth } from '@/context/AuthContext';
import { useRouter } from 'next/navigation';

export default function CustomerLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    const { user, loading } = useAuth();
    const router = useRouter();

    // Protect the route
    React.useEffect(() => {
        if (!loading && !user) {
            router.push('/login');
        }
    }, [loading, user, router]);

    if (loading) {
        return <div className="min-h-screen flex items-center justify-center">Loading...</div>;
    }

    if (!user) {
        return null; // Will redirect
    }

    return (
        <div className="max-w-7xl mx-auto px-4 py-8">
            <div className="flex flex-col md:flex-row gap-8">
                <div className="w-full md:w-64 shrink-0">
                    <CustomerSidebar />
                </div>
                <div className="flex-1">
                    {children}
                </div>
            </div>
        </div>
    );
}
