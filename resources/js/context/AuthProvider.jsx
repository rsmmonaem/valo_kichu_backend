import React, { createContext, useState, useEffect, useContext } from 'react';
import api from '../services/api';
import toast from 'react-hot-toast';

const AuthContext = createContext();

export const AuthProvider = ({ children }) => {
    const [user, setUser] = useState(null);
    const [loading, setLoading] = useState(true);

    const fetchUser = async () => {
        try {
            const { data } = await api.get('/me');
            setUser(data);
        } catch (error) {
            setUser(null);
        } finally {
            setLoading(false);
        }
    };

    const login = async (email, password) => {
        try {
            await api.get('/sanctum/csrf-cookie', { baseURL: '/' }); // Initialize CSRF protection from root
            const { data } = await api.post('/login', { email, password });

            // If using token based (Bearer), store it. 
            // But Sanctum SPA mode uses cookies primarily for first-party.
            // If API returns token, we can use it, but cookie is safer for web.
            if (data.access_token) {
                localStorage.setItem('token', data.access_token);
                api.defaults.headers.common['Authorization'] = `Bearer ${data.access_token}`;
            }

            await fetchUser();
            toast.success('Logged in successfully!');
            return true;
        } catch (error) {
            toast.error(error.response?.data?.message || 'Login failed');
            return false;
        }
    };

    const logout = async () => {
        try {
            await api.post('/logout');
            setUser(null);
            localStorage.removeItem('token');
            delete api.defaults.headers.common['Authorization'];
            toast.success('Logged out');
        } catch (error) {
            console.error('Logout failed', error);
        }
    };

    useEffect(() => {
        const token = localStorage.getItem('token');
        if (token) {
            api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        }
        fetchUser();
    }, []);

    return (
        <AuthContext.Provider value={{ user, login, logout, loading, isAuthenticated: !!user }}>
            {children}
        </AuthContext.Provider>
    );
};

export const useAuth = () => useContext(AuthContext);
