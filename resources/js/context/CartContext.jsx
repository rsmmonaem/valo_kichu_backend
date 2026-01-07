import React, { createContext, useContext, useState, useEffect } from 'react';
import toast from 'react-hot-toast';

const CartContext = createContext();

export const useCart = () => useContext(CartContext);

export const CartProvider = ({ children }) => {
    const [cart, setCart] = useState([]);
    const [isCartOpen, setIsCartOpen] = useState(false);

    // Load cart from local storage
    useEffect(() => {
        const savedCart = localStorage.getItem('safayat_cart');
        if (savedCart) {
            setCart(JSON.parse(savedCart));
        }
    }, []);

    // Save cart to local storage
    useEffect(() => {
        localStorage.setItem('safayat_cart', JSON.stringify(cart));
    }, [cart]);

    const addToCart = (product, quantity = 1, variation = null) => {
        setCart(prevCart => {
            const existingItemIndex = prevCart.findIndex(item =>
                item.id === product.id &&
                JSON.stringify(item.variation) === JSON.stringify(variation)
            );

            if (existingItemIndex > -1) {
                const newCart = [...prevCart];
                newCart[existingItemIndex].quantity += quantity;
                toast.success('Cart updated');
                return newCart;
            } else {
                toast.success('Added to cart');
                return [...prevCart, { ...product, quantity, variation }];
            }
        });
        setIsCartOpen(true);
    };

    const removeFromCart = (productId, variation = null) => {
        setCart(prevCart => prevCart.filter(item =>
            !(item.id === productId && JSON.stringify(item.variation) === JSON.stringify(variation))
        ));
        toast.error('Removed from cart');
    };

    const updateQuantity = (productId, quantity, variation = null) => {
        if (quantity < 1) return;
        setCart(prevCart => prevCart.map(item => {
            if (item.id === productId && JSON.stringify(item.variation) === JSON.stringify(variation)) {
                return { ...item, quantity };
            }
            return item;
        }));
    };

    const clearCart = () => {
        setCart([]);
        localStorage.removeItem('safayat_cart');
    };

    const cartTotal = cart.reduce((total, item) => {
        const price = item.sale_price || item.base_price;
        return total + (parseFloat(price) * item.quantity);
    }, 0);

    const cartCount = cart.reduce((count, item) => count + item.quantity, 0);

    return (
        <CartContext.Provider value={{
            cart,
            addToCart,
            removeFromCart,
            updateQuantity,
            clearCart,
            cartTotal,
            cartCount,
            isCartOpen,
            setIsCartOpen
        }}>
            {children}
        </CartContext.Provider>
    );
};
