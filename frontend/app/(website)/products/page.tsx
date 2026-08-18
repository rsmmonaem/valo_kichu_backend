import { getProducts, getSettings, getCategory } from '@/lib/api';
import ProductCard from '@/components/ProductCard';
import { Metadata } from 'next';

type Props = {
    searchParams: Promise<{ [key: string]: string | string[] | undefined }>
}

export async function generateMetadata({ searchParams }: Props): Promise<Metadata> {
    const params = await searchParams;
    const categorySlug = params.category as string;
    const settings = await getSettings();

    if (categorySlug) {
        // If viewing a category, try to get specific category SEO
        const { data: category } = await getCategory(categorySlug);
        if (category && category.id) {
            return {
                title: category.meta_title || category.name + ' | ValoKichu',
                description: category.meta_description || `Browse ${category.name} products.`,
                keywords: category.meta_keywords || ''
            };
        }
    }

    // Default to Products Page Global settings
    return {
        title: settings.products_page_title || 'Products | ValoKichu',
        description: settings.products_page_description || 'Browse our collection of high-quality products.',
    };
}

export default async function ProductsPage() {
    const response = await getProducts();
    const products = response.data?.data || [];

    return (
        <div className="container mx-auto px-4 py-8">
            <h1 className="text-3xl font-bold mb-6">Our Products</h1>

            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 md:gap-6">
                {products.map((product) => (
                    <ProductCard key={product.id} product={product} />
                ))}
            </div>

            {products.length === 0 && (
                <div className="text-center py-12">
                    <p className="text-gray-500">No products found.</p>
                </div>
            )}
        </div>
    );
}
