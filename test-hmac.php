<?php

/**
 * Test script to verify HMAC Authentication for the Dropshipping API
 */

$apiUrl = 'http://localhost:8000/api/v1/dropshipping/products';
$apiKey = 'eb764341-121b-484a-a83e-803fae005650';
$apiSecret = 'HlkniNCYjKADKIYwG8kR9FaS3aIwEb1vDN0hOuqm';

$timestamp = time();
// Signature logic: hash_hmac('sha256', timestamp + apiKey, secret)
$signature = hash_hmac('sha256', $timestamp . $apiKey, $apiSecret);

echo "--- Generating HMAC Request ---\n";
echo "Timestamp: $timestamp\n";
echo "API Key: $apiKey\n";
echo "Signature: $signature\n\n";

$ch = curl_init($apiUrl);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "X-API-Key: $apiKey",
    "X-Timestamp: $timestamp",
    "X-Signature: $signature",
    "Accept: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    echo 'Curl error: ' . curl_error($ch) . "\n";
}

curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response Body:\n";
print_r(json_decode($response, true));
