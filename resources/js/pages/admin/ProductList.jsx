import React, { useEffect, useState } from "react";
import { Plus, Edit, Trash2, Search } from "lucide-react";
import { useNavigate } from "react-router-dom";
import api from "../../services/api";

const ProductList = () => {
    const navigate = useNavigate();
    const [products, setProducts] = useState([]);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState("");

    useEffect(() => {
        fetchProducts();
    }, [search]);

    const fetchProducts = async () => {
        setLoading(true);
        try {
            const { data } = await api.get("/admin/v1/products", {
                params: { search }
            });
            console.log(data)
            setProducts(data.data || []);
        } catch (e) {
            console.error(e);
        } finally {
            setLoading(false);
        }
    };

    const handleDelete = async (id) => {
        if (!window.confirm("Are you sure?")) return;
        await api.delete(`/admin/v1/products/${id}`);
        fetchProducts();
    };

    return (
        <div>
            <div className="flex justify-between items-center mb-6">
                <h1 className="text-2xl font-bold">Products</h1>
                <button
                    onClick={() => navigate("/admin/products/new")}
                    className="bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center gap-2"
                >
                    <Plus size={18} /> Add Product
                </button>
            </div>

            {/* Search */}
            <div className="mb-4 relative max-w-sm">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size={18} />
                <input
                    className="w-full pl-10 pr-4 py-2 border rounded-lg"
                    placeholder="Search products..."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                />
            </div>

            {/* Table */}
            <div className="bg-white rounded-xl shadow border">
                <table className="w-full">
                    <thead className="bg-gray-50 border-b">
                        <tr>
                            <th className="p-4 text-left">Product</th>
                            <th className="p-4">Price</th>
                            <th className="p-4">Stock</th>
                            <th className="p-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {loading ? (
                            <tr>
                                <td colSpan="4" className="p-6 text-center">Loading...</td>
                            </tr>
                        ) : products.map(p => (
                            <tr key={p.id} className="border-t hover:bg-gray-50">
                                <td className="p-4 font-medium">{p.name}</td>
                                <td className="p-4">৳ {p.base_price}</td>
                                <td className="p-4">{p.stock_quantity}</td>
                                <td className="p-4 text-right">
                                    <button
                                        onClick={() => navigate(`/admin/products/${p.id}/edit`)}
                                        className="text-blue-600 mr-3"
                                    >
                                        <Edit size={18} />
                                    </button>
                                    <button
                                        onClick={() => handleDelete(p.id)}
                                        className="text-red-600"
                                    >
                                        <Trash2 size={18} />
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
};

export default ProductList;
