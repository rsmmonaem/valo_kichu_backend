import React, { createContext, useContext, useState, useEffect } from 'react';
import api from '../services/api';

const ConfigContext = createContext();

export const useConfig = () => useContext(ConfigContext);

export const ConfigProvider = ({ children }) => {
    const [config, setConfig] = useState(null);
    const [loading, setLoading] = useState(true);

    const fetchConfig = async () => {
        try {
            const { data } = await api.get('/v1/config/app-config');
            setConfig(data);
            localStorage.setItem('app_config', JSON.stringify(data));
        } catch (error) {
            console.error("Failed to fetch config", error);
            // Fallback to cached config if available
            const cached = localStorage.getItem('app_config');
            if (cached) {
                setConfig(JSON.parse(cached));
            }
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchConfig();
    }, []);

    return (
        <ConfigContext.Provider value={{ config, loading, fetchConfig }}>
            {children}
        </ConfigContext.Provider>
    );
};
