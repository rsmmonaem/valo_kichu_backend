import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { ChevronRight, ChevronDown, X, Menu, Search } from 'lucide-react';
import clsx from 'clsx';

const MobileCategorySidebar = ({ isOpen, onClose, categories }) => {
    const [expandedCats, setExpandedCats] = useState({});

    const toggleExpand = (id) => {
        setExpandedCats(prev => ({
            ...prev,
            [id]: !prev[id]
        }));
    };

    return (
        <>
            {/* Overlay */}
            {isOpen && (
                <div
                    className="fixed inset-0 bg-black/50 z-[60] backdrop-blur-sm transition-opacity"
                    onClick={onClose}
                />
            )}

            {/* Sidebar */}
            <div className={clsx(
                "fixed top-0 left-0 h-full w-[80%] max-w-sm bg-white z-[70] shadow-2xl transition-transform duration-300 ease-in-out transform flex flex-col",
                isOpen ? "translate-x-0" : "-translate-x-full"
            )}>
                {/* Header */}
                <div className="p-4 border-b border-gray-100 flex items-center justify-between bg-primary/5">
                    <h2 className="font-bold text-lg flex items-center gap-2 text-primary">
                        <Menu size={20} /> Categories
                    </h2>
                    <button onClick={onClose} className="p-2 hover:bg-gray-100 rounded-full text-gray-500">
                        <X size={20} />
                    </button>
                </div>

                {/* Search */}
                <div className="p-4 border-b border-gray-100">
                    <form onSubmit={(e) => {
                        e.preventDefault();
                        const val = e.target.search.value;
                        if (val.trim()) {
                            window.location.href = `/products?search=${encodeURIComponent(val)}`;
                            onClose();
                        }
                    }}>
                        <div className="relative">
                            <input
                                name="search"
                                type="text"
                                placeholder="Search products..."
                                className="w-full pl-4 pr-10 py-2 border rounded-lg focus:ring-1 focus:ring-primary focus:outline-none text-sm"
                            />
                            <button type="submit" className="absolute right-2 top-2 text-gray-500 hover:text-primary">
                                <Search size={18} />
                            </button>
                        </div>
                    </form>
                </div>

                {/* Content */}
                <div className="flex-1 overflow-y-auto px-2 py-4">
                    <ul className="space-y-1">
                        <li className="px-2">
                            <Link
                                to="/products?category=all"
                                className="block py-3 px-4 font-bold text-gray-800 bg-gray-50 rounded-lg hover:bg-gray-100"
                                onClick={onClose}
                            >
                                All Products
                            </Link>
                        </li>
                        {categories.map(cat => (
                            <li key={cat.id} className="border-b border-gray-50 last:border-0">
                                <div className="flex items-center justify-between py-1">
                                    <Link
                                        to={`/products?category=${cat.slug || cat.id}`}
                                        className="flex-1 py-3 px-4 text-sm font-medium text-gray-700 hover:text-primary active:text-primary flex items-center gap-3"
                                        onClick={onClose}
                                    >
                                        <div className="w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center text-xs font-bold text-gray-400">
                                            {cat.image ? (
                                                <img src={cat.image?.startsWith('http') ? cat.image : `/storage/${cat.image}`} alt="" className="w-full h-full object-cover rounded-full" />
                                            ) : (
                                                cat.name.charAt(0)
                                            )}
                                        </div>
                                        {cat.name}
                                    </Link>

                                    {cat.subcategories && cat.subcategories.length > 0 && (
                                        <button
                                            onClick={() => toggleExpand(cat.id)}
                                            className="p-3 text-gray-400 hover:text-primary"
                                        >
                                            <ChevronDown
                                                size={16}
                                                className={clsx("transition-transform", expandedCats[cat.id] ? "rotate-180" : "")}
                                            />
                                        </button>
                                    )}
                                </div>

                                {/* Subcategories */}
                                {cat.subcategories && cat.subcategories.length > 0 && (
                                    <div className={clsx(
                                        "overflow-hidden transition-all duration-300 bg-gray-50/50 rounded-lg mx-2",
                                        expandedCats[cat.id] ? "max-h-[1000px] mb-2" : "max-h-0"
                                    )}>
                                        <ul className="py-1">
                                            {cat.subcategories.map(sub => (
                                                <li key={sub.id}>
                                                    <div className="flex items-center justify-between">
                                                        <Link
                                                            to={`/products?category=${sub.slug || sub.id}`}
                                                            className="flex-1 block py-2 px-4 pl-12 text-sm text-gray-600 hover:text-primary"
                                                            onClick={onClose}
                                                        >
                                                            {sub.name}
                                                        </Link>
                                                        {sub.subcategories && sub.subcategories.length > 0 && (
                                                            <button
                                                                onClick={() => toggleExpand(sub.id)}
                                                                className="p-2 pr-4 text-gray-400"
                                                            >
                                                                <ChevronDown
                                                                    size={14}
                                                                    className={clsx("transition-transform", expandedCats[sub.id] ? "rotate-180" : "")}
                                                                />
                                                            </button>
                                                        )}
                                                    </div>

                                                    {/* Level 3 */}
                                                    {sub.subcategories && sub.subcategories.length > 0 && (
                                                        <div className={clsx(
                                                            "overflow-hidden transition-all duration-300 bg-gray-100/50 mx-4 rounded",
                                                            expandedCats[sub.id] ? "max-h-[500px]" : "max-h-0"
                                                        )}>
                                                            {sub.subcategories.map(grandSub => (
                                                                <Link
                                                                    key={grandSub.id}
                                                                    to={`/products?category=${grandSub.slug || grandSub.id}`}
                                                                    className="block py-2 px-4 pl-8 text-xs text-gray-500 hover:text-primary"
                                                                    onClick={onClose}
                                                                >
                                                                    - {grandSub.name}
                                                                </Link>
                                                            ))}
                                                        </div>
                                                    )}
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                )}
                            </li>
                        ))}
                    </ul>
                </div>
            </div>
        </>
    );
};

export default MobileCategorySidebar;
