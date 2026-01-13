// src/components/ProductModal.jsx
import { useState } from 'react';
import { useNavigate } from 'react-router-dom';     // ← Add this
import { X } from 'lucide-react';
import { useCart } from '../../context/CartContext';

export default function ProductModal({ product, onClose }) {
  const [quantity, setQuantity] = useState(1);
  const { addToCart } = useCart();
  const navigate = useNavigate();                    // ← Add this

  if (!product) return null;

  const handleAddToCart = () => {
    addToCart(product, quantity);
    onClose();
    // navigate('/checkout');
  };

  const handleBuyNow = () => {
    addToCart(product, quantity);
    navigate('/checkout');
    // or: navigate(`/products/${product.id}`) if you want product detail first
  };

  return (
    <>
      <div
        className="fixed inset-0 bg-black/65 backdrop-blur-sm z-40"
        onClick={onClose}
      />

      <div className="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
        <div
          className="
            relative w-full max-w-4xl max-h-[90vh] overflow-y-auto
            bg-white rounded-2xl shadow-2xl
            animate-in fade-in zoom-in-95 duration-200
          "
          onClick={(e) => e.stopPropagation()}
        >
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

          <div className="p-6 sm:p-8 md:p-10">
            <div className="grid gap-8 md:grid-cols-2">
              {/* Product Image */}
              <div className="rounded-xl overflow-hidden bg-gray-50 shadow-sm">
                {product.images?.[0] ? (
                  <img
                    src={product.images[0]}
                    alt={product.name}
                    className="w-full aspect-square object-cover"
                  />
                ) : (
                  <div className="w-full aspect-square flex items-center justify-center text-gray-400">
                    No Image Available
                  </div>
                )}
              </div>

              {/* Product Info */}
              <div className="flex flex-col">
                <h2 className="text-2xl sm:text-3xl font-bold text-gray-900 mb-3 leading-tight">
                  {product.name}
                </h2>

                <div className="mb-6">
                  <span className="text-3xl font-bold text-primary">
                    ৳{product.sale_price || product.base_price}
                  </span>
                  {product.sale_price && (
                    <span className="ml-3 text-lg text-gray-400 line-through">
                      ৳{product.base_price}
                    </span>
                  )}
                </div>

                <div className="text-gray-600 mb-8 leading-relaxed">
                  {product.description || 'No description available for this product.'}
                </div>

                {/* Buttons */}
                <div className="mt-auto flex flex-col sm:flex-row gap-4">
                  <button
                    onClick={handleAddToCart}
                    className="
                      flex-1 px-8 py-4
                      bg-primary text-white font-medium
                      rounded-xl hover:bg-primary/90
                      transition-colors text-lg shadow-sm
                    "
                  >
                    Add to Cart
                  </button>

                  <button
                    onClick={handleBuyNow}
                    className="
                      flex-1 px-8 py-4
                      bg-primary hover:bg-primary/90
                      text-white font-medium
                      rounded-xl transition-colors text-lg shadow-sm
                    "
                  >
                    Buy Now
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}