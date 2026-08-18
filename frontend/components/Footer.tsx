import Link from 'next/link';
import { Phone, Facebook, Youtube, Instagram, Twitter } from 'lucide-react';

export default function Footer() {
    return (
        <footer className="bg-white border-t border-gray-200 pt-16 pb-8 mb-16 md:mb-0">
            <div className="container mx-auto px-4">
                <div className="grid grid-cols-1 md:grid-cols-4 gap-8 mb-12">
                    {/* Company Info */}
                    <div className="space-y-4">
                        <Link href="/" className="flex items-center gap-2">
                            <div className="bg-blue-600 text-white p-1.5 rounded font-bold text-lg">V</div>
                            <span className="text-xl font-bold text-gray-800">
                                Valokichu
                            </span>
                        </Link>
                        <p className="text-sm text-gray-500 leading-relaxed">
                            Premium wholesale marketplace connecting you directly with best manufacturers. Quality products, factory prices.
                        </p>
                        <div className="space-y-2 pt-2">
                            <div className="flex items-center gap-3 text-sm text-gray-600">
                                <Phone size={16} className="text-blue-600" />
                                <span>+8801943-707070</span>
                            </div>
                            <div className="flex items-center gap-3 text-sm text-gray-600">
                                <span className="w-4 flex justify-center text-blue-600">@</span>
                                <span>support@valokichu.com</span>
                            </div>
                        </div>
                    </div>

                    {/* Customer Service */}
                    <div>
                        <h4 className="font-bold text-gray-800 mb-6">Customer Service</h4>
                        <ul className="space-y-3 text-sm text-gray-500">
                            <li><Link href="/help" className="hover:text-blue-600 transition-colors">Help Center</Link></li>
                            <li><Link href="/track" className="hover:text-blue-600 transition-colors">Track Order</Link></li>
                            <li><Link href="/returns" className="hover:text-blue-600 transition-colors">Returns & Refunds</Link></li>
                            <li><Link href="/shipping" className="hover:text-blue-600 transition-colors">Shipping Info</Link></li>
                            <li><Link href="/contact" className="hover:text-blue-600 transition-colors">Contact Us</Link></li>
                        </ul>
                    </div>

                    {/* Quick Links */}
                    <div>
                        <h4 className="font-bold text-gray-800 mb-6">Quick Links</h4>
                        <ul className="space-y-3 text-sm text-gray-500">
                            <li><Link href="/about" className="hover:text-blue-600 transition-colors">About Us</Link></li>
                            <li><Link href="/careers" className="hover:text-blue-600 transition-colors">Careers</Link></li>
                            <li><Link href="/privacy" className="hover:text-blue-600 transition-colors">Privacy Policy</Link></li>
                            <li><Link href="/terms" className="hover:text-blue-600 transition-colors">Terms & Conditions</Link></li>
                            <li><Link href="/sitemap" className="hover:text-blue-600 transition-colors">Sitemap</Link></li>
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
                    <p>© 2026 Valokichu Commerce. All rights reserved.</p>
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
    );
}
