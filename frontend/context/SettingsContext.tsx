"use client";

import React, { createContext, useContext, useEffect, useState } from 'react';
import { getSettings } from '@/lib/api';

interface SettingsContextType {
    settings: Record<string, string>;
    loading: boolean;
}

const SettingsContext = createContext<SettingsContextType>({ settings: {}, loading: true });

export const useSettings = () => useContext(SettingsContext);

export const SettingsProvider = ({ children }: { children: React.ReactNode }) => {
    const [settings, setSettings] = useState<Record<string, string>>({});
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchPublicSettings = async () => {
            try {
                const settingsMap = await getSettings();
                setSettings(settingsMap);
            } catch (error) {
                console.error("Failed to fetch settings", error);
            } finally {
                setLoading(false);
            }
        };

        fetchPublicSettings();
    }, []);

    // Apply colors to root
    useEffect(() => {
        if (!loading) {
            const root = document.documentElement;
            if (settings.primary_color) {
                root.style.setProperty('--primary-color', settings.primary_color);
                // Can verify if we want to dynamically override tailwind colors via efficient CSS vars
                // For now, we assume simple overrides if customized.
            }
            if (settings.secondary_color) {
                root.style.setProperty('--secondary-color', settings.secondary_color);
            }
        }
    }, [settings, loading]);

    return (
        <SettingsContext.Provider value={{ settings, loading }}>
            {children}
        </SettingsContext.Provider>
    );
};
