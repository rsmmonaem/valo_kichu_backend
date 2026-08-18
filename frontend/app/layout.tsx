import type { Metadata } from 'next';
import './globals.css';
import { AuthProvider } from '@/context/AuthContext';
import { CartProvider } from '@/context/CartContext';

export const metadata: Metadata = {
  title: 'Valokichu - Premium Wholesale Marketplace',
  description: 'Connect directly with best manufacturers.',
};

import { Inter } from 'next/font/google';

const inter = Inter({ subsets: ['latin'], variable: '--font-sans' });

import { SettingsProvider } from '@/context/SettingsContext';

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="en">
      <body className={`${inter.variable} min-h-screen bg-gray-50 flex flex-col font-sans text-gray-800 antialiased`}>
        <SettingsProvider>
          <AuthProvider>
            <CartProvider>
              {children}
            </CartProvider>
          </AuthProvider>
        </SettingsProvider>
      </body>
    </html>
  );
}

