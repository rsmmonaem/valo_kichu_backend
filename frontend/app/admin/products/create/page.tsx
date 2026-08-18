"use client";

import React from 'react';
import ProductForm from '@/components/admin/ProductForm';
import { ArrowLeft } from 'lucide-react';
import Link from 'next/link';

const CreateProductPage = () => {
    return (
        <div>
            <div className="mb-6 flex items-center gap-4">
                <Link href="/admin/products" className="p-2 bg-white rounded-full shadow-sm hover:bg-gray-50 transition">
                    <ArrowLeft size={20} className="text-gray-600" />
                </Link>
                <h1 className="text-2xl font-bold text-gray-800">Add New Product</h1>
            </div>

            <ProductForm />
        </div>
    );
};

export default CreateProductPage;
