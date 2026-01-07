import React, { useEffect, useState } from "react";
import api from "../../services/api";
import toast from "react-hot-toast";
import Sidebar from "../../components/customer/Sidebar";
import { User, Mail, Phone, MapPin, Calendar, ShieldCheck, Camera, X } from "lucide-react";
import { useAuth } from "../../context/AuthProvider";

const Profile = () => {
    // We can assume useAuth might have some user data, but fetching fresh data is safer for a profile page
    // const { user: authUser } = useAuth(); 

    const [user, setUser] = useState(null);
    const [loading, setLoading] = useState(true);
    const [isEditing, setIsEditing] = useState(false);
    const [formData, setFormData] = useState({
        first_name: '',
        last_name: '',
        phone_number: '',
        image: null,
        current_password: '',
        new_password: '',
        new_password_confirmation: ''
    });
    const [imagePreview, setImagePreview] = useState(null);

    useEffect(() => {
        fetchProfile();
    }, []);

    const fetchProfile = async () => {
        try {
            const res = await api.get("/v1/auth/user");
            const userData = res.data.data || res.data;
            setUser(userData);
            setFormData(prev => ({
                ...prev,
                first_name: userData.first_name || '',
                last_name: userData.last_name || '',
                phone_number: userData.phone_number || '',
            }));
        } catch (error) {
            console.error("Profile fetch error:", error);
            toast.error("Failed to load profile");
        } finally {
            setLoading(false);
        }
    };

    const handleInputChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({ ...prev, [name]: value }));
    };

    const handleImageChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            setFormData(prev => ({ ...prev, image: file }));
            setImagePreview(URL.createObjectURL(file));
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        const data = new FormData();
        data.append('first_name', formData.first_name);
        data.append('last_name', formData.last_name);
        data.append('phone_number', formData.phone_number);

        if (formData.image) {
            data.append('image', formData.image);
        }

        if (formData.current_password && formData.new_password) {
            data.append('current_password', formData.current_password);
            data.append('password', formData.new_password);
            data.append('password_confirmation', formData.new_password_confirmation);
        }

        // Use _method PUT for Laravel multipart/form-data support if needed, but POST /update-profile usually works
        // data.append('_method', 'PUT'); 

        try {
            const res = await api.post("/v1/auth/user", data, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            toast.success("Profile updated successfully");
            setUser(res.data.data || res.data); // Update local user state
            setIsEditing(false);
            // Optional: clear password fields
            setFormData(prev => ({
                ...prev,
                current_password: '',
                new_password: '',
                new_password_confirmation: '',
                image: null // Reset image file input
            }));
            setImagePreview(null);
        } catch (error) {
            console.error("Update failed:", error);
            const msg = error.response?.data?.message || "Failed to update profile";
            toast.error(msg);
        }
    };

    return (
        <div className="max-w-7xl mx-auto px-4 py-8">
            <div className="flex flex-col md:flex-row gap-8">
                {/* Sidebar */}
                <div className="w-full md:w-64 shrink-0">
                    <Sidebar />
                </div>

                {/* Main Content */}
                <div className="flex-1">
                    {loading ? (
                        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-8 animate-pulse text-center">
                            Loading profile...
                        </div>
                    ) : !user ? (
                        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center text-red-500">
                            Failed to load user data.
                        </div>
                    ) : (
                        <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden relative">
                            {/* Header Banner */}
                            <div className="h-32 bg-gradient-to-r from-blue-600 to-blue-400"></div>

                            <div className="px-8 pb-8">
                                <div className="relative flex items-end justify-between -mt-12 mb-6">
                                    <div className="flex items-end gap-6">
                                        <div className="relative group">
                                            <img
                                                src={imagePreview || user.image || `https://ui-avatars.com/api/?name=${user.first_name}+${user.last_name}&background=random`}
                                                alt="Profile"
                                                className="w-24 h-24 rounded-full object-cover border-4 border-white shadow-md bg-white"
                                            />
                                            {isEditing && (
                                                <label className="absolute inset-0 flex items-center justify-center bg-black/50 rounded-full cursor-pointer opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <Camera className="text-white" size={24} />
                                                    <input type="file" className="hidden" accept="image/*" onChange={handleImageChange} />
                                                </label>
                                            )}
                                        </div>
                                        <div className="mb-1">
                                            <h2 className="text-2xl font-bold text-gray-900">
                                                {user.first_name} {user.last_name}
                                            </h2>
                                            <div className="flex items-center gap-2 text-sm text-gray-500">
                                                <span className="capitalize">{user.role || 'Customer'}</span>
                                                {user.is_verified && (
                                                    <span className="flex items-center gap-1 text-green-600 bg-green-50 px-2 py-0.5 rounded-full text-xs font-medium border border-green-100">
                                                        <ShieldCheck size={12} /> Verified
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                    <button
                                        onClick={() => setIsEditing(!isEditing)}
                                        className={`px-4 py-2 text-sm font-medium rounded-lg transition-colors ${isEditing ? 'bg-red-50 text-red-600 hover:bg-red-100' : 'bg-gray-900 text-white hover:bg-gray-800'}`}
                                    >
                                        {isEditing ? 'Cancel Edit' : 'Edit Profile'}
                                    </button>
                                </div>

                                {isEditing ? (
                                    <form onSubmit={handleSubmit} className="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                                            <input type="text" name="first_name" value={formData.first_name} onChange={handleInputChange} className="w-full p-2 border rounded-lg" required />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                                            <input type="text" name="last_name" value={formData.last_name} onChange={handleInputChange} className="w-full p-2 border rounded-lg" required />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                            <input type="text" name="phone_number" value={formData.phone_number} onChange={handleInputChange} className="w-full p-2 border rounded-lg" required />
                                        </div>

                                        <div className="md:col-span-2 border-t pt-4 mt-2">
                                            <h3 className="font-semibold text-gray-900 mb-4">Change Password</h3>
                                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                <div>
                                                    <label className="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                                                    <input type="password" name="current_password" value={formData.current_password} onChange={handleInputChange} className="w-full p-2 border rounded-lg" />
                                                </div>
                                                <div>
                                                    <label className="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                                                    <input type="password" name="new_password" value={formData.new_password} onChange={handleInputChange} className="w-full p-2 border rounded-lg" />
                                                </div>
                                                <div>
                                                    <label className="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                                                    <input type="password" name="new_password_confirmation" value={formData.new_password_confirmation} onChange={handleInputChange} className="w-full p-2 border rounded-lg" />
                                                </div>
                                            </div>
                                        </div>

                                        <div className="md:col-span-2 flex justify-end gap-3 mt-4">
                                            <button type="button" onClick={() => setIsEditing(false)} className="px-5 py-2 text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200">Cancel</button>
                                            <button type="submit" className="px-5 py-2 text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-lg shadow-blue-200">Save Changes</button>
                                        </div>
                                    </form>
                                ) : (
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <InfoItem icon={Mail} label="Email Address" value={user.email} />
                                        <InfoItem icon={Phone} label="Phone Number" value={user.phone_number} />
                                        <InfoItem icon={User} label="Gender" value={user.gender} />
                                        <InfoItem icon={Calendar} label="Date of Birth" value={user.date_of_birth} />
                                        <div className="md:col-span-2">
                                            <InfoItem icon={MapPin} label="Address" value={user.address} />
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
};

const InfoItem = ({ icon: Icon, label, value }) => (
    <div className="p-4 rounded-xl bg-gray-50 border border-gray-100 group hover:border-blue-100 hover:bg-blue-50/50 transition-colors">
        <div className="flex items-center gap-3 mb-1">
            <div className="p-1.5 rounded-lg bg-white text-gray-400 group-hover:text-blue-500 shadow-sm border border-gray-100">
                <Icon size={16} />
            </div>
            <span className="text-xs font-medium text-gray-500 uppercase tracking-wider">{label}</span>
        </div>
        <p className="pl-11 font-medium text-gray-900">{value || "Not set"}</p>
    </div>
)

export default Profile;
