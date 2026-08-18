import { getProduct } from '@/lib/api';
import ProductDetails from '@/components/ProductDetails';
import { notFound } from 'next/navigation';

export async function generateMetadata({ params }: { params: Promise<{ slug: string }> }) {
    const { slug } = await params;

    // Check if slug is valid
    if (!slug) return { title: 'Product Not Found' };

    const { data: product } = await getProduct(slug);

    if (!product) {
        return {
            title: 'Product Not Found',
        };
    }

    return {
        title: product.meta_title || product.name || 'Product Details',
        description: product.meta_description || product.description?.substring(0, 160) || 'Product details',
    };
}

export default async function ProductPage({ params }: { params: Promise<{ slug: string }> }) {
    const { slug } = await params;
    const { data: product } = await getProduct(slug);

    if (!product || !product.id) {
        notFound();
    }

    return (
        <div className="container mx-auto px-4 py-8">
            <ProductDetails product={product} />
        </div>
    );
}
