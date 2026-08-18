import type { Metadata } from 'next';
export const dynamic = 'force-dynamic';
import Header from '@/components/Header';
import Footer from '@/components/Footer';
import { getCategoryList, getSettings } from '@/lib/api';

export async function generateMetadata(): Promise<Metadata> {
    let settings = {
        site_title: 'Valokichu - Premium Wholesale Marketplace',
        site_description: 'Connect directly with best manufacturers.',
        site_keywords: 'wholesale, marketplace, ecommerce'
    };

    try {
        const settingsMap = await getSettings({ next: { revalidate: 60 } } as any);
        if (settingsMap.site_title) settings.site_title = settingsMap.site_title;
        if (settingsMap.site_description) settings.site_description = settingsMap.site_description;
        if (settingsMap.site_keywords) settings.site_keywords = settingsMap.site_keywords;
    } catch (e) {
        console.error('Metadata fetch failed', e);
    }

    return {
        title: settings.site_title,
        description: settings.site_description,
        keywords: settings.site_keywords ? settings.site_keywords.split(',') : [],
    };
}

export default async function WebsiteLayout({
    children,
}: Readonly<{
    children: React.ReactNode;
}>) {
    const { data: categories } = await getCategoryList();

    return (
        <>
            <Header categories={categories || []} />
            <main className="flex-grow">
                {children}
            </main>
            <Footer />
        </>
    );
}
