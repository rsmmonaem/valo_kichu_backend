"use client";

import React, { useEffect, useState } from 'react';
import ProductForm from '@/components/admin/ProductForm';
import { ArrowLeft } from 'lucide-react';
import Link from 'next/link';
import { useParams } from 'next/navigation';
import { authFetch } from '@/lib/api';
import toast from 'react-hot-toast';

const EditProductPage = () => {
    const params = useParams();
    const id = params.id;
    const [product, setProduct] = useState<any>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchProduct = async () => {
            try {
                const res = await authFetch(`/admin/v1/products/${id}`);
                if (res.ok) {
                    const data = await res.json();
                    setProduct(data.data || data);
                } else {
                    toast.error("Failed to load product");
                }
            } catch (error) {
                console.error(error);
                toast.error("Error loading product");
            } finally {
                setLoading(false);
            }
        };

        if (id) {
            fetchProduct();
        }
    }, [id]);

    if (loading) {
        return <div className="p-8 text-center text-gray-500">Loading product details...</div>;
    }

    if (!product) {
        return <div className="p-8 text-center text-red-500">Product not found.</div>;
    }

    return (
        <div>
            <div className="mb-6 flex items-center gap-4">
                <Link href="/admin/products" className="p-2 bg-white rounded-full shadow-sm hover:bg-gray-50 transition">
                    <ArrowLeft size={20} className="text-gray-600" />
                </Link>
                <h1 className="text-2xl font-bold text-gray-800">Edit Product: {product.name}</h1>
            </div>

            <ProductForm initialData={product} isEdit={true} />
        </div>
    );
};

export default EditProductPage;
