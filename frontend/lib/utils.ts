import { clsx, type ClassValue } from "clsx";
import { twMerge } from "tailwind-merge";

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

/**
 * Safely parses API response attributes stored as JSON string
 */
export function parseAttributes(rawAttr: string | any[] | object | null | undefined): any[] {
    if (!rawAttr) return [];

    // If it's already an array, return as is
    if (Array.isArray(rawAttr)) return rawAttr;

    // If it's already an object (not string), wrap in array if needed
    if (typeof rawAttr === "object") return [rawAttr];

    try {
        // Clean extra quotes if needed
        let cleaned = String(rawAttr).replace(/^"+|"+$/g, "");

        // Replace escaped quotes if present
        cleaned = cleaned.replace(/\\"/g, '"').replace(/\\\\/g, "\\");

        const parsed = JSON.parse(cleaned);

        // Ensure it returns an array
        return Array.isArray(parsed) ? parsed : [parsed];
    } catch (error) {
        // console.error("Failed to parse attributes:", error, rawAttr);
        return [];
    }
}

/**
 * Safely parses a badly formatted gallery_images API response
 */
export function parseGalleryImages(rawArray: string | any[] | null | undefined): string[] {
    if (!rawArray) return [];

    try {
        // If it's already a string, wrap it into an array for uniform handling
        const arr = Array.isArray(rawArray) ? rawArray : [rawArray];

        // Join everything into a single string
        let joined = arr.join(',');

        // Remove leading/trailing quotes
        joined = joined.replace(/^"+|"+$/g, '');

        // Replace escaped quotes and backslashes
        joined = joined.replace(/\\"/g, '"').replace(/\\\\/g, '\\');

        // Parse JSON
        const parsed = JSON.parse(joined);

        // Ensure the result is an array
        return Array.isArray(parsed) ? parsed : [];
    } catch (error) {
        // console.error('Failed to parse gallery images:', error, rawArray);
        return [];
    }
}
