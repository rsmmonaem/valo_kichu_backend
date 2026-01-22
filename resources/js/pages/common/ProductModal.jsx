import { useState, useEffect, useRef } from "react";
import { useNavigate } from "react-router-dom";
import {
    X,
    Minus,
    Plus,
    ShoppingCart,
    Star,
} from "lucide-react";
import { useCart } from "../../context/CartContext";
import points from "../../../../public/coin.png";

export default function ProductModal({ product, onClose }) {
    console.log(product);
    const [quantity, setQuantity] = useState(1);
    const { addToCart } = useCart();
    const navigate = useNavigate();
    const [isMobile, setIsMobile] = useState(false);
    const modalRef = useRef(null);

    useEffect(() => {
        const checkMobile = () => {
            setIsMobile(window.innerWidth < 768);
        };
        
        checkMobile();
        window.addEventListener('resize', checkMobile);
        
        // Prevent body scrolling when modal is open
        document.body.style.overflow = 'hidden';
        
        return () => {
            window.removeEventListener('resize', checkMobile);
            // Re-enable body scrolling when modal closes
            document.body.style.overflow = 'auto';
        };
    }, []);

    // Close modal on Escape key press
    useEffect(() => {
        const handleEscape = (e) => {
            if (e.key === 'Escape') {
                onClose();
            }
        };
        
        document.addEventListener('keydown', handleEscape);
        return () => document.removeEventListener('keydown', handleEscape);
    }, [onClose]);

    if (!product) return null;
    
    const [galleryId, setGalleryId] = useState(1);
    const [preview, setPreview] = useState("");
    
    const galleryImages = product?.gallery_images?.map((image, index) => ({
        id: index + 1,
        img: image,
    })) ?? [];

    /* ---------------- DATA ---------------- */
    const colorData = product?.colors?.map((data, index) => ({
        id: index + 1,
        name: data.name,
        img: data.image,
    })) ?? [];

    const weightData = ["250g", "500g", "1kg"];
    const sizeData = ["S", "M", "L", "XL"];

    const [color, setColor] = useState(colorData[0] || {});
    const [weight, setWeight] = useState(weightData[0]);
    const [size, setSize] = useState(sizeData[0]);

    /* ---------------- HANDLERS ---------------- */
    const handleAddToCart = () => {
        addToCart(product, quantity);
        onClose();
    };

    const handleBuyNow = () => {
        addToCart(product, quantity);
        navigate("/checkout");
        onClose();
    };

    return (
        <>
            {/* Overlay */}
            <div
                className="fixed inset-0 bg-black/70 backdrop-blur-sm z-40"
                onClick={onClose}
            />

            {/* Modal */}
            <div className="fixed inset-0 z-50 flex items-center justify-center p-4 md:p-6">
                <div
                    ref={modalRef}
                    className="relative w-full max-w-6xl max-h-[90vh] md:max-h-[92vh] bg-white rounded-3xl shadow-[0_30px_80px_rgba(0,0,0,0.35)] animate-in fade-in zoom-in-95 duration-300 overflow-hidden flex flex-col"
                >
                    {/* Fixed Close Button */}
                    <button
                        onClick={onClose}
                        className="absolute top-4 right-4 z-50 p-2.5 rounded-full bg-white/90 backdrop-blur-sm shadow-lg hover:bg-white transition-all hover:scale-105 active:scale-95"
                        aria-label="Close modal"
                    >
                        <X size={20} className="text-gray-800" />
                    </button>

                    {/* Scrollable Content Area */}
                    <div className="overflow-y-auto flex-1">
                        {/* MAIN CONTENT */}
                        <div className="p-6 md:p-8 grid lg:grid-cols-2 gap-8 md:gap-10">
                            {/* LEFT */}
                            <div>
                                {/* Image */}
                                <div className="relative rounded-2xl overflow-hidden bg-gray-100 group">
                                    <img
                                        src={preview === "" ? product.image : preview}
                                        alt={product.name}
                                        className="w-full aspect-square object-cover transition-transform duration-500 group-hover:scale-110"
                                    />
                                    <span className="absolute top-4 left-4 px-4 py-1 text-sm font-semibold text-white rounded-full bg-[#FFAC1C] shadow-lg">
                                        Best Seller
                                    </span>
                                </div>

                                {/* Thumbnails */}
                                <div className="flex gap-3 mt-4 overflow-x-auto pb-2">
                                    {galleryImages.map((g) => (
                                        <button
                                            key={g.id}
                                            onClick={() => {
                                                setGalleryId(g.id);
                                                setPreview(g.img);
                                            }}
                                            className={`flex-shrink-0 h-16 w-16 rounded-xl overflow-hidden border-2 transition
                                                ${g.id === galleryId
                                                    ? "border-[#FFAC1C] ring-2 ring-[#FFAC1C]/40"
                                                    : "border-gray-200 hover:border-[#FFAC1C]"
                                                }`}
                                        >
                                            <img
                                                src={g.img}
                                                className="w-full h-full object-cover"
                                                alt={`Gallery ${g.id}`}
                                            />
                                        </button>
                                    ))}
                                </div>

                                {/* Key Features - Desktop/Tablet position */}
                                {!isMobile && (
                                    <div className="mt-6 bg-gray-50 rounded-2xl p-6 shadow-inner">
                                        <h3 className="text-lg font-bold mb-4">
                                            Key Features
                                        </h3>
                                        <ul className="space-y-3">
                                            {(product.key_features || [
                                                "High quality materials",
                                                "Long-lasting durability",
                                                "Easy to maintain",
                                                "Modern stylish design",
                                            ]).map((f, i) => (
                                                <li
                                                    key={i}
                                                    className="flex items-start gap-3 text-gray-700"
                                                >
                                                    <span className="mt-2 h-2 w-2 rounded-full bg-[#FFAC1C]" />
                                                    {f}
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                )}

                                {/* Video - Desktop/Tablet position */}
                                {!isMobile && (
                                    <div className="mt-6 rounded-2xl overflow-hidden shadow-lg aspect-video">
                                        <iframe
                                            src="https://www.youtube.com/embed/Q15nlbbvIoY"
                                            className="w-full h-full"
                                            title="Product video"
                                            allowFullScreen
                                        />
                                    </div>
                                )}
                            </div>

                            {/* RIGHT */}
                            <div>
                                <h2 className="text-2xl md:text-3xl font-extrabold mb-3">
                                    {product.name}
                                </h2>

                                {/* Rating */}
                                <div className="flex items-center gap-1 mb-4">
                                    {[...Array(5)].map((_, i) => (
                                        <Star
                                            key={i}
                                            size={18}
                                            className="text-[#FFAC1C] fill-[#FFAC1C]"
                                        />
                                    ))}
                                    <span className="text-sm text-gray-500 ml-2">
                                        (120 reviews)
                                    </span>
                                </div>

                                {/* Price */}
                                <div className="flex items-center gap-4 mb-6">
                                    <span className="text-3xl md:text-4xl font-extrabold text-primary">
                                        ৳{product.sale_price || product.base_price}
                                    </span>
                                    {product.sale_price && (
                                        <span className="text-lg text-gray-400 line-through">
                                            ৳{product.base_price}
                                        </span>
                                    )}
                                </div>

                                {/* Loyalty */}
                                <div className="flex items-center gap-3 p-4 bg-yellow-50 border border-yellow-200 rounded-2xl w-fit mb-6">
                                    <img src={points} className="w-6 h-6" alt="Points" />
                                    <div>
                                        <p className="text-sm text-gray-500">
                                            Earn Loyalty Coins
                                        </p>
                                        <p className="font-bold text-yellow-600">
                                            {product.loyalty_points || 200} Coins
                                        </p>
                                    </div>
                                </div>

                                {/* Color */}
                                <div className={`mb-6 ${product.attributes.length>0 ? "":"hidden"}`}>
                                    <h3 className="font-semibold mb-3">
                                        Color: {color.name}
                                    </h3>
                                    <div className="grid grid-cols-3 gap-4">
                                        {colorData.map((c) => (
                                            <div
                                                key={c.id}
                                                onClick={() => { setColor(c); setPreview(c.img) }}
                                                className={`p-3 rounded-xl cursor-pointer transition hover:scale-105
                                                    ${c.id === color.id
                                                        ? "bg-[#FFAC1C] text-white shadow-lg"
                                                        : "bg-gray-100"
                                                    }`}
                                            >
                                                <img
                                                    src={c.img}
                                                    className="rounded-lg w-full aspect-square object-cover"
                                                    alt={c.name}
                                                />
                                            </div>
                                        ))}
                                    </div>
                                </div>

                                {/* Weight */}
                                <div className={`mb-6 ${product.attributes.length>0 ? "":"hidden"}`}>
                                    <h3 className="font-semibold mb-3">
                                        Weight: {weight}
                                    </h3>
                                    <div className="grid grid-cols-3 gap-4">
                                        {weightData.map((w) => (
                                            <div
                                                key={w}
                                                onClick={() => setWeight(w)}
                                                className={`p-3 text-center rounded-xl cursor-pointer transition hover:scale-105
                                                    ${w === weight
                                                        ? "bg-[#FFAC1C] text-white shadow-lg"
                                                        : "bg-gray-100"
                                                    }`}
                                            >
                                                {w}
                                            </div>
                                        ))}
                                    </div>
                                </div>

                                {/* Size */}
                                <div className={`mb-6 ${product.attributes.length>0 ? "":"hidden"}`}>
                                    <h3 className="font-semibold mb-3">
                                        Size: {size}
                                    </h3>
                                    <div className="grid grid-cols-4 gap-4">
                                        {sizeData.map((s) => (
                                            <div
                                                key={s}
                                                onClick={() => setSize(s)}
                                                className={`p-3 text-center rounded-xl cursor-pointer transition hover:scale-105
                                                    ${s === size
                                                        ? "bg-[#FFAC1C] text-white shadow-lg"
                                                        : "bg-gray-100"
                                                    }`}
                                            >
                                                {s}
                                            </div>
                                        ))}
                                    </div>
                                </div>

                                {/* Quantity */}
                                <div className="flex items-center gap-4 mb-6">
                                    <span className="font-semibold text-lg">
                                        Quantity
                                    </span>
                                    <div className="flex items-center border rounded-xl overflow-hidden">
                                        <button
                                            className="px-4 py-3 hover:bg-gray-100 active:bg-gray-200"
                                            onClick={() => setQuantity(Math.max(1, quantity - 1))}
                                        >
                                            <Minus size={16} />
                                        </button>
                                        <span className="px-6 font-bold min-w-[40px] text-center">
                                            {quantity}
                                        </span>
                                        <button
                                            className="px-4 py-3 hover:bg-gray-100 active:bg-gray-200"
                                            onClick={() => setQuantity(quantity + 1)}
                                        >
                                            <Plus size={16} />
                                        </button>
                                    </div>
                                </div>

                                {/* Key Features - Mobile position */}
                                {isMobile && (
                                    <div className="mt-6 bg-gray-50 rounded-2xl p-6 shadow-inner">
                                        <h3 className="text-lg font-bold mb-4">
                                            Key Features
                                        </h3>
                                        <ul className="space-y-3">
                                            {(product.key_features || [
                                                "High quality materials",
                                                "Long-lasting durability",
                                                "Easy to maintain",
                                                "Modern stylish design",
                                            ]).map((f, i) => (
                                                <li
                                                    key={i}
                                                    className="flex items-start gap-3 text-gray-700"
                                                >
                                                    <span className="mt-2 h-2 w-2 rounded-full bg-[#FFAC1C]" />
                                                    {f}
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                )}

                                {/* Video - Mobile position */}
                                {isMobile && (
                                    <div className="mt-6 rounded-2xl overflow-hidden shadow-lg aspect-video">
                                        <iframe
                                            src="https://www.youtube.com/embed/Q15nlbbvIoY"
                                            className="w-full h-full"
                                            title="Product video"
                                            allowFullScreen
                                        />
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Description - Below everything */}
                        <div className="px-6 md:px-8 pb-6 md:pb-8">
                            <div className="bg-gray-50 rounded-2xl p-6 shadow-inner">
                                <h3 className="text-xl font-bold mb-3">
                                    Description
                                </h3>
                                <p className="text-gray-700 leading-relaxed">
                                    {product.description ||
                                        "No description available."}
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Sticky CTA */}
                    <div className="sticky bottom-0 bg-white border-t p-4 flex gap-4 z-40">
                        <button
                            onClick={handleAddToCart}
                            className="flex-1 py-3 rounded-xl text-md font-semibold bg-[#FFAC1C] text-white shadow-lg hover:opacity-90 transition flex items-center justify-center gap-2"
                        >
                            <ShoppingCart size={18} />
                            Add to Cart
                        </button>

                        <button
                            onClick={handleBuyNow}
                            className="flex-1 py-3 rounded-xl text-md font-semibold bg-[#FFAC1C] text-white shadow-lg hover:opacity-90 transition"
                        >
                            Buy Now
                        </button>
                    </div>
                </div>
            </div>
        </>
    );
}