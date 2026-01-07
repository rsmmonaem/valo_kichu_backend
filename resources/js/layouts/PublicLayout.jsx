import React, { useState, useEffect } from 'react';
import { Outlet, Link, useNavigate } from 'react-router-dom';
import { ShoppingCart, User, Search, Menu, Phone, Heart, Camera, Facebook, Youtube, Instagram, Twitter } from 'lucide-react';
import { useAuth } from '../context/AuthProvider';
import { useCart } from '../context/CartContext';
import toast from 'react-hot-toast';
import api from '../services/api';

const PublicLayout = () => {
    const { user, logout } = useAuth();
    const { cartCount } = useCart();
    const navigate = useNavigate();
    const [search, setSearch] = useState('');
    const [settings, setSettings] = useState({});

    useEffect(() => {
        const fetchSettings = async () => {
            try {
                // Fetch from the correct config endpoint
                const res = await api.get('/v1/config/app-config');

                // The response is already an object, no need to convert array
                const config = res.data;
                setSettings(config);

                // Apply dynamic colors if they exist
                if (config.primary_color) {
                    document.documentElement.style.setProperty('--primary-color', config.primary_color);
                }
            } catch (error) {
                console.error("Failed to fetch settings", error);
            }
        };
        fetchSettings();
    }, []);

    const handleLogout = async () => {
        await logout();
        navigate('/login');
        toast.success('Logged out successfully');
    };

    const categories = [
        { name: 'Bags', icon: '👜' },
        { name: 'Shoes', icon: '👠' },
        { name: 'Jewelry', icon: '💍' },
        { name: 'Beauty', icon: '💄' },
        { name: 'Mens', icon: '👔' },
        { name: 'Womens', icon: '👗' },
        { name: 'Baby', icon: '👶' },
        { name: 'Watches', icon: '⌚' },
        { name: 'Gadgets', icon: '🎧' },
        { name: 'Home', icon: '🏠' },
    ];

    return (
        <div className="min-h-screen bg-gray-50 flex flex-col font-sans text-gray-800">
            {/* Top Bar */}
            <div className="bg-gray-100 text-xs text-gray-500 py-1 border-b border-gray-200">
                <div className="container mx-auto px-4 flex justify-between items-center">
                    <div className="flex gap-4">
                        <span>Language: English</span>
                        <span>Currency: BDT (৳)</span>
                    </div>
                    <div className="flex gap-4">
                        <Link to="/seller" className="hover:text-primary">Become a Seller</Link>
                        <Link to="/help" className="hover:text-primary">Help Center</Link>
                        <Link to="/app" className="hover:text-primary">Download App</Link>
                    </div>
                </div>
            </div>

            {/* Main Header (Sticky) */}
            <header className="sticky top-0 z-50 bg-white shadow-sm border-b border-gray-100">
                <div className="container mx-auto px-4 py-4">
                    <div className="flex items-center justify-between gap-4 md:gap-8">
                        {/* Logo */}
                        <Link to="/" className="flex items-center gap-2 flex-shrink-0 group">
                            {settings.site_logo ? (
                                <img src={settings.site_logo.startsWith('http') ? settings.site_logo : `/storage/${settings.site_logo}`} alt="Logo" className="h-10 w-auto group-hover:scale-105 transition-transform" />
                            ) : (
                                <>
                                    <div className="bg-primary text-white p-2 rounded-lg font-bold text-xl group-hover:scale-105 transition-transform duration-200">
                                        S
                                    </div>
                                    <span className="text-2xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-primary to-primary">Safayat</span>
                                </>
                            )}
                        </Link>

                        {/* Search Bar */}
                        <div className="flex-1 max-w-2xl relative hidden md:block group focus-within:ring-2 focus-within:ring-primary/20 rounded-full transition-all">
                            <input
                                type="text"
                                placeholder="Search products by keyword or image..."
                                className="w-full pl-6 pr-14 py-2.5 border-2 border-primary rounded-full focus:outline-none text-sm placeholder-gray-400"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                            <button className="absolute right-14 top-1/2 -translate-y-1/2 text-gray-400 hover:text-primary transition-colors p-1">
                                <Camera size={20} />
                            </button>
                            <button className="absolute right-1 top-1 bottom-1 bg-primary text-white px-6 rounded-full hover:opacity-90 transition-colors flex items-center justify-center">
                                <Search size={20} />
                            </button>
                        </div>

                        {/* Actions */}
                        <div className="flex items-center gap-6 flex-shrink-0">
                            <Link to="/wishlist" className="relative text-gray-600 hover:text-primary transition-colors flex flex-col items-center gap-0.5 group">
                                <div className="relative">
                                    <Heart size={24} className="group-hover:fill-primary/10 transition-colors" />
                                    <span className="absolute -top-1 -right-1 bg-primary text-white text-[10px] w-4 h-4 flex items-center justify-center rounded-full">0</span>
                                </div>
                                <span className="text-[10px] font-medium">Wishlist</span>
                            </Link>

                            <Link to="/cart" className="relative text-gray-600 hover:text-primary transition-colors flex flex-col items-center gap-0.5 group">
                                <div className="relative">
                                    <ShoppingCart size={24} className="group-hover:fill-primary/10 transition-colors" />
                                    {cartCount > 0 && (
                                        <span className="absolute -top-1 -right-1 bg-primary text-white text-[10px] w-4 h-4 flex items-center justify-center rounded-full">{cartCount}</span>
                                    )}
                                </div>
                                <span className="text-[10px] font-medium">Cart</span>
                            </Link>

                            {user ? (
                                <div className="relative group flex flex-col items-center gap-0.5 cursor-pointer">
                                    <div className="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center text-primary border border-primary/20">
                                        <User size={18} />
                                    </div>
                                    <span className="text-[10px] font-medium text-gray-900 max-w-[60px] truncate">{user.name}</span>

                                    {/* Dropdown */}
                                    <div className="absolute top-full right-0 w-48 bg-white rounded-xl shadow-lg border border-gray-100 py-1 hidden group-hover:block animate-in fade-in slide-in-from-top-1">
                                        <div className="px-4 py-2 border-b border-gray-50 mb-1">
                                            <p className="text-xs text-gray-500">Signed in as</p>
                                            <p className="text-sm font-bold text-gray-800 truncate">{user.email}</p>
                                        </div>
                                        {['super_admin', 'child_admin'].includes(user.role) && (
                                            <Link to="/admin/dashboard" className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-primary">Admin Dashboard</Link>
                                        )}
                                        <Link to="/orders" className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-primary">My Orders</Link>
                                        <Link to="/profile" className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-primary">Profile</Link>
                                        <div className="border-t border-gray-50 mt-1 pt-1">
                                            <button onClick={handleLogout} className="w-full text-left px-4 py-2 text-sm text-primary hover:bg-primary/10">Sign Out</button>
                                        </div>
                                    </div>
                                </div>
                            ) : (
                                <Link to="/login" className="flex flex-col items-center gap-0.5 text-gray-600 hover:text-primary transition-colors">
                                    <User size={24} />
                                    <span className="text-[10px] font-medium">Login</span>
                                </Link>
                            )}
                        </div>
                    </div>
                </div>

                {/* Categories Bar */}
                <div className="border-t border-gray-100 bg-white">
                    <div className="container mx-auto px-4">
                        <div className="flex items-center gap-6 py-2 text-sm font-medium text-gray-700 overflow-x-auto no-scrollbar">
                            <div className="flex items-center gap-2 bg-gray-900 text-white px-4 py-2 rounded-md cursor-pointer flex-shrink-0 hover:bg-gray-800 transition-colors">
                                <Menu size={18} />
                                <span>All Categories</span>
                            </div>
                            {categories.map((cat) => (
                                <Link
                                    key={cat.name}
                                    to={`/products?category=${cat.name.toLowerCase()}`}
                                    className="hover:text-primary whitespace-nowrap flex-shrink-0 flex items-center gap-1.5 px-2 py-1 hover:bg-primary/10 rounded-md transition-colors"
                                >
                                    <span>{cat.icon}</span>
                                    <span>{cat.name}</span>
                                </Link>
                            ))}
                        </div>
                    </div>
                </div>
            </header>

            {/* Main Content */}
            <main className="flex-grow">
                <Outlet />
            </main>

            {/* Footer */}
            <footer className="bg-white border-t border-gray-200 pt-16 pb-8">
                <div className="container mx-auto px-4">
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-8 mb-12">
                        {/* Company Info */}
                        <div className="space-y-4">
                            <Link to="/" className="flex items-center gap-2">
                                {settings.site_logo ? (
                                    <img src={settings.site_logo.startsWith('http') ? settings.site_logo : `/storage/${settings.site_logo}`} alt="Logo" className="h-8 w-auto" />
                                ) : (
                                    <>
                                        <div className="bg-primary text-white p-1.5 rounded font-bold text-lg">S</div>
                                        <span className="text-xl font-bold text-gray-800">Safayat</span>
                                    </>
                                )}
                            </Link>
                            <p className="text-sm text-gray-500 leading-relaxed">
                                Premium wholesale marketplace connecting you directly with best manufacturers. Quality products, factory prices.
                            </p>
                            <div className="space-y-2 pt-2">
                                <div className="flex items-center gap-3 text-sm text-gray-600">
                                    <Phone size={16} className="text-primary" />
                                    <span>+880 1700-811396</span>
                                </div>
                                <div className="flex items-center gap-3 text-sm text-gray-600">
                                    <span className="w-4 flex justify-center text-primary">@</span>
                                    <span>support@safayat.com</span>
                                </div>
                            </div>
                        </div>

                        {/* Customer Service */}
                        <div>
                            <h4 className="font-bold text-gray-800 mb-6">Customer Service</h4>
                            <ul className="space-y-3 text-sm text-gray-500">
                                <li><Link to="/help" className="hover:text-primary transition-colors">Help Center</Link></li>
                                <li><Link to="/track" className="hover:text-primary transition-colors">Track Order</Link></li>
                                <li><Link to="/returns" className="hover:text-primary transition-colors">Returns & Refunds</Link></li>
                                <li><Link to="/shipping" className="hover:text-primary transition-colors">Shipping Info</Link></li>
                                <li><Link to="/contact" className="hover:text-primary transition-colors">Contact Us</Link></li>
                            </ul>
                        </div>

                        {/* Quick Links */}
                        <div>
                            <h4 className="font-bold text-gray-800 mb-6">Quick Links</h4>
                            <ul className="space-y-3 text-sm text-gray-500">
                                <li><Link to="/about" className="hover:text-primary transition-colors">About Us</Link></li>
                                <li><Link to="/careers" className="hover:text-primary transition-colors">Careers</Link></li>
                                <li><Link to="/privacy" className="hover:text-primary transition-colors">Privacy Policy</Link></li>
                                <li><Link to="/terms" className="hover:text-primary transition-colors">Terms & Conditions</Link></li>
                                <li><Link to="/sitemap" className="hover:text-primary transition-colors">Sitemap</Link></li>
                            </ul>
                        </div>

                        {/* App & Social */}
                        <div>
                            <h4 className="font-bold text-gray-800 mb-6">Download App</h4>
                            <div className="space-y-4">
                                <button className="bg-gray-900 text-white px-6 py-3 rounded-lg flex items-center gap-3 hover:bg-gray-800 transition w-full md:w-auto">
                                    <div className="text-2xl">►</div>
                                    <div className="text-left">
                                        <div className="text-[10px] uppercase tracking-wider">Get it on</div>
                                        <div className="text-sm font-bold">Google Play</div>
                                    </div>
                                </button>

                                <div className="pt-4">
                                    <h5 className="font-bold text-gray-800 mb-3 text-sm">Follow Us</h5>
                                    <div className="flex gap-4">
                                        <a href="#" className="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center hover:scale-110 transition-transform"><Facebook size={18} /></a>
                                        <a href="#" className="w-10 h-10 bg-red-600 text-white rounded-full flex items-center justify-center hover:scale-110 transition-transform"><Youtube size={18} /></a>
                                        <a href="#" className="w-10 h-10 bg-pink-600 text-white rounded-full flex items-center justify-center hover:scale-110 transition-transform"><Instagram size={18} /></a>
                                        <a href="#" className="w-10 h-10 bg-sky-500 text-white rounded-full flex items-center justify-center hover:scale-110 transition-transform"><Twitter size={18} /></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="border-t border-gray-100 pt-8 flex flex-col md:flex-row justify-between items-center gap-4 text-sm text-gray-500">
                        <p>© 2025 Safayat Commerce. All rights reserved.</p>
                        <div className="flex items-center gap-6">
                            <span>We Accept:</span>
                            <div className="flex items-center gap-2">
                                <div className="h-6 w-10 bg-pink-600 rounded text-[8px] text-white flex items-center justify-center font-bold">bKash</div>
                                <div className="h-6 w-10 bg-orange-600 rounded text-[8px] text-white flex items-center justify-center font-bold">Nagad</div>
                                <div className="h-6 w-10 bg-blue-800 rounded text-[8px] text-white flex items-center justify-center font-bold">VISA</div>
                            </div>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    );
};

export default PublicLayout;
