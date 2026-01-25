/**
 * Safely parses a badly formatted gallery_images API response
 * Handles double-escaped JSON strings and returns a clean array
 * @param {Array|string} rawArray - The API response array or string
 * @returns {Array} - Clean array of strings
 */
export function parseGalleryImages(rawArray) {
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
      console.error('Failed to parse gallery images:', error, rawArray);
      return [];
    }
  }
  