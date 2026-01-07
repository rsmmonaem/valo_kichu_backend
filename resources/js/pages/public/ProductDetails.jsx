import React, { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import api from '../../services/api';
import { useCart } from '../../context/CartContext';
import { Star, Truck, ShieldCheck, RefreshCw, Minus, Plus, ShoppingCart } from 'lucide-react';
import toast from 'react-hot-toast';

const ProductDetails = () => {
    const { id } = useParams();
    const navigate = useNavigate();
    const { addToCart } = useCart();
    const [product, setProduct] = useState(null);
    const [loading, setLoading] = useState(true);
    const [selectedImage, setSelectedImage] = useState(0);
    const [quantity, setQuantity] = useState(1);

    useEffect(() => {
        fetchProduct();
    }, [id]);

    const fetchProduct = async () => {
        try {
            const { data } = await api.get(`/products/${id}`);
            setProduct(data);
        } catch (error) {
            console.error("Failed to fetch product", error);
            toast.error("Failed to load product");
        } finally {
            setLoading(false);
        }
    };

    const handleAddToCart = () => {
        addToCart(product, quantity);
    };

    if (loading) return <div className="min-h-screen flex items-center justify-center bg-gray-50">Loading...</div>;
    if (!product) return <div className="min-h-screen flex items-center justify-center bg-gray-50">Product not found</div>;

    const images = product.images || [];

    return (
        <div className="bg-gray-50 min-h-screen py-8">
            <div className="container mx-auto px-4">
                <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-0 md:gap-8">
                        {/* Image Gallery */}
                        <div className="p-6 md:p-8 bg-white">
                            <div className="aspect-square rounded-xl overflow-hidden bg-gray-50 mb-4 border border-gray-100 relative">
                                {images.length > 0 ? (
                                    <img src={images[selectedImage]} alt={product.name} className="w-full h-full object-cover" />
                                ) : (
                                    <div className="w-full h-full flex items-center justify-center text-gray-400">No Image</div>
                                )}
                            </div>
                            <div className="flex gap-4 overflow-x-auto pb-2">
                                {images.map((img, idx) => (
                                    <button
                                        key={idx}
                                        onClick={() => setSelectedImage(idx)}
                                        className={`w-20 h-20 flex-shrink-0 rounded-lg overflow-hidden border-2 ${selectedImage === idx ? 'border-primary' : 'border-transparent'} hover:border-primary/50 transition`}
                                    >
                                        <img src={img} alt="" className="w-full h-full object-cover" />
                                    </button>
                                ))}
                            </div>
                        </div>

                        {/* Product Info */}
                        <div className="p-6 md:p-8 bg-gray-50 md:bg-white flex flex-col">
                            <div className="mb-2">
                                <span className="text-primary font-bold text-xs uppercase tracking-wider bg-primary/10 px-2 py-1 rounded">
                                    {product.category?.name || 'Store Item'}
                                </span>
                            </div>
                            <h1 className="text-2xl md:text-3xl font-bold text-gray-900 mb-4 leading-tight">{product.name}</h1>

                            <div className="flex items-center gap-4 mb-6">
                                <div className="flex items-center text-yellow-500 gap-1">
                                    <Star size={18} fill="currentColor" />
                                    <span className="font-bold text-gray-900">4.8</span>
                                    <span className="text-gray-400 text-sm">(120 Reviews)</span>
                                </div>
                                <span className="text-gray-300">|</span>
                                <span className="text-green-600 font-medium text-sm">In Stock: {product.stock_quantity}</span>
                            </div>

                            <div className="flex items-baseline gap-4 mb-8">
                                <span className="text-4xl font-bold text-primary">৳{product.sale_price || product.base_price}</span>
                                {product.sale_price && (
                                    <span className="text-xl text-gray-400 line-through">৳{product.base_price}</span>
                                )}
                            </div>

                            <div className="mb-8 border-t border-b border-gray-100 py-6 space-y-4">
                                <p className="text-gray-600 leading-relaxed">{product.description}</p>

                                <div className="flex flex-col gap-2">
                                    <span className="font-bold text-gray-800 text-sm">Quantity:</span>
                                    <div className="flex items-center border border-gray-300 rounded-lg w-fit bg-white">
                                        <button
                                            onClick={() => setQuantity(Math.max(1, quantity - 1))}
                                            className="p-3 text-gray-600 hover:text-primary transition"
                                        >
                                            <Minus size={18} />
                                        </button>
                                        <span className="w-12 text-center font-bold text-gray-800">{quantity}</span>
                                        <button
                                            onClick={() => setQuantity(quantity + 1)}
                                            className="p-3 text-gray-600 hover:text-primary transition"
                                        >
                                            <Plus size={18} />
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div className="flex gap-4 mt-auto">
                                <button
                                    onClick={handleAddToCart}
                                    className="flex-1 bg-white border-2 border-primary text-primary py-3.5 rounded-xl font-bold hover:bg-primary/5 transition flex items-center justify-center gap-2"
                                >
                                    <ShoppingCart size={20} /> Add to Cart
                                </button>
                                <button
                                    onClick={() => {
                                        addToCart(product, quantity);
                                        navigate('/checkout');
                                    }}
                                    className="flex-1 bg-primary text-white py-3.5 rounded-xl font-bold hover:bg-primary/90 transition shadow-lg shadow-primary/30"
                                >
                                    Buy Now
                                </button>
                            </div>

                            {/* Trust Signals */}
                            <div className="grid grid-cols-3 gap-4 mt-8 pt-6 border-t border-gray-100">
                                <div className="text-center">
                                    <div className="w-10 h-10 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-2"><Truck size={20} /></div>
                                    <span className="text-[10px] text-gray-500 font-medium uppercase">Fast Delivery</span>
                                </div>
                                <div className="text-center">
                                    <div className="w-10 h-10 bg-green-50 text-green-600 rounded-full flex items-center justify-center mx-auto mb-2"><ShieldCheck size={20} /></div>
                                    <span className="text-[10px] text-gray-500 font-medium uppercase">Authentic</span>
                                </div>
                                <div className="text-center">
                                    <div className="w-10 h-10 bg-purple-50 text-purple-600 rounded-full flex items-center justify-center mx-auto mb-2"><RefreshCw size={20} /></div>
                                    <span className="text-[10px] text-gray-500 font-medium uppercase">Easy Returns</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default ProductDetails;
