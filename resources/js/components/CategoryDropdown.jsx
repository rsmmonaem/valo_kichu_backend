import React from 'react';
import { Link } from 'react-router-dom';
import { ChevronRight } from 'lucide-react';

const CategoryDropdown = ({ categories }) => {
    return (
        <div className="absolute top-full left-0 w-64 bg-white shadow-xl border border-gray-100 rounded-b-xl py-2 hidden group-hover:block z-50 animate-in fade-in slide-in-from-top-2">
            <ul>
                {categories.map((cat) => (
                    <li key={cat.id} className="group/item relative">
                        <Link
                            to={`/products?category=${cat.slug || cat.id}`}
                            className="flex items-center justify-between px-6 py-2.5 hover:bg-gray-50 hover:text-primary text-sm font-medium text-gray-700 transition"
                        >
                            <span className="flex items-center gap-2">
                                {/* {cat.icon && <span className="text-base">{cat.icon}</span>} */}
                                {cat.name}
                            </span>
                            {cat.subcategories && cat.subcategories.length > 0 && (
                                <ChevronRight size={14} className="text-gray-400 group-hover/item:text-primary" />
                            )}
                        </Link>

                        {/* Subcategories (Recursive or Level 2) */}
                        {cat.subcategories && cat.subcategories.length > 0 && (
                            <div className="absolute top-0 left-full w-60 bg-white shadow-xl border border-gray-100 rounded-xl py-2 hidden group-hover/item:block -ml-2">
                                <ul>
                                    {cat.subcategories.map(sub => (
                                        <li key={sub.id} className="group/sub relative">
                                            <Link
                                                to={`/products?category=${sub.slug || sub.id}`}
                                                className="flex items-center justify-between px-6 py-2.5 hover:bg-gray-50 hover:text-primary text-sm text-gray-600 transition"
                                            >
                                                <span>{sub.name}</span>
                                                {sub.subcategories && sub.subcategories.length > 0 && (
                                                    <ChevronRight size={14} className="text-gray-400" />
                                                )}
                                            </Link>

                                            {/* Level 3 Subcategories */}
                                            {sub.subcategories && sub.subcategories.length > 0 && (
                                                <div className="absolute top-0 left-full w-56 bg-white shadow-xl border border-gray-100 rounded-xl py-2 hidden group-hover/sub:block -ml-2">
                                                    <ul>
                                                        {sub.subcategories.map(grandSub => (
                                                            <li key={grandSub.id}>
                                                                <Link
                                                                    to={`/products?category=${grandSub.slug || grandSub.id}`}
                                                                    className="block px-6 py-2.5 hover:bg-gray-50 hover:text-primary text-sm text-gray-500 transition"
                                                                >
                                                                    {grandSub.name}
                                                                </Link>
                                                            </li>
                                                        ))}
                                                    </ul>
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
    );
};

export default CategoryDropdown;
