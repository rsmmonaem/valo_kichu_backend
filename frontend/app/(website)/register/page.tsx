"use client";

import React, { useState } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { Lock, Mail, User } from 'lucide-react';
import { authFetch } from '@/lib/api';
import { useAuth } from '@/context/AuthContext';

const RegisterPage = () => {
    const { login } = useAuth();
    const router = useRouter();
    const [name, setName] = useState('');
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [confirmPassword, setConfirmPassword] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [error, setError] = useState('');

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        if (password !== confirmPassword) {
            setError("Passwords do not match");
            return;
        }

        setIsSubmitting(true);
        setError('');

        try {
            const res = await authFetch('/register', {
                method: 'POST',
                body: JSON.stringify({ name, email, password, password_confirmation: confirmPassword }),
            });

            const data = await res.json();

            if (res.ok && data.access_token) {
                // Fetch User Data
                const userRes = await authFetch('/v1/auth/user', {
                    headers: { 'Authorization': `Bearer ${data.access_token}` }
                });

                let userData = { name, email, id: 0 };
                if (userRes.ok) {
                    const userResData = await userRes.json();
                    userData = userResData.data || userResData;
                }

                login(data.access_token, userData);
                router.push('/');
            } else {
                setError(data.message || data.error || 'Registration failed');
            }
        } catch (err) {
            setError('Something went wrong. Please try again.');
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <div className="min-h-screen flex items-center justify-center bg-gray-50 px-4 py-12">
            <div className="bg-white p-8 rounded-xl shadow-lg max-w-md w-full border border-gray-100">
                <div className="text-center mb-8">
                    <h2 className="text-3xl font-bold text-gray-800">Create Account</h2>
                    <p className="text-gray-500 mt-2">Join Valokichu today</p>
                </div>

                {error && (
                    <div className="bg-red-50 text-red-600 p-3 rounded-lg mb-6 text-sm text-center">
                        {error}
                    </div>
                )}

                <form onSubmit={handleSubmit} className="space-y-6">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                        <div className="relative">
                            <User className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size={20} />
                            <input
                                type="text"
                                className="w-full pl-10 pr-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-blue-600/20 focus:border-blue-600 outline-none transition"
                                placeholder="John Doe"
                                value={name}
                                onChange={(e) => setName(e.target.value)}
                                required
                            />
                        </div>
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                        <div className="relative">
                            <Mail className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size={20} />
                            <input
                                type="email"
                                className="w-full pl-10 pr-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-blue-600/20 focus:border-blue-600 outline-none transition"
                                placeholder="you@example.com"
                                value={email}
                                onChange={(e) => setEmail(e.target.value)}
                                required
                            />
                        </div>
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <div className="relative">
                            <Lock className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size={20} />
                            <input
                                type="password"
                                className="w-full pl-10 pr-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-blue-600/20 focus:border-blue-600 outline-none transition"
                                placeholder="••••••••"
                                value={password}
                                onChange={(e) => setPassword(e.target.value)}
                                required
                            />
                        </div>
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                        <div className="relative">
                            <Lock className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size={20} />
                            <input
                                type="password"
                                className="w-full pl-10 pr-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-blue-600/20 focus:border-blue-600 outline-none transition"
                                placeholder="••••••••"
                                value={confirmPassword}
                                onChange={(e) => setConfirmPassword(e.target.value)}
                                required
                            />
                        </div>
                    </div>

                    <div className="text-sm">
                        <label className="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" className="rounded border-gray-300 text-blue-600 focus:ring-blue-600" required />
                            <span className="text-gray-600">I agree to the <a href="#" className="text-blue-600 hover:underline">Terms & Conditions</a></span>
                        </label>
                    </div>

                    <button
                        type="submit"
                        disabled={isSubmitting}
                        className="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition font-bold shadow-lg shadow-blue-600/30 disabled:opacity-70 disabled:cursor-not-allowed"
                    >
                        {isSubmitting ? 'Creating Account...' : 'Create Account'}
                    </button>
                </form>

                <p className="text-center mt-8 text-sm text-gray-600">
                    Already have an account? <Link href="/login" className="text-blue-600 hover:text-blue-700 font-bold">Sign In</Link>
                </p>
            </div>
        </div>
    );
};

export default RegisterPage;
