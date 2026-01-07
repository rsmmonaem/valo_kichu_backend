import React from 'react';
import { useSearchParams, Link } from 'react-router-dom';
import { CheckCircle, ArrowRight } from 'lucide-react';

const OrderSuccess = () => {
    const [searchParams] = useSearchParams();
    const orderNumber = searchParams.get('order');

    return (
        <div className="min-h-screen bg-gray-50 flex flex-col items-center justify-center py-20 px-4">
            <div className="bg-white p-10 rounded-3xl shadow-sm text-center max-w-md w-full border border-gray-100">
                <div className="w-24 h-24 bg-green-50 text-green-500 rounded-full flex items-center justify-center mx-auto mb-8 animate-bounce">
                    <CheckCircle size={48} />
                </div>
                <h1 className="text-3xl font-bold text-gray-800 mb-2">Order Confirmed!</h1>
                <p className="text-gray-500 mb-6">Your order has been placed successfully.</p>

                {orderNumber && (
                    <div className="bg-gray-50 p-4 rounded-xl mb-8 border border-gray-200 border-dashed">
                        <span className="text-xs text-gray-400 uppercase tracking-widest font-semibold">Order Number</span>
                        <div className="text-xl font-bold text-gray-800 break-all">{orderNumber}</div>
                    </div>
                )}

                <div className="space-y-3">
                    <Link to="/orders" className="block w-full bg-gray-900 text-white py-3.5 rounded-xl font-bold hover:bg-gray-800 transition">
                        View My Orders
                    </Link>
                    <Link to="/" className="block w-full bg-white text-gray-600 border border-gray-200 py-3.5 rounded-xl font-bold hover:bg-gray-50 transition">
                        Continue Shopping
                    </Link>
                </div>
            </div>
        </div>
    );
};

export default OrderSuccess;
