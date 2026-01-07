<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ParseMultipartPut
{
    /**
     * Handle an incoming request.
     * 
     * This middleware parses multipart/form-data for PUT requests
     * since PHP doesn't automatically parse it.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only process PUT/PATCH requests with multipart/form-data
        if (in_array($request->method(), ['PUT', 'PATCH']) && 
            str_contains($request->header('Content-Type', ''), 'multipart/form-data')) {
            
            $this->parseMultipartData($request);
        }

        return $next($request);
    }

    /**
     * Parse multipart/form-data from raw input
     */
    protected function parseMultipartData(Request $request): void
    {
        $content = $request->getContent();
        $contentType = $request->header('Content-Type', '');
        
        // Extract boundary
        if (!preg_match('/boundary=(.*?)(?:;|$)/i', $contentType, $matches)) {
            return;
        }
        
        $boundary = '--' . trim($matches[1]);
        $parts = explode($boundary, $content);
        
        $data = [];
        $files = [];
        
        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part) || $part === '--' || $part === '') {
                continue;
            }
            
            // Split headers and body
            if (preg_match('/Content-Disposition:\s*form-data;\s*name="([^"]+)"(?:;\s*filename="([^"]+)")?/i', $part, $matches)) {
                $fieldName = $matches[1];
                $filename = isset($matches[2]) && !empty($matches[2]) ? $matches[2] : null;
                
                // Extract the value (after headers and blank line)
                $valueParts = preg_split('/\r?\n\r?\n/', $part, 2);
                if (isset($valueParts[1])) {
                    $value = $valueParts[1];
                    // Remove trailing boundary markers and whitespace
                    $value = rtrim($value, "\r\n--");
                    $value = rtrim($value);
                    
                    if ($filename) {
                        // It's a file
                        $tmpPath = tempnam(sys_get_temp_dir(), 'laravel_upload_');
                        file_put_contents($tmpPath, $value);
                        
                        // Get MIME type
                        $mimeType = mime_content_type($tmpPath) ?: 'application/octet-stream';
                        
                        $files[$fieldName] = new \Illuminate\Http\UploadedFile(
                            $tmpPath,
                            $filename,
                            $mimeType,
                            null,
                            true
                        );
                    } else {
                        // It's a regular field
                        $data[$fieldName] = $value;
                    }
                }
            }
        }
        
        // Merge into request
        if (!empty($data)) {
            $request->merge($data);
        }
        foreach ($files as $key => $file) {
            $request->files->set($key, $file);
        }
    }
}

