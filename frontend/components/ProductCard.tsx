"use client";

import React from 'react';
import Link from 'next/link';
import { Star } from 'lucide-react';
import { Product } from '@/lib/api';

interface ProductCardProps {
    product: Product;
}

const ProductCard: React.FC<ProductCardProps> = ({ product }) => {

    // Image handling logic ported from React app
    let displayImage: string | null = null;

    if (typeof product.image === 'string') {
        displayImage = product.image;
    } else if (typeof product.thumbnail === 'string') {
        displayImage = product.thumbnail;
    }

    if (!displayImage && Array.isArray(product.images) && product.images.length > 0) {
        const firstImg = product.images[0];
        if (typeof firstImg === 'string') {
            displayImage = firstImg;
        } else if (typeof firstImg === 'object' && firstImg?.image) {
            displayImage = firstImg.image;
        }
    }

    // Fallback for gallery_images parsing if needed, but simplistic for now

    const finalImage = (displayImage && displayImage.startsWith('http'))
        ? displayImage
        : displayImage
            ? `http://127.0.0.1:8000/storage/${displayImage.replace(/^\/?storage\//, '')}`
            : 'https://placehold.co/400x400?text=No+Image';

    const basePrice = parseFloat(product.base_price || product.price || '0');
    const salePrice = product.sale_price ? parseFloat(product.sale_price) : null;
    const hasDiscount = salePrice && salePrice > 0 && salePrice < basePrice;

    return (
        <Link href={`/products/${product.slug}`} className="group bg-white rounded-xl border border-gray-100 hover:border-blue-600/30 hover:shadow-lg transition duration-300 overflow-hidden flex flex-col h-full relative cursor-pointer block">
            <div className="aspect-square bg-gray-100 relative overflow-hidden">
                <img
                    src={finalImage}
                    alt={product.name}
                    className="w-full h-full object-cover group-hover:scale-110 transition duration-500"
                    onError={(e) => (e.currentTarget.src = 'https://placehold.co/400x400?text=No+Image')}
                />

                {hasDiscount && salePrice && (
                    <div className="absolute top-2 left-2 bg-blue-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-sm">
                        -{Math.round(((basePrice - salePrice) / basePrice) * 100)}%
                    </div>
                )}
            </div>

            <div className="p-3 flex flex-col flex-grow">
                <h4 className="text-sm text-gray-700 font-medium mb-1 line-clamp-2 leading-relaxed group-hover:text-blue-600 transition" title={product.name}>
                    {product.name}
                </h4>
                <div className="mt-auto pt-2">
                    <div className="flex items-baseline gap-2 mb-1">
                        <span className="text-lg font-bold text-blue-600">৳{hasDiscount ? salePrice : basePrice}</span>
                        {hasDiscount && (
                            <span className="text-xs text-gray-400 line-through">৳{basePrice}</span>
                        )}
                    </div>
                    {/* <div className="flex items-center justify-between text-[10px] text-gray-500">
                        <span>{product.sold_count || '0'} sold</span>
                        {product.rating > 0 && (
                            <div className="flex items-center gap-0.5 text-yellow-500">
                                <Star size={10} className="fill-yellow-500" />
                                <span>{product.rating || '0.0'}</span>
                            </div>
                        )}
                    </div> */}
                </div>
            </div>
        </Link>
    );
};

export default ProductCard;
