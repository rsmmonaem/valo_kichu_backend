import './bootstrap';
import React from 'react';
import ReactDOM from 'react-dom/client';
import AppRouter from './router';
import { Toaster } from 'react-hot-toast';

if (document.getElementById('app')) {
    const root = ReactDOM.createRoot(document.getElementById('app'));
    root.render(
        <React.StrictMode>
            <AppRouter />
            <Toaster position="top-right" />
        </React.StrictMode>
    );
}
