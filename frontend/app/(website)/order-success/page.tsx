"use client";

import React, { Suspense } from 'react';
import { useSearchParams } from 'next/navigation';
import Link from 'next/link';
import { CheckCircle, Package, Home } from 'lucide-react';

const OrderSuccessContent = () => {
    const searchParams = useSearchParams();
    const orderId = searchParams.get('order');

    return (
        <div className="min-h-[70vh] flex flex-col items-center justify-center px-4 py-12">
            <div className="bg-white p-8 md:p-12 rounded-2xl shadow-xl shadow-blue-500/10 text-center max-w-lg w-full border border-gray-100">
                <div className="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <CheckCircle size={40} className="text-green-600" />
                </div>

                <h1 className="text-3xl font-bold text-gray-900 mb-2">Order Confirmed!</h1>
                <p className="text-gray-500 mb-8">
                    Thank you for your purchase. We have received your order and it will be processed soon.
                </p>

                {orderId && (
                    <div className="bg-gray-50 rounded-lg p-4 mb-8">
                        <p className="text-sm text-gray-500 mb-1">Order Number</p>
                        <p className="text-xl font-mono font-bold text-gray-800">#{orderId}</p>
                    </div>
                )}

                <div className="flex flex-col gap-3">
                    <Link href="/customer/orders" className="flex items-center justify-center gap-2 bg-blue-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-blue-700 transition">
                        <Package size={20} /> View Order Details
                    </Link>
                    <Link href="/" className="flex items-center justify-center gap-2 text-gray-600 px-6 py-3 rounded-lg font-medium hover:bg-gray-50 transition">
                        <Home size={20} /> Return to Home
                    </Link>
                </div>
            </div>
        </div>
    );
};

export default function OrderSuccessPage() {
    return (
        <Suspense fallback={<div>Loading...</div>}>
            <OrderSuccessContent />
        </Suspense>
    );
}
