"use client";

import React, { useState } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { Lock, Mail } from 'lucide-react';
import { useAuth } from '@/context/AuthContext';
import { authFetch } from '@/lib/api';

const LoginPage = () => {
    const { login } = useAuth();
    const router = useRouter();
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [error, setError] = useState('');

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        setError('');

        try {
            // First get CSRF cookie if needed, but for API token based auth it might not be strictly required if using 'api/*' routes loosely
            // await authFetch('/sanctum/csrf-cookie', { method: 'GET' }); 

            const res = await authFetch('/login', {
                method: 'POST',
                body: JSON.stringify({ email, password }),
            });

            const data = await res.json();

            if (res.ok && data.access_token) {
                // Fetch User Data
                const userRes = await authFetch('/v1/auth/user', {
                    headers: { 'Authorization': `Bearer ${data.access_token}` }
                });

                let userData = { name: 'User', email: email, id: 0 };
                if (userRes.ok) {
                    const userResData = await userRes.json();
                    userData = userResData.data || userResData;
                }

                login(data.access_token, userData);

                // Redirect based on role if needed
                router.push('/');
            } else {
                setError(data.message || 'Invalid email or password');
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
                    <h2 className="text-3xl font-bold text-gray-800">Welcome Back</h2>
                    <p className="text-gray-500 mt-2">Sign in to your account</p>
                </div>

                {error && (
                    <div className="bg-red-50 text-red-600 p-3 rounded-lg mb-6 text-sm text-center">
                        {error}
                    </div>
                )}

                <form onSubmit={handleSubmit} className="space-y-6">
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

                    <div className="flex items-center justify-between text-sm">
                        <label className="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" className="rounded border-gray-300 text-blue-600 focus:ring-blue-600" />
                            <span className="text-gray-600">Remember me</span>
                        </label>
                        <a href="#" className="text-blue-600 hover:text-blue-700 font-medium">Forgot password?</a>
                    </div>

                    <button
                        type="submit"
                        disabled={isSubmitting}
                        className="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition font-bold shadow-lg shadow-blue-600/30 disabled:opacity-70 disabled:cursor-not-allowed"
                    >
                        {isSubmitting ? 'Signing in...' : 'Sign In'}
                    </button>
                </form>

                <p className="text-center mt-8 text-sm text-gray-600">
                    Don't have an account? <Link href="/register" className="text-blue-600 hover:text-blue-700 font-bold">Create Account</Link>
                </p>
            </div>
        </div>
    );
};

export default LoginPage;
