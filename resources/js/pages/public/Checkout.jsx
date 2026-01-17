import React, { useState } from "react";
import { useCart } from "../../context/CartContext";
import { useAuth } from "../../context/AuthProvider";
import { useConfig } from "../../context/ConfigContext";
import { useNavigate } from "react-router-dom";
import api from "../../services/api";
import toast from "react-hot-toast";
import { MapPin, Phone, CreditCard, CheckCircle } from "lucide-react";
import clsx from "clsx";

const Checkout = () => {
    const { cart, cartTotal, clearCart } = useCart();
    const { user } = useAuth();
    const { config } = useConfig();
    const navigate = useNavigate();
    const [loading, setLoading] = useState(false);

    const [paymentProcess, setPaymentProcess] = useState(true);
    const [checkoutData, setCheckoutData] = useState({
        name: `${user?.first_name || ""} ${user?.last_name || ""}`.trim(),
        phone: user?.phone || "",
        email: user?.email || "", // Ensure we have email for address saving
        address_line1: "",
        address_line2: "",
        city: "",
        district: "",
        country: "Bangladesh",
        zip_code: "",
        payment_method: "cod",
        notes: "",
        area:""
    });

    if (cart.length === 0) {
        navigate("/cart");
        return null;
    }

    const handleChange = (e) => {
        const updatedData = {
            ...checkoutData,
            [e.target.name]: e.target.value,
        };
    
        setCheckoutData(updatedData);
    
        const isValid =
            updatedData.name.trim().length > 0 &&
            updatedData.phone.trim().length >= 11 &&
            updatedData.address_line1.trim().length > 0 &&
            updatedData.city.trim().length > 0 &&
            updatedData.district.trim().length > 0 &&
            updatedData.area=="Inside Dhaka" || "Outside Dhaka";

    
        // setPaymentProcess(!isValid);

        // console.log(isValid);
        if(isValid)
        {
            setPaymentProcess(false);
            console.log(checkoutData);
        }
        else setPaymentProcess(true);
        
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);

        try {
            // 1. Save Address to Address Book
            // The API requires specific fields: title, name, phone, email, address_line1, city, district, country
            const addressPayload = {
                title: "Shipping Address", // Default title
                name: checkoutData.name,
                phone: checkoutData.phone,
                email: checkoutData.email || user?.email || "guest@example.com", // Basic fallback
                address_line1: checkoutData.address_line1,
                address_line2: checkoutData.address_line2,
                city: checkoutData.city,
                district: checkoutData.district,
                state: checkoutData.country,
                postal_code: checkoutData.zip_code,
                country:checkoutData.country
            };
            // console.log(addressPayload);
            // Attempt to save address. If it fails due to validation, the catch block will handle it.
            // We await this to ensure address is saved before order is placed.
            try {
                const ckdata=await api.post("/v1/auth/addresses", addressPayload);
                console.log(ckdata.data)
            } catch (addressError) {
                console.warn(
                    "Address save failed, proceeding with order anyway if possible/required, or logging.",
                    addressError
                );
                // Optional: Decide if we block order placement if address save fails.
                // For now, we proceed, but typically you might want to stop here:
                // toast.error("Could not save address. Please check your details.");
                // setLoading(false);
                // return;
            }

            // 2. Place Order
            const orderPayload = {
                name: checkoutData.name,
                products: cart.map((item) => ({
                    product_id: item.id,
                    product_variation_id: item.variation?.id || null,
                    quantity: item.quantity,
                })),
                // Construct a full address string for the order record
                shipping_address: `${checkoutData.address_line1}, ${
                    checkoutData.address_line2
                        ? checkoutData.address_line2 + ", "
                        : ""
                }${checkoutData.city}, ${checkoutData.district}, ${
                    checkoutData.country
                } - ${checkoutData.zip_code}`,
                contact_number: checkoutData.phone,
                payment_method: checkoutData.payment_method,
                notes: checkoutData.notes,
            };
            // const payload = {
            //     name: "Imani House1",
            //     products: [
            //       { product_id: 3, variant_id: null, quantity: 1 },
            //       { product_id: 1, variant_id: null, quantity: 1 }
            //     ],
            //     shipping_address: "...",
            //     contact_number: "+1 (473) 183-9206",
            //     payment_method: "cash_on_delivery",
            //     notes: "Officiis numquam obc"
            //   };
              
            //   await api.post('/api/v1/order/checkout', payload);
              
            // console.log(orderPayload);

            const { data } = await api.post("/v1/order/checkout", orderPayload);

            // Handle Payment Redirection
            if (data.payment_result?.payment_url) {
                toast.loading("Redirecting to bKash...");
                setTimeout(() => {
                    window.location.href = data.payment_result.payment_url;
                }, 1000);
                return;
            }

            toast.success("Order placed successfully!");
            // console.log(data.id);
            clearCart();
            // navigate(`/order-success?order=${data.order.order_number}`);
            navigate(`/order-success?order=${data.id}`);
        } catch (error) {
            console.error("Order placement failed", error);
            const msg =
                error.response?.data?.message ||
                (error.response?.data?.errors
                    ? Object.values(error.response.data.errors)
                          .flat()
                          .join(", ")
                    : "Failed to place order");
            toast.error(msg);
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="bg-gray-50 min-h-screen py-10">
            <div className="container mx-auto px-4">
                <h1 className="text-2xl font-bold text-gray-800 mb-8 text-center">
                    Checkout
                </h1>

                <form
                    onSubmit={handleSubmit}
                    className="grid grid-cols-1 lg:grid-cols-2 gap-8 max-w-5xl mx-auto"
                >
                    {/* Shipping Details */}
                    <div className="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 space-y-6">
                        <div className="flex items-center gap-3 border-b border-gray-100 pb-4 mb-2">
                            <MapPin className="text-red-600" />
                            <h2 className="text-lg font-bold text-gray-800">
                                Shipping Information
                            </h2>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Full Name
                            </label>
                            <input
                                type="text"
                                name="name"
                                value={checkoutData.name}
                                onChange={handleChange}
                                className="w-full p-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-800 focus:outline-none focus:border-red-500"
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Contact Number
                            </label>
                            <div className="relative">
                                <Phone
                                    className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"
                                    size={18}
                                />
                                <input
                                    type="text"
                                    name="phone"
                                    required
                                    placeholder="Enter your phone number"
                                    className="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:border-red-500"
                                    value={checkoutData.phone}
                                    onChange={handleChange}
                                />
                            </div>
                        </div>

                        {/* Structured Address Fields */}
                        <div className="grid grid-cols-1 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Address Line 1
                                </label>
                                <input
                                    type="text"
                                    name="address_line1"
                                    required
                                    placeholder="Street, House No, etc."
                                    className="w-full p-3 border border-gray-200 rounded-lg focus:outline-none focus:border-red-500"
                                    value={checkoutData.address_line1}
                                    onChange={handleChange}
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Address Line 2 (Optional)
                                </label>
                                <input
                                    type="text"
                                    name="address_line2"
                                    placeholder="Building, Floor, etc."
                                    className="w-full p-3 border border-gray-200 rounded-lg focus:outline-none focus:border-red-500"
                                    value={checkoutData.address_line2}
                                    onChange={handleChange}
                                />
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        City
                                    </label>
                                    <input
                                        type="text"
                                        name="city"
                                        required
                                        placeholder="City"
                                        className="w-full p-3 border border-gray-200 rounded-lg focus:outline-none focus:border-red-500"
                                        value={checkoutData.city}
                                        onChange={handleChange}
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        District
                                    </label>
                                    <input
                                        type="text"
                                        name="district"
                                        required
                                        placeholder="District"
                                        className="w-full p-3 border border-gray-200 rounded-lg focus:outline-none focus:border-red-500"
                                        value={checkoutData.district}
                                        onChange={handleChange}
                                    />
                                </div>
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Postal Code
                                    </label>
                                    <input
                                        type="text"
                                        name="zip_code"
                                        placeholder="Zip Code"
                                        className="w-full p-3 border border-gray-200 rounded-lg focus:outline-none focus:border-red-500"
                                        value={checkoutData.zip_code}
                                        onChange={handleChange}
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Country
                                    </label>
                                    <input
                                        type="text"
                                        name="country"
                                        required
                                        placeholder="Country"
                                        className="w-full p-3 border border-gray-200 rounded-lg focus:outline-none focus:border-red-500"
                                        value={checkoutData.country}
                                        onChange={handleChange}
                                    />
                                </div>
                            </div>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Order Notes (Optional)
                            </label>
                            <textarea
                                name="notes"
                                rows="2"
                                placeholder="Any special instructions?"
                                className="w-full p-3 border border-gray-200 rounded-lg focus:outline-none focus:border-red-500"
                                value={checkoutData.notes}
                                onChange={handleChange}
                            ></textarea>
                        </div>
                    </div>

                    {/* Order Summary & Payment */}
                    <div className="space-y-6">
                        {/* Location set */}
                        <div className="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
                            <div className="flex items-center gap-3 border-b border-gray-100 pb-4 mb-4">
                                <CheckCircle className="text-red-600" />
                                <h2 className="text-lg font-bold text-gray-800">
                                    Select Area
                                </h2>
                            </div>

                            <div className="space-y-3 max-h-60 overflow-y-auto pr-2 mb-6 text-sm">
                                <label htmlFor="">Chose Location</label>
                                <select
                                    name="area"
                                    id="area"
                                    onChange={handleChange}
                                    className="w-full p-3 border border-gray-200 rounded-lg focus:outline-none focus:border-red-500"
                                >
                                    <option value="Select Area">Select Area</option>
                                    <option value="Inside Dhaka">Inside Dhaka</option>
                                    <option value="Outside Dhaka">
                                        Outside Dhaka
                                    </option>
                                </select>
                            </div>
                        </div>
                        {/* Order and cost information */}
                        <div className="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
                            <div className="flex items-center gap-3 border-b border-gray-100 pb-4 mb-4">
                                <CheckCircle className="text-red-600" />
                                <h2 className="text-lg font-bold text-gray-800">
                                    Your Order
                                </h2>
                            </div>

                            <div className="space-y-3 max-h-60 overflow-y-auto pr-2 mb-6 text-sm">
                                {cart.map((item, idx) => (
                                    <div
                                        key={idx}
                                        className="flex justify-between items-center bg-gray-50 p-2 rounded"
                                    >
                                        <div className="flex items-center gap-3">
                                            <div className="w-10 h-10 bg-gray-200 rounded overflow-hidden">
                                                {item.images &&
                                                    item.images.length > 0 && (
                                                        <img
                                                            src={item.images[0]}
                                                            className="w-full h-full object-cover"
                                                        />
                                                    )}
                                            </div>
                                            <div>
                                                <p className="font-bold text-gray-800">
                                                    {item.name}
                                                </p>
                                                <p className="text-xs text-gray-500">
                                                    Qty: {item.quantity}
                                                </p>
                                            </div>
                                        </div>
                                        <p className="font-bold">
                                            ৳
                                            {(item.sale_price ||
                                                item.base_price) *
                                                item.quantity}
                                        </p>
                                    </div>
                                ))}
                            </div>

                            <div className="space-y-2 pt-4 border-t border-gray-100 text-sm">
                                <div className="flex justify-between text-gray-600">
                                    <span>Subtotal</span>
                                    <span>৳{cartTotal}</span>
                                </div>
                                <div className="flex justify-between text-gray-600">
                                    <span>Shipping</span>
                                    <span>৳100</span>
                                </div>
                                <div className="flex justify-between text-lg font-bold text-gray-800 pt-2">
                                    <span>Total</span>
                                    <span className="text-red-600">
                                        ৳{cartTotal + 100}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {/* process order button */}
                        <div className="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
                            <div className="flex items-center gap-3 mb-4">
                                <CreditCard className="text-red-600" />
                                <h2 className="text-lg font-bold text-gray-800">
                                    Payment Method
                                </h2>
                            </div>

                            <div className="space-y-3">
                                {config?.payment_methods?.map((method) => (
                                    <label
                                        key={method.key_name}
                                        className={clsx(
                                            "flex items-center gap-3 p-4 border rounded-lg cursor-pointer transition-all",
                                            checkoutData.payment_method ===
                                                method.key_name
                                                ? "border-red-200 bg-red-50"
                                                : "border-gray-200 hover:bg-gray-50"
                                        )}
                                    >
                                        <input
                                            type="radio"
                                            name="payment_method"
                                            value={method.key_name}
                                            checked={
                                                checkoutData.payment_method ===
                                                method.key_name
                                            }
                                            onChange={handleChange}
                                            className="text-red-600 focus:ring-red-500"
                                        />
                                        <div className="flex-1">
                                            <div className="flex items-center justify-between">
                                                <span className="font-bold text-gray-800 block">
                                                    {method.additional_datas
                                                        ?.gateway_title ||
                                                        method.key_name}
                                                </span>
                                                {method.additional_datas
                                                    ?.gateway_image && (
                                                    <img
                                                        src={
                                                            method
                                                                .additional_datas
                                                                .gateway_image
                                                        }
                                                        alt={method.key_name}
                                                        className="h-6 object-contain"
                                                    />
                                                )}
                                            </div>
                                            {method.type === "cod" && (
                                                <span className="text-xs text-gray-500">
                                                    Pay when you receive your
                                                    order
                                                </span>
                                            )}
                                            {method.type === "online" && (
                                                <span className="text-xs text-gray-500">
                                                    Secure payment via{" "}
                                                    {
                                                        method.additional_datas
                                                            ?.gateway_title
                                                    }
                                                </span>
                                            )}
                                        </div>
                                    </label>
                                ))}

                                {(!config?.payment_methods ||
                                    config.payment_methods.length === 0) && (
                                    <div className="text-center p-4 text-gray-500">
                                        No payment methods available.
                                    </div>
                                )}
                            </div>

                            <button
                                type="submit"
                                disabled={paymentProcess}
                                className="w-full mt-6 bg-red-600 text-white py-4 rounded-xl font-bold hover:bg-red-700 transition shadow-lg shadow-red-200 disabled:bg-gray-400 disabled:cursor-not-allowed"
                            >
                                {loading
                                    ? "Placing Order..."
                                    : `Place Order (৳${cartTotal + 100})`}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    );
};

export default Checkout;
