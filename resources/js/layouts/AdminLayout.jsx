import React, { useState, useEffect } from "react";
import { Link, Outlet, useNavigate, useLocation } from "react-router-dom";
import { useAuth } from "../context/AuthProvider";
import {
    LayoutDashboard,
    Package,
    ShoppingCart,
    Settings,
    LogOut,
    Menu,
    X,
    Tag,
    Image,
    ChevronDown,
} from "lucide-react";
import clsx from "clsx";

const AdminLayout = () => {
    const { user, logout } = useAuth();
    const navigate = useNavigate();
    const location = useLocation();

    const [isSidebarOpen, setIsSidebarOpen] = useState(true);
    const [openMenus, setOpenMenus] = useState({});
    const [activeMenu, setActiveMenu] = useState(null);

    const handleLogout = async () => {
        await logout();
        navigate("/login");
    };

    const toggleSubMenu = (name) => {
        setOpenMenus((prev) => ({
            ...prev,
            [name]: !prev[name],
        }));
    };

    // Auto-select parent menu when route changes (sub-category click / refresh)
    useEffect(() => {
        const active = navItems.find((item) =>
            location.pathname.startsWith(item.path)
        );
        if (active) {
            setActiveMenu(active.name);
            if (active.subItems) {
                setOpenMenus((prev) => ({
                    ...prev,
                    [active.name]: true,
                }));
            }
        }
    }, [location.pathname]);

    const navItems = [
        { name: "Dashboard", path: "/admin/dashboard", icon: LayoutDashboard },
        {
            name: "Products Manage",
            path: "/admin/products",
            icon: Package,
            subItems: [
                "Product List",
                "Add new Product",
                "Bulk import",
            ],
        },
        { name: "Orders", path: "/admin/orders", icon: ShoppingCart },
        { name: "Brands", path: "/admin/brands", icon: Tag },
        {
            name: "Categories",
            path: "/admin/categories",
            icon: Menu,
            subItems: [
                "Categories",
                "Sub Categories",
                "Sub sub categories",
            ],
        },
        { name: "Banners", path: "/admin/banners", icon: Image },
        { name: "Appearance", path: "/admin/appearance", icon: Settings },
    ];

    return (
        <div className="flex h-screen bg-gray-100">
            {/* Sidebar */}
            <aside
                className={clsx(
                    "bg-slate-900 text-white transition-all duration-300 flex flex-col",
                    isSidebarOpen ? "w-64" : "w-20"
                )}
            >
                <div className="p-4 flex items-center justify-between border-b border-slate-700">
                    <span
                        className={clsx(
                            "font-bold text-xl",
                            !isSidebarOpen && "hidden"
                        )}
                    >
                        ValokichuAdmin
                    </span>
                    <button
                        onClick={() => setIsSidebarOpen(!isSidebarOpen)}
                        className="p-1 hover:bg-slate-800 rounded"
                    >
                        {isSidebarOpen ? <X size={20} /> : <Menu size={20} />}
                    </button>
                </div>

                <nav className="flex-1 p-4 space-y-2 overflow-y-auto">
                    {navItems.map((item) => {
                        const hasSubItems =
                            item.subItems && item.subItems.length > 0;
                        const isOpen = openMenus[item.name];

                        const isActive =
                            location.pathname.startsWith(item.path) ||
                            activeMenu === item.name;

                        return (
                            <div key={item.name}>
                                {/* Main Item */}
                                <div
                                    onClick={() => {
                                        if (hasSubItems) {
                                            toggleSubMenu(item.name);
                                            setActiveMenu(item.name);
                                        }
                                    }}
                                    className={clsx(
                                        "flex items-center justify-between p-3 rounded-lg transition-colors cursor-pointer",
                                        isActive
                                            ? "bg-blue-600 text-white"
                                            : "text-slate-300 hover:bg-slate-800 hover:text-white"
                                    )}
                                >
                                    {hasSubItems ? (
                                        <div className="flex items-center gap-3 flex-1">
                                            <item.icon size={20} />
                                            <span
                                                className={clsx(
                                                    !isSidebarOpen && "hidden"
                                                )}
                                            >
                                                {item.name}
                                            </span>
                                        </div>
                                    ) : (
                                        <Link
                                            to={item.path}
                                            onClick={() =>
                                                setActiveMenu(item.name)
                                            }
                                            className="flex items-center gap-3 flex-1"
                                        >
                                            <item.icon size={20} />
                                            <span
                                                className={clsx(
                                                    !isSidebarOpen && "hidden"
                                                )}
                                            >
                                                {item.name}
                                            </span>
                                        </Link>
                                    )}

                                    {hasSubItems && isSidebarOpen && (
                                        <ChevronDown
                                            size={16}
                                            className={clsx(
                                                "transition-transform",
                                                isOpen && "rotate-180"
                                            )}
                                        />
                                    )}
                                </div>

                                {/* Subcategories */}
                                {hasSubItems && isOpen && isSidebarOpen && (
                                    <div className="ml-9 mt-1 space-y-1">
                                        {item.subItems.map((sub, index) =>
                                            index === 0 ? (
                                                <Link
                                                    key={sub}
                                                    to={`${item.path}`}
                                                    className="block p-2 text-sm text-slate-400 hover:text-white hover:bg-slate-800 rounded"
                                                >
                                                    {sub}
                                                </Link>
                                            ) : (
                                                <Link
                                                    key={sub}
                                                    to={`${item.path}/${sub
                                                        .toLowerCase()
                                                        .replace(/\s+/g, "-")}`}
                                                    className="block p-2 text-sm text-slate-400 hover:text-white hover:bg-slate-800 rounded"
                                                >
                                                    {sub}
                                                </Link>
                                            )
                                        )}
                                    </div>
                                )}
                            </div>
                        );
                    })}
                </nav>

                <div className="p-4 border-t border-slate-700">
                    <button
                        onClick={handleLogout}
                        className="flex items-center gap-3 p-3 w-full rounded-lg text-red-400 hover:bg-slate-800 transition-colors"
                    >
                        <LogOut size={20} />
                        <span className={clsx(!isSidebarOpen && "hidden")}>
                            Logout
                        </span>
                    </button>
                </div>
            </aside>

            {/* Main Content */}
            <main className="flex-1 flex flex-col overflow-hidden">
                <header className="bg-white shadow h-16 flex items-center justify-between px-6">
                    <h2 className="text-xl font-semibold text-gray-800">
                        {navItems.find((i) =>
                            location.pathname.startsWith(i.path)
                        )?.name ||
                            activeMenu ||
                            "Admin Panel"}
                    </h2>
                    <span className="text-sm text-gray-600">
                        Welcome, {user?.name}
                    </span>
                </header>

                <div className="flex-1 overflow-auto p-6">
                    <Outlet />
                </div>
            </main>
        </div>
    );
};

export default AdminLayout;