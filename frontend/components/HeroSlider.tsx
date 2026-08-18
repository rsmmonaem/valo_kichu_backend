"use client";

import React, { useState, useEffect } from 'react';
import Link from 'next/link';
import clsx from 'clsx';
import { Banner } from '@/lib/api';

interface HeroSliderProps {
    banners: Banner[];
}

const HeroSlider: React.FC<HeroSliderProps> = ({ banners }) => {
    const [currentIndex, setCurrentIndex] = useState(0);

    useEffect(() => {
        if (banners.length <= 1) return;
        const interval = setInterval(() => {
            setCurrentIndex(prev => (prev + 1) % banners.length);
        }, 5000);
        return () => clearInterval(interval);
    }, [banners.length]);

    if (banners.length === 0) {
        return (
            <div className="relative h-[250px] md:h-[350px] lg:h-[400px] rounded-2xl overflow-hidden bg-gray-900 group">
                <div className="absolute inset-0 flex flex-col justify-center items-center text-white">
                    <p className="text-lg opacity-50">No Banners Available</p>
                </div>
            </div>
        );
    }

    return (
        <div className="relative h-[250px] md:h-[350px] lg:h-[400px] rounded-2xl overflow-hidden bg-gray-100 group">
            {banners.map((banner, index) => (
                <div
                    key={banner.id || index}
                    className={clsx(
                        "absolute inset-0 transition-opacity duration-1000 ease-in-out",
                        index === currentIndex ? "opacity-100 z-10" : "opacity-0 z-0"
                    )}
                >
                    <img
                        src={banner.image_url || ((banner.image && banner.image.startsWith('http')) ? banner.image : `http://127.0.0.1:8000/storage/${banner.image}`)}
                        alt={banner.title || 'Banner'}
                        className="w-full h-full object-cover"
                        onError={(e) => (e.currentTarget.style.display = 'none')}
                    />
                    <div className="absolute inset-0 bg-black/20 flex flex-col justify-center px-6 md:px-16 text-white text-left">
                        {banner.title && (
                            <h2 className="text-2xl md:text-5xl font-extrabold max-w-lg leading-tight uppercase drop-shadow-md">
                                {banner.title}
                            </h2>
                        )}
                        {banner.link && (
                            <Link href={banner.link} className="mt-6 bg-blue-600 text-white px-6 py-2 rounded-full font-bold text-sm w-fit hover:bg-blue-600/90 transition shadow-lg">
                                Shop Now
                            </Link>
                        )}
                    </div>
                </div>
            ))}

            {banners.length > 1 && (
                <div className="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2 z-20">
                    {banners.map((_, index) => (
                        <button
                            key={index}
                            onClick={() => setCurrentIndex(index)}
                            className={clsx(
                                "w-2 h-2 rounded-full transition-all",
                                index === currentIndex ? "bg-blue-600 w-6" : "bg-white/50"
                            )}
                        />
                    ))}
                </div>
            )}
        </div>
    );
};

export default HeroSlider;
