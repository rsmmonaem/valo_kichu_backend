import React from "react";

function ProductCard({product}) {
    return (
        <div>
            <div className="aspect-square bg-gray-100 relative overflow-hidden">
                {product.images && product.images.length > 0 ? (
                    <img
                        src={product.images[0]}
                        alt={product.name}
                        className="w-full h-full object-cover group-hover:scale-105 transition duration-500"
                    />
                ) : (
                    <div className="w-full h-full flex items-center justify-center text-gray-400">
                        No Image
                    </div>
                )}
            </div>
            <div className="p-4">
                <h3 className="text-sm text-gray-700 font-medium line-clamp-2 h-10 mb-2 group-hover:text-primary transition">
                    {product.name}
                </h3>
                <div className="flex items-end justify-between">
                    <div>
                        <div className="text-lg font-bold text-primary">
                            ৳{product.sale_price || product.base_price}
                        </div>
                        {product.sale_price && (
                            <span className="text-xs text-gray-400 line-through">
                                ৳{product.base_price}
                            </span>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}

export default ProductCard;
