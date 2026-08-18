"use client";

import React, { createContext, useState, useEffect, useContext } from 'react';
import { useRouter } from 'next/navigation';

interface User {
    id: number;
    name: string;
    email: string;
    role?: string;
    first_name?: string;
    last_name?: string;
    phone?: string;
    phone_number?: string;
    address?: string;
    image?: string;
    is_verified?: boolean;
    gender?: string;
    date_of_birth?: string;
}

interface AuthContextType {
    user: User | null;
    loading: boolean;
    login: (token: string, userData: User) => void;
    logout: () => void;
    isAuthenticated: boolean;
    updateUser: (data: User) => void;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const AuthProvider = ({ children }: { children: React.ReactNode }) => {
    const [user, setUser] = useState<User | null>(null);
    const [loading, setLoading] = useState(true);
    const router = useRouter();

    useEffect(() => {
        const initAuth = async () => {
            const token = localStorage.getItem('token');
            const storedUser = localStorage.getItem('user');

            if (token && storedUser) {
                try {
                    setUser(JSON.parse(storedUser));
                    // Optionally verify token validity with API here
                } catch (e) {
                    console.error("Failed to parse stored user", e);
                    localStorage.removeItem('token');
                    localStorage.removeItem('user');
                }
            }
            setLoading(false);
        };

        initAuth();
    }, []);

    const login = (token: string, userData: User) => {
        localStorage.setItem('token', token);
        localStorage.setItem('user', JSON.stringify(userData));
        setUser(userData);

        // Redirect logic could also be here or handled in the component
    };

    const logout = () => {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        setUser(null);
        router.push('/login');
    };

    const updateUser = (data: User) => {
        setUser(data);
        localStorage.setItem('user', JSON.stringify(data));
    };

    return (
        <AuthContext.Provider value={{ user, loading, login, logout, isAuthenticated: !!user, updateUser }}>
            {children}
        </AuthContext.Provider>
    );
};

export const useAuth = () => {
    const context = useContext(AuthContext);
    if (!context) {
        throw new Error('useAuth must be used within an AuthProvider');
    }
    return context;
};
