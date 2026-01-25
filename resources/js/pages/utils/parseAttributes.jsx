/**
 * Safely parses API response attributes stored as JSON string
 * @param {string|Array|Object} rawAttr - The raw attributes from API
 * @returns {Array} - Parsed attributes array
 */
export function parseAttributes(rawAttr) {
    if (!rawAttr) return [];

    // If it's already an array, return as is
    if (Array.isArray(rawAttr)) return rawAttr;

    // If it's already an object (not string), wrap in array if needed
    if (typeof rawAttr === "object") return [rawAttr];

    try {
        // Clean extra quotes if needed
        let cleaned = rawAttr.replace(/^"+|"+$/g, "");

        // Replace escaped quotes if present
        cleaned = cleaned.replace(/\\"/g, '"').replace(/\\\\/g, "\\");

        const parsed = JSON.parse(cleaned);

        // Ensure it returns an array
        return Array.isArray(parsed) ? parsed : [parsed];
    } catch (error) {
        console.error("Failed to parse attributes:", error, rawAttr);
        return [];
    }
}
