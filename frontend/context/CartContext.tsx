"use client";

import React, { createContext, useContext, useState, useEffect } from 'react';

export interface CartItem {
    id: number;
    name: string;
    slug: string;
    price: number;
    sale_price?: number;
    base_price?: number;
    image: string;
    quantity: number;
    variant?: {
        id?: number;
        size?: string;
        color?: string;
        [key: string]: any;
    };
    [key: string]: any;
}

interface CartContextType {
    cart: CartItem[];
    addToCart: (item: CartItem) => void;
    removeFromCart: (id: number, variantId?: number) => void;
    updateQuantity: (id: number, quantity: number, variantId?: number) => void;
    clearCart: () => void;
    cartTotal: number;
    cartCount: number;
    isCartOpen: boolean;
    toggleCart: () => void;
}

const CartContext = createContext<CartContextType | undefined>(undefined);

export const CartProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
    const [cart, setCart] = useState<CartItem[]>([]);
    const [isCartOpen, setIsCartOpen] = useState(false);

    // Load from local storage on mount
    useEffect(() => {
        const savedCart = localStorage.getItem('cart');
        if (savedCart) {
            try {
                setCart(JSON.parse(savedCart));
            } catch (e) {
                console.error("Failed to parse cart", e);
            }
        }
    }, []);

    // Save to local storage on change
    useEffect(() => {
        localStorage.setItem('cart', JSON.stringify(cart));
    }, [cart]);

    const addToCart = (newItem: CartItem) => {
        setCart((prevCart) => {
            const existingItemIndex = prevCart.findIndex(
                (item) => item.id === newItem.id && item.variant?.id === newItem.variant?.id
            );

            if (existingItemIndex > -1) {
                const newCart = [...prevCart];
                newCart[existingItemIndex].quantity += newItem.quantity;
                return newCart;
            } else {
                return [...prevCart, newItem];
            }
        });
        setIsCartOpen(true);
    };

    const removeFromCart = (id: number, variantId?: number) => {
        setCart((prevCart) => prevCart.filter((item) => !(item.id === id && item.variant?.id === variantId)));
    };

    const updateQuantity = (id: number, quantity: number, variantId?: number) => {
        if (quantity < 1) return;
        setCart((prevCart) =>
            prevCart.map((item) =>
                item.id === id && item.variant?.id === variantId ? { ...item, quantity } : item
            )
        );
    };

    const clearCart = () => {
        setCart([]);
    };

    const toggleCart = () => {
        setIsCartOpen(!isCartOpen);
    };

    const cartTotal = cart.reduce((total, item) => {
        const price = item.price || 0;
        return total + price * item.quantity;
    }, 0);

    const cartCount = cart.reduce((count, item) => count + item.quantity, 0);

    return (
        <CartContext.Provider
            value={{
                cart,
                addToCart,
                removeFromCart,
                updateQuantity,
                clearCart,
                cartTotal,
                cartCount,
                isCartOpen,
                toggleCart,
            }}
        >
            {children}
        </CartContext.Provider>
    );
};

export const useCart = () => {
    const context = useContext(CartContext);
    if (!context) {
        throw new Error("useCart must be used within a CartProvider");
    }
    return context;
};
