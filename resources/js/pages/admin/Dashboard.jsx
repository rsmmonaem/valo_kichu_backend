import React from 'react';
import { DollarSign, ShoppingBag, Users, Package } from 'lucide-react';

const StatCard = ({ title, value, icon: Icon, color }) => (
    <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <div className="flex items-center justify-between">
            <div>
                <p className="text-sm text-gray-500 mb-1">{title}</p>
                <h3 className="text-2xl font-bold text-gray-800">{value}</h3>
            </div>
            <div className={`p-3 rounded-lg ${color}`}>
                <Icon size={24} className="text-white" />
            </div>
        </div>
    </div>
);

const Dashboard = () => {
    const [data, setData] = React.useState(null);
    const [loading, setLoading] = React.useState(true);
    const [error, setError] = React.useState(null);

    React.useEffect(() => {
        const fetchStats = async () => {
            try {
                const response = await api.get('/admin/dashboard/stats');
                setData(response.data);
            } catch (err) {
                setError('Failed to load dashboard data');
            } finally {
                setLoading(false);
            }
        };
        fetchStats();
    }, []);

    if (loading) return <div className="p-8 text-center">Loading dashboard...</div>;
    if (error) return <div className="p-8 text-center text-red-500">{error}</div>;

    return (
        <div className="space-y-6">
            <h1 className="text-2xl font-bold text-gray-800">Dashboard Overview</h1>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <StatCard
                    title="Total Revenue"
                    value={`৳ ${data.stats.revenue.toLocaleString()}`}
                    icon={DollarSign}
                    color="bg-emerald-500"
                />
                <StatCard
                    title="Total Orders"
                    value={data.stats.orders.toLocaleString()}
                    icon={ShoppingBag}
                    color="bg-blue-500"
                />
                <StatCard
                    title="Customers"
                    value={data.stats.customers.toLocaleString()}
                    icon={Users}
                    color="bg-indigo-500"
                />
                <StatCard
                    title="Products"
                    value={data.stats.products.toLocaleString()}
                    icon={Package}
                    color="bg-orange-500"
                />
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <h3 className="font-bold text-gray-800 mb-4">Recent Orders</h3>
                    <div className="space-y-4">
                        {data.recent_orders.map(order => (
                            <div key={order.id} className="flex items-center justify-between pb-4 border-b last:border-0 last:pb-0">
                                <div>
                                    <p className="font-medium text-gray-800">Order #ORD-{order.id}</p>
                                    <p className="text-xs text-gray-500">{new Date(order.created_at).toLocaleDateString()} - {order.user?.name}</p>
                                </div>
                                <span className={clsx(
                                    "px-2 py-1 rounded text-xs capitalize",
                                    order.status === 'completed' ? "bg-green-100 text-green-700" :
                                        order.status === 'pending' ? "bg-yellow-100 text-yellow-700" : "bg-gray-100 text-gray-700"
                                )}>
                                    {order.status}
                                </span>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <h3 className="font-bold text-gray-800 mb-4">Trending Products</h3>
                    <div className="space-y-4">
                        {data.trending_products.length > 0 ? (
                            data.trending_products.map((item, index) => (
                                <div key={index} className="flex items-center gap-4 pb-4 border-b last:border-0 last:pb-0">
                                    <div className="w-10 h-10 rounded bg-gray-100 overflow-hidden">
                                        {item.image && <img src={item.image} alt={item.name} className="w-full h-full object-cover" />}
                                    </div>
                                    <div className="flex-1">
                                        <p className="text-sm font-medium text-gray-800 truncate">{item.name}</p>
                                        <p className="text-xs text-gray-500">{item.total_sold} units sold</p>
                                    </div>
                                </div>
                            ))
                        ) : (
                            <p className="text-gray-500 text-sm">No sales data yet.</p>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
};

import clsx from 'clsx';
import api from '../../services/api';

export default Dashboard;
