import React, { useEffect, useState } from "react";
import api from "../../services/api";
import Sidebar from "../../components/customer/Sidebar";
import { Heart, Trash2, ShoppingCart } from "lucide-react";
import toast from "react-hot-toast";
import { useCart } from "../../context/CartContext";

const Wishlist = () => {
    const [wishlist, setWishlist] = useState([]);
    const [loading, setLoading] = useState(true);
    const { addToCart } = useCart();

    useEffect(() => {
        fetchWishlist();
    }, []);

    const fetchWishlist = async () => {
        try {
            const res = await api.get("/v1/favourites");
            setWishlist(res.data.data || res.data);
        } catch (error) {
            console.error("Fetch wishlist error:", error);
            // toast.error("Failed to load wishlist");
        } finally {
            setLoading(false);
        }
    };

    const removeFromWishlist = async (id) => {
        try {
            await api.delete(`/v1/favourites/remove/${id}`);
            setWishlist(prev => prev.filter(item => item.id !== id));
            toast.success("Removed from wishlist");
        } catch (error) {
            toast.error("Failed to remove item");
        }
    };

    const handleAddToCart = (item) => {
        // Adapt item structure if needed for cart
        addToCart({
            id: item.product_id || item.id,
            name: item.product?.name || item.name,
            base_price: item.product?.sale_price || item.product?.base_price,
            images: item.product?.images || [],
            quantity: 1
        });
        toast.success("Added to cart");
    };

    return (
        <div className="max-w-7xl mx-auto px-4 py-8">
            <div className="flex flex-col md:flex-row gap-8">
                {/* Sidebar */}
                <div className="w-full md:w-64 shrink-0">
                    <Sidebar />
                </div>

                {/* Main Content */}
                <div className="flex-1">
                    <div className="flex items-center justify-between mb-6">
                        <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
                            <Heart className="text-red-600" /> My Wishlist
                        </h1>
                        <span className="text-gray-500 text-sm">{wishlist.length} Items</span>
                    </div>

                    {loading ? (
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            {[1, 2, 3].map((n) => (
                                <div key={n} className="h-64 bg-gray-50 rounded-xl animate-pulse"></div>
                            ))}
                        </div>
                    ) : wishlist.length === 0 ? (
                        <div className="text-center py-12 bg-gray-50 rounded-xl border border-gray-100">
                            <Heart className="mx-auto text-gray-300 mb-4" size={48} />
                            <h3 className="text-lg font-medium text-gray-900">Your wishlist is empty</h3>
                            <p className="text-gray-500 mt-1">Save items you love to buy later.</p>
                            <a href="/" className="inline-block mt-4 px-6 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition">
                                Start Shopping
                            </a>
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            {wishlist.map((item) => (
                                <div key={item.id} className="bg-white border border-gray-100 rounded-xl overflow-hidden hover:shadow-lg transition-shadow group relative">
                                    <div className="aspect-square bg-gray-100 relative overflow-hidden">
                                        {item.product?.images?.[0] ? (
                                            <img
                                                src={item.product?.images?.[0]}
                                                alt={item.product?.name}
                                                className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                            />
                                        ) : (
                                            <div className="w-full h-full flex items-center justify-center text-gray-400">No Image</div>
                                        )}

                                        <button
                                            onClick={() => removeFromWishlist(item.id)}
                                            className="absolute top-2 right-2 p-2 bg-white/80 backdrop-blur rounded-full text-red-500 hover:bg-red-50 transition-colors shadow-sm opacity-0 group-hover:opacity-100"
                                        >
                                            <Trash2 size={16} />
                                        </button>
                                    </div>

                                    <div className="p-4">
                                        <h3 className="font-medium text-gray-900 line-clamp-1 mb-1">{item.product?.name}</h3>
                                        <div className="flex items-center justify-between mt-3">
                                            <span className="text-lg font-bold text-red-600">
                                                ৳{item.product?.sale_price || item.product?.base_price}
                                            </span>
                                            <button
                                                onClick={() => handleAddToCart(item)}
                                                className="p-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition-colors"
                                            >
                                                <ShoppingCart size={18} />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
};

export default Wishlist;
