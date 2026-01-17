// src/components/ProductModal.jsx
import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { X } from "lucide-react";
import { useCart } from "../../context/CartContext";
import points from "../../../../public/coin.png";
import {
    Star,
    Truck,
    ShieldCheck,
    RefreshCw,
    Minus,
    Plus,
    ShoppingCart,
} from "lucide-react";

export default function ProductModal({ product, onClose }) {
    const [quantity, setQuantity] = useState(1);

    const [hoverColor, setHoverColor] = useState("");
    const { addToCart } = useCart();
    const navigate = useNavigate();

    if (!product) return null;

    const colorData = [
        {
            id: 1,
            name: "white",
            img: "https://cdn.prod.website-files.com/6256995755a7ea0a3d8fbd11/675978de0439400df2c67ea4_675972afd73198379a1a44ad_image%2520(6)%25201.jpeg",
        },
        {
            id: 2,
            name: "black",
            img: "https://media.istockphoto.com/id/483960103/photo/blank-black-t-shirt-front-with-clipping-path.jpg?s=612x612&w=0&k=20&c=d8qlXILMYhugXGw6zX7Jer2SLPrLPORfsDsfRDWc-50=",
        },
        {
            id: 3,
            name: "gray",
            img: "https://cdn.prod.website-files.com/6256995755a7ea0a3d8fbd11/675978de0439400df2c67ea4_675972afd73198379a1a44ad_image%2520(6)%25201.jpeg",
        },
    ];
    const galleryTab=useState({id:colorData[0].id,img:colorData[0].img})
    const [color, setColor] = useState({
        id: colorData[0].id,
        name: colorData[0].name,
        img: colorData[0].img,
    });
    const weightData = ["250g", "500g", "1kg"];
    const sizeData = ["S", "M", "L", "XL"];

    const [weight, setWeight] = useState(weightData[0]);
    const [hoverWeight, setHoverWeight] = useState("");

    const [size, setSize] = useState(sizeData[0]);
    const [hoverSize, setHoverSize] = useState("");

    const [activeTab, setActiveTab] = useState("description");
    const [reviews, setReviews] = useState([
        {
            id: 1,
            name: "Rahim",
            avatar: "https://i.pravatar.cc/150?img=3",
            rating: 5,
            comment: "Excellent product!",
            media: [],
        },
    ]);

    const [newReview, setNewReview] = useState({
        name: "",
        rating: 5,
        comment: "",
        media: [],
    });

    const handleMediaUpload = (e) => {
        const files = Array.from(e.target.files);
        const previews = files.map((file) => ({
            url: URL.createObjectURL(file),
            type: file.type.startsWith("video") ? "video" : "image",
        }));

        setNewReview({
            ...newReview,
            media: [...newReview.media, ...previews],
        });
    };

    const handleAddToCart = () => {
        addToCart(product, quantity);
        onClose();
    };

    const handleBuyNow = () => {
        addToCart(product, quantity);
        navigate("/checkout");
    };

    return (
        <>
            {/* Overlay */}
            <div
                className="fixed inset-0 bg-black/65 backdrop-blur-sm z-40"
                onClick={onClose}
            />

            {/* Modal container */}
            <div className="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
                <div
                    className="
                        relative w-full max-w-4xl max-h-[90vh] overflow-y-auto
                        flex flex-col
                        bg-white rounded-2xl shadow-2xl
                        animate-in fade-in zoom-in-95 duration-200
                    "
                    onClick={(e) => e.stopPropagation()}
                >
                    {/* Close button */}
                    <button
                        onClick={onClose}
                        className="
                            absolute right-4 top-4 z-10
                            p-2 rounded-full bg-white/90 hover:bg-gray-100
                            text-gray-700 transition-colors
                        "
                        aria-label="Close modal"
                    >
                        <X size={24} />
                    </button>

                    {/* Modal content */}
                    <div className="p-6 sm:p-8 md:p-10 flex-1">
                        <div className="grid gap-8 md:grid-cols-2">
                            {/* Product Image */}
                            <div>
                                <div className="rounded-xl h-100 overflow-hidden bg-gray-50 shadow-sm">
                                    {/* {product.images?.[0] ? (
                                        <img
                                            src={product.images[0]}
                                            alt={product.name}
                                            className="w-full aspect-square object-cover"
                                        />
                                    ) : (
                                        <div className="w-full aspect-square flex items-center justify-center text-gray-400">
                                            No Image Available
                                        </div>
                                    )} */}
                                    <img
                                        src={color.img}
                                        alt={product.name}
                                        className="w-full aspect-square object-cover"
                                    />
                                </div>
                                <div className="flex gap-5 mt-3">
                                  {colorData.map((data)=>(
                                    <div className="h-13 w-13 bg-gray-400 rounded">
                                      <img src={data.img} alt="" srcset="" />
                                    </div>
                                    // <div className="h-13 w-13 bg-gray-400 rounded"></div>
                                  ))}
                                    
                                    {/* <div className="h-13 w-13 bg-gray-400 rounded"></div>
                                    <div className="h-13 w-13 bg-gray-400 rounded"></div> */}
                                </div>

                                {/* Key Features Section */}
                                <div className="mt-6 mb-2">
                                    <h3 className="text-lg font-semibold text-gray-900 mb-3">
                                        Key Features
                                    </h3>

                                    <ul className="space-y-2">
                                        {(
                                            product.key_features || [
                                                "High quality materials",
                                                "Long-lasting durability",
                                                "Easy to use & maintain",
                                                "Modern and stylish design",
                                            ]
                                        ).map((feature, index) => (
                                            <li
                                                key={index}
                                                className="flex items-start gap-3 text-gray-600"
                                            >
                                                <span className="mt-1 h-2 w-2 rounded-full bg-[#FFAC1C]" />
                                                <span className="leading-relaxed">
                                                    {feature}
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                                <div className="relative w-full rounded-xl bg-gray-100 shadow-sm aspect-video">
                                    <iframe
                                        src="https://www.youtube.com/embed/Q15nlbbvIoY?si=G-ErVU0ulIsed2aE"
                                        controls
                                        className="w-full h-full "
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                        allowFullScreen
                                    />
                                </div>
                            </div>

                            {/* Product Info */}
                            <div className="flex flex-col">
                                <h2 className="text-2xl sm:text-3xl font-bold text-gray-900 mb-3 leading-tight">
                                    {product.name}
                                </h2>

                                <div className="mb-6">
                                    <span className="text-3xl font-bold text-primary">
                                        ৳
                                        {product.sale_price ||
                                            product.base_price}
                                    </span>
                                    {product.sale_price && (
                                        <span className="ml-3 text-lg text-gray-400 line-through">
                                            ৳{product.base_price}
                                        </span>
                                    )}
                                </div>

                                {/* Loyalty Points Section */}
                                <div className="flex items-center gap-3 mt-1 p-3 bg-yellow-50 rounded-xl border border-yellow-200 w-fit">
                                    <img
                                        src={points}
                                        alt="Loyalty Points"
                                        className="w-6 h-6"
                                    />
                                    <div className="flex flex-col">
                                        <span className="text-sm text-gray-500">
                                            Earn Loyalty Coins
                                        </span>
                                        <span className="font-semibold text-yellow-600 text-lg">
                                            {product.loyalty_points || 200}{" "}
                                            coins
                                        </span>
                                    </div>
                                </div>
                                {/* Color */}
                                <div className="mt-8">
                                    <h3 className="text-lg font-semibold text-gray-900 mb-4">
                                        Color:
                                        {hoverColor.length > 0
                                            ? hoverColor
                                            : color.name}
                                    </h3>
                                    <div
                                        className={`grid grid-cols-1 sm:grid-cols-3 gap-4`}
                                    >
                                        {colorData.map((data) => (
                                            <div
                                                className={`p-1 rounded-xl border ${
                                                    data.id === color.id
                                                        ? "bg-amber-400"
                                                        : "bg-gray-50"
                                                }  `}
                                                onClick={() =>
                                                    setColor({
                                                        id: data.id,
                                                        name: data.name,
                                                        img: data.img,
                                                    })
                                                }
                                                onMouseEnter={() =>
                                                    setHoverColor(data.name)
                                                }
                                                onMouseLeave={() =>
                                                    setHoverColor("")
                                                }
                                            >
                                                <img
                                                    src={data.img}
                                                    alt=""
                                                    srcset=""
                                                />
                                            </div>
                                        ))}
                                    </div>
                                </div>
                                {/* Weight */}
                                <div className="mt-8">
                                    <h3 className="text-lg font-semibold text-gray-900 mb-4">
                                        Weight: {hoverWeight || weight}
                                    </h3>

                                    <div className="grid grid-cols-3 gap-4">
                                        {weightData.map((w) => (
                                            <div
                                                key={w}
                                                onClick={() => setWeight(w)}
                                                onMouseEnter={() =>
                                                    setHoverWeight(w)
                                                }
                                                onMouseLeave={() =>
                                                    setHoverWeight("")
                                                }
                                                className={`p-3 text-center rounded-xl border cursor-pointer transition
                    ${w === weight ? "bg-amber-400" : "bg-gray-50"}
                `}
                                            >
                                                <span className="font-medium text-gray-800">
                                                    {w}
                                                </span>
                                            </div>
                                        ))}
                                    </div>
                                </div>

                                {/* Size */}
                                <div className="mt-8">
                                    <h3 className="text-lg font-semibold text-gray-900 mb-4">
                                        Size: {hoverSize || size}
                                    </h3>

                                    <div className="grid grid-cols-4 gap-4">
                                        {sizeData.map((s) => (
                                            <div
                                                key={s}
                                                onClick={() => setSize(s)}
                                                onMouseEnter={() =>
                                                    setHoverSize(s)
                                                }
                                                onMouseLeave={() =>
                                                    setHoverSize("")
                                                }
                                                className={`p-3 text-center rounded-xl border cursor-pointer transition
                    ${s === size ? "bg-amber-400" : "bg-gray-50"}
                `}
                                            >
                                                <span className="font-medium text-gray-800">
                                                    {s}
                                                </span>
                                            </div>
                                        ))}
                                    </div>
                                </div>

                                {/* quantity */}
                                <div className="flex flex-col gap-2">
                                    <span className="font-bold text-gray-800 text-sm">
                                        Quantity:
                                    </span>
                                    <div className="flex items-center border border-gray-300 rounded-lg w-fit bg-white">
                                        <button
                                            onClick={() =>
                                                setQuantity(
                                                    Math.max(1, quantity - 1)
                                                )
                                            }
                                            className="p-3 text-gray-600 hover:text-primary transition"
                                        >
                                            <Minus size={18} />
                                        </button>
                                        <span className="w-12 text-center font-bold text-gray-800">
                                            {quantity}
                                        </span>
                                        <button
                                            onClick={() =>
                                                setQuantity(quantity + 1)
                                            }
                                            className="p-3 text-gray-600 hover:text-primary transition"
                                        >
                                            <Plus size={18} />
                                        </button>
                                    </div>
                                </div>

                                {/* description */}

                                {/* <div className="text-gray-600 mb-8 leading-relaxed">
                                    {product.description ||
                                        "No description available for this product."}
                                </div> */}

                            </div>
                        </div>
                    </div>
{/* new review */}
                                {/* Description & Review Tabs */}
                                <div className=" p-4 sm:p-6">
                                    {/* Tabs */}
                                    <div className="flex justify-center gap-4  border-b mb-4">
                                        <button
                                            onClick={() =>
                                                setActiveTab("description")
                                            }
                                            className={`pb-2 font-semibold transition
                ${
                    activeTab === "description"
                        ? "border-b-2 border-amber-400 text-amber-500"
                        : "text-gray-500"
                }
            `}
                                        >
                                            Description
                                        </button>

                                        <button
                                            onClick={() =>
                                                setActiveTab("review")
                                            }
                                            className={`pb-2 font-semibold transition
                ${
                    activeTab === "review"
                        ? "border-b-2 border-amber-400 text-amber-500"
                        : "text-gray-500"
                }
            `}
                                        >
                                            Reviews ({reviews.length})
                                        </button>
                                    </div>

                                    {/* Content */}
                                    {activeTab === "description" && (
                                        <p className="text-gray-600 leading-relaxed">
                                            {product.description ||
                                                "No description available for this product."}
                                        </p>
                                    )}

                                    {activeTab === "review" && (
                                        <div className="space-y-6">
                                            <div className="p-6 border rounded-xl bg-white w-full max-w-5xl ">
                                                {/* Top: Average Rating */}
                                               
                                                <div className="flex items-center gap-6 mb-6">
                                                    {/* Average */}
                                                    <div className="text-center">
                                                        <p className="text-4xl font-bold text-gray-900">
                                                            5.00
                                                        </p>
                                                        <div className="flex justify-center mt-1 text-amber-400">
                                                            ★★★★★
                                                        </div>
                                                        <p className="text-gray-500 text-sm">
                                                            1 Ratings
                                                        </p>
                                                    </div>

                                                    {/* Rating Bars */}
                                                    <div className="flex-1 space-y-2">
                                                        <div className="flex items-center gap-2">
                                                            <span className="text-sm w-24 text-gray-600">
                                                                Excellent
                                                            </span>
                                                            <div className="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                                                                <div className="h-2 bg-amber-400 rounded-full w-full"></div>
                                                            </div>
                                                            <span className="text-sm w-5 text-gray-600">
                                                                1
                                                            </span>
                                                        </div>

                                                        <div className="flex items-center gap-2">
                                                            <span className="text-sm w-24 text-gray-600">
                                                                Good
                                                            </span>
                                                            <div className="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                                                                <div className="h-2 bg-amber-400 rounded-full w-0"></div>
                                                            </div>
                                                            <span className="text-sm w-5 text-gray-600">
                                                                0
                                                            </span>
                                                        </div>

                                                        <div className="flex items-center gap-2">
                                                            <span className="text-sm w-24 text-gray-600">
                                                                Average
                                                            </span>
                                                            <div className="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                                                                <div className="h-2 bg-amber-400 rounded-full w-0"></div>
                                                            </div>
                                                            <span className="text-sm w-5 text-gray-600">
                                                                0
                                                            </span>
                                                        </div>

                                                        <div className="flex items-center gap-2">
                                                            <span className="text-sm w-24 text-gray-600">
                                                                Below Average
                                                            </span>
                                                            <div className="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                                                                <div className="h-2 bg-amber-400 rounded-full w-0"></div>
                                                            </div>
                                                            <span className="text-sm w-5 text-gray-600">
                                                                0
                                                            </span>
                                                        </div>

                                                        <div className="flex items-center gap-2">
                                                            <span className="text-sm w-24 text-gray-600">
                                                                Poor
                                                            </span>
                                                            <div className="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                                                                <div className="h-2 bg-amber-400 rounded-full w-0"></div>
                                                            </div>
                                                            <span className="text-sm w-5 text-gray-600">
                                                                0
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div className="text-center font-semibold text-gray-700 border-t pt-2">
                                                    Product Review
                                                </div>
                                            </div>

                                            {/* Existing Reviews */}
                                            {reviews.map((review) => (
                                                <div
                                                    key={review.id}
                                                    className="p-4 border rounded-xl bg-gray-50"
                                                >
                                                    {/* Profile */}
                                                    <div className="flex items-center gap-3 mb-2">
                                                        <img
                                                            src={review.avatar}
                                                            alt={review.name}
                                                            className="w-10 h-10 rounded-full object-cover"
                                                        />
                                                        <div>
                                                            <h4 className="font-semibold text-gray-800">
                                                                {review.name}
                                                            </h4>
                                                            <span className="text-amber-400 text-sm">
                                                                {"★".repeat(
                                                                    review.rating
                                                                )}
                                                            </span>
                                                        </div>
                                                    </div>

                                                    {/* Comment */}
                                                    <p className="text-gray-600 text-sm mb-3">
                                                        {review.comment}
                                                    </p>

                                                    {/* Media */}
                                                    {review.media.length >
                                                        0 && (
                                                        <div className="grid grid-cols-3 gap-3">
                                                            {review.media.map(
                                                                (m, idx) =>
                                                                    m.type ===
                                                                    "image" ? (
                                                                        <img
                                                                            key={
                                                                                idx
                                                                            }
                                                                            src={
                                                                                m.url
                                                                            }
                                                                            className="rounded-lg h-24 w-full object-cover"
                                                                        />
                                                                    ) : (
                                                                        <video
                                                                            key={
                                                                                idx
                                                                            }
                                                                            src={
                                                                                m.url
                                                                            }
                                                                            controls
                                                                            className="rounded-lg h-24 w-full object-cover"
                                                                        />
                                                                    )
                                                            )}
                                                        </div>
                                                    )}
                                                </div>
                                            ))}

                                            {/* Add Review */}
                                            <div className="p-4 border rounded-xl bg-white">
                                                <h4 className="font-semibold text-gray-900 mb-3">
                                                    Write a Review
                                                </h4>

                                                <input
                                                    type="text"
                                                    placeholder="Your name"
                                                    value={newReview.name}
                                                    onChange={(e) =>
                                                        setNewReview({
                                                            ...newReview,
                                                            name: e.target
                                                                .value,
                                                        })
                                                    }
                                                    className="w-full mb-3 p-2 border rounded-lg"
                                                />

                                                <select
                                                    value={newReview.rating}
                                                    onChange={(e) =>
                                                        setNewReview({
                                                            ...newReview,
                                                            rating: Number(
                                                                e.target.value
                                                            ),
                                                        })
                                                    }
                                                    className="w-full mb-3 p-2 border rounded-lg"
                                                >
                                                    {[5, 4, 3, 2, 1].map(
                                                        (r) => (
                                                            <option
                                                                key={r}
                                                                value={r}
                                                            >
                                                                {r} Star
                                                            </option>
                                                        )
                                                    )}
                                                </select>

                                                <textarea
                                                    rows="3"
                                                    placeholder="Write your review..."
                                                    value={newReview.comment}
                                                    onChange={(e) =>
                                                        setNewReview({
                                                            ...newReview,
                                                            comment:
                                                                e.target.value,
                                                        })
                                                    }
                                                    className="w-full mb-3 p-2 border rounded-lg"
                                                />

                                                {/* Media Upload */}
                                                <input
                                                    type="file"
                                                    accept="image/*,video/*"
                                                    multiple
                                                    onChange={handleMediaUpload}
                                                    className="mb-3"
                                                />

                                                {/* Media Preview */}
                                                {newReview.media.length > 0 && (
                                                    <div className="grid grid-cols-4 gap-3 mb-3">
                                                        {newReview.media.map(
                                                            (m, i) =>
                                                                m.type ===
                                                                "image" ? (
                                                                    <img
                                                                        key={i}
                                                                        src={
                                                                            m.url
                                                                        }
                                                                        className="h-20 w-full object-cover rounded-lg"
                                                                    />
                                                                ) : (
                                                                    <video
                                                                        key={i}
                                                                        src={
                                                                            m.url
                                                                        }
                                                                        className="h-20 w-full object-cover rounded-lg"
                                                                    />
                                                                )
                                                        )}
                                                    </div>
                                                )}

                                                <button
                                                    onClick={() => {
                                                        if (
                                                            !newReview.name ||
                                                            !newReview.comment
                                                        )
                                                            return;

                                                        setReviews([
                                                            ...reviews,
                                                            {
                                                                id: Date.now(),
                                                                name: newReview.name,
                                                                avatar: `https://i.pravatar.cc/150?u=${newReview.name}`,
                                                                rating: newReview.rating,
                                                                comment:
                                                                    newReview.comment,
                                                                media: newReview.media,
                                                            },
                                                        ]);

                                                        setNewReview({
                                                            name: "",
                                                            rating: 5,
                                                            comment: "",
                                                            media: [],
                                                        });
                                                    }}
                                                    className="px-4 py-2 bg-amber-400 text-white rounded-lg hover:bg-amber-500 transition"
                                                >
                                                    Submit Review
                                                </button>
                                            </div>
                                        </div>
                                    )}
                                </div>
                    {/* Sticky bottom buttons */}
                    <div className="sticky bottom-0 bg-white flex justify-center">
                        <div className="sticky bottom-0 bg-white p-4 flex flex-col sm:flex-row gap-4 shadow-t w-[70%]">
                            <button
                                onClick={handleAddToCart}
                                className="
                                
                                flex-1 px-8 py-4
                                bg-[#FFAC1C] text-white font-medium
                                rounded-xl hover:bg-[#FFAC1C]/90 hover:shadow-xl/30
                                transition-colors text-lg shadow-sm cursor-pointer
                            "
                            >
                                Add to Cart
                            </button>

                            <button
                                onClick={handleBuyNow}
                                className="
                                
                                flex-1 px-8 py-4
                                bg-[#FFAC1C] text-white font-medium
                                rounded-xl hover:bg-[#FFAC1C]/90 hover:shadow-xl/30
                                transition-colors text-lg shadow-sm cursor-pointer
                            "
                            >
                                Buy Now
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
