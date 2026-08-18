import React from 'react';
import { NavLink, useNavigate } from 'react-router-dom';
import { User, Heart, Package, LogOut } from 'lucide-react';
import { useAuth } from '../../context/AuthProvider';

const Sidebar = () => {
    const { logout } = useAuth();
    const navigate = useNavigate();

    const handleLogout = async () => {
        await logout();
        navigate('/login');
    };

    const navItems = [
        { path: '/profile', label: 'My Profile', icon: User },
        { path: '/wishlist', label: 'Wishlist', icon: Heart },
        { path: '/orders', label: 'My Orders', icon: Package },
    ];

    return (
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-4 h-fit">
            <nav className="space-y-1">
                {navItems.map((item) => (
                    <NavLink
                        key={item.path}
                        to={item.path}
                        className={({ isActive }) =>
                            `flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium transition-colors ${isActive
                                ? 'bg-primary/10 text-primary'
                                : 'text-gray-600 hover:bg-primary/5 hover:text-primary'
                            }`
                        }
                    >
                        <item.icon size={18} />
                        {item.label}
                    </NavLink>
                ))}

                <button
                    onClick={handleLogout}
                    className="w-full flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-red-600 hover:bg-red-50 transition-colors mt-4"
                >
                    <LogOut size={18} />
                    Logout
                </button>
            </nav>
        </div>
    );
};

export default Sidebar;
