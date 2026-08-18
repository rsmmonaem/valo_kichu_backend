<?php
/**
 * Automated Extraction Script for CyberPanel Deployment
 * This script unzips the deployment package uploaded via FTP.
 */

$zipFile = 'deploy.zip';
$extractTo = __DIR__;

header('Content-Type: text/plain');

if (!file_exists($zipFile)) {
    die("Error: $zipFile not found. Please upload the zip file first.\n");
}

if (!class_exists('ZipArchive')) {
    die("Error: PHP ZipArchive extension is not enabled on this server.\n");
}

echo "Starting extraction of $zipFile to $extractTo...\n";

$zip = new ZipArchive;
if ($zip->open($zipFile) === TRUE) {
    // Stage 1: Standard Extraction
    $zip->extractTo($extractTo);
    $zip->close();
    echo "Files extracted.\n";

    // Stage 2: Flatten structure if deep folders exist
    // We look for the server.js file to find the real root
    $it = new RecursiveDirectoryIterator($extractTo);
    foreach(new RecursiveIteratorIterator($it) as $file) {
        if ($file->getFilename() === 'server.js') {
            $sourceDir = $file->getPath();
            if ($sourceDir !== $extractTo) {
                echo "Found project root at $sourceDir. Moving files to $extractTo...\n";
                // Move everything from sourceDir up to extractTo
                $cmd = "cp -rp " . escapeshellarg($sourceDir) . "/. " . escapeshellarg($extractTo) . "/";
                exec($cmd, $output, $returnVar);
                if ($returnVar === 0) {
                    echo "Files moved successfully.\n";
                } else {
                    echo "Error moving files. Manual intervention required.\n";
                }
            }
            break; 
        }
    }
    
    echo "Extraction completed successfully!\n";
    
    // Cleanup
    echo "Deleting $zipFile...\n";
    unlink($zipFile);
} else {
    echo "Error: Failed to open $zipFile.\n";
}
?>
